<?php

declare(strict_types=1);

namespace App\Interface\Http\Middleware;

interface Middleware
{
  public function handle(callable $next): void;
}
