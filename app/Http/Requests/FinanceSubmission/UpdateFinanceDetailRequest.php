<?php

namespace App\Http\Requests\FinanceSubmission;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFinanceDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance-submissions.update') && $this->route('financialSubmission')?->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'submission_request_category_id' => ['required', 'uuid', Rule::exists('submission_request_categories', 'id')->where('is_active', true)],
            'submission_request_type_id' => ['required', 'uuid', Rule::exists('submission_request_types', 'id')->where('is_active', true)],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'needed_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'finance_notes' => ['nullable', 'string', 'max:5000'],
            'rejection_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
