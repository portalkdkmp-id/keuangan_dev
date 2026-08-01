<?php

namespace App\Http\Requests\Director;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class StartDirectorReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('director-submissions.review') && $this->route('financialSubmission')?->status === SubmissionStatus::DIRECTOR_REVIEW;
    }

    public function rules(): array
    {
        return [];
    }
}
