<?php

namespace App\Models;

use App\Enums\AuditLogAction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int|null $actor_user_id
 * @property string $actor_name_snapshot
 * @property string $actor_role_snapshot
 * @property \App\Enums\AuditLogAction|string $action
 * @property string $action_label
 * @property string $action_value
 * @property string $module
 * @property string|null $subject_type
 * @property int|string|null $subject_id
 * @property string $description
 * @property array<string, mixed>|null $old_values
 * @property array<string, mixed>|null $new_values
 * @property array<string, mixed>|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 * @property-read string $formatted_created_at
 * @property-read \App\Models\User|null $actor
 * @property-read \Illuminate\Database\Eloquent\Model|null $subject
 */
class AuditLog extends Model
{
    use HasFactory;

    /**
     * Audit logs are immutable and append-only.
     */
    const UPDATED_AT = null;

    /**
     * Boot model and enforce strict append-only immutability.
     */
    protected static function booted(): void
    {
        static::updating(function ($model) {
            throw new \RuntimeException('Audit logs are immutable and cannot be updated.');
        });

        static::deleting(function ($model) {
            throw new \RuntimeException('Audit logs are append-only and cannot be deleted.');
        });
    }

    protected $fillable = [
        'actor_user_id',
        'actor_name_snapshot',
        'actor_role_snapshot',
        'action',
        'module',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Interact with the action attribute.
     * Converts valid enum string values to AuditLogAction enum,
     * while safely preserving unknown or legacy action strings without throwing ValueError.
     */
    protected function action(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => is_string($value) ? (AuditLogAction::tryFrom($value) ?? $value) : $value,
            set: fn (mixed $value) => $value instanceof AuditLogAction ? $value->value : (is_string($value) ? $value : (string) $value),
        );
    }

    /**
     * Get a human-readable action label safely.
     */
    public function getActionLabelAttribute(): string
    {
        if ($this->action instanceof AuditLogAction) {
            return $this->action->label();
        }

        if (is_string($this->action)) {
            $enum = AuditLogAction::tryFrom($this->action);
            if ($enum) {
                return $enum->label();
            }

            return ucwords(str_replace(['_', '-'], ' ', $this->action));
        }

        return 'Unknown Action';
    }

    /**
     * Get the raw action string value safely.
     */
    public function getActionValueAttribute(): string
    {
        if ($this->action instanceof AuditLogAction) {
            return $this->action->value;
        }

        return (string) ($this->action ?? '');
    }

    /**
     * Actor user relation.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    /**
     * Polymorphic relation to subject (Lead, Partner, Payment, etc.).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'subject_type', 'subject_id');
    }

    /**
     * Formatted IST created timestamp.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at
            ? $this->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') . ' IST'
            : '-';
    }

    /**
     * Scope by module.
     */
    public function scopeModule(Builder $query, ?string $module): Builder
    {
        if (empty($module)) {
            return $query;
        }

        return $query->where('module', $module);
    }

    /**
     * Scope by action.
     */
    public function scopeAction(Builder $query, ?string $action): Builder
    {
        if (empty($action)) {
            return $query;
        }

        return $query->where('action', $action);
    }

    /**
     * Scope by actor.
     */
    public function scopeActor(Builder $query, mixed $actorId): Builder
    {
        if (empty($actorId)) {
            return $query;
        }

        return $query->where('actor_user_id', $actorId);
    }

    /**
     * Scope by actor role snapshot.
     */
    public function scopeRole(Builder $query, ?string $role): Builder
    {
        if (empty($role)) {
            return $query;
        }

        return $query->where('actor_role_snapshot', $role);
    }

    /**
     * Scope by date range (in IST).
     */
    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if (! empty($from)) {
            $fromDate = Carbon::parse($from, 'Asia/Kolkata')->startOfDay()->timezone('UTC');
            $query->where('created_at', '>=', $fromDate);
        }

        if (! empty($to)) {
            $toDate = Carbon::parse($to, 'Asia/Kolkata')->endOfDay()->timezone('UTC');
            $query->where('created_at', '<=', $toDate);
        }

        return $query;
    }

    /**
     * Scope by subject type.
     */
    public function scopeSubjectType(Builder $query, ?string $subjectType): Builder
    {
        if (empty($subjectType)) {
            return $query;
        }

        return $query->where('subject_type', $subjectType);
    }

    /**
     * Scope by search query across description, subject ID, or actor snapshot.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        $search = trim((string) $search);
        if ($search === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('description', 'like', "%{$search}%")
                ->orWhere('actor_name_snapshot', 'like', "%{$search}%")
                ->orWhere('action', 'like', "%{$search}%")
                ->orWhere('subject_id', $search);
        });
    }
}
