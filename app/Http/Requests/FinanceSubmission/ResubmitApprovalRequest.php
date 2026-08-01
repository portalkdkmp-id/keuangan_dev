<?php

namespace App\Http\Requests\FinanceSubmission;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class ResubmitApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance-submissions.resubmit-approval') && $this->route('financialSubmission')?->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED;
    }

    public function rules(): array
    {
        return [
            'change_summary' => ['required', 'string', 'min:10', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
