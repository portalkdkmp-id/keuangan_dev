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
            return in_array($submission->status, [SubmissionStatus::SUBMITTED, SubmissionStatus::FINANCE_REVIEW], true);
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
        return $user->can('submissions.delete') && $submission->canBeDeletedBy($user);
    }

    public function review(User $user, FinancialSubmission $submission): bool
    {
        return $user->can('finance-submissions.review') && $submission->status === SubmissionStatus::SUBMITTED;
    }
}
