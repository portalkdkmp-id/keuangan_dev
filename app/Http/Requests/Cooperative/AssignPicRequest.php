<?php

namespace App\Http\Requests\Cooperative;

use Illuminate\Foundation\Http\FormRequest;

class AssignPicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('assignPic', $this->route('cooperative')) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'exists:users,id'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
