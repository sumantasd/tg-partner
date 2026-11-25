<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case InReview = 'in_review';
    case Approved = 'approved';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::InReview => 'In Review',
            self::Approved => 'Approved',
            self::InProgress => 'In Progress',
            self::OnHold => 'On Hold',
            self::Completed => 'Completed',
            self::Rejected => 'Rejected',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Draft => 'bg-gray-100 text-gray-700 border-gray-300',
            self::Submitted => 'bg-blue-50 text-blue-700 border-blue-200',
            self::InReview => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Approved => 'bg-purple-50 text-purple-700 border-purple-200',
            self::InProgress => 'bg-sky-50 text-sky-700 border-sky-200',
            self::OnHold => 'bg-orange-50 text-orange-700 border-orange-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 border-rose-200',
            self::Cancelled => 'bg-gray-200 text-gray-800 border-gray-400',
        };
    }
}
