<?php

namespace App\Http\Requests\Approval;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class RequestApprovalRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval-submissions.request-revision') && $this->route('financialSubmission')?->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'revision_subject' => ['nullable', 'string', 'max:200'],
            'revision_message' => ['required', 'string', 'min:10', 'max:5000'],
            'revision_fields' => ['nullable', 'array'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'revision_subject' => $this->input('revision_subject') ?: 'Revisi Finance Approval',
            'revision_fields' => $this->input('revision_fields') ?: ['other'],
        ]);
    }
}
