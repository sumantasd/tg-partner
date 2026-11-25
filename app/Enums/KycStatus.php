<?php

namespace App\Enums;

enum KycStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Pending = 'pending';
    case UnderReview = 'under_review';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case CorrectionRequested = 'correction_requested';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not Submitted',
            self::Pending => 'Pending Verification',
            self::UnderReview => 'Under Review',
            self::Approved => 'Approved',
            self::Rejected => 'Rejected',
            self::CorrectionRequested => 'Correction Requested',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isUnderReview(): bool
    {
        return $this === self::UnderReview;
    }

    public function isApproved(): bool
    {
        return $this === self::Approved;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isCorrectionRequested(): bool
    {
        return $this === self::CorrectionRequested;
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::NotSubmitted, self::CorrectionRequested], true);
    }

    public function isLocked(): bool
    {
        return ! $this->isEditable();
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::NotSubmitted => 'bg-slate-100 text-slate-600 border-slate-200',
            self::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
            self::UnderReview => 'bg-sky-50 text-sky-700 border-sky-200',
            self::Approved => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CorrectionRequested => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
