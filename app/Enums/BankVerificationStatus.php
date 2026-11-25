<?php

namespace App\Enums;

enum BankVerificationStatus: string
{
    case NotSubmitted = 'not_submitted';
    case Pending = 'pending';
    case Verified = 'verified';
    case Rejected = 'rejected';
    case CorrectionRequested = 'correction_requested';

    public function label(): string
    {
        return match ($this) {
            self::NotSubmitted => 'Not Submitted',
            self::Pending => 'Pending Verification',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
            self::CorrectionRequested => 'Correction Requested',
        };
    }

    public function isPending(): bool
    {
        return $this === self::Pending;
    }

    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    public function isRejected(): bool
    {
        return $this === self::Rejected;
    }

    public function isCorrectionRequested(): bool
    {
        return $this === self::CorrectionRequested;
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::NotSubmitted => 'bg-slate-100 text-slate-600 border-slate-200',
            self::Pending => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Verified => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 border-rose-200',
            self::CorrectionRequested => 'bg-purple-50 text-purple-700 border-purple-200',
        };
    }
}
