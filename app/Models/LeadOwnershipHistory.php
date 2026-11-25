<?php

namespace App\Models;

use App\Enums\LeadOwnershipStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadOwnershipHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'previous_partner_id',
        'new_partner_id',
        'from_status',
        'to_status',
        'action',
        'reason',
        'valid_until_before',
        'valid_until_after',
        'performed_by',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => LeadOwnershipStatus::class,
            'to_status' => LeadOwnershipStatus::class,
            'valid_until_before' => 'datetime',
            'valid_until_after' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function previousPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'previous_partner_id');
    }

    public function newPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'new_partner_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
