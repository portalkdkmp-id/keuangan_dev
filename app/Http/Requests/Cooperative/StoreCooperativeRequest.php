<?php

namespace App\Http\Requests\Cooperative;

use App\Models\Cooperative;
use App\Rules\ValidRegionHierarchy;
use Illuminate\Foundation\Http\FormRequest;

class StoreCooperativeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Cooperative::class) ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules() + ['nik' => ['required', 'string', 'max:255', 'unique:cooperatives,nik']];
    }

    protected function baseRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new ValidRegionHierarchy($this->all())],
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'district_id' => ['required', 'exists:districts,id'],
            'village_id' => ['required', 'exists:villages,id'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
