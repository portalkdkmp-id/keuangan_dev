<?php

namespace App\Policies;

use App\Models\Cooperative;
use App\Models\User;

class CooperativePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('cooperatives.view');
    }

    public function view(User $user, Cooperative $cooperative): bool
    {
        return $user->can('cooperatives.view') && Cooperative::query()->whereKey($cooperative->id)->accessibleBy($user)->exists();
    }

    public function create(User $user): bool
    {
        return $user->can('cooperatives.create');
    }

    public function update(User $user, Cooperative $cooperative): bool
    {
        return $user->can('cooperatives.update');
    }

    public function delete(User $user, Cooperative $cooperative): bool
    {
        return $user->can('cooperatives.delete');
    }

    public function assignPic(User $user, Cooperative $cooperative): bool
    {
        return $user->can('cooperatives.assign-pic');
    }
}
