<?php

namespace Database\Factories;

use App\Enums\SubmissionStatus;
use App\Enums\SubmissionType;
use App\Models\Cooperative;
use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialSubmission>
 */
class FinancialSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_number' => 'FR/'.now()->format('Y/m').'/'.fake()->unique()->numerify('######'),
            'type' => SubmissionType::FUND_REQUEST,
            'status' => SubmissionStatus::DRAFT,
            'cooperative_id' => Cooperative::factory(),
            'submitted_by' => User::factory(),
            'current_assignee_role' => null,
            'title' => fake()->sentence(3),
            'purpose' => fake()->paragraph(),
            'needed_date' => now()->addWeek()->toDateString(),
            'notes' => null,
            'total_amount' => 0,
        ];
    }
}
