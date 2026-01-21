<?php

declare(strict_types=1);

namespace App\Interface\Http\Controller;

use App\Interface\Http\Request\Json;
use App\Interface\Http\Response\JsonResponse;
use App\Application\UseCase\GetBalance;
use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\LedgerRepository;
use App\Infrastructure\Id\BinaryId;

final class BalanceController
{
  public function show(): void
  {
    try {
      $app = require __DIR__ . '/../../../../bootstrap/app.php';
      $db = ($app['db'])();


      $data = Json::body();
      $walletHex = (string)($data['wallet_id'] ?? '');
      $includeDerived = (bool)($data['include_derived'] ?? false);

      $uc = new GetBalance(
        new WalletRepository($db),
        new LedgerAccountRepository($db),
        new LedgerRepository($db)
      );

      $res = $uc->execute(BinaryId::fromHex($walletHex), $includeDerived);

      $payload = [
        'wallet_id' => BinaryId::toHex($res['wallet_id']),
        'currency' => 'NGN',
        'balance_kobo' => $res['balance_kobo'],
      ];

      if ($includeDerived) {
        $payload['derived_balance_kobo'] = $res['derived_balance_kobo'];
        $payload['diff_kobo'] = $res['diff_kobo'];
      }

      JsonResponse::send(200, $payload);
    } catch (\Throwable $e) {
      JsonResponse::send(400, ['error' => $e->getMessage()]);
    }
  }
}
