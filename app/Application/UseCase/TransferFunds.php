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

final class TransferFunds
{
  public function __construct(
    private \PDO $db,
    private WalletRepository $wallets,
    private LedgerAccountRepository $accounts,
    private LedgerRepository $ledger,
    private IdempotencyRepository $idem,
    private AuditRepository $audit,
  ) {}

  /**
   * amountKobo must be > 0 (integer).
   * idempotencyKey must be stable across retries.
   */
  public function execute(
    string $fromWalletId16,
    string $toWalletId16,
    int $amountKobo,
    string $idempotencyKey,
    ?string $externalRef = null,
    ?string $actor = null,
  ): array {
    if ($amountKobo <= 0) {
      throw new \App\Application\Exception\ValidationException('amount_kobo must be a positive integer');
    }
    $idempotencyKey = trim($idempotencyKey);
    if ($idempotencyKey === '' || strlen($idempotencyKey) > 80) {
      throw new \App\Application\Exception\ValidationException('idempotency_key required (max 80 bytes)');
    }
    if ($fromWalletId16 === $toWalletId16) {
      throw new \App\Application\Exception\ValidationException('cannot transfer to same wallet');
    }

    // Request hash (binds idemKey to exact request payload)
    $hash = hash('sha256', json_encode([
      'from' => bin2hex($fromWalletId16),
      'to' => bin2hex($toWalletId16),
      'amount_kobo' => $amountKobo,
      'external_ref' => $externalRef,
    ]), true);

    return Tx::run($this->db, function () use (
      $fromWalletId16,
      $toWalletId16,
      $amountKobo,
      $idempotencyKey,
      $externalRef,
      $actor,
      $hash
    ) {
      // Idempotency gate
      $idemState = $this->idem->begin($idempotencyKey, $hash);

      if ($idemState['state'] === 'conflict') {
        throw new \RuntimeException('Idempotency-Key reuse with different request payload');
      }
      if ($idemState['state'] === 'done') {
        // Already completed earlier
        return ['journal_id' => $idemState['journal_id'], 'status' => 'duplicate'];
      }

      // Validate wallets
      $fromW = $this->wallets->findWallet($fromWalletId16);
      $toW   = $this->wallets->findWallet($toWalletId16);
      if (!$fromW || !$toW) {
        $this->idem->fail($idempotencyKey);
        throw new \App\Application\Exception\NotFoundException('Wallet not found');
      }
      if ($fromW['status'] !== 'active' || $toW['status'] !== 'active') {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Wallet not active');
      }
      if ($fromW['currency'] !== 'NGN' || $toW['currency'] !== 'NGN') {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Only NGN supported');
      }

      // Resolve accounts
      $fromAcc = $this->accounts->findAccountIdByWallet($fromWalletId16);
      $toAcc   = $this->accounts->findAccountIdByWallet($toWalletId16);
      if (!$fromAcc || !$toAcc) {
        $this->idem->fail($idempotencyKey);
        throw new \RuntimeException('Ledger account missing');
      }

      // Lock balances deterministically to avoid deadlocks
      $pairs = [
        ['id' => $fromAcc, 'role' => 'from'],
        ['id' => $toAcc,   'role' => 'to'],
      ];
      usort($pairs, fn($a, $b) => strcmp($a['id'], $b['id'])); // binary-safe compare ok in PHP strings

      $balances = [];
      foreach ($pairs as $p) {
        $balances[$p['role']] = $this->accounts->lockBalance($p['id']);
      }

      // Insufficient funds check (sender balance must cover amount)
      if ($balances['from'] < $amountKobo) {
        $this->idem->fail($idempotencyKey);
        throw new \App\Application\Exception\ConflictException('Insufficient funds');
      }

      // Journal + double-entry lines
      $journalId = Uuid::v4Bytes();
      $this->ledger->createJournal($journalId, $idempotencyKey, 'transfer', $externalRef);

      // Sender: -amount, Receiver: +amount
      $this->ledger->addLine(Uuid::v4Bytes(), $journalId, $fromAcc, -$amountKobo);
      $this->ledger->addLine(Uuid::v4Bytes(), $journalId, $toAcc,   +$amountKobo);

      // Update balances (still within same transaction and locks held)
      $this->accounts->applyDelta($fromAcc, -$amountKobo);
      $this->accounts->applyDelta($toAcc,   +$amountKobo);

      // Mark completed
      $this->idem->complete($idempotencyKey, $journalId);

      $this->audit->log($actor, 'transfer.created', 'journal', $journalId, [
        'from_wallet' => bin2hex($fromWalletId16),
        'to_wallet' => bin2hex($toWalletId16),
        'amount_kobo' => $amountKobo,
        'currency' => 'NGN',
      ]);

      return ['journal_id' => $journalId, 'status' => 'ok'];
    });
  }
}
