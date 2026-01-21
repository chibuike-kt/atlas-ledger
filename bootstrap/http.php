<?php

declare(strict_types=1);

use App\Interface\Http\Router\Router;

$router = new Router();

require __DIR__ . '/../routes/api.php';

return $router;
