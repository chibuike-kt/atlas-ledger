<?php

declare(strict_types=1);

namespace App\Interface\Http\Controller;

use App\Interface\Http\Request\Json;
use App\Interface\Http\Response\JsonResponse;
use App\Application\UseCase\CreateWallet;
use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\AuditRepository;
use App\Infrastructure\Id\BinaryId;

final class WalletController
{
  public function create(): void
  {
    try {
      $app = require __DIR__ . '/../../../../bootstrap/app.php';
      $db = ($app['db'])();


      $data = Json::body();
      $ownerType = (string)($data['owner_type'] ?? '');
      $ownerId   = (string)($data['owner_id'] ?? '');
      $actor = \App\Interface\Http\Request\Context::get('actor');


      $uc = new CreateWallet(
        $db,
        new WalletRepository($db),
        new LedgerAccountRepository($db),
        new AuditRepository($db),
      );

      $res = $uc->execute($ownerType, $ownerId, $actor);

      JsonResponse::send(201, [
        'wallet_id' => BinaryId::toHex($res['wallet_id']),
        'currency' => 'NGN'
      ]);
    } catch (\Throwable $e) {
      JsonResponse::send(400, ['error' => $e->getMessage()]);
    }
  }
}
