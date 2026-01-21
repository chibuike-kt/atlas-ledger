<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Interface\Http\Response\JsonResponse;
use App\Interface\Http\Request\Context;

final class AdminAuth implements Middleware
{
  public function __construct(private array $keys) {}

  public function handle(callable $next): void
  {
    $key = $_SERVER['HTTP_X_ADMIN_KEY'] ?? '';

    foreach ($this->keys as $k) {
      if ($k !== '' && hash_equals($k, $key)) {
        Context::set('actor', 'admin');
        $next();
        return;
      }
    }

    JsonResponse::send(401, ['error' => 'Admin unauthorized']);
  }
}
