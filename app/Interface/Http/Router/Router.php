<?php

declare(strict_types=1);

namespace App\Interface\Http\Router;

final class Router
{
  /** @var array<string, array<string, callable>> */
  private array $routes = [];

  public function get(string $path, callable $handler): void
  {
    $this->routes['GET'][$path] = $handler;
  }

  public function post(string $path, callable $handler): void
  {
    $this->routes['POST'][$path] = $handler;
  }

  public function dispatch(string $method, string $uri): void
  {
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $handler = $this->routes[$method][$path] ?? null;

    if (!$handler) {
      http_response_code(404);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['error' => 'Not found']);
      return;
    }

    $handler();
  }
}
