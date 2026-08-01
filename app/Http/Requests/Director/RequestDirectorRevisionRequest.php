<?php

namespace App\Http\Requests\Director;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestDirectorRevisionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'revision_subject' => $this->input('revision_subject') ?: 'Revisi Finance Approval',
            'revision_fields' => $this->input('revision_fields') ?: ['other'],
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('director-submissions.request-revision') && $this->route('financialSubmission')?->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'revision_subject' => ['required', 'string', 'max:200'],
            'revision_message' => ['required', 'string', 'min:10', 'max:5000'],
            'revision_fields' => ['required', 'array', 'min:1'],
            'revision_fields.*' => ['required', 'string', Rule::in(['approval_amount', 'approval_notes', 'finance_review', 'submission_amount', 'category', 'submission_type', 'bank_account', 'attachment', 'needed_date', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
