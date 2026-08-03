<?php

namespace App\Services\Reimbursement;

use App\Enums\ReimbursementAttachmentType;
use App\Models\FinancialSubmission;
use Illuminate\Validation\ValidationException;

class ReimbursementValidationService
{
    public function ensureSubmittable(FinancialSubmission $submission): void
    {
        $submission->load('reimbursementDetail.expenses.attachments');
        if (! $submission->isReimbursement() || ! $submission->reimbursementDetail || $submission->reimbursementDetail->expenses->isEmpty()) {
            throw ValidationException::withMessages(['reimbursement' => 'Detail transaksi reimbursement belum lengkap.']);
        }
        foreach ($submission->reimbursementDetail->expenses as $expense) {
            $types = $expense->attachments->pluck('attachment_type')->map(fn ($v) => $v instanceof ReimbursementAttachmentType ? $v->value : $v);
            if (! $types->contains('purchase_proof')) {
                throw ValidationException::withMessages(['purchase_proofs' => 'Setiap transaksi wajib memiliki bukti pembelian atau sewa.']);
            }if (! $types->contains('payment_proof')) {
                throw ValidationException::withMessages(['payment_proofs' => 'Setiap transaksi wajib memiliki bukti pembayaran.']);
            }
        }
    }
}
