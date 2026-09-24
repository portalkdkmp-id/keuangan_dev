<?php

namespace App\Http\Requests\Pic;

use Illuminate\Foundation\Http\FormRequest;

class ImportPicsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pics.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx', 'max:51200'],
            'dry_run' => ['sometimes', 'boolean'],
        ];
    }
}
