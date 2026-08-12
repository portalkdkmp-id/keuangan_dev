<?php

namespace App\Policies;

use App\Enums\SubmissionStatus;
use App\Models\SubmissionAttachment;
use App\Models\User;

class SubmissionAttachmentPolicy
{
    public function view(User $user, SubmissionAttachment $attachment): bool
    {
        return $user->can('submissions.export') || $user->can('view', $attachment->submission);
    }

    public function delete(User $user, SubmissionAttachment $attachment): bool
    {
        return $user->can('update', $attachment->submission)
            || ($user->can('finance-submissions.update-approval-revision')
                && $attachment->submission->status === SubmissionStatus::APPROVAL_REVISION_REQUESTED);
    }
}
