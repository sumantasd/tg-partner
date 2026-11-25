<?php

namespace App\Enums;

enum CustomerPaymentType: string
{
    case Advance = 'advance';
    case Milestone = 'milestone';
    case Final = 'final';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Advance => 'Advance Payment',
            self::Milestone => 'Milestone Payment',
            self::Final => 'Final Payment',
            self::Monthly => 'Monthly Payment',
        };
    }
}
