<?php

namespace App\Models;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\FixedCommissionFrequency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $commission_number
 * @property int $project_id
 * @property int $partner_id
 * @property int|null $customer_payment_id
 * @property int|null $withdrawal_request_id
 * @property int|null $payout_id
 * @property string|null $milestone_name
 * @property int|null $milestone_sequence
 * @property \App\Enums\CommissionType $commission_type
 * @property string|null $policy_source
 * @property int|null $commission_policy_id
 * @property int|null $policy_version
 * @property string|null $policy_code
 * @property \App\Enums\FixedCommissionFrequency|null $commission_frequency
 * @property string|null $billing_month
 * @property string|null $billing_period
 * @property \Carbon\Carbon|null $effective_policy_date
 * @property \Carbon\Carbon $calculated_at
 * @property float $calculation_basis
 * @property float $rate_applied
 * @property float $amount
 * @property string $currency
 * @property \App\Enums\CommissionStatus $status
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property \Carbon\Carbon|null $payable_at
 * @property \Carbon\Carbon|null $paid_at
 * @property int|null $parent_commission_id
 * @property string|null $adjustment_type
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\CustomerPayment|null $customerPayment
 * @property-read \App\Models\WithdrawalRequest|null $withdrawalRequest
 * @property-read \App\Models\Payout|null $payout
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\CommissionPolicy|null $commissionPolicy
 * @property-read \App\Models\Commission|null $parentCommission
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $adjustments
 */
class Commission extends Model
{
    use HasFactory;

    // Financial ledger - NO soft deletes

    protected $fillable = [
        'commission_number',
        'project_id',
        'partner_id',
        'customer_payment_id',
        'withdrawal_request_id',
        'payout_id',
        'milestone_name',
        'milestone_sequence',
        'commission_type',
        'policy_source',
        'commission_policy_id',
        'policy_version',
        'policy_code',
        'commission_frequency',
        'billing_month',
        'billing_period',
        'effective_policy_date',
        'calculated_at',
        'calculation_basis',
        'rate_applied',
        'amount',
        'currency',
        'status',
        'approved_at',
        'approved_by',
        'payable_at',
        'paid_at',
        'parent_commission_id',
        'adjustment_type',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommissionStatus::class,
            'commission_type' => CommissionType::class,
            'commission_frequency' => FixedCommissionFrequency::class,
            'calculation_basis' => 'decimal:2',
            'rate_applied' => 'decimal:2',
            'amount' => 'decimal:2',
            'policy_version' => 'integer',
            'effective_policy_date' => 'date',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
            'payable_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Commission $commission) {
            if ($commission->status === CommissionStatus::Paid) {
                throw new \DomainException('Paid commissions are immutable financial records and cannot be deleted.');
            }
        });
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Authoritative 1:1 relationship with CustomerPayment.
     *
     * @return BelongsTo<CustomerPayment, $this>
     */
    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    /**
     * @return BelongsTo<WithdrawalRequest, $this>
     */
    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(WithdrawalRequest::class, 'withdrawal_request_id');
    }

    /**
     * @return BelongsTo<Payout, $this>
     */
    public function payout(): BelongsTo
    {
        return $this->belongsTo(Payout::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approvedBy(): BelongsTo
    {
        return $this->approver();
    }

    /**
     * @return BelongsTo<CommissionPolicy, $this>
     */
    public function commissionPolicy(): BelongsTo
    {
        return $this->belongsTo(CommissionPolicy::class, 'commission_policy_id');
    }

    /**
     * @return BelongsTo<Commission, $this>
     */
    public function parentCommission(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_commission_id');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_commission_id');
    }
}
