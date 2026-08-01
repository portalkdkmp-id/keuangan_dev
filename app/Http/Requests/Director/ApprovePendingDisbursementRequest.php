<?php

namespace App\Http\Requests\Director;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApprovePendingDisbursementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('director-submissions.approve') && $this->route('financialSubmission')?->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
        }];
    }
}
