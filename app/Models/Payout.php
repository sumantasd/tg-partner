<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $payout_number
 * @property int $partner_id
 * @property int $withdrawal_request_id
 * @property float $amount
 * @property string $currency
 * @property string $payment_method
 * @property string|null $transaction_reference
 * @property \App\Enums\PayoutStatus $payment_status
 * @property \Carbon\Carbon $payment_date
 * @property int|null $processed_by
 * @property array<string, mixed>|null $recipient_snapshot
 * @property string|null $receipt_path
 * @property string|null $notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\WithdrawalRequest|null $withdrawalRequest
 * @property-read \App\Models\User|null $processor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 */
class Payout extends Model
{
    use HasFactory;

    // Financial ledger - NO soft deletes

    protected $fillable = [
        'payout_number',
        'partner_id',
        'withdrawal_request_id',
        'amount',
        'currency',
        'payment_method',
        'transaction_reference',
        'payment_status',
        'payment_date',
        'processed_by',
        'recipient_snapshot',
        'receipt_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_status' => PayoutStatus::class,
            'payment_date' => 'date',
            'recipient_snapshot' => 'encrypted:array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Payout $payout) {
            // Completed payouts are strictly immutable
            if ($payout->getOriginal('payment_status') === PayoutStatus::Completed->value) {
                throw new \DomainException('Completed payouts are permanently sealed and cannot be modified.');
            }
        });

        static::deleting(function (Payout $payout) {
            throw new \DomainException('Financial payout records cannot be deleted.');
        });
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === PayoutStatus::Completed;
    }

    public function isFailedOrCancelled(): bool
    {
        return in_array($this->payment_status, [PayoutStatus::Failed, PayoutStatus::Cancelled], true);
    }

    /**
     * @return BelongsTo<Partner, $this>
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * @return BelongsTo<WithdrawalRequest, $this>
     */
    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(WithdrawalRequest::class, 'withdrawal_request_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }
}
