<?php

declare(strict_types=1);

namespace App\Interface\Http\Request;

final class Context
{
  /** @var array<string,mixed> */
  private static array $data = [];

  public static function set(string $key, mixed $value): void
  {
    self::$data[$key] = $value;
  }

  public static function get(string $key, mixed $default = null): mixed
  {
    return self::$data[$key] ?? $default;
  }

  public static function clear(): void
  {
    self::$data = [];
  }
}
