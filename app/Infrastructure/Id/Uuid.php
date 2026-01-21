<?php

declare(strict_types=1);

namespace App\Infrastructure\Id;

final class Uuid
{
  public static function v4Bytes(): string
  {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return $data; // BINARY(16)
  }

  public static function toHex(string $bytes16): string
  {
    return bin2hex($bytes16);
  }
}
