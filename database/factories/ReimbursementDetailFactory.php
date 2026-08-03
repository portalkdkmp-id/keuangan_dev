<?php

namespace Database\Factories;

use App\Models\FinancialSubmission;
use App\Models\ReimbursementDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReimbursementDetailFactory extends Factory
{
    protected $model = ReimbursementDetail::class;

    public function definition(): array
    {
        return ['financial_submission_id' => FinancialSubmission::factory(), 'claimant_user_id' => User::factory(), 'claimant_bank_name_snapshot' => 'Bank Test', 'claimant_account_number_snapshot' => fake()->numerify('##########'), 'claimant_account_holder_snapshot' => fake()->name(), 'expense_total' => 500000, 'claimed_amount' => 500000, 'source_type' => 'manual'];
    }
}
