<?php

namespace App\Http\Requests\Approval;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;

class ResubmitDirectorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('approval-submissions.resubmit-director') && $this->route('financialSubmission')?->status === SubmissionStatus::DIRECTOR_REVISION_REQUESTED;
    }

    public function rules(): array
    {
        return [
            'change_summary' => ['required', 'string', 'min:10', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
