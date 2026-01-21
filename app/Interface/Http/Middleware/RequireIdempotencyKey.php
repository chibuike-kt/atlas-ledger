<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Interface\Http\Response\JsonResponse;

final class RequireIdempotencyKey implements Middleware
{
  public function handle(callable $next): void
  {
    $key = $_SERVER['HTTP_IDEMPOTENCY_KEY'] ?? '';
    $key = trim((string)$key);

    if ($key === '' || strlen($key) > 80) {
      JsonResponse::send(400, ['error' => 'Idempotency-Key header is required (max 80 bytes)']);
      return;
    }

    $next();
  }
}
