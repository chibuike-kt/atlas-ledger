<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class LedgerAccountRepository
{
  public function __construct(private PDO $db) {}

  public function createForWallet(string $accountId16, string $walletId16): void
  {
    $st = $this->db->prepare("
            INSERT INTO ledger_accounts (id, type, ref_id, name, currency)
            VALUES (?, 'wallet', ?, NULL, 'NGN')
        ");
    $st->execute([$accountId16, $walletId16]);

    $st2 = $this->db->prepare("
            INSERT INTO account_balances (account_id, balance_minor)
            VALUES (?, 0)
        ");
    $st2->execute([$accountId16]);
  }

  public function findAccountIdByWallet(string $walletId16): ?string
  {
    $st = $this->db->prepare("SELECT id FROM ledger_accounts WHERE type='wallet' AND ref_id=? LIMIT 1");
    $st->execute([$walletId16]);
    $id = $st->fetchColumn();
    return $id !== false ? (string)$id : null;
  }

  /** Locks the balance row (FOR UPDATE) and returns current balance_minor */
  public function lockBalance(string $accountId16): int
  {
    $st = $this->db->prepare("SELECT balance_minor FROM account_balances WHERE account_id=? FOR UPDATE");
    $st->execute([$accountId16]);
    $bal = $st->fetchColumn();
    if ($bal === false) {
      throw new \RuntimeException('Balance row missing');
    }
    return (int)$bal;
  }

  public function applyDelta(string $accountId16, int $deltaMinor): void
  {
    $st = $this->db->prepare("
            UPDATE account_balances
            SET balance_minor = balance_minor + ?
            WHERE account_id = ?
        ");
    $st->execute([$deltaMinor, $accountId16]);
    if ($st->rowCount() !== 1) {
      throw new \RuntimeException('Failed to update balance');
    }
  }

  public function findSystemAccountId(string $name): ?string
  {
    $st = $this->db->prepare("
        SELECT id FROM ledger_accounts
        WHERE type='system' AND name=? AND currency='NGN'
        LIMIT 1
    ");
    $st->execute([$name]);
    $id = $st->fetchColumn();
    return $id !== false ? (string)$id : null;
  }

  public function getBalance(string $accountId16): int
  {
    $st = $this->db->prepare("SELECT balance_minor FROM account_balances WHERE account_id=? LIMIT 1");
    $st->execute([$accountId16]);
    $bal = $st->fetchColumn();
    if ($bal === false) throw new \RuntimeException('Balance row missing');
    return (int)$bal;
  }

  public function findOrCreateHoldAccountForWallet(string $walletId16): string
  {
    $st = $this->db->prepare("SELECT id FROM ledger_accounts WHERE type='wallet_hold' AND ref_id=? LIMIT 1");
    $st->execute([$walletId16]);
    $id = $st->fetchColumn();
    if ($id !== false) return (string)$id;

    $accountId = \App\Infrastructure\Id\Uuid::v4Bytes();

    $ins = $this->db->prepare("
      INSERT INTO ledger_accounts (id, type, ref_id, name, currency)
      VALUES (?, 'wallet_hold', ?, NULL, 'NGN')
    ");
    $ins->execute([$accountId, $walletId16]);

    $ins2 = $this->db->prepare("INSERT INTO account_balances (account_id, balance_minor) VALUES (?, 0)");
    $ins2->execute([$accountId]);

    return $accountId;
  }
}
