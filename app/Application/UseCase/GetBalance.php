<?php

declare(strict_types=1);

namespace App\Application\UseCase;

use App\Infrastructure\Database\WalletRepository;
use App\Infrastructure\Database\LedgerAccountRepository;
use App\Infrastructure\Database\LedgerRepository;

final class GetBalance
{
  public function __construct(
    private WalletRepository $wallets,
    private LedgerAccountRepository $accounts,
    private LedgerRepository $ledger
  ) {}

  public function execute(string $walletId16, bool $includeDerived = false): array
  {
    $w = $this->wallets->findWallet($walletId16);
    if (!$w) throw new \RuntimeException('Wallet not found');
    if ($w['currency'] !== 'NGN') throw new \RuntimeException('Only NGN supported');

    $acc = $this->accounts->findAccountIdByWallet($walletId16);
    if (!$acc) throw new \RuntimeException('Ledger account missing');

    $stored = $this->accounts->getBalance($acc);

    $out = [
      'wallet_id' => $walletId16,
      'currency' => 'NGN',
      'balance_kobo' => $stored,
    ];

    if ($includeDerived) {
      $derived = $this->ledger->derivedBalance($acc);
      $out['derived_balance_kobo'] = $derived;
      $out['diff_kobo'] = $stored - $derived;
    }

    return $out;
  }
}
