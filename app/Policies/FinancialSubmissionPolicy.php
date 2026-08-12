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

    public function createReimbursement(User $user): bool
    {
        return $user->can('reimbursements.create');
    }

    public function updateReimbursement(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('reimbursements.update') && $submission->isReimbursement() && $submission->canBeEditedBy($user);
    }

    public function submitReimbursement(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('reimbursements.submit') && $submission->isReimbursement() && $submission->canBeSubmittedBy($user);
    }

    public function downloadReimbursementAttachment(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('reimbursements.download-attachment') && $this->view($user, $submission);
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
                SubmissionStatus::APPROVAL_REVISION_REQUESTED,
                SubmissionStatus::APPROVAL_REJECTED,
                SubmissionStatus::DIRECTOR_REVIEW,
                SubmissionStatus::DIRECTOR_IN_REVIEW,
                SubmissionStatus::DIRECTOR_REVISION_REQUESTED,
                SubmissionStatus::PENDING_DISBURSEMENT,
                SubmissionStatus::FUND_DISBURSED,
                SubmissionStatus::DIRECTOR_REJECTED,
            ], true);
        }

        if ($user->can('approval-submissions.view')) {
            return in_array($submission->status, [
                SubmissionStatus::APPROVAL_REVIEW,
                SubmissionStatus::APPROVAL_IN_REVIEW,
                SubmissionStatus::APPROVAL_REVISION_REQUESTED,
                SubmissionStatus::APPROVAL_REJECTED,
                SubmissionStatus::DIRECTOR_REVIEW,
                SubmissionStatus::DIRECTOR_IN_REVIEW,
                SubmissionStatus::DIRECTOR_REVISION_REQUESTED,
                SubmissionStatus::PENDING_DISBURSEMENT,
                SubmissionStatus::FUND_DISBURSED,
                SubmissionStatus::DIRECTOR_REJECTED,
            ], true);
        }

        if ($user->can('director-submissions.view')) {
            return in_array($submission->status, [
                SubmissionStatus::DIRECTOR_REVIEW,
                SubmissionStatus::DIRECTOR_IN_REVIEW,
                SubmissionStatus::DIRECTOR_REVISION_REQUESTED,
                SubmissionStatus::PENDING_DISBURSEMENT,
                SubmissionStatus::FUND_DISBURSED,
                SubmissionStatus::DIRECTOR_REJECTED,
            ], true);
        }

        return $user->can('submissions.view')
            && $submission->isOwnedBy($user)
            && $user->assignedCooperatives()->whereKey($submission->cooperative_id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('submissions.create');
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

    public function startApprovalReview(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.review') && $submission->status === SubmissionStatus::APPROVAL_REVIEW;
    }

    public function approve(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.approve') && $submission->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function rejectApproval(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.reject') && $submission->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function requestApprovalRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.request-revision') && $submission->status === SubmissionStatus::APPROVAL_IN_REVIEW;
    }

    public function viewApprovalRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.view-approval-revision') && $submission->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED;
    }

    public function updateApprovalRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.update-approval-revision') && $submission->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED;
    }

    public function resubmitToApproval(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.resubmit-approval') && $submission->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED;
    }

    public function viewDirectorQueue(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.view') && $submission->status === SubmissionStatus::DIRECTOR_REVIEW;
    }

    public function startDirectorReview(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.review') && $submission->status === SubmissionStatus::DIRECTOR_REVIEW;
    }

    public function approvePendingDisbursement(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.approve') && $submission->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function approveAndDisburse(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.approve') && $user->can('director-submissions.disburse') && $submission->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function disburse(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.disburse') && $submission->status === SubmissionStatus::PENDING_DISBURSEMENT;
    }

    public function rejectByDirector(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.reject') && $submission->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function requestDirectorRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('director-submissions.request-revision') && $submission->status === SubmissionStatus::DIRECTOR_IN_REVIEW;
    }

    public function viewDirectorRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.view-director-revision') && $submission->status === SubmissionStatus::DIRECTOR_REVISION_REQUESTED;
    }

    public function updateDirectorRevision(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.update-director-revision') && $submission->status === SubmissionStatus::DIRECTOR_REVISION_REQUESTED;
    }

    public function resubmitToDirector(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('approval-submissions.resubmit-director') && $submission->status === SubmissionStatus::DIRECTOR_REVISION_REQUESTED;
    }
}
