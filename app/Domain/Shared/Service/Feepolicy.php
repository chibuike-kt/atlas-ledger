<?php

declare(strict_types=1);

namespace App\Domain\Shared\Service;

final class FeePolicy
{
  // Example: 1.5% + ₦50, capped at ₦500
  public function withdrawalFeeKobo(int $amountKobo): int
  {
    $percent = intdiv($amountKobo * 150, 10000); // 1.5% in basis points
    $fixed = 5000; // ₦50
    $fee = $percent + $fixed;

    $cap = 50000; // ₦500
    return min($fee, $cap);
  }
}
