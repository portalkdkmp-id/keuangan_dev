<?php

namespace App\Policies;

use App\Models\CooperativeBankAccount;
use App\Models\User;

class CooperativeBankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cooperative-bank-accounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('cooperative-bank-accounts.create');
    }

    public function update(User $user, CooperativeBankAccount $account): bool
    {
        return $user->can('cooperative-bank-accounts.update');
    }

    public function delete(User $user, CooperativeBankAccount $account): bool
    {
        return $user->can('cooperative-bank-accounts.delete');
    }

    public function setPrimary(User $user, CooperativeBankAccount $account): bool
    {
        return $user->can('cooperative-bank-accounts.set-primary');
    }
}
