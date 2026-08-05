<?php

namespace App\Http\Requests\Submission;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submit', $this->route('financialSubmission')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $submission = $this->route('financialSubmission')?->loadMissing('items');
            if (! $submission?->isDraft()) {
                $validator->errors()->add('submission', 'Hanya draft yang dapat dikirim.');
            }
            if ($submission && ($submission->items->isEmpty() || (float) $submission->total_amount <= 0)) {
                $validator->errors()->add('submission', 'Pengajuan harus memiliki item dengan total lebih dari 0.');
            }
            if ($submission && ! $this->user()?->hasAnyRole(['super_admin', 'finance_staff']) && ! $this->user()?->assignedCooperatives()->whereKey($submission->cooperative_id)->exists()) {
                $validator->errors()->add('cooperative_id', 'Assignment koperasi sudah tidak aktif untuk user ini.');
            }
        }];
    }
}
