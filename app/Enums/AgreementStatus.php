<?php

namespace App\Enums;

enum AgreementStatus: string
{
    case PendingAcceptance = 'pending_acceptance';
    case Accepted          = 'accepted';
    case Revoked           = 'revoked';
    case Superseded        = 'superseded';

    public function label(): string
    {
        return match ($this) {
            self::PendingAcceptance => 'Pending Acceptance',
            self::Accepted          => 'Accepted',
            self::Revoked           => 'Revoked',
            self::Superseded        => 'Superseded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingAcceptance => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800',
            self::Accepted          => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800',
            self::Revoked           => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800',
            self::Superseded        => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700',
        };
    }
}
