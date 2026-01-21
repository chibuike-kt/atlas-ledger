<?php

declare(strict_types=1);

namespace App\Domain\Wallet\Entity;

use App\Domain\Shared\ValueObject\Money;

final class Wallet
{
  public function __construct(
    private string $id,          // BINARY(16)
    private string $ownerType,
    private string $ownerId,
    private string $currency = Money::CURRENCY
  ) {}

  public function id(): string
  {
    return $this->id;
  }

  public function currency(): string
  {
    return $this->currency;
  }
}
