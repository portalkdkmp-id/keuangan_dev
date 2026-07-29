<?php

namespace App\Http\Requests\Submission;

class UpdateSubmissionRequest extends StoreSubmissionRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('financialSubmission')) ?? false;
    }
}
