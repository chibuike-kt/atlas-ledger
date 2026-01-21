<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class WithdrawalRepository
{
  public function __construct(private PDO $db) {}

  public function insert(
    string $withdrawId16,
    string $walletId16,
    ?string $holdId16,
    int $amountMinor,
    int $feeMinor,
    int $netMinor,
    string $idempotencyKey,
    ?string $externalRef
  ): void {
    $st = $this->db->prepare("
          INSERT INTO withdrawals (id, wallet_id, hold_id, amount_minor, fee_minor, net_minor, idempotency_key, external_ref)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
    $st->execute([$withdrawId16, $walletId16, $holdId16, $amountMinor, $feeMinor, $netMinor, $idempotencyKey, $externalRef]);
  }

  public function setStatus(string $withdrawId16, string $status): void
  {
    $st = $this->db->prepare("UPDATE withdrawals SET status=? WHERE id=?");
    $st->execute([$status, $withdrawId16]);
  }
}
