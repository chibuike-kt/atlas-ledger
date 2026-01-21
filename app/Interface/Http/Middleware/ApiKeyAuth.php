<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Interface\Http\Response\JsonResponse;
use App\Interface\Http\Request\Context;

final class ApiKeyAuth implements Middleware
{
  /** @param string[] $allowedKeys */
  public function __construct(private array $allowedKeys) {}

  public function handle(callable $next): void
  {
    $key = $_SERVER['HTTP_X_API_KEY'] ?? '';

    if ($key === '' || !$this->isAllowed($key)) {
      JsonResponse::send(401, ['error' => 'Unauthorized']);
      return;
    }

    // Store "actor" derived from key (do NOT trust request body for this)
    Context::set('actor', 'api_key:' . substr(hash('sha256', $key), 0, 12));

    $next();
  }

  private function isAllowed(string $provided): bool
  {
    foreach ($this->allowedKeys as $k) {
      if ($k !== '' && hash_equals($k, $provided)) {
        return true;
      }
    }
    return false;
  }
}
