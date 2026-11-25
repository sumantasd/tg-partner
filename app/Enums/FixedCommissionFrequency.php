<?php

namespace App\Enums;

enum FixedCommissionFrequency: string
{
    case PerPayment = 'per_payment';
    case PerProject = 'per_project';
    case PerMilestone = 'per_milestone';

    public function label(): string
    {
        return match ($this) {
            self::PerPayment => 'Per Verified Payment',
            self::PerProject => 'Per Project (One-time)',
            self::PerMilestone => 'Per Project Milestone',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::PerPayment => 'Fixed INR commission is awarded on each verified customer payment received for the project.',
            self::PerProject => 'Fixed INR commission is awarded once upon total project deal verification or completion.',
            self::PerMilestone => 'Fixed INR commission is distributed across defined milestone deliveries.',
        };
    }
}
