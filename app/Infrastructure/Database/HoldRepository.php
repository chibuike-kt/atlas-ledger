<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class HoldRepository
{
  public function __construct(private PDO $db) {}

  public function insert(
    string $holdId16,
    string $walletId16,
    int $amountMinor,
    string $reason,
    string $idempotencyKey,
    ?string $externalRef,
    ?string $expiresAtUtc // 'Y-m-d H:i:s.u' or null
  ): void {
    $st = $this->db->prepare("
          INSERT INTO wallet_holds (id, wallet_id, amount_minor, reason, idempotency_key, external_ref, expires_at)
          VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
    $st->execute([$holdId16, $walletId16, $amountMinor, $reason, $idempotencyKey, $externalRef, $expiresAtUtc]);
  }

  public function findById(string $holdId16): ?array
  {
    $st = $this->db->prepare("SELECT * FROM wallet_holds WHERE id=? LIMIT 1");
    $st->execute([$holdId16]);
    $row = $st->fetch();
    return $row ?: null;
  }

  public function markReleased(string $holdId16): void
  {
    $st = $this->db->prepare("UPDATE wallet_holds SET status='released' WHERE id=? AND status='active'");
    $st->execute([$holdId16]);
    if ($st->rowCount() !== 1) throw new \RuntimeException('Hold not active or not found');
  }

  public function markCaptured(string $holdId16): void
  {
    $st = $this->db->prepare("UPDATE wallet_holds SET status='captured' WHERE id=? AND status='active'");
    $st->execute([$holdId16]);
    if ($st->rowCount() !== 1) throw new \RuntimeException('Hold not active or not found');
  }
}
