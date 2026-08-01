<?php

namespace App\Http\Requests\Director;

use App\Enums\PaymentMethod;
use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class DisburseSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('director-submissions.disburse') && $this->route('financialSubmission')?->status === SubmissionStatus::PENDING_DISBURSEMENT;
    }

    public function rules(): array
    {
        return [
            'transfer_date' => ['required', 'date'],
            'transferred_at' => ['required', 'date'],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'source_account_name' => ['nullable', 'string', 'max:255'],
            'source_account_number' => ['nullable', 'string', 'max:100'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'attachments' => ['required', 'array', 'min:1', 'max:5'],
            'attachments.*' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->input('payment_method') === PaymentMethod::OTHER->value && ! $this->filled('notes')) {
                $validator->errors()->add('notes', 'Catatan wajib diisi jika metode pembayaran Other.');
            }
        }];
    }
}
