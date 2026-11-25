<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Partner], true);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Partner && $user->partner) {
            return $customer->first_partner_id === $user->partner->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Customer $customer): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function delete(User $user, Customer $customer): bool
    {
        return $user->role === UserRole::Admin;
    }
}
