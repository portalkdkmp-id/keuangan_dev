<?php

namespace App\Services\Reimbursement;

class ReimbursementCalculator
{
    public function total(array $expenses): string
    {
        $cents = collect($expenses)->sum(fn ($e) => (int) round(((float) $e['actual_amount']) * 100));

        return number_format($cents / 100, 2, '.', '');
    }
}
