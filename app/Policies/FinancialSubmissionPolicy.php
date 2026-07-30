<?php

namespace App\Policies;

use App\Enums\SubmissionStatus;
use App\Models\FinancialSubmission;
use App\Models\User;

class FinancialSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('submissions.view') || $user->can('finance-submissions.view');
    }

    public function view(User $user, FinancialSubmission $submission): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if ($user->can('finance-submissions.view')) {
            return in_array($submission->status, [
                SubmissionStatus::SUBMITTED,
                SubmissionStatus::FINANCE_REVIEW,
                SubmissionStatus::REVISION_REQUESTED,
                SubmissionStatus::FINANCE_VALIDATED,
                SubmissionStatus::APPROVAL_REVIEW,
            ], true);
        }

        if ($user->can('approval-submissions.view')) {
            return $submission->status === SubmissionStatus::APPROVAL_REVIEW;
        }

        return $user->can('submissions.view')
            && $submission->isOwnedBy($user)
            && $user->assignedCooperatives()->whereKey($submission->cooperative_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.create') && $user->assignedCooperatives()->exists();
    }

    public function update(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('submissions.update') && $submission->canBeEditedBy($user);
    }

    public function delete(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('submissions.delete') && $submission->canBeDeletedBy($user);
    }

    public function submit(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('submissions.submit') && $submission->canBeSubmittedBy($user);
    }

    public function cancel(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('submissions.delete')
            && $submission->isOwnedBy($user)
            && in_array($submission->status, [SubmissionStatus::DRAFT, SubmissionStatus::REVISION_REQUESTED], true);
    }

    public function review(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.review') && $submission->status === SubmissionStatus::SUBMITTED;
    }

    public function updateFinance(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.update') && $submission->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function requestRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.request-revision') && $submission->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function validateFinance(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.validate') && $submission->status === SubmissionStatus::FINANCE_REVIEW;
    }

    public function forwardApproval(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.forward-approval') && in_array($submission->status, [SubmissionStatus::FINANCE_REVIEW, SubmissionStatus::FINANCE_VALIDATED], true);
    }

    public function resubmit(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('submissions.resubmit') && $submission->status === SubmissionStatus::REVISION_REQUESTED && $submission->isOwnedBy($user);
    }
}
