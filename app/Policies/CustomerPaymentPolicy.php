<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\CustomerPayment;
use App\Models\User;

class CustomerPaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function view(User $user, CustomerPayment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, CustomerPayment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function verify(User $user, CustomerPayment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function reject(User $user, CustomerPayment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function reverse(User $user, CustomerPayment $payment): bool
    {
        return $user->role === UserRole::Admin;
    }
}
