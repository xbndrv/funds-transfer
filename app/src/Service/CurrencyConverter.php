<?php

namespace App\Service;

readonly class CurrencyConverter
{
    public function __construct(
        private ExchangeRateProvider $rateProvider
    ) {
    }

    public function convert(int $amount, string $from, string $to, bool $ceil = false): ?int
    {
        if ($from == $to || 0 == $amount) {
            return $amount;
        }

        $rate = $this->rateProvider->getRate($from, $to);

        return is_null($rate) ? null : $this->count($amount, $rate, $ceil);
    }

    private function count(int $fromAmount, float $rate, bool $ceil): int
    {
        return $ceil ? (int) ceil($fromAmount * $rate) : (int) floor($fromAmount * $rate);
    }
}
