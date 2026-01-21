<?php

declare(strict_types=1);

namespace App\Interface\Http\Router;

use App\Interface\Http\Request\Context;
use App\Interface\Http\Response\JsonResponse;
use App\Application\Exception\ValidationException;
use App\Application\Exception\NotFoundException;
use App\Application\Exception\ConflictException;
use App\Application\Exception\AuthException;

final class Router
{
  /**
   * @var array<string, array<string, array{handler: callable, middleware: array<int, object>}>>
   */
  private array $routes = [];

  /** @param object[] $middleware */
  public function get(string $path, callable $handler, array $middleware = []): void
  {
    $this->routes['GET'][$path] = ['handler' => $handler, 'middleware' => $middleware];
  }

  /** @param object[] $middleware */
  public function post(string $path, callable $handler, array $middleware = []): void
  {
    $this->routes['POST'][$path] = ['handler' => $handler, 'middleware' => $middleware];
  }

  public function dispatch(string $method, string $uri): void
  {
    Context::clear();

    $path = parse_url($uri, PHP_URL_PATH) ?: '/';
    $route = $this->routes[$method][$path] ?? null;

    if (!$route) {
      JsonResponse::send(404, ['error' => 'Not found']);
      return;
    }

    $handler = $route['handler'];
    $middleware = $route['middleware'];

    $pipeline = array_reduce(
      array_reverse($middleware),
      fn(callable $next, object $mw) => function () use ($mw, $next) {
        if (!method_exists($mw, 'handle')) {
          throw new \RuntimeException('Invalid middleware');
        }
        $mw->handle($next);
      },
      fn() => $handler()
    );

    try {
      $pipeline();
    } catch (\Throwable $e) {
      $code = 500;
      $payload = ['error' => 'Server error'];

      if ($e instanceof ValidationException) {
        $code = 422;
        $payload['error'] = $e->getMessage();
      } elseif ($e instanceof NotFoundException) {
        $code = 404;
        $payload['error'] = $e->getMessage();
      } elseif ($e instanceof ConflictException) {
        $code = 409;
        $payload['error'] = $e->getMessage();
      } elseif ($e instanceof AuthException) {
        $code = 401;
        $payload['error'] = $e->getMessage();
      }

      \App\Interface\Http\Response\JsonResponse::send($code, $payload);
    }
  }
}
