<?php

namespace App\Services\Advance;

use App\Enums\AccountabilityStatus;
use App\Enums\AdvanceStatus;
use App\Models\AdvanceDetail;
use App\Models\FundAccountabilityReport;

class AdvanceClosingService
{
    public function evaluate(FundAccountabilityReport $report): void
    {
        if (! $report->advanceDetail) {
            return;
        }$status = match ($report->status) {
            AccountabilityStatus::RETURN_PENDING => AdvanceStatus::RETURN_PENDING,AccountabilityStatus::REIMBURSEMENT_PENDING => AdvanceStatus::REIMBURSEMENT_PENDING,AccountabilityStatus::CLOSED => AdvanceStatus::CLOSED,AccountabilityStatus::SUBMITTED => AdvanceStatus::SETTLEMENT_SUBMITTED,AccountabilityStatus::REVISION_REQUESTED => AdvanceStatus::SETTLEMENT_REVISION_REQUESTED,AccountabilityStatus::FINANCE_VERIFIED => AdvanceStatus::SETTLEMENT_VERIFIED,default => $report->advanceDetail->advance_status
        };
        $values = ['advance_status' => $status];
        if ($status === AdvanceStatus::CLOSED) {
            $values += ['settled_at' => now(), 'closed_at' => now()];
        }$report->advanceDetail->update($values);
    }

    public function tryClose(AdvanceDetail $advance): bool
    {
        if ($advance->advance_status === AdvanceStatus::CLOSED) {
            return true;
        }$report = $advance->settlement;
        if ($report?->status !== AccountabilityStatus::CLOSED) {
            return false;
        }$advance->update(['advance_status' => AdvanceStatus::CLOSED, 'settled_at' => now(), 'closed_at' => now()]);

        return true;
    }
}
