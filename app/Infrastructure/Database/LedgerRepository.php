<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class LedgerRepository
{
  public function __construct(private PDO $db) {}

  public function createJournal(string $journalId16, string $idempotencyKey, string $reason, ?string $externalRef): void
  {
    $st = $this->db->prepare("
          INSERT INTO ledger_journals (id, idempotency_key, reason, external_ref, currency)
          VALUES (?, ?, ?, ?, 'NGN')
        ");
    $st->execute([$journalId16, $idempotencyKey, $reason, $externalRef]);
  }

  public function addLine(string $lineId16, string $journalId16, string $accountId16, int $amountMinor): void
  {
    $st = $this->db->prepare("
          INSERT INTO ledger_lines (id, journal_id, account_id, amount_minor)
          VALUES (?, ?, ?, ?)
        ");
    $st->execute([$lineId16, $journalId16, $accountId16, $amountMinor]);
  }

  public function derivedBalance(string $accountId16): int
  {
    $st = $this->db->prepare("SELECT COALESCE(SUM(amount_minor), 0) FROM ledger_lines WHERE account_id=?");
    $st->execute([$accountId16]);
    return (int)$st->fetchColumn();
  }
}
