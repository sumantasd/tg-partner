<?php

namespace App\Enums;

enum LeadActivityType: string
{
    case LeadSubmitted = 'lead_submitted';
    case LeadAssigned = 'lead_assigned';
    case LeadReassigned = 'lead_reassigned';
    case Call = 'call';
    case Email = 'email';
    case WhatsApp = 'whatsapp';
    case Meeting = 'meeting';
    case FollowUp = 'follow_up';
    case Quotation = 'quotation';
    case Note = 'note';
    case AdminComment = 'admin_comment';
    case StatusChange = 'status_change';
    case OwnershipReassigned = 'ownership_reassigned';
    case OwnershipExtended = 'ownership_extended';
    case OwnershipReleased = 'ownership_released';
    case DisputeRaised = 'dispute_raised';
    case DisputeResolved = 'dispute_resolved';
    case DisputeRejected = 'dispute_rejected';
    case SlaBreached = 'sla_breached';
    case SlaEscalated = 'sla_escalated';
    case FirstContactMet = 'first_contact_met';

    public function label(): string
    {
        return match ($this) {
            self::LeadSubmitted => 'Lead Submitted',
            self::LeadAssigned => 'Lead Assigned',
            self::LeadReassigned => 'Lead Reassigned',
            self::Call => 'Phone Call',
            self::Email => 'Email',
            self::WhatsApp => 'WhatsApp',
            self::Meeting => 'Client Meeting',
            self::FollowUp => 'Follow-up Scheduled',
            self::Quotation => 'Quotation Sent',
            self::Note => 'Internal Note',
            self::AdminComment => 'Admin Comment',
            self::StatusChange => 'Status Changed',
            self::OwnershipReassigned => 'Lead Ownership Reassigned',
            self::OwnershipExtended => 'Lead Ownership Extended',
            self::OwnershipReleased => 'Lead Ownership Released',
            self::DisputeRaised => 'Ownership Dispute Raised',
            self::DisputeResolved => 'Dispute Resolved',
            self::DisputeRejected => 'Dispute Rejected',
            self::SlaBreached => 'SLA Breached',
            self::SlaEscalated => 'SLA Escalated',
            self::FirstContactMet => 'First Contact SLA Met',
        };
    }

    public function isInternalOnly(): bool
    {
        return match ($this) {
            self::Note, self::AdminComment, self::SlaBreached, self::SlaEscalated => true,
            default => false,
        };
    }
}
