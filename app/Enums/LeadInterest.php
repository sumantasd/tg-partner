<?php

namespace App\Enums;

enum LeadInterest: string
{
    case Hot = 'hot';
    case Warm = 'warm';
    case Cold = 'cold';
    case NotInterested = 'not_interested';

    public function label(): string
    {
        return match ($this) {
            self::Hot => 'Hot',
            self::Warm => 'Warm',
            self::Cold => 'Cold',
            self::NotInterested => 'Not Interested',
        };
    }
}
