<?php

namespace App\Policies;

use App\Models\FundReceiptConfirmation;
use App\Models\User;

class FundReceiptConfirmationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('fund-receipts.view');
    }

    public function view(User $user, FundReceiptConfirmation $receipt): bool
    {
        return $user->hasRole('super_admin') || $user->can('fund-distributions.monitor') || $receipt->recipient_user_id === $user->id;
    }
}
