<?php

declare(strict_types=1);

namespace App\Infrastructure\Id;

use InvalidArgumentException;

final class BinaryId
{
  public static function fromHex(string $hex): string
  {
    $hex = strtolower(trim($hex));
    if (!preg_match('/^[0-9a-f]{32}$/', $hex)) {
      throw new InvalidArgumentException('Invalid id hex (expected 32 hex chars)');
    }
    return hex2bin($hex);
  }

  public static function toHex(string $bytes16): string
  {
    if (strlen($bytes16) !== 16) {
      throw new InvalidArgumentException('Invalid binary id length (expected 16 bytes)');
    }
    return bin2hex($bytes16);
  }
}
