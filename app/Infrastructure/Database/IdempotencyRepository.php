<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class IdempotencyRepository
{
  public function __construct(private PDO $db) {}

  public function begin(string $idemKey, string $requestHash32): array
  {
    // Attempt insert
    try {
      $st = $this->db->prepare("
              INSERT INTO idempotency_keys (idem_key, request_hash, status)
              VALUES (?, ?, 'started')
            ");
      $st->execute([$idemKey, $requestHash32]);

      return ['state' => 'new', 'journal_id' => null];
    } catch (\PDOException $e) {
      // Duplicate primary key -> fetch
      $st2 = $this->db->prepare("SELECT request_hash, journal_id, status FROM idempotency_keys WHERE idem_key=? LIMIT 1");
      $st2->execute([$idemKey]);
      $row = $st2->fetch();
      if (!$row) throw $e;

      if (!hash_equals($row['request_hash'], $requestHash32)) {
        return ['state' => 'conflict', 'journal_id' => null];
      }

      if ($row['status'] === 'completed' && !empty($row['journal_id'])) {
        return ['state' => 'done', 'journal_id' => $row['journal_id']];
      }

      return ['state' => 'in_progress', 'journal_id' => $row['journal_id'] ?? null];
    }
  }

  public function complete(string $idemKey, string $journalId16): void
  {
    $st = $this->db->prepare("
          UPDATE idempotency_keys
          SET journal_id=?, status='completed'
          WHERE idem_key=?
        ");
    $st->execute([$journalId16, $idemKey]);
  }

  public function fail(string $idemKey): void
  {
    $st = $this->db->prepare("
          UPDATE idempotency_keys
          SET status='failed'
          WHERE idem_key=?
        ");
    $st->execute([$idemKey]);
  }
}
