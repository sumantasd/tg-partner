<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\InactivityStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $employee_id
 * @property string $password
 * @property \App\Enums\UserRole $role
 * @property \App\Enums\UserStatus $status
 * @property string|null $designation
 * @property string|null $department
 * @property \Carbon\Carbon|null $joining_date
 * @property string|null $profile_photo_path
 * @property \Carbon\Carbon|null $last_login_at
 * @property \Carbon\Carbon|null $last_logout_at
 * @property \Carbon\Carbon|null $last_meaningful_activity_at
 * @property \Carbon\Carbon|null $last_lead_submission_at
 * @property \Carbon\Carbon|null $last_lead_update_at
 * @property \Carbon\Carbon|null $last_customer_interaction_at
 * @property \App\Enums\InactivityStatus $inactivity_status
 * @property \Carbon\Carbon|null $inactivity_warning_sent_at
 * @property \Carbon\Carbon|null $inactivity_alert_sent_at
 * @property \Carbon\Carbon|null $inactivity_critical_sent_at
 * @property bool $inactivity_flagged_for_review
 * @property \Carbon\Carbon|null $inactivity_flagged_at
 * @property int|null $inactivity_flagged_by
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Partner|null $partner
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'employee_id',
        'password',
        'role',
        'designation',
        'department',
        'joining_date',
        'profile_photo_path',
        'last_login_at',
        'last_logout_at',
        'last_meaningful_activity_at',
        'last_lead_submission_at',
        'last_lead_update_at',
        'last_customer_interaction_at',
        'inactivity_status',
        'inactivity_warning_sent_at',
        'inactivity_alert_sent_at',
        'inactivity_critical_sent_at',
        'inactivity_flagged_for_review',
        'inactivity_flagged_at',
        'inactivity_flagged_by',
        'inactivity_review_reason',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'joining_date' => 'date',
            'last_login_at' => 'datetime',
            'last_logout_at' => 'datetime',
            'last_meaningful_activity_at' => 'datetime',
            'last_lead_submission_at' => 'datetime',
            'last_lead_update_at' => 'datetime',
            'last_customer_interaction_at' => 'datetime',
            'inactivity_status' => InactivityStatus::class,
            'inactivity_warning_sent_at' => 'datetime',
            'inactivity_alert_sent_at' => 'datetime',
            'inactivity_critical_sent_at' => 'datetime',
            'inactivity_flagged_for_review' => 'boolean',
            'inactivity_flagged_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function getMobileNumberAttribute(): ?string
    {
        return $this->phone;
    }

    public function setMobileNumberAttribute(?string $value): void
    {
        $this->attributes['phone'] = $value;
    }

    public function getEmployeeIdDisplayAttribute(): string
    {
        return $this->employee_id ?: ('TG-TM-' . str_pad((string) $this->id, 3, '0', STR_PAD_LEFT));
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }
        return null;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeamMember(): bool
    {
        return $this->role === UserRole::TeamMember;
    }

    public function isPartner(): bool
    {
        return $this->role === UserRole::Partner;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function isPending(): bool
    {
        return $this->status === UserStatus::Pending;
    }

    public function isSuspended(): bool
    {
        return $this->status === UserStatus::Suspended;
    }

    /**
     * @return HasOne<Partner, $this>
     */
    public function partner(): HasOne
    {
        return $this->hasOne(Partner::class);
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function loggedLeadActivities(): HasMany
    {
        return $this->hasMany(LeadActivity::class, 'user_id');
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function verifiedCustomerPayments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'verified_by');
    }

    /**
     * @return HasMany<WithdrawalRequest, $this>
     */
    public function reviewedWithdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class, 'reviewed_by');
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * @return HasMany<SupportTicketReply, $this>
     */
    public function supportTicketReplies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class);
    }

    /**
     * @return HasMany<Partner, $this>
     */
    public function approvedPartners(): HasMany
    {
        return $this->hasMany(Partner::class, 'approved_by');
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function processedPayouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'processed_by');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function approvedCommissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'approved_by');
    }

    /**
     * @return HasMany<Notification, $this>
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * @return HasMany<UserInactivityHistory, $this>
     */
    public function inactivityHistories(): HasMany
    {
        return $this->hasMany(UserInactivityHistory::class)->orderByDesc('created_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function inactivityFlaggedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivity_flagged_by');
    }

    public function getLatestActivityDate(): ?\Carbon\CarbonInterface
    {
        $activityDates = array_filter([
            $this->last_meaningful_activity_at,
            $this->last_login_at,
            $this->last_lead_submission_at,
            $this->last_customer_interaction_at,
            $this->last_lead_update_at,
        ]);

        if (! empty($activityDates)) {
            return collect($activityDates)->max();
        }

        return $this->created_at;
    }

    public function getInactivityDays(): int
    {
        $latest = $this->getLatestActivityDate();
        if (! $latest || $latest->isFuture()) {
            return 0;
        }

        return max(0, (int) $latest->diffInDays(now()));
    }

    public function isAtRisk(): bool
    {
        return $this->inactivity_status === InactivityStatus::AtRisk;
    }

    public function isInactivityCritical(): bool
    {
        return $this->inactivity_status === InactivityStatus::Critical;
    }
}
