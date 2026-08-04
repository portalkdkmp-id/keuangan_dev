<?php

namespace App\Http\Requests\AdvanceSettlement;

use Illuminate\Foundation\Http\FormRequest;

class SaveAdvanceSettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['summary' => ['required', 'string', 'max:10000'], 'usage_date_from' => ['nullable', 'date'], 'usage_date_to' => ['nullable', 'date', 'after_or_equal:usage_date_from'], 'items' => ['required', 'array', 'min:1', 'max:50'], 'items.*.expense_date' => ['required', 'date'], 'items.*.description' => ['required', 'string', 'max:2000'], 'items.*.category_id' => ['nullable', 'uuid', 'exists:submission_request_types,id'], 'items.*.amount' => ['required', 'numeric', 'min:0.01'], 'items.*.vendor_name' => ['required', 'string', 'max:255'], 'items.*.invoice_number' => ['nullable', 'string', 'max:255'], 'items.*.payment_method' => ['required', 'in:bank_transfer,cash,debit_card,credit_card,e_wallet,other'], 'items.*.payment_reference' => ['nullable', 'string', 'max:255'], 'items.*.notes' => ['nullable', 'string', 'max:2000'], 'purchase_proofs' => ['nullable', 'array'], 'purchase_proofs.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], 'payment_proofs' => ['nullable', 'array'], 'payment_proofs.*.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240']];
    }
}
