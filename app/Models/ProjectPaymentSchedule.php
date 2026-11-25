<?php

namespace App\Models;

use App\Enums\PaymentScheduleStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $project_id
 * @property int $customer_id
 * @property int $sequence_number
 * @property string $billing_month
 * @property string $billing_period
 * @property \Carbon\Carbon $due_date
 * @property float $expected_amount
 * @property float $received_amount
 * @property float $outstanding_amount
 * @property \App\Enums\PaymentScheduleStatus $status
 * @property int|null $customer_payment_id
 * @property bool $is_paused
 * @property \Carbon\Carbon|null $paused_at
 * @property \Carbon\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\Customer|null $customer
 * @property-read \App\Models\CustomerPayment|null $customerPayment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomerPayment> $payments
 */
class ProjectPaymentSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'customer_id',
        'sequence_number',
        'billing_month',
        'billing_period',
        'due_date',
        'expected_amount',
        'received_amount',
        'outstanding_amount',
        'status',
        'customer_payment_id',
        'is_paused',
        'paused_at',
        'cancelled_at',
        'cancellation_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'expected_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'status' => PaymentScheduleStatus::class,
            'is_paused' => 'boolean',
            'paused_at' => 'datetime',
            'cancelled_at' => 'datetime',
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
     * @return BelongsTo<CustomerPayment, $this>
     */
    public function customerPayment(): BelongsTo
    {
        return $this->belongsTo(CustomerPayment::class, 'customer_payment_id');
    }

    /**
     * @return HasMany<CustomerPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(CustomerPayment::class, 'payment_schedule_id');
    }

    /**
     * Check if schedule is overdue.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, [PaymentScheduleStatus::Verified, PaymentScheduleStatus::Cancelled], true)) {
            return false;
        }

        $grace = $this->project ? $this->project->grace_period_days : 0;
        $deadline = $this->due_date->copy()->addDays($grace);

        return Carbon::now()->startOfDay()->gt($deadline);
    }

    /**
     * Check if schedule is fully settled.
     */
    public function isFullyPaid(): bool
    {
        return (float) $this->received_amount >= (float) $this->expected_amount;
    }
}
