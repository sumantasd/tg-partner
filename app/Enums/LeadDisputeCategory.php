<?php

namespace App\Enums;

enum LeadDisputeCategory: string
{
    case DuplicateRejection       = 'duplicate_rejection';
    case UnauthorizedReassignment = 'unauthorized_reassignment';
    case IncorrectOwnership       = 'incorrect_ownership';
    case MissingOwnership         = 'missing_ownership';
    case Other                    = 'other';

    public function label(): string
    {
        return match ($this) {
            self::DuplicateRejection       => 'Duplicate Submission Rejection',
            self::UnauthorizedReassignment => 'Unauthorized Lead Reassignment',
            self::IncorrectOwnership       => 'Incorrect Ownership Attribution',
            self::MissingOwnership         => 'Missing Referral Attribution',
            self::Other                    => 'Other Dispute',
        };
    }
}
