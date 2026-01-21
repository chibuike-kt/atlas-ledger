<?php

declare(strict_types=1);

namespace App\Interface\Http\Request;

use RuntimeException;

final class Json
{
  /** @return array<string,mixed> */
  public static function body(): array
  {
    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);

    if (!is_array($data)) {
      throw new RuntimeException('Invalid JSON body');
    }
    return $data;
  }
}
