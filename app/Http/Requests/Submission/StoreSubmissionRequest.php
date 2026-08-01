<?php

namespace App\Http\Requests\Submission;

use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FinancialSubmission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'cooperative_id' => ['required', 'uuid', 'exists:cooperatives,id'],
            'submission_request_category_id' => ['required', 'uuid', Rule::exists('submission_request_categories', 'id')->where('is_active', true)],
            'submission_request_type_id' => ['required', 'uuid', Rule::exists('submission_request_types', 'id')->where('is_active', true)],
            'recipient_bank_account_id' => ['required', 'uuid', 'exists:user_bank_accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'title' => ['required', 'string', 'max:200'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'needed_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'action' => ['nullable', Rule::in(['draft', 'submit'])],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,doc,docx'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->user()?->hasRole('finance_staff') && ! $this->user()?->assignedCooperatives()->whereKey($this->input('cooperative_id'))->exists()) {
                $validator->errors()->add('cooperative_id', 'Koperasi tidak termasuk assignment Anda.');
            }

            if (! $this->user()?->bankAccounts()->whereKey($this->input('recipient_bank_account_id'))->where('is_active', true)->exists()) {
                $validator->errors()->add('recipient_bank_account_id', 'Rekening penerima tidak tersedia untuk user ini.');
            }

            if ($this->user()?->hasRole('pic_kdkmp')) {
                $isSales = SubmissionRequestCategory::whereKey($this->input('submission_request_category_id'))
                    ->where('slug', 'operasional-tim-sales')
                    ->exists();

                if ($isSales) {
                    $validator->errors()->add('submission_request_category_id', 'PIC KDKMP tidak dapat memilih Operasional tim Sales.');
                }
            }
        }];
    }
}
