<?php

namespace App\Models;

use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\FixedCommissionFrequency;
use App\Enums\ProjectPaymentType;
use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $partner_id
 * @property int $customer_id
 * @property int|null $lead_id
 * @property int|null $assigned_to
 * @property string $project_code
 * @property string $client_name
 * @property string|null $client_company
 * @property string|null $client_email
 * @property string|null $client_phone
 * @property string $project_title
 * @property string|null $service_category
 * @property string|null $project_description
 * @property float|null $estimated_budget
 * @property float|null $final_deal_value
 * @property \App\Enums\ProjectPaymentType $payment_type
 * @property float|null $monthly_amount
 * @property int|null $duration_months
 * @property \Carbon\Carbon|null $billing_start_date
 * @property int|null $monthly_due_day
 * @property \Carbon\Carbon|null $first_payment_due_date
 * @property int $grace_period_days
 * @property bool $schedules_paused
 * @property \Carbon\Carbon|null $schedules_paused_at
 * @property string $currency
 * @property \App\Enums\ProjectStatus $status
 * @property \App\Enums\CommissionType|null $agreed_commission_type
 * @property float|null $agreed_commission_rate
 * @property string|null $commission_model
 * @property \App\Enums\CommissionType|null $custom_commission_type
 * @property float|null $custom_commission_rate
 * @property \App\Enums\FixedCommissionFrequency|null $custom_fixed_frequency
 * @property \Carbon\Carbon|null $commission_effective_date
 * @property string|null $commission_status
 * @property int|null $commission_policy_id
 * @property string|null $commission_notes
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $expected_completion_date
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $approved_at
 * @property \Carbon\Carbon|null $completed_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\Lead|null $lead
 * @property-read \App\Models\User|null $assignedTo
 * @property-read \App\Models\CommissionPolicy|null $commissionPolicy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectStatusHistory> $statusHistories
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerPayment> $payments
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectPaymentSchedule> $paymentSchedules
 */
class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'partner_id',
        'customer_id',
        'lead_id',
        'assigned_to',
        'project_code',
        'client_name',
        'client_company',
        'client_email',
        'client_phone',
        'project_title',
        'service_category',
        'project_description',
        'estimated_budget',
        'final_deal_value',
        'payment_type',
        'monthly_amount',
        'duration_months',
        'billing_start_date',
        'monthly_due_day',
        'first_payment_due_date',
        'grace_period_days',
        'schedules_paused',
        'schedules_paused_at',
        'currency',
        'status',
        'agreed_commission_type',
        'agreed_commission_rate',
        'commission_model',
        'custom_commission_type',
        'custom_commission_rate',
        'custom_fixed_frequency',
        'commission_effective_date',
        'commission_status',
        'commission_policy_id',
        'commission_notes',
        'rejection_reason',
        'start_date',
        'expected_completion_date',
        'submitted_at',
        'approved_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'payment_type' => ProjectPaymentType::class,
            'agreed_commission_type' => CommissionType::class,
            'custom_commission_type' => CommissionType::class,
            'custom_fixed_frequency' => FixedCommissionFrequency::class,
            'estimated_budget' => 'decimal:2',
            'final_deal_value' => 'decimal:2',
            'monthly_amount' => 'decimal:2',
            'duration_months' => 'integer',
            'monthly_due_day' => 'integer',
            'grace_period_days' => 'integer',
            'schedules_paused' => 'boolean',
            'schedules_paused_at' => 'datetime',
            'billing_start_date' => 'date',
            'first_payment_due_date' => 'date',
            'agreed_commission_rate' => 'decimal:2',
            'custom_commission_rate' => 'decimal:2',
            'commission_effective_date' => 'date',
            'start_date' => 'date',
            'expected_completion_date' => 'date',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Lead, $this>
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * @return HasMany<ProjectStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ProjectStatusHistory::class);
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * @return BelongsTo<CommissionPolicy, $this>
     */
    public function commissionPolicy(): BelongsTo
    {
        return $this->belongsTo(CommissionPolicy::class, 'commission_policy_id');
    }

    /**
     * @return HasMany<ProjectPaymentSchedule, $this>
     */
    public function paymentSchedules(): HasMany
    {
        return $this->hasMany(ProjectPaymentSchedule::class)->orderBy('sequence_number', 'asc');
    }

    public function isMonthlyPayment(): bool
    {
        return $this->payment_type === ProjectPaymentType::Monthly;
    }

    public function isSinglePayment(): bool
    {
        return $this->payment_type !== ProjectPaymentType::Monthly;
    }

    public function totalVerifiedPayments(): float
    {
        return (float) $this->payments()->where('status', CustomerPaymentStatus::Verified)->sum('amount');
    }

    public function totalOutstandingAmount(): float
    {
        $total = (float) ($this->final_deal_value ?? 0);
        $verified = $this->totalVerifiedPayments();

        return max(0, $total - $verified);
    }

    /**
     * Check if project has an active custom commission override.
     */
    public function hasCustomCommissionOverride(): bool
    {
        if ($this->commission_model === 'custom' && $this->commission_status === 'active') {
            return true;
        }

        // Backwards compatibility for existing projects with agreed rate > 0
        if ($this->commission_model !== 'global_default' && (float) $this->agreed_commission_rate > 0) {
            return true;
        }

        return false;
    }
}
