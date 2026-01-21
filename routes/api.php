<?php

declare(strict_types=1);

use App\Interface\Http\Controller\HealthController;

/** @var \App\Interface\Http\Router\Router $router */
$router->get('/health', [new HealthController(), 'index']);
