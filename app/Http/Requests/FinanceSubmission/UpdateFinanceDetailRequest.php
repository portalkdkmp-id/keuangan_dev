<?php

namespace App\Http\Requests\FinanceSubmission;

use App\Enums\SubmissionStatus;
use App\Models\SubmissionRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFinanceDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('financialSubmission');

        return ($this->user()?->can('finance-submissions.update') && $submission?->status === SubmissionStatus::FINANCE_REVIEW)
            || ($this->user()?->can('finance-submissions.update-approval-revision') && $submission?->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED);
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
            'items' => ['sometimes', 'array', 'min:1', 'max:50'],
            'items.*.name' => ['required_with:items', 'string', 'max:500'],
            'items.*.request_type_id' => ['required_with:items', 'uuid', Rule::exists('submission_request_types', 'id')->where('is_active', true)],
            'items.*.other_type_name' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['required_with:items', 'numeric', 'min:0.01'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,doc,docx'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $categoryId = $this->input('submission_request_category_id');
            $typeIds = collect($this->input('items', []))->pluck('request_type_id')->push($this->input('submission_request_type_id'))->filter()->unique();

            SubmissionRequestType::query()
                ->whereKey($typeIds)
                ->whereNotNull('submission_request_category_id')
                ->where('submission_request_category_id', '!=', $categoryId)
                ->get()
                ->each(fn (SubmissionRequestType $type) => $validator->errors()->add('submission_request_type_id', "Jenis {$type->name} tidak sesuai dengan kategori yang dipilih."));
        }];
    }
}
