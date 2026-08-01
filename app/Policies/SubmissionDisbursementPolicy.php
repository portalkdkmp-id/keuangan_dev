<?php

namespace App\Policies;

use App\Models\SubmissionDisbursement;
use App\Models\User;

class SubmissionDisbursementPolicy
{
    public function view(User $user, SubmissionDisbursement $disbursement): bool
    {
        return $user->hasRole('super_admin')
            || $user->can('fund-distributions.monitor')
            || $user->can('director-disbursements.view')
            || ($user->can('fund-receipts.view') && $disbursement->submission()->where('submitted_by', $user->id)->exists());
    }

    public function downloadProof(User $user, SubmissionDisbursement $disbursement): bool
    {
        return $this->view($user, $disbursement);
    }
}
