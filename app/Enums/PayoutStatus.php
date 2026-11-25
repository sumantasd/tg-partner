<?php

namespace App\Enums;

enum PayoutStatus: string
{
    case Initiated = 'initiated';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Initiated => 'Initiated',
            self::Processing => 'Processing',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function isSettled(): bool
    {
        return $this === self::Completed;
    }

    public function isReleased(): bool
    {
        return in_array($this, [self::Failed, self::Cancelled], true);
    }
}

