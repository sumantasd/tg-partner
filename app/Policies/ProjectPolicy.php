<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::Admin, UserRole::Partner, UserRole::TeamMember], true);
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($user->role === UserRole::Partner && $user->partner) {
            return $project->partner_id === $user->partner->id;
        }

        if ($user->role === UserRole::TeamMember) {
            return $project->assigned_to === $user->id;
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->role === UserRole::Admin;
    }
}
