<?php

namespace App\Http\Requests\Director;

use App\Enums\SubmissionStatus;
use App\Http\Requests\Director\Concerns\ValidatesDisbursement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class DisburseSubmissionRequest extends FormRequest
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
        return $this->user()?->can('director-submissions.disburse') && $this->route('financialSubmission')?->status === SubmissionStatus::PENDING_DISBURSEMENT;
    }

    public function rules(): array
    {
        return $this->disbursementRules();
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $this->validateDisbursement($validator);
        }];
    }
}
