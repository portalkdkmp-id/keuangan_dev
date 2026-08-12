<?php

namespace App\Http\Requests\Pic;

use Illuminate\Foundation\Http\FormRequest;

class StorePicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pics.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:255', 'unique:users,phone'],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'password' => ['required', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
