<?php

namespace App\Http\Requests\Cooperative;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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

    public function after(): array
    {
        return [function (Validator $validator) {
            $cooperative = $this->route('cooperative');
            if (! $cooperative?->city_id) {
                return;
            }

            $valid = User::query()
                ->whereKey($this->input('user_id'))
                ->where('city_id', $cooperative->city_id)
                ->exists();

            if (! $valid) {
                $validator->errors()->add('user_id', 'PIC harus berada di area kota/kabupaten koperasi.');
            }
        }];
    }
}
