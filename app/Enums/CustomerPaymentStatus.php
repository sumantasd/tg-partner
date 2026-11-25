<?php

namespace App\Enums;

enum CustomerPaymentStatus: string
{
    case Recorded = 'recorded';
    case Verified = 'verified';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Recorded => 'Recorded (Pending Verification)',
            self::Verified => 'Verified',
            self::Rejected => 'Rejected',
        };
    }
}
