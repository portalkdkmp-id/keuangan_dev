<?php

namespace Database\Factories;

use App\Enums\SubmissionAttachmentType;
use App\Models\FinancialSubmission;
use App\Models\SubmissionAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionAttachment>
 */
class SubmissionAttachmentFactory extends Factory
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
            'uploaded_by' => User::factory(),
            'original_name' => 'document.pdf',
            'stored_name' => fake()->uuid().'.pdf',
            'disk' => 'local',
            'path' => 'finance-submissions/document.pdf',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'size' => 1024,
            'attachment_type' => SubmissionAttachmentType::SUPPORTING_DOCUMENT,
            'description' => null,
        ];
    }
}
