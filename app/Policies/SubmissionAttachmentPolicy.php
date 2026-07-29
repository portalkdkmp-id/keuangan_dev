<?php

namespace App\Policies;

use App\Models\SubmissionAttachment;
use App\Models\User;

class SubmissionAttachmentPolicy
{
    public function view(User $user, SubmissionAttachment $attachment): bool
    {
        return $user->can('view', $attachment->submission);
    }

    public function delete(User $user, SubmissionAttachment $attachment): bool
    {
        return $user->can('update', $attachment->submission);
    }
}
