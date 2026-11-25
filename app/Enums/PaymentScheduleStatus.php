<?php

namespace App\Enums;

enum PaymentScheduleStatus: string
{
    case Upcoming = 'upcoming';
    case Due = 'due';
    case PartiallyPaid = 'partially_paid';
    case PaidPendingVerification = 'paid_pending_verification';
    case Verified = 'verified';
    case Overdue = 'overdue';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Upcoming => 'Upcoming',
            self::Due => 'Due',
            self::PartiallyPaid => 'Partially Paid',
            self::PaidPendingVerification => 'Paid (Pending Verification)',
            self::Verified => 'Verified',
            self::Overdue => 'Overdue',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Upcoming => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
            self::Due => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
            self::PartiallyPaid => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300',
            self::PaidPendingVerification => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300',
            self::Verified => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
            self::Overdue => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
            self::Cancelled => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
        };
    }
}
