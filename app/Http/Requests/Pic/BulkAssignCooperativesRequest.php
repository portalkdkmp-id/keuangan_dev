<?php

namespace App\Http\Requests\Pic;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignCooperativesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pics.assign-cooperatives') ?? false;
    }

    public function rules(): array
    {
        return [
            'cooperative_ids' => ['present', 'array'],
            'cooperative_ids.*' => ['uuid', 'distinct', 'exists:cooperatives,id'],
            'visible_cooperative_ids' => ['required', 'array', 'min:1', 'max:50'],
            'visible_cooperative_ids.*' => ['uuid', 'distinct', 'exists:cooperatives,id'],
        ];
    }
}
