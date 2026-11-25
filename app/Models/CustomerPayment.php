<?php

namespace App\Models;

use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $payment_number
 * @property int $project_id
 * @property int $customer_id
 * @property int $partner_id
 * @property \App\Enums\CustomerPaymentType $payment_type
 * @property string|null $billing_month
 * @property string|null $billing_period
 * @property int|null $payment_schedule_id
 * @property float $amount
 * @property string $currency
 * @property string $payment_method
 * @property string|null $transaction_reference
 * @property \Carbon\Carbon $payment_date
 * @property string|null $proof_attachment_path
 * @property int|null $recorded_by
 * @property \App\Enums\CustomerPaymentStatus $status
 * @property int|null $verified_by
 * @property \Carbon\Carbon|null $verified_at
 * @property string|null $rejection_reason
 * @property bool $commission_generated
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\User|null $recordedBy
 * @property-read \App\Models\User|null $verifiedBy
 * @property-read \App\Models\ProjectPaymentSchedule|null $paymentSchedule
 * @property-read \App\Models\Commission|null $commission
 */
class CustomerPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_number',
        'project_id',
        'customer_id',
        'partner_id',
        'payment_type',
        'billing_month',
        'billing_period',
        'payment_schedule_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'payment_date',
        'proof_attachment_path',
        'recorded_by',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'commission_generated',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_type' => CustomerPaymentType::class,
            'status' => CustomerPaymentStatus::class,
            'amount' => 'decimal:2',
            'payment_date' => 'date',
            'verified_at' => 'datetime',
            'commission_generated' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
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
    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<ProjectPaymentSchedule, $this>
     */
    public function paymentSchedule(): BelongsTo
    {
        return $this->belongsTo(ProjectPaymentSchedule::class, 'payment_schedule_id');
    }

    /**
     * Authoritative 1:1 relationship with Commission.
     *
     * @return HasOne<Commission, $this>
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class, 'customer_payment_id');
    }
}
