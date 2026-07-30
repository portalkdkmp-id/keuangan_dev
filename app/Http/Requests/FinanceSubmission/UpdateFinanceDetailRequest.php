<?php

namespace App\Http\Requests\FinanceSubmission;

use App\Enums\PaymentMethod;
use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFinanceDetailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('finance-submissions.update') && $this->route('financialSubmission')?->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function rules(): array
    {
        return [
            'budget_account_code' => ['nullable', 'string', 'max:100'],
            'budget_account_name' => ['nullable', 'string', 'max:255'],
            'cost_center_code' => ['nullable', 'string', 'max:100'],
            'cost_center_name' => ['nullable', 'string', 'max:255'],
            'expense_group' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'beneficiary_name' => ['nullable', 'string', 'max:255'],
            'beneficiary_bank' => ['nullable', 'string', 'max:255'],
            'beneficiary_account_number' => ['nullable', 'string', 'max:100'],
            'beneficiary_account_holder' => ['nullable', 'string', 'max:255'],
            'tax_applicable' => ['required', 'boolean'],
            'tax_notes' => ['nullable', 'string', 'max:5000'],
            'finance_notes' => ['nullable', 'string', 'max:5000'],
            'validated_total_amount' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('payment_method') === PaymentMethod::BANK_TRANSFER->value) {
                foreach (['beneficiary_bank', 'beneficiary_account_number', 'beneficiary_account_holder'] as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, 'Wajib diisi untuk metode transfer bank.');
                    }
                }
            }

            if ($this->boolean('tax_applicable') && ! $this->filled('tax_notes')) {
                $validator->errors()->add('tax_notes', 'Catatan pajak wajib diisi.');
            }

            $submission = $this->route('financialSubmission');
            if ($this->filled('validated_total_amount') && (float) $this->input('validated_total_amount') !== (float) $submission->total_amount && ! $this->filled('finance_notes')) {
                $validator->errors()->add('finance_notes', 'Catatan finance wajib diisi jika nominal validasi berbeda.');
            }
        }];
    }
}
