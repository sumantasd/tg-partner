<?php

namespace App\Models;

use App\Enums\LeadSlaEscalationType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSlaEscalation extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'assigned_to',
        'escalation_type',
        'severity',
        'due_at',
        'breached_at',
        'details',
        'notification_sent',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'escalation_type' => LeadSlaEscalationType::class,
            'due_at' => 'datetime',
            'breached_at' => 'datetime',
            'notification_sent' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
