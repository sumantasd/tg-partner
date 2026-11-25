<?php

namespace App\Enums;

enum LeadDisputeStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Resolved    = 'resolved';
    case Rejected    = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending     => 'Pending Review',
            self::UnderReview => 'Under Review',
            self::Resolved    => 'Resolved',
            self::Rejected    => 'Rejected',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending     => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
            self::UnderReview => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
            self::Resolved    => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            self::Rejected    => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
        };
    }
}
