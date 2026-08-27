<?php

namespace App\Http\Requests;

use App\Enums\SubmissionStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmissionExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('submissions.export') ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', Rule::enum(SubmissionStatus::class)],
            'cooperative_id' => ['nullable', 'uuid', 'exists:cooperatives,id'],
            'pic_id' => ['nullable', 'uuid', 'exists:users,id'],
            'created_from' => ['nullable', 'date'],
            'created_to' => ['nullable', 'date', 'after_or_equal:created_from'],
            'status_updated_from' => ['nullable', 'date'],
            'status_updated_to' => ['nullable', 'date', 'after_or_equal:status_updated_from'],
        ];
    }
}
