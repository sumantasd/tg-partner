<?php

namespace App\Models;

use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $lead_id
 * @property int|null $user_id
 * @property \App\Enums\LeadActivityType $activity_type
 * @property string|null $from_status
 * @property string|null $to_status
 * @property \App\Enums\LeadInterest|null $customer_interest
 * @property string $summary
 * @property string|null $details
 * @property int|null $call_duration_seconds
 * @property \Carbon\Carbon|null $next_followup_date
 * @property bool $is_internal
 * @property \Carbon\Carbon|null $created_at
 * @property-read \App\Models\Lead|null $lead
 * @property-read \App\Models\User|null $user
 */
class LeadActivity extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'lead_id',
        'user_id',
        'activity_type',
        'from_status',
        'to_status',
        'customer_interest',
        'summary',
        'details',
        'call_duration_seconds',
        'next_followup_date',
        'is_internal',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'activity_type' => LeadActivityType::class,
            'customer_interest' => LeadInterest::class,
            'next_followup_date' => 'date',
            'is_internal' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function scopePartnerVisible($query)
    {
        return $query->where('is_internal', false);
    }

    public function scopeInternalOnly($query)
    {
        return $query->where('is_internal', true);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
