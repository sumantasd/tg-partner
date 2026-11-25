<?php

namespace App\Models;

use App\Enums\WithdrawalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $request_number
 * @property int $partner_id
 * @property float $amount
 * @property string $currency
 * @property string $payout_method
 * @property string|null $partner_notes
 * @property array<string, mixed>|null $recipient_snapshot
 * @property \App\Enums\WithdrawalStatus $status
 * @property string|null $admin_notes
 * @property string|null $rejection_reason
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $reviewed_at
 * @property \Carbon\Carbon|null $settled_at
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\Payout|null $payout
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 */
class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_number',
        'partner_id',
        'amount',
        'currency',
        'payout_method',
        'partner_notes',
        'recipient_snapshot',
        'status',
        'admin_notes',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => WithdrawalStatus::class,
            'recipient_snapshot' => 'encrypted:array',
            'reviewed_at' => 'datetime',
            'settled_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Unidirectional 1:1 relationship via payouts.withdrawal_request_id
     *
     * @return HasOne<Payout, $this>
     */
    public function payout(): HasOne
    {
        return $this->hasOne(Payout::class, 'withdrawal_request_id');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    public function isPending(): bool
    {
        return $this->status === WithdrawalStatus::Pending;
    }

    public function isApproved(): bool
    {
        return $this->status === WithdrawalStatus::Approved;
    }

    public function isProcessing(): bool
    {
        return $this->status === WithdrawalStatus::Processing;
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }

    public function isRejected(): bool
    {
        return $this->status === WithdrawalStatus::Rejected;
    }

    public function isCancelled(): bool
    {
        return $this->status === WithdrawalStatus::Cancelled;
    }

    public function canBeCancelledByPartner(): bool
    {
        return $this->status === WithdrawalStatus::Pending;
    }

    public function getDestinationSummary(): string
    {
        $snap = $this->recipient_snapshot ?? [];
        if (($snap['payout_method'] ?? $this->payout_method) === 'upi') {
            return 'UPI: ' . ($snap['upi_id'] ?? 'N/A');
        }

        $bank = $snap['bank_name'] ?? 'Bank';
        $acc = $snap['masked_account'] ?? '••••';

        return "{$bank} ({$acc})";
    }
}
