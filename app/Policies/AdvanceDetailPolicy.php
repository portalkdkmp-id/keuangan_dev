<?php

namespace App\Policies;

use App\Enums\SubmissionStatus;
use App\Models\AdvanceDetail;
use App\Models\User;

class AdvanceDetailPolicy
{
    public function view(User $u, AdvanceDetail $a): bool
    {
        return $u->hasRole('super_admin') || $u->can('advances.review') || $u->can('advances.approve') || $u->can('advances.disburse') || ($u->can('advances.view') && in_array($u->id, [$a->requester_id, $a->responsible_user_id], true));
    }

    public function update(User $u, AdvanceDetail $a): bool
    {
        return $u->can('advances.update') && $a->requester_id === $u->id && in_array($a->submission->status, [SubmissionStatus::DRAFT, SubmissionStatus::REVISION_REQUESTED], true);
    }

    public function submit(User $u, AdvanceDetail $a): bool
    {
        return $u->can('advances.submit') && $this->update($u, $a);
    }

    public function downloadAttachment(User $u, AdvanceDetail $a): bool
    {
        return $u->can('advances.download-attachment') && $this->view($u, $a);
    }
}
