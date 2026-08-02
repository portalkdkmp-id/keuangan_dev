<?php

namespace App\Http\Requests\Director\Concerns;

use App\Enums\DisbursementRecipientType;
use App\Enums\PaymentMethod;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait ValidatesDisbursement
{
    protected function disbursementRules(): array
    {
        return [
            'transfer_date' => ['required', 'date'],
            'transferred_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'source_company_bank_account_id' => ['required', 'uuid', Rule::exists('company_bank_accounts', 'id')->where('is_active', true)],
            'recipient_type' => ['required', Rule::enum(DisbursementRecipientType::class)],
            'recipient_user_id' => ['nullable', 'uuid', 'exists:users,id'],
            'recipient_cooperative_id' => ['nullable', 'uuid', 'exists:cooperatives,id'],
            'destination_bank_account_id' => ['nullable', 'uuid'],
            'destination_bank_name' => ['nullable', 'string', 'max:255'],
            'destination_account_number' => ['nullable', 'string', 'max:255'],
            'destination_account_holder' => ['nullable', 'string', 'max:255'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    protected function validateDisbursement(Validator $validator): void
    {
        $type = $this->input('recipient_type');
        if (in_array($type, [DisbursementRecipientType::FINANCE_STAFF->value, DisbursementRecipientType::PIC_KDKMP->value], true)) {
            foreach (['recipient_user_id', 'destination_bank_account_id'] as $field) {
                if (! $this->filled($field)) {
                    $validator->errors()->add($field, 'Field ini wajib diisi untuk penerima user.');
                }
            }
        }
        if ($type === DisbursementRecipientType::COOPERATIVE->value) {
            foreach (['recipient_cooperative_id', 'destination_bank_account_id'] as $field) {
                if (! $this->filled($field)) {
                    $validator->errors()->add($field, 'Field ini wajib diisi untuk penerima koperasi.');
                }
            }
        }
        if ($type === DisbursementRecipientType::OTHER->value) {
            foreach (['destination_bank_name', 'destination_account_number', 'destination_account_holder', 'recipient_name', 'notes'] as $field) {
                if (! $this->filled($field)) {
                    $validator->errors()->add($field, 'Field ini wajib diisi untuk penerima lain.');
                }
            }
        }
        if ($this->input('payment_method') === PaymentMethod::OTHER->value && ! $this->filled('notes')) {
            $validator->errors()->add('notes', 'Catatan wajib diisi jika metode pembayaran Other.');
        }
    }
}
