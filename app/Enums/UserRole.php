<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case TeamMember = 'team_member';
    case Partner = 'partner';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::TeamMember => 'Internal Team Member',
            self::Partner => 'Partner',
        };
    }
}
