<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class WalletRepository
{
  public function __construct(private PDO $db) {}

  public function existsForOwner(string $ownerType, string $ownerId): bool
  {
    $st = $this->db->prepare("SELECT 1 FROM wallets WHERE owner_type=? AND owner_id=? AND currency='NGN' LIMIT 1");
    $st->execute([$ownerType, $ownerId]);
    return (bool)$st->fetchColumn();
  }

  public function insertWallet(string $walletId16, string $ownerType, string $ownerId): void
  {
    $st = $this->db->prepare("
            INSERT INTO wallets (id, owner_type, owner_id, currency, status)
            VALUES (?, ?, ?, 'NGN', 'active')
        ");
    $st->execute([$walletId16, $ownerType, $ownerId]);
  }

  public function findWallet(string $walletId16): ?array
  {
    $st = $this->db->prepare("SELECT id, owner_type, owner_id, currency, status FROM wallets WHERE id=? LIMIT 1");
    $st->execute([$walletId16]);
    $row = $st->fetch();
    return $row ?: null;
  }
}
