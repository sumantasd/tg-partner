<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\FixedCommissionFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'policy_code',
        'name',
        'description',
        'commission_type',
        'commission_value',
        'fixed_frequency',
        'effective_from',
        'effective_until',
        'status',
        'is_default',
        'version',
        'parent_policy_id',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'commission_type' => CommissionType::class,
        'fixed_frequency' => FixedCommissionFrequency::class,
        'commission_value' => 'float',
        'is_default' => 'boolean',
        'version' => 'integer',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function parentPolicy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_policy_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_policy_id');
    }

    /**
     * Check if policy is currently active based on status and effective dates.
     */
    public function isCurrentlyEffective(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $today = now()->toDateString();
        $starts = $this->effective_from->toDateString();
        $ends = $this->effective_until ? $this->effective_until->toDateString() : null;

        if ($today < $starts) {
            return false;
        }

        if ($ends && $today > $ends) {
            return false;
        }

        return true;
    }

    /**
     * Get active global default commission policy.
     */
    public static function getActiveDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('effective_until')
                  ->orWhereDate('effective_until', '>=', now()->toDateString());
            })
            ->latest('version')
            ->first();
    }
}
