<?php

namespace App\Http\Requests\Approval;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class RejectSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval-submissions.reject') && $this->route('financialSubmission')?->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'rejection_reason' => ['required', 'string', 'min:10', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
