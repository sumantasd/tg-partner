<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case InProgress = 'in_progress';
    case WaitingOnPartner = 'waiting_on_partner';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::InProgress => 'In Progress',
            self::WaitingOnPartner => 'Waiting on Partner',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }
}

