<?php

namespace App\Http\Requests\Director;

use App\Enums\PaymentMethod;
use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ApproveAndDisburseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->filled('transfer_date') && $this->filled('transferred_at')) {
            $this->merge([
                'transfer_date' => substr((string) $this->input('transferred_at'), 0, 10),
            ]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->can('director-submissions.approve')
            && $this->user()?->can('director-submissions.disburse')
            && $this->route('financialSubmission')?->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0.01'],
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
            $submission = $this->route('financialSubmission');
            if (! $submission?->approval_approved_amount) {
                return;
            }
            if ((float) $this->input('approved_amount') > (float) $submission->approval_approved_amount) {
                $validator->errors()->add('approved_amount', 'Nominal Director tidak boleh lebih besar dari nominal Finance Approval.');
            }
            if ((float) $this->input('approved_amount') < (float) $submission->approval_approved_amount && ! $this->filled('notes')) {
                $validator->errors()->add('notes', 'Catatan wajib diisi jika nominal Director lebih kecil.');
            }
            if ($this->input('payment_method') === PaymentMethod::OTHER->value && ! $this->filled('notes')) {
                $validator->errors()->add('notes', 'Catatan wajib diisi jika metode pembayaran Other.');
            }
        }];
    }
}
