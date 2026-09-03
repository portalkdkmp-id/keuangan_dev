<?php

namespace App\Http\Requests\Submission;

use App\Models\FinancialSubmission;
use App\Models\SubmissionRequestCategory;
use App\Models\SubmissionRequestType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
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
            'cooperative_id' => [Rule::requiredIf(fn () => $this->requiresCooperative()), 'nullable', 'uuid', 'exists:cooperatives,id'],
            'submission_request_category_id' => ['required', 'uuid', Rule::exists('submission_request_categories', 'id')->where('is_active', true)],
            'submission_request_type_id' => ['nullable', 'uuid', Rule::exists('submission_request_types', 'id')->where('is_active', true)],
            'recipient_bank_account_id' => ['required', 'uuid', 'exists:user_bank_accounts,id'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.name' => ['required', 'string', 'max:500'],
            'items.*.request_type_id' => ['required', 'uuid', Rule::exists('submission_request_types', 'id')->where('is_active', true)],
            'items.*.other_type_name' => ['nullable', 'string', 'max:255'],
            'items.*.amount' => ['required', 'numeric', 'min:0.01'],
            'title' => ['required', 'string', 'max:200'],
            'purpose' => ['nullable', 'string', 'max:5000'],
            'needed_date' => [
                'nullable',
                'date',
                Rule::when($this->isCreating(), ['after_or_equal:'.today()->addDays(7)->toDateString()]),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_urgent' => ['sometimes', 'boolean'],
            'action' => ['nullable', Rule::in(['draft', 'submit'])],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,webp,xlsx,xls,doc,docx'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->requiresCooperative() && ! $this->user()?->assignedCooperatives()->whereKey($this->input('cooperative_id'))->exists()) {
                $validator->errors()->add('cooperative_id', 'Koperasi tidak termasuk assignment Anda.');
            }

            if (! $this->user()?->bankAccounts()->whereKey($this->input('recipient_bank_account_id'))->where('is_active', true)->exists()) {
                $validator->errors()->add('recipient_bank_account_id', 'Rekening penerima tidak tersedia untuk user ini.');
            }

            if ($this->user()?->hasRole('pic_kdkmp')) {
                $isSales = SubmissionRequestCategory::whereKey($this->input('submission_request_category_id'))
                    ->where('slug', 'operasional-tim-sales')
                    ->exists();

                if ($isSales) {
                    $validator->errors()->add('submission_request_category_id', 'PIC KDKMP tidak dapat memilih Operasional tim Sales.');
                }
            }

            foreach ($this->input('items', []) as $index => $item) {
                $requestType = SubmissionRequestType::query()->find($item['request_type_id'] ?? null);
                if ($requestType?->submission_request_category_id && $requestType->submission_request_category_id !== $this->input('submission_request_category_id')) {
                    $validator->errors()->add("items.$index.request_type_id", 'Jenis pengajuan tidak sesuai dengan kategori yang dipilih.');
                }

                $isOther = $requestType && in_array($requestType->slug, ['lainnya', 'other'], true);
                if ($isOther && blank($item['other_type_name'] ?? null)) {
                    $validator->errors()->add("items.$index.other_type_name", 'Jenis item lainnya wajib diisi.');
                }
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        if ($this->isInternalCategory()) {
            $this->merge(['cooperative_id' => null]);
        }

        if ($this->isCreating()) {
            $neededDate = $this->input('needed_date');
            if (is_string($neededDate) && Carbon::canBeCreatedFromFormat($neededDate, 'Y-m-d')) {
                $this->merge([
                    'is_urgent' => Carbon::createFromFormat('Y-m-d', $neededDate)->isSameDay(today()->addDays(7)),
                ]);
            } else {
                $this->merge(['is_urgent' => false]);
            }
        }

        $items = $this->input('items');
        if (is_array($items) && isset($items[0]) && ! array_key_exists('name', $items[0])) {
            $this->merge(['items' => collect($items)->map(fn (array $item) => [
                'name' => $item['description'] ?? $this->input('title', 'Item pengajuan'),
                'request_type_id' => $item['request_type_id'] ?? $this->input('submission_request_type_id'),
                'other_type_name' => $item['other_type_name'] ?? null,
                'amount' => count($items) === 1 && $this->filled('amount')
                    ? $this->input('amount')
                    : ($item['subtotal'] ?? ((float) ($item['quantity'] ?? 1) * (float) ($item['unit_price'] ?? 0))),
            ])->all()]);
        }

        if (! $this->has('items') && $this->filled('amount') && $this->filled('submission_request_type_id')) {
            $this->merge(['items' => [[
                'name' => $this->input('title', 'Item pengajuan'),
                'request_type_id' => $this->input('submission_request_type_id'),
                'other_type_name' => null,
                'amount' => $this->input('amount'),
            ]]]);
        }
    }

    private function canSubmitInternal(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'finance_staff']) ?? false;
    }

    private function isInternalCategory(): bool
    {
        return SubmissionRequestCategory::query()
            ->whereKey($this->input('submission_request_category_id'))
            ->where('is_internal', true)
            ->exists();
    }

    private function requiresCooperative(): bool
    {
        return ! $this->canSubmitInternal() && ! $this->isInternalCategory();
    }

    private function isCreating(): bool
    {
        return $this->route('financialSubmission') === null;
    }
}
