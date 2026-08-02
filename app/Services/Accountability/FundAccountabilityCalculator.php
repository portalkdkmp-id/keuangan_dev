<?php

namespace App\Services\Accountability;

class FundAccountabilityCalculator
{
    public function calculate(string|int|float $received, array $items): array
    {
        $receivedCents = $this->cents($received);
        $realizedCents = array_sum(array_map(fn ($item) => $this->cents($item['amount']), $items));

        return ['realized_amount' => $this->money($realizedCents), 'remaining_amount' => $this->money(max($receivedCents - $realizedCents, 0)), 'additional_amount' => $this->money(max($realizedCents - $receivedCents, 0))];
    }

    private function cents(string|int|float $value): int
    {
        $value = number_format((float) $value, 2, '.', '');
        [$whole, $decimal] = explode('.', $value);

        return ((int) $whole * 100) + (int) $decimal;
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
