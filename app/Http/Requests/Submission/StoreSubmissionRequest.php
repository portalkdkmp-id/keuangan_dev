<?php

namespace App\Http\Requests\Submission;

use App\Models\FinancialSubmission;
use App\Models\SubmissionCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', FinancialSubmission::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'cooperative_id' => ['required', 'uuid', 'exists:cooperatives,id'],
            'title' => ['required', 'string', 'max:200'],
            'purpose' => ['required', 'string', 'max:5000'],
            'needed_date' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.category_id' => ['required', 'uuid', Rule::exists('submission_categories', 'id')->where('is_active', true)],
            'items.*.description' => ['required', 'string', 'max:2000'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if (! $this->user()?->assignedCooperatives()->whereKey($this->input('cooperative_id'))->exists()) {
                $validator->errors()->add('cooperative_id', 'Koperasi tidak termasuk assignment Anda.');
            }

            $otherIds = SubmissionCategory::where('code', 'other')->pluck('id')->all();
            foreach ($this->input('items', []) as $index => $item) {
                if (in_array($item['category_id'] ?? null, $otherIds, true) && mb_strlen(trim($item['description'] ?? '')) < 10) {
                    $validator->errors()->add("items.{$index}.description", 'Deskripsi kategori Lainnya minimal 10 karakter.');
                }
            }
        }];
    }
}
