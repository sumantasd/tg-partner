<?php

namespace App\Enums;

enum CommissionStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Payable = 'payable';
    case InWithdrawal = 'in_withdrawal';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending Review',
            self::Approved => 'Approved',
            self::Payable => 'Payable',
            self::InWithdrawal => 'In Withdrawal',
            self::Paid => 'Paid',
            self::Cancelled => 'Cancelled',
        };
    }
}
