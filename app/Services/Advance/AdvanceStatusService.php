<?php

namespace App\Services\Advance;

use App\Enums\AdvanceStatus;
use App\Enums\SubmissionStatus;
use App\Models\AdvanceDetail;
use App\Models\FinancialSubmission;

class AdvanceStatusService
{
    public function syncFromSubmissionStatus(FinancialSubmission $submission): ?AdvanceDetail
    {
        if (! $submission->isAdvance() || ! $submission->advanceDetail) {
            return null;
        }$status = match ($submission->status) {
            SubmissionStatus::DRAFT => AdvanceStatus::DRAFT,SubmissionStatus::SUBMITTED => AdvanceStatus::SUBMITTED,SubmissionStatus::FINANCE_REVIEW => AdvanceStatus::UNDER_REVIEW,SubmissionStatus::REVISION_REQUESTED => AdvanceStatus::DRAFT,SubmissionStatus::FINANCE_VALIDATED,SubmissionStatus::APPROVAL_REVIEW,SubmissionStatus::APPROVAL_IN_REVIEW,SubmissionStatus::DIRECTOR_REVIEW,SubmissionStatus::DIRECTOR_IN_REVIEW => AdvanceStatus::APPROVED,SubmissionStatus::PENDING_DISBURSEMENT => AdvanceStatus::PENDING_DISBURSEMENT,SubmissionStatus::FUND_DISBURSED => AdvanceStatus::DISBURSED,SubmissionStatus::CANCELLED => AdvanceStatus::CANCELLED,SubmissionStatus::APPROVAL_REJECTED,SubmissionStatus::DIRECTOR_REJECTED => AdvanceStatus::REJECTED,default => $submission->advanceDetail->advance_status
        };
        $submission->advanceDetail->update(['advance_status' => $status]);

        return $submission->advanceDetail->refresh();
    }
}
