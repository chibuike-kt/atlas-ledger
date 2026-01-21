<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

use App\Interface\Http\Response\JsonResponse;
use App\Interface\Http\Request\Context;
use App\Infrastructure\Database\RateLimitRepository;

final class RateLimiter implements Middleware
{
  public function __construct(
    private RateLimitRepository $repo,
    private int $limit,
    private int $windowSeconds
  ) {}

  public function handle(callable $next): void
  {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    $actor = (string)Context::get('actor', 'anon');

    // Key design: actor + ip + route + method
    $k = hash('sha256', $actor . '|' . $ip . '|' . $method . '|' . $path, true);

    [$hits, $resetAt] = $this->repo->hit($k, $this->limit, $this->windowSeconds);

    header('X-RateLimit-Limit: ' . $this->limit);
    header('X-RateLimit-Remaining: ' . max(0, $this->limit - $hits));
    header('X-RateLimit-Reset: ' . $resetAt);

    if ($hits > $this->limit) {
      JsonResponse::send(429, [
        'error' => 'Too Many Requests',
        'retry_after_seconds' => max(0, $resetAt - time()),
      ]);
      return;
    }

    $next();
  }
}
