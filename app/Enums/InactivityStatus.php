<?php

namespace App\Enums;

enum InactivityStatus: string
{
    case Active = 'active';
    case AtRisk = 'at_risk';
    case Inactive = 'inactive';
    case Critical = 'critical';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::AtRisk => 'At Risk (30d+)',
            self::Inactive => 'Inactive (60d+)',
            self::Critical => 'Critical (90d+)',
            self::Suspended => 'Suspended',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            self::AtRisk => 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            self::Inactive => 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400 border-orange-200 dark:border-orange-800',
            self::Critical => 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400 border-rose-200 dark:border-rose-800 animate-pulse',
            self::Suspended => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300 border-slate-300 dark:border-slate-700',
        };
    }

    public function isAtRiskOrWorse(): bool
    {
        return in_array($this, [self::AtRisk, self::Inactive, self::Critical, self::Suspended], true);
    }
}
