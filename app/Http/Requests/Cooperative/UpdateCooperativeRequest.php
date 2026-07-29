<?php

namespace App\Http\Requests\Cooperative;

use App\Rules\ValidRegionHierarchy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCooperativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('cooperative')) ?? false;
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'max:255', Rule::unique('cooperatives', 'nik')->ignore($this->route('cooperative'))],
            'name' => ['required', 'string', 'max:255', new ValidRegionHierarchy($this->all())],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
