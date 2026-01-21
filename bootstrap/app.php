<?php

declare(strict_types=1);

use App\Infrastructure\Config\Env;
use App\Infrastructure\Database\Connection;

Env::load(__DIR__ . '/../.env');

$config = [
  'app' => require __DIR__ . '/../config/app.php',
  'db'  => require __DIR__ . '/../config/database.php',
  'security' => require __DIR__ . '/../config/security.php',
];

$container = [
  'config' => $config,
];

// lazy DB factory
$container['db'] = function () use ($config) {
  return Connection::fromConfig($config['db']['mysql']);
};

return $container;
