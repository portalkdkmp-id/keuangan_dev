<?php

namespace App\Http\Requests\FundReturn;

use Illuminate\Foundation\Http\FormRequest;

class SaveFundReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['source_user_bank_account_id' => ['nullable', 'uuid', 'exists:user_bank_accounts,id'], 'destination_company_bank_account_id' => ['required', 'uuid', 'exists:company_bank_accounts,id'], 'transfer_date' => ['required', 'date'], 'transferred_at' => ['required', 'date'], 'payment_method' => ['required', 'in:bank_transfer,cash,other'], 'transaction_reference' => ['nullable', 'string', 'max:255'], 'notes' => ['nullable', 'string', 'max:5000'], 'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240']];
    }
}
