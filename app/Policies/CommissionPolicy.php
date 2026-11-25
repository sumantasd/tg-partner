<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Commission;
use App\Models\User;

class CommissionPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Partner], true);
    }

    public function view(User $user, Commission $commission): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Partner && $user->partner) {
            return $commission->partner_id === $user->partner->id;
        }

        return false;
    }

    public function manage(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
