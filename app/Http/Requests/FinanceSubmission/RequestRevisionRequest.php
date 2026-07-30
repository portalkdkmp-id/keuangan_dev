<?php

namespace App\Http\Requests\FinanceSubmission;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RequestRevisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance-submissions.request-revision') && $this->route('financialSubmission')?->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:200'],
            'message' => ['required', 'string', 'max:5000'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', Rule::in(['title', 'purpose', 'needed_date', 'items', 'attachments', 'cooperative', 'other'])],
        ];
    }
}
