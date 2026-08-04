<?php

namespace App\Policies;

use App\Enums\AccountabilityStatus;
use App\Models\FinancialSubmission;
use App\Models\FundAccountabilityReport;
use App\Models\User;

class FundAccountabilityReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('accountability-reports.view');
    }

    public function view(User $user, FundAccountabilityReport $report): bool
    {
        if ($report->source_type === 'advance') {
            return $user->hasRole('super_admin') || $user->can('advance-settlements.review') || $user->can('advance-settlements.approve') || ($user->can('advance-settlements.view') && $report->submitted_by === $user->id);
        }

        return $user->hasRole('super_admin') || $user->can('accountability-reports.review') || $user->can('accountability-reports.approve') || $user->can('fund-monitoring.view') || ($user->can('accountability-reports.view') && $report->submitted_by === $user->id);
    }

    public function create(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('accountability-reports.create') && $submission->submitted_by === $user->id && $submission->receiptConfirmations()->exists() && ! $submission->accountabilityReport()->exists();
    }

    public function update(User $user, FundAccountabilityReport $report): bool
    {
        return $user->can('accountability-reports.update') && $report->submitted_by === $user->id && in_array($report->status, [AccountabilityStatus::DRAFT, AccountabilityStatus::REVISION_REQUESTED], true);
    }

    public function submit(User $user, FundAccountabilityReport $report): bool
    {
        return $user->can('accountability-reports.submit') && $this->update($user, $report);
    }

    public function review(User $user, FundAccountabilityReport $report): bool
    {
        if ($report->source_type === 'advance') {
            return $user->can('advance-settlements.review') && $report->submitted_by !== $user->id && in_array($report->status, [AccountabilityStatus::SUBMITTED, AccountabilityStatus::FINANCE_REVIEW], true);
        }

        return $user->can('accountability-reports.review') && in_array($report->status, [AccountabilityStatus::SUBMITTED, AccountabilityStatus::FINANCE_REVIEW], true);
    }

    public function approve(User $user, FundAccountabilityReport $report): bool
    {
        if ($report->source_type === 'advance') {
            return $user->can('advance-settlements.approve') && $report->status === AccountabilityStatus::FINANCE_VERIFIED;
        }

        return $user->can('accountability-reports.approve') && $report->status === AccountabilityStatus::FINANCE_VERIFIED;
    }

    public function downloadAttachment(User $user, FundAccountabilityReport $report): bool
    {
        return $user->can('accountability-reports.download-attachment') && $this->view($user, $report);
    }

    public function createFundReturn(User $user, FundAccountabilityReport $report): bool
    {
        return $user->can('fund-returns.create') && $report->submitted_by === $user->id && $report->status === AccountabilityStatus::RETURN_PENDING && ! $report->fundReturn()->exists();
    }

    public function createShortfallReimbursement(User $user, FundAccountabilityReport $report): bool
    {
        return $user->can('reimbursements.create') && $report->submitted_by === $user->id && $report->status === AccountabilityStatus::REIMBURSEMENT_PENDING && ! $report->generatedReimbursement()->exists();
    }
}
