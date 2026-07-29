<?php

namespace App\Http\Requests\Cooperative;

use App\Models\Cooperative;
use Illuminate\Foundation\Http\FormRequest;

class ImportCooperativesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cooperative::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'province_id' => ['nullable', 'uuid', 'exists:provinces,id'],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }
}
