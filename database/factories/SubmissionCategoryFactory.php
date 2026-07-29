<?php

namespace Database\Factories;

use App\Models\SubmissionCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionCategory>
 */
class SubmissionCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->slug(),
            'name' => fake()->words(2, true),
            'description' => null,
            'is_active' => true,
            'sort_order' => 1,
        ];
    }
}
