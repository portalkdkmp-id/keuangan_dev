<?php

namespace Database\Factories;

use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use App\Models\SubmissionItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionItem>
 */
class SubmissionItemFactory extends Factory
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
            'category_id' => SubmissionCategory::factory(),
            'category_name' => 'Operasional',
            'description' => fake()->sentence(),
            'quantity' => 1,
            'unit' => 'pcs',
            'unit_price' => 100000,
            'subtotal' => 100000,
            'notes' => null,
            'sort_order' => 0,
        ];
    }
}
