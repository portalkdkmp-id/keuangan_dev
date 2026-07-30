<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class ResubmitSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submissions.resubmit')
            && $this->route('financialSubmission')?->status === SubmissionStatus::REVISION_REQUESTED;
    }

    public function rules(): array
    {
        return ['message' => ['nullable', 'string', 'max:5000']];
    }
}
