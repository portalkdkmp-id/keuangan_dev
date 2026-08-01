<?php

namespace App\Policies;

use App\Models\FundDistribution;
use App\Models\SubmissionDisbursement;
use App\Models\User;

class FundDistributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fund-distributions.view');
    }

    public function view(User $user, FundDistribution $distribution): bool
    {
        return $user->hasRole('super_admin') || $user->can('fund-distributions.monitor') || ($user->can('fund-distributions.view') && ($distribution->distributed_by === $user->id || $distribution->recipient_user_id === $user->id || $distribution->submission->submitted_by === $user->id));
    }

    public function create(User $user, SubmissionDisbursement $disbursement): bool
    {
        return $user->can('fund-distributions.create') && $disbursement->requires_distribution && $disbursement->recipient_user_id === $user->id;
    }

    public function downloadProof(User $user, FundDistribution $distribution): bool
    {
        return $user->can('fund-distributions.download-proof') && $this->view($user, $distribution);
    }
}
