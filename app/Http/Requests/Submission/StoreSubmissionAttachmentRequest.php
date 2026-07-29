<?php

namespace App\Http\Requests\Submission;

use App\Enums\SubmissionAttachmentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubmissionAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('financialSubmission')) ?? false;
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
