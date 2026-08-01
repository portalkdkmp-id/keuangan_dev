<?php

namespace App\Http\Requests\Approval;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestApprovalRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval-submissions.request-revision') && $this->route('financialSubmission')?->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'revision_subject' => ['required', 'string', 'max:200'],
            'revision_message' => ['required', 'string', 'min:10', 'max:5000'],
            'revision_fields' => ['required', 'array', 'min:1'],
            'revision_fields.*' => ['required', 'string', Rule::in(['title', 'category', 'submission_type', 'amount', 'needed_date', 'pic_notes', 'finance_notes', 'bank_account', 'attachment', 'cooperative', 'other'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
