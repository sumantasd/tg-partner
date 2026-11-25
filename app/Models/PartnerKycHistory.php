<?php

namespace App\Models;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $partner_id
 * @property int $partner_kyc_id
 * @property int $version
 * @property string $action
 * @property \App\Enums\KycStatus $kyc_status
 * @property \App\Enums\BankVerificationStatus $bank_verification_status
 * @property array<string, mixed>|null $submission_snapshot
 * @property int|null $admin_id
 * @property string|null $reason
 * @property array<string>|null $correction_fields
 * @property string|null $internal_notes
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Carbon\Carbon|null $created_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\PartnerKyc|null $partnerKyc
 * @property-read \App\Models\User|null $admin
 */
class PartnerKycHistory extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'partner_kyc_histories';

    protected $fillable = [
        'partner_id',
        'partner_kyc_id',
        'version',
        'action',
        'kyc_status',
        'bank_verification_status',
        'submission_snapshot',
        'admin_id',
        'reason',
        'correction_fields',
        'internal_notes',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'kyc_status' => KycStatus::class,
            'bank_verification_status' => BankVerificationStatus::class,
            'submission_snapshot' => 'array',
            'correction_fields' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function getSnapshotDataAttribute(): ?array
    {
        return $this->submission_snapshot;
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    public function partnerKyc(): BelongsTo
    {
        return $this->belongsTo(PartnerKyc::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
