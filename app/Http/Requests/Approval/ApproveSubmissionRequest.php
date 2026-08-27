<?php

namespace App\Http\Requests\Approval;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ApproveSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval-submissions.approve') && $this->route('financialSubmission')?->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function rules(): array
    {
        return [
            'approved_amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_urgent' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            $review = $this->route('financialSubmission')?->activeApprovalReview;
            if (! $review) {
                return;
            }
            if ((float) $this->input('approved_amount') > (float) $review->submitted_amount) {
                $validator->errors()->add('approved_amount', 'Nominal disetujui tidak boleh lebih besar dari nominal review finance.');
            }
            if ((float) $this->input('approved_amount') < (float) $review->submitted_amount && ! $this->filled('notes')) {
                $validator->errors()->add('notes', 'Catatan approval wajib diisi jika nominal disetujui lebih kecil.');
            }
        }];
    }
}
