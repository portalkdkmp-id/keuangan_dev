<?php

namespace App\Http\Requests\Distribution;

use App\Enums\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFundDistributionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('transfer_date') && $this->filled('transferred_at')) {
            $this->merge(['transfer_date' => substr((string) $this->input('transferred_at'), 0, 10)]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('fund-distributions.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'string', 'max:100'],
            'recipient_type' => ['required', Rule::in(['pic_kdkmp', 'cooperative', 'other'])],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'recipient_cooperative_id' => ['nullable', 'uuid', 'exists:cooperatives,id'],
            'destination_bank_account_id' => ['nullable', 'uuid'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'destination_bank_name' => ['nullable', 'string', 'max:255'],
            'destination_account_number' => ['nullable', 'string', 'max:255'],
            'destination_account_holder' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'transfer_date' => ['required', 'date'],
            'transferred_at' => ['required', 'date'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }
}
