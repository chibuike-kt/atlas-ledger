<?php

declare(strict_types=1);

namespace App\Infrastructure\Config;

final class Env
{
  public static function load(string $path): void
  {
    if (!is_file($path)) return;

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
      $line = trim($line);
      if ($line === '' || str_starts_with($line, '#')) continue;

      [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
      $k = trim($k);
      $v = trim($v);

      if ($k === '' || getenv($k) !== false) continue;

      // strip optional quotes
      $v = preg_replace('/^"(.*)"$/', '$1', $v) ?? $v;
      $v = preg_replace("/^'(.*)'$/", '$1', $v) ?? $v;

      putenv("$k=$v");
      $_ENV[$k] = $v;
      $_SERVER[$k] = $v;
    }
  }
}
