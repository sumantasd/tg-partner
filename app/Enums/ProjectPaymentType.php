<?php

namespace App\Enums;

enum ProjectPaymentType: string
{
    case Single = 'single';
    case Monthly = 'monthly';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Single Payment',
            self::Monthly => 'Monthly Payment',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Single => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
            self::Monthly => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300',
        };
    }
}
