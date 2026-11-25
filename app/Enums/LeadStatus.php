<?php

namespace App\Enums;

enum LeadStatus: string
{
    case Submitted = 'submitted';
    case Assigned = 'assigned';
    case Contacted = 'contacted';
    case InDiscussion = 'in_discussion';
    case QuotationSent = 'quotation_sent';
    case Negotiating = 'negotiating';
    case Won = 'won';
    case Lost = 'lost';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Submitted => 'Submitted',
            self::Assigned => 'Assigned',
            self::Contacted => 'Contacted',
            self::InDiscussion => 'In Discussion',
            self::QuotationSent => 'Quotation Sent',
            self::Negotiating => 'Negotiating',
            self::Won => 'Won (Converted)',
            self::Lost => 'Lost',
            self::Rejected => 'Rejected',
        };
    }
}
