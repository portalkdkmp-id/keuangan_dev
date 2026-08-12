<?php

namespace App\Http\Requests\Pic;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pics.update') ?? false;
    }

    public function rules(): array
    {
        $pic = $this->route('pic');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($pic)],
            'phone' => ['nullable', 'string', 'max:255', Rule::unique('users', 'phone')->ignore($pic)],
            'city_id' => ['required', 'uuid', 'exists:cities,id'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
