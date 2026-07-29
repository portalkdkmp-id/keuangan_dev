<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\SubmissionStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionStatusHistory>
 */
class SubmissionStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'financial_submission_id' => FinancialSubmission::factory(),
            'from_status' => null,
            'to_status' => SubmissionStatus::DRAFT,
            'changed_by' => User::factory(),
            'action' => 'created',
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
