<?php

declare(strict_types=1);

namespace App\Interface\Http\Controller;

use App\Interface\Http\Request\Json;
use App\Interface\Http\Response\JsonResponse;
use App\Application\UseCase\DepositFunds;
use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\LedgerRepository;
use App\Infrastructure\Database\IdempotencyRepository;
use App\Infrastructure\Database\AuditRepository;
use App\Infrastructure\Id\BinaryId;

final class DepositController
{
  public function create(): void
  {
    try {
      $app = require __DIR__ . '/../../../../bootstrap/app.php';
      $db = ($app['db'])();


      $data = Json::body();
      $toHex  = (string)($data['to_wallet_id'] ?? '');
      $amount = (int)($data['amount_kobo'] ?? 0);
      $externalRef = isset($data['external_ref']) ? (string)$data['external_ref'] : null;
      $actor = \App\Interface\Http\Request\Context::get('actor');
      // Idempotency key via header (preferred)

      $idemKey = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
      if ($idemKey === '') $idemKey = (string)($data['idempotency_key'] ?? '');

      $uc = new DepositFunds(
        $db,
        new WalletRepository($db),
        new LedgerAccountRepository($db),
        new LedgerRepository($db),
        new IdempotencyRepository($db),
        new AuditRepository($db),
      );

      $res = $uc->execute(
        BinaryId::fromHex($toHex),
        $amount,
        $idemKey,
        $externalRef,
        $actor
      );

      JsonResponse::send(200, [
        'status' => $res['status'],
        'journal_id' => BinaryId::toHex($res['journal_id']),
        'currency' => 'NGN'
      ]);
    } catch (\Throwable $e) {
      JsonResponse::send(400, ['error' => $e->getMessage()]);
    }
  }
}
