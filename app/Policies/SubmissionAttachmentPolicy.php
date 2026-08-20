<?php

namespace App\Policies;

use App\Enums\SubmissionStatus;
use App\Models\SubmissionAttachment;
use App\Models\User;

class SubmissionAttachmentPolicy
{
    public function view(User $user, SubmissionAttachment $attachment): bool
    {
        if ($user->can('submissions.export')) {
            return ! $user->hasRole('pic_kdkmp') || $attachment->submission->submitted_by === $user->id;
        }

        return $user->can('view', $attachment->submission);
    }

    public function delete(User $user, SubmissionAttachment $attachment): bool
    {
        return $user->can('update', $attachment->submission)
            || ($user->can('finance-submissions.update-approval-revision')
                && $attachment->submission->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED);
    }
}
