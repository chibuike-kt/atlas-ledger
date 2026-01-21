<?php

declare(strict_types=1);

use App\Interface\Http\Controller\HealthController;
use App\Interface\Http\Controller\WalletController;
use App\Interface\Http\Controller\TransferController;
use App\Interface\Http\Controller\DepositController;
use App\Interface\Http\Controller\BalanceController;

use App\Interface\Http\Middleware\ApiKeyAuth;
use App\Interface\Http\Middleware\RequireIdempotencyKey;
use App\Interface\Http\Middleware\RateLimiter;

use App\Infrastructure\Database\RateLimitRepository;

use App\Interface\Http\Controller\AdminReconcileController;
use App\Interface\Http\Middleware\AdminAuth;

/** @var \App\Interface\Http\Router\Router $router */
$app = require __DIR__ . '/../bootstrap/app.php';
$keys = $app['config']['security']['api_keys'];

$auth = new ApiKeyAuth($keys);
$idem = new RequireIdempotencyKey();
$adminAuth = new AdminAuth($app['config']['security']['admin_keys']);

$router->get('/health', [new HealthController(), 'index']);

// DB needed for rate limiter:
$db = ($app['db'])();
$rateRepo = new RateLimitRepository($db);

// Limits (tune later)
$rl_wallets  = new RateLimiter($rateRepo, 30, 60);  // 30/min
$rl_money    = new RateLimiter($rateRepo, 10, 60);  // 10/min
$rl_balance  = new RateLimiter($rateRepo, 60, 60);  // 60/min

$router->post('/wallets', [new WalletController(), 'create'], [$auth, $rl_wallets]);

$router->post('/deposits', [new DepositController(), 'create'], [$auth, $idem, $rl_money]);
$router->post('/transfers', [new TransferController(), 'create'], [$auth, $idem, $rl_money]);

$router->post('/wallets/balance', [new BalanceController(), 'show'], [$auth, $rl_balance]);

$router->post(
  '/admin/reconcile',
  [new AdminReconcileController(), 'run'],
  [$adminAuth]
);
