<?php

namespace App\Models;

use App\Enums\LeadDisputeCategory;
use App\Enums\LeadDisputeStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $dispute_number
 * @property int $lead_id
 * @property int $partner_id
 * @property \App\Enums\LeadDisputeCategory $dispute_category
 * @property \App\Enums\LeadDisputeStatus $status
 * @property string $customer_phone_claimed
 * @property string|null $customer_email_claimed
 * @property string $explanation
 * @property string|null $evidence_path
 * @property string|null $evidence_filename
 * @property string|null $admin_notes
 * @property string|null $resolution_reason
 * @property int|null $resolved_by
 * @property \Carbon\Carbon|null $resolved_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Lead|null $lead
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\User|null $resolver
 */
class LeadDispute extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'dispute_number',
        'lead_id',
        'partner_id',
        'dispute_category',
        'status',
        'customer_phone_claimed',
        'customer_email_claimed',
        'explanation',
        'evidence_path',
        'evidence_filename',
        'admin_notes',
        'resolution_reason',
        'resolved_by',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'dispute_category' => LeadDisputeCategory::class,
            'status' => LeadDisputeStatus::class,
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
