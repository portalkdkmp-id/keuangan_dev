<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionAttachmentType;
use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('financialSubmission');

        return ($this->user()?->can('update', $submission) ?? false)
            || (($this->user()?->can('finance-submissions.update-approval-revision') ?? false)
                && $submission?->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,doc,docx'],
            'attachment_type' => ['nullable', Rule::enum(SubmissionAttachmentType::class)],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
