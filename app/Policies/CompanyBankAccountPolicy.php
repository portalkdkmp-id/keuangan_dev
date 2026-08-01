<?php

namespace App\Policies;

use App\Models\CompanyBankAccount;
use App\Models\User;

class CompanyBankAccountPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('company-bank-accounts.view');
    }

    public function create(User $user): bool
    {
        return $user->can('company-bank-accounts.create');
    }

    public function update(User $user, CompanyBankAccount $account): bool
    {
        return $user->can('company-bank-accounts.update');
    }

    public function delete(User $user, CompanyBankAccount $account): bool
    {
        return $user->can('company-bank-accounts.delete');
    }

    public function setPrimary(User $user, CompanyBankAccount $account): bool
    {
        return $user->can('company-bank-accounts.set-primary');
    }
}
