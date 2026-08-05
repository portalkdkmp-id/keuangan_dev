<?php

namespace App\Http\Requests\Advance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAdvanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('advances.create') || $this->user()?->can('advances.update');
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:200'], 'cooperative_id' => [Rule::requiredIf(! $this->canSubmitInternal()), 'nullable', 'uuid', 'exists:cooperatives,id'], 'purpose' => ['required', 'string', 'max:5000'], 'estimated_amount' => ['required', 'numeric', 'min:0.01'], 'expected_transaction_date' => ['nullable', 'date', 'after_or_equal:today'], 'expected_settlement_date' => ['required', 'date', 'after_or_equal:today'], 'recipient_bank_account_id' => ['required', 'uuid', 'exists:user_bank_accounts,id'], 'notes' => ['nullable', 'string', 'max:5000'], 'attachments' => ['nullable', 'array', 'max:10'], 'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,doc,docx', 'max:10240']];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $transaction = $this->date('expected_transaction_date');
            $settlement = $this->date('expected_settlement_date');
            if ($transaction && $settlement && $settlement->lt($transaction)) {
                $v->errors()->add('expected_settlement_date', 'Deadline tidak boleh sebelum tanggal transaksi.');
            }if ($settlement && $settlement->gt(now()->addDays(config('finance.advance.max_settlement_days', 30))->endOfDay())) {
                $v->errors()->add('expected_settlement_date', 'Deadline melebihi batas settlement yang diizinkan.');
            }if (! $this->user()?->bankAccounts()->where('is_active', true)->whereKey($this->input('recipient_bank_account_id'))->exists()) {
                $v->errors()->add('recipient_bank_account_id', 'Rekening harus aktif dan milik penanggung jawab.');
            }
        }];
    }

    private function canSubmitInternal(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'finance_staff']) ?? false;
    }
}
