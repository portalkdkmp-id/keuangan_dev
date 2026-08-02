<?php

namespace App\Services\Distribution;

class FundDistributionCalculator
{
    public function remaining(string|int|float $amount, string|int|float $distributed): string
    {
        return number_format(($this->cents($amount) - $this->cents($distributed)) / 100, 2, '.', '');
    }

    public function compare(string|int|float $left, string|int|float $right): int
    {
        return $this->cents($left) <=> $this->cents($right);
    }

    private function cents(string|int|float $value): int
    {
        $normalized = number_format((float) $value, 2, '.', '');
        [$whole, $decimal] = explode('.', $normalized);

        return ((int) $whole * 100) + (int) $decimal;
    }
}
