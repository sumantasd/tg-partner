<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Upi = 'upi';
    case BankTransfer = 'bank_transfer';
    case Neft = 'neft';
    case Rtgs = 'rtgs';
    case Imps = 'imps';
    case Cheque = 'cheque';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Cash',
            self::Upi => 'UPI',
            self::BankTransfer => 'Bank Transfer',
            self::Neft => 'NEFT',
            self::Rtgs => 'RTGS',
            self::Imps => 'IMPS',
            self::Cheque => 'Cheque',
            self::Other => 'Other',
        };
    }
}
