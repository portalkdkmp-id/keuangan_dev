<?php

namespace App\Services\Advance;

use App\Models\FinancialSubmission;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AdvanceValidationService
{
    public function ensureSubmittable(User $actor, FinancialSubmission $submission): void
    {
        if (! $submission->isAdvance() || $submission->submitted_by !== $actor->id) {
            throw ValidationException::withMessages(['advance' => 'Panjar tidak dapat diajukan.']);
        }$detail = $submission->advanceDetail;
        if (! $detail || ! $detail->recipient_bank_account_id || ! $detail->expected_settlement_date) {
            throw ValidationException::withMessages(['advance' => 'Detail panjar belum lengkap.']);
        }$threshold = config('finance.advance.supporting_document_threshold', 0);
        if ((float) $detail->estimated_amount >= (float) $threshold && ! $submission->attachments()->exists()) {
            throw ValidationException::withMessages(['attachments' => 'Minimal satu dokumen pendukung panjar wajib diunggah.']);
        }
    }
}
