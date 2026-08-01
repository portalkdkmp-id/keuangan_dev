<?php

namespace App\Http\Requests\Accountability;

use Illuminate\Foundation\Http\FormRequest;

class SaveAccountabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'summary' => ['required', 'string', 'max:10000'], 'usage_date_from' => ['nullable', 'date'], 'usage_date_to' => ['nullable', 'date', 'after_or_equal:usage_date_from'],
            'items' => ['required', 'array', 'min:1'], 'items.*.expense_date' => ['required', 'date'], 'items.*.description' => ['required', 'string', 'max:255'], 'items.*.category_id' => ['nullable', 'uuid', 'exists:submission_request_types,id'], 'items.*.amount' => ['required', 'numeric', 'min:0.01'], 'items.*.vendor_name' => ['nullable', 'string', 'max:255'], 'items.*.invoice_number' => ['nullable', 'string', 'max:255'], 'items.*.notes' => ['nullable', 'string', 'max:2000'],
            'attachments' => ['nullable', 'array', 'max:10'], 'attachments.*' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'], 'attachment_type' => ['nullable', 'in:invoice,receipt,photo,handover_document,activity_report,other'],
        ];
    }
}
