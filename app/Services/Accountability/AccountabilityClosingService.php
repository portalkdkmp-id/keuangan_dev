<?php

namespace App\Services\Accountability;

use App\Enums\AccountabilityStatus;
use App\Models\FundAccountabilityReport;

class AccountabilityClosingService
{
    public function settleAfterApproval(FundAccountabilityReport $report): void
    {
        $status = (float) $report->remaining_amount > 0 ? AccountabilityStatus::RETURN_PENDING : ((float) $report->additional_amount > 0 ? AccountabilityStatus::REIMBURSEMENT_PENDING : AccountabilityStatus::CLOSED);
        $report->update(['status' => $status, 'closed_at' => $status === AccountabilityStatus::CLOSED ? now() : null]);
    }

    public function close(FundAccountabilityReport $report): void
    {
        $report->update(['status' => AccountabilityStatus::CLOSED, 'closed_at' => now()]);
    }
}
