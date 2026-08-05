<?php

namespace App\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveReimbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'], 'cooperative_id' => [Rule::requiredIf(! $this->canSubmitInternal()), 'nullable', 'uuid', 'exists:cooperatives,id'], 'claimant_bank_account_id' => ['required', 'uuid', 'exists:user_bank_accounts,id'],
            'summary' => ['nullable', 'string', 'max:5000'], 'expenses' => ['required', 'array', 'min:1', 'max:50'], 'expenses.*.expense_date' => ['required', 'date'],
            'expenses.*.expense_type_id' => ['required', 'uuid', 'exists:submission_request_types,id'], 'expenses.*.vendor_name' => ['required', 'string', 'max:255'],
            'expenses.*.description' => ['required', 'string', 'max:2000'], 'expenses.*.actual_amount' => ['required', 'numeric', 'min:0.01'],
            'expenses.*.payment_method' => ['required', 'in:bank_transfer,cash,debit_card,credit_card,e_wallet,other'], 'expenses.*.payment_reference' => ['nullable', 'string', 'max:255'],
            'expenses.*.notes' => ['nullable', 'string', 'max:2000'], 'purchase_proofs' => ['nullable', 'array'], 'purchase_proofs.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'payment_proofs' => ['nullable', 'array'], 'payment_proofs.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    private function canSubmitInternal(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'finance_staff']) ?? false;
    }
}
