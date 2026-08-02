<?php

namespace Database\Factories;

use App\Models\ReimbursementDetail;
use App\Models\ReimbursementExpense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReimbursementExpenseFactory extends Factory
{
    protected $model = ReimbursementExpense::class;

    public function definition(): array
    {
        return ['reimbursement_detail_id' => ReimbursementDetail::factory(), 'expense_date' => now()->toDateString(), 'expense_type_name_snapshot' => 'ATK dan Fotokopi', 'vendor_name' => fake()->company(), 'description' => fake()->sentence(), 'actual_amount' => 500000, 'payment_method' => 'bank_transfer'];
    }
}
