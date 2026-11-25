<?php

namespace App\Models;

use App\Enums\LeadInterest;
use App\Enums\LeadOwnershipStatus;
use App\Enums\LeadSlaStatus;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $lead_number
 * @property int|null $partner_id
 * @property int|null $original_partner_id
 * @property int|null $assigned_to
 * @property int|null $assigned_by
 * @property \Carbon\Carbon|null $assigned_at
 * @property int|null $created_by
 * @property string $customer_name
 * @property string|null $customer_company
 * @property string $customer_phone
 * @property string|null $whatsapp_number
 * @property string|null $customer_email
 * @property string|null $city
 * @property string|null $state
 * @property string|null $service_required
 * @property string|null $lead_source
 * @property string|null $priority
 * @property string|null $project_description
 * @property float|null $estimated_value
 * @property string $currency
 * @property \App\Enums\LeadStatus $status
 * @property \App\Enums\LeadOwnershipStatus $ownership_status
 * @property \Carbon\Carbon|null $ownership_started_at
 * @property \Carbon\Carbon|null $ownership_expires_at
 * @property string|null $ownership_change_reason
 * @property \App\Enums\LeadInterest|null $interest_level
 * @property float|null $quotation_value
 * @property \Carbon\Carbon|null $quotation_sent_at
 * @property \Carbon\Carbon|null $next_followup_date
 * @property string|null $loss_reason
 * @property string|null $rejection_reason
 * @property bool $is_duplicate_flag
 * @property int|null $duplicate_matched_lead_id
 * @property int|null $converted_customer_id
 * @property int|null $converted_project_id
 * @property \Carbon\Carbon|null $converted_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $first_contact_due_at
 * @property \Carbon\Carbon|null $first_contacted_at
 * @property \App\Enums\LeadSlaStatus|null $first_contact_sla_status
 * @property \Carbon\Carbon|null $last_contacted_at
 * @property \Carbon\Carbon|null $followup_due_at
 * @property \App\Enums\LeadSlaStatus|null $followup_sla_status
 * @property \App\Enums\LeadSlaStatus|null $overall_sla_status
 * @property int $missed_followups_count
 * @property \Carbon\Carbon|null $last_escalated_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\Partner|null $originalPartner
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \App\Models\User|null $assignedBy
 * @property-read \App\Models\Lead|null $duplicateMatchedLead
 * @property-read \App\Models\Customer|null $convertedCustomer
 * @property-read \App\Models\Project|null $convertedProject
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadOwnershipHistory> $ownershipHistories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadDispute> $disputes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadActivity> $activities
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadSlaEscalation> $slaEscalations
 */
class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_number',
        'partner_id',
        'original_partner_id',
        'assigned_to',
        'assigned_by',
        'assigned_at',
        'created_by',
        'customer_name',
        'customer_company',
        'customer_phone',
        'whatsapp_number',
        'customer_email',
        'city',
        'state',
        'service_required',
        'lead_source',
        'priority',
        'project_description',
        'estimated_value',
        'currency',
        'status',
        'ownership_status',
        'ownership_started_at',
        'ownership_expires_at',
        'ownership_change_reason',
        'interest_level',
        'quotation_value',
        'quotation_sent_at',
        'next_followup_date',
        'loss_reason',
        'rejection_reason',
        'is_duplicate_flag',
        'duplicate_matched_lead_id',
        'converted_customer_id',
        'converted_project_id',
        'converted_at',
        'submitted_at',
        'first_contact_due_at',
        'first_contacted_at',
        'first_contact_sla_status',
        'last_contacted_at',
        'followup_due_at',
        'followup_sla_status',
        'overall_sla_status',
        'missed_followups_count',
        'last_escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => LeadStatus::class,
            'ownership_status' => LeadOwnershipStatus::class,
            'interest_level' => LeadInterest::class,
            'first_contact_sla_status' => LeadSlaStatus::class,
            'followup_sla_status' => LeadSlaStatus::class,
            'overall_sla_status' => LeadSlaStatus::class,
            'estimated_value' => 'decimal:2',
            'quotation_value' => 'decimal:2',
            'assigned_at' => 'datetime',
            'ownership_started_at' => 'datetime',
            'ownership_expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'quotation_sent_at' => 'datetime',
            'next_followup_date' => 'date',
            'converted_at' => 'datetime',
            'is_duplicate_flag' => 'boolean',
            'first_contact_due_at' => 'datetime',
            'first_contacted_at' => 'datetime',
            'last_contacted_at' => 'datetime',
            'followup_due_at' => 'datetime',
            'missed_followups_count' => 'integer',
            'last_escalated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function originalPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'original_partner_id');
    }

    /**
     * @return HasMany<LeadOwnershipHistory, $this>
     */
    public function ownershipHistories(): HasMany
    {
        return $this->hasMany(LeadOwnershipHistory::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<LeadDispute, $this>
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(LeadDispute::class)->orderByDesc('created_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function duplicateMatchedLead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'duplicate_matched_lead_id');
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function convertedProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<LeadActivity, $this>
     */
    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<LeadSlaEscalation, $this>
     */
    public function slaEscalations(): HasMany
    {
        return $this->hasMany(LeadSlaEscalation::class)->orderByDesc('created_at');
    }

    public function isFirstContactOverdue(): bool
    {
        return $this->first_contact_sla_status === LeadSlaStatus::Overdue;
    }

    public function isFollowupOverdue(): bool
    {
        return $this->followup_sla_status === LeadSlaStatus::Overdue;
    }

    public function isUnderActiveSla(): bool
    {
        return !in_array($this->status, [
            LeadStatus::Won,
            LeadStatus::Lost,
            LeadStatus::Rejected,
        ], true) && $this->assigned_to !== null && $this->overall_sla_status !== LeadSlaStatus::Exempt;
    }

    public function getFirstContactRemainingHuman(): string
    {
        if ($this->first_contacted_at) {
            return 'Contacted';
        }

        if (!$this->first_contact_due_at) {
            return 'No Target';
        }

        $now = now();
        if ($now->greaterThan($this->first_contact_due_at)) {
            return 'Overdue by ' . $this->first_contact_due_at->diffForHumans($now, \Carbon\CarbonInterface::DIFF_ABSOLUTE);
        }

        return $now->diffForHumans($this->first_contact_due_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE) . ' remaining';
    }

    public function getFollowupRemainingHuman(): string
    {
        if (!$this->followup_due_at) {
            return 'No Target';
        }

        $now = now();
        if ($now->greaterThan($this->followup_due_at)) {
            return 'Overdue by ' . $this->followup_due_at->diffForHumans($now, \Carbon\CarbonInterface::DIFF_ABSOLUTE);
        }

        return $now->diffForHumans($this->followup_due_at, \Carbon\CarbonInterface::DIFF_ABSOLUTE) . ' remaining';
    }
}
