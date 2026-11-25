<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Determine whether the user can view any leads list.
     */
    public function viewAny(User $user): bool
    {
        return $user->isActive();
    }

    /**
     * Determine whether the user can view the specific lead.
     */
    public function view(User $user, Lead $lead): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isPartner()) {
            return $user->partner && $lead->partner_id === $user->partner->id;
        }

        if ($user->isTeamMember()) {
            return $lead->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can submit new leads.
     */
    public function create(User $user): bool
    {
        return ($user->isPartner() && $user->isActive()) || $user->isAdmin();
    }

    /**
     * Determine whether the user can update the lead / status / activities.
     */
    public function update(User $user, Lead $lead): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isTeamMember()) {
            return $lead->assigned_to === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can assign/reassign the lead.
     */
    public function assign(User $user, Lead $lead): bool
    {
        return $user->isAdmin();
    }
}
