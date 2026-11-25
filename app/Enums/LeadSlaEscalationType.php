<?php

namespace App\Enums;

enum LeadSlaEscalationType: string
{
    case FirstContactBreach = 'first_contact_breach';
    case FollowupBreach = 'followup_breach';
    case RepeatedMissedFollowup = 'repeated_missed_followup';

    public function label(): string
    {
        return match ($this) {
            self::FirstContactBreach => 'First Contact SLA Breach',
            self::FollowupBreach => 'Follow-up SLA Breach',
            self::RepeatedMissedFollowup => 'Repeated Missed Follow-ups',
        };
    }
}
