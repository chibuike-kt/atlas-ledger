<?php

declare(strict_types=1);

namespace App\Interface\Http\Controller;

use App\Interface\Http\Response\JsonResponse;

final class AdminReconcileController
{
  public function run(): void
  {
    $app = require __DIR__ . '/../../../../bootstrap/app.php';
    $db = ($app['db'])();

    // Unbalanced journals
    $journals = $db->query("
          SELECT COUNT(*) AS cnt
          FROM (
            SELECT j.id
            FROM ledger_journals j
            JOIN ledger_lines l ON l.journal_id = j.id
            GROUP BY j.id
            HAVING SUM(l.amount_minor) <> 0 OR COUNT(*) < 2
          ) x
        ")->fetchColumn();

    // Balance drift
    $drift = $db->query("
          SELECT COUNT(*) AS cnt
          FROM (
            SELECT a.id
            FROM ledger_accounts a
            JOIN account_balances b ON b.account_id = a.id
            LEFT JOIN ledger_lines l ON l.account_id = a.id
            GROUP BY a.id, b.balance_minor
            HAVING b.balance_minor <> COALESCE(SUM(l.amount_minor), 0)
          ) y
        ")->fetchColumn();

    JsonResponse::send(200, [
      'unbalanced_journals' => (int)$journals,
      'balance_drifts' => (int)$drift,
      'status' => ((int)$journals === 0 && (int)$drift === 0) ? 'healthy' : 'corrupt'
    ]);
  }
}
