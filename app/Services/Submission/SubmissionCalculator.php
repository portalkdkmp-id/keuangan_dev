<?php

namespace App\Services\Submission;

class SubmissionCalculator
{
    public function itemSubtotal(string|int|float $quantity, string|int|float $unitPrice): string
    {
        return number_format(((float) $quantity) * ((float) $unitPrice), 2, '.', '');
    }

    public function total(array $items): string
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) ($item['subtotal'] ?? $this->itemSubtotal($item['quantity'], $item['unit_price']));
        }

        return number_format($total, 2, '.', '');
    }
}
