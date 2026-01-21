<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Infrastructure\Database\Tx;
use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\LedgerRepository;
use App\Infrastructure\Database\IdempotencyRepository;
use App\Infrastructure\Database\AuditRepository;
use App\Infrastructure\Id\Uuid;

final class DepositFunds
{
  public function __construct(
    private \PDO $db,
    private WalletRepository $wallets,
    private LedgerAccountRepository $accounts,
    private LedgerRepository $ledger,
    private IdempotencyRepository $idem,
    private AuditRepository $audit,
  ) {}

  public function execute(
    string $toWalletId16,
    int $amountKobo,
    string $idempotencyKey,
    ?string $externalRef = null,
    ?string $actor = null,
  ): array {
    if ($amountKobo <= 0) {
      throw new \InvalidArgumentException('amount_kobo must be a positive integer');
    }

    $idempotencyKey = trim($idempotencyKey);
    if ($idempotencyKey === '' || strlen($idempotencyKey) > 80) {
      throw new \InvalidArgumentException('idempotency_key required (max 80 bytes)');
    }

    $hash = hash('sha256', json_encode([
      'to' => bin2hex($toWalletId16),
      'amount_kobo' => $amountKobo,
      'external_ref' => $externalRef,
    ]), true);

    return Tx::run($this->db, function () use ($toWalletId16, $amountKobo, $idempotencyKey, $externalRef, $actor, $hash) {
      $idemState = $this->idem->begin($idempotencyKey, $hash);
      if ($idemState['state'] === 'conflict') {
        throw new \RuntimeException('Idempotency-Key reuse with different request payload');
      }
      if ($idemState['state'] === 'done') {
        return ['journal_id' => $idemState['journal_id'], 'status' => 'duplicate'];
      }

      $toW = $this->wallets->findWallet($toWalletId16);
      if (!$toW) {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Wallet not found');
      }
      if ($toW['status'] !== 'active') {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Wallet not active');
      }
      if ($toW['currency'] !== 'NGN') {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Only NGN supported');
      }

      $toAcc = $this->accounts->findAccountIdByWallet($toWalletId16);
      if (!$toAcc) {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Ledger account missing');
      }

      $fundingAcc = $this->accounts->findSystemAccountId('funding');
      if (!$fundingAcc) {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('System funding account missing');
      }

      // Lock balances in deterministic order
      $pairs = [
        ['id' => $fundingAcc, 'role' => 'funding'],
        ['id' => $toAcc,      'role' => 'to'],
      ];
      usort($pairs, fn($a, $b) => strcmp($a['id'], $b['id']));

      foreach ($pairs as $p) {
        $this->accounts->lockBalance($p['id']);
      }

      // Journal + lines:
      // funding: -amount (money leaves external world)
      // wallet:  +amount
      $journalId = Uuid::v4Bytes();
      $this->ledger->createJournal($journalId, $idempotencyKey, 'deposit', $externalRef);

      $this->ledger->addLine(Uuid::v4Bytes(), $journalId, $fundingAcc, -$amountKobo);
      $this->ledger->addLine(Uuid::v4Bytes(), $journalId, $toAcc,      +$amountKobo);

      $this->accounts->applyDelta($fundingAcc, -$amountKobo);
      $this->accounts->applyDelta($toAcc,      +$amountKobo);

      $this->idem->complete($idempotencyKey, $journalId);

      $this->audit->log($actor, 'deposit.created', 'journal', $journalId, [
        'to_wallet' => bin2hex($toWalletId16),
        'amount_kobo' => $amountKobo,
        'currency' => 'NGN',
      ]);

      return ['journal_id' => $journalId, 'status' => 'ok'];
    });
  }
}
