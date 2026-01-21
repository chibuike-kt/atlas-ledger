<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final class Money
{
  public const CURRENCY = 'NGN';

  private int $amountMinor;

  private function __construct(int $amountMinor)
  {
    if ($amountMinor === 0) {
      throw new InvalidArgumentException('Amount cannot be zero');
    }
    $this->amountMinor = $amountMinor;
  }

  public static function credit(int $kobo): self
  {
    if ($kobo <= 0) {
      throw new InvalidArgumentException('Credit must be positive');
    }
    return new self($kobo);
  }

  public static function debit(int $kobo): self
  {
    if ($kobo <= 0) {
      throw new InvalidArgumentException('Debit must be positive');
    }
    return new self(-$kobo);
  }

  public function amountMinor(): int
  {
    return $this->amountMinor;
  }

  public function currency(): string
  {
    return self::CURRENCY;
  }
}
