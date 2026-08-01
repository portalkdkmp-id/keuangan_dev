<?php

namespace App\Http\Requests\Director;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Director\Concerns\ValidatesDisbursement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveAndDisburseRequest extends FormRequest
{
    use ValidatesDisbursement;

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
            ...$this->disbursementRules(),
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->validateDisbursement($validator);
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
        }];
    }
}
