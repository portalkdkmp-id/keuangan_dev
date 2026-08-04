<?php

namespace App\Services\Accountability;

use App\Enums\AccountabilityStatus;
use App\Models\FundAccountabilityReport;
use App\Services\Advance\AdvanceClosingService;

class AccountabilityClosingService
{
    public function __construct(private readonly AdvanceClosingService $advances) {}

    public function settleAfterApproval(FundAccountabilityReport $report): void
    {
        $status = (float) $report->remaining_amount > 0 ? AccountabilityStatus::RETURN_PENDING : ((float) $report->additional_amount > 0 ? AccountabilityStatus::REIMBURSEMENT_PENDING : AccountabilityStatus::CLOSED);
        $report->update(['status' => $status, 'closed_at' => $status === AccountabilityStatus::CLOSED ? now() : null]);
        $this->advances->evaluate($report->fresh('advanceDetail'));
    }

    public function close(FundAccountabilityReport $report): void
    {
        $report->update(['status' => AccountabilityStatus::CLOSED, 'closed_at' => now()]);
        $this->advances->evaluate($report->fresh('advanceDetail'));
    }

    public function syncAdvanceStatus(FundAccountabilityReport $report): void
    {
        $this->advances->evaluate($report->fresh('advanceDetail'));
    }
}
