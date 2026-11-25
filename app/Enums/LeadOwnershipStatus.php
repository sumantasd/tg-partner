<?php

namespace App\Enums;

enum LeadOwnershipStatus: string
{
    case Protected   = 'protected';
    case UnderReview = 'under_review';
    case Disputed    = 'disputed';
    case Reassigned  = 'reassigned';
    case Released    = 'released';
    case Expired     = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Protected   => 'Protected',
            self::UnderReview => 'Under Review',
            self::Disputed    => 'Disputed',
            self::Reassigned  => 'Reassigned',
            self::Released    => 'Released',
            self::Expired     => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Protected   => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            self::UnderReview => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950/60 dark:text-sky-300 dark:border-sky-800',
            self::Disputed    => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
            self::Reassigned  => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-950/60 dark:text-purple-300 dark:border-purple-800',
            self::Released    => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
            self::Expired     => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
        };
    }
}
