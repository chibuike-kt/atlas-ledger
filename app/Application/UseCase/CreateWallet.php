<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\AuditRepository;
use App\Infrastructure\Database\Tx;
use App\Infrastructure\Id\Uuid;

final class CreateWallet
{
  public function __construct(
    private \PDO $db,
    private WalletRepository $wallets,
    private LedgerAccountRepository $accounts,
    private AuditRepository $audit,
  ) {}

  public function execute(string $ownerType, string $ownerId, ?string $actor = null): array
  {
    $ownerType = trim($ownerType);
    $ownerId = trim($ownerId);

    if ($ownerType === '' || $ownerId === '') {
      throw new \InvalidArgumentException('ownerType and ownerId are required');
    }

    return Tx::run($this->db, function () use ($ownerType, $ownerId, $actor) {
      // Unique constraint enforces one NGN wallet per owner
      $walletId = Uuid::v4Bytes();
      $accountId = Uuid::v4Bytes();

      $this->wallets->insertWallet($walletId, $ownerType, $ownerId);
      $this->accounts->createForWallet($accountId, $walletId);

      $this->audit->log($actor, 'wallet.created', 'wallet', $walletId, [
        'owner_type' => $ownerType,
        'owner_id' => $ownerId,
        'currency' => 'NGN'
      ]);

      return ['wallet_id' => $walletId, 'account_id' => $accountId];
    });
  }
}
