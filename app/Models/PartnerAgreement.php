<?php

namespace App\Models;

use App\Enums\AgreementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $agreement_number
 * @property int $partner_id
 * @property int $template_id
 * @property string $template_version
 * @property int|null $commission_policy_id
 * @property int|null $commission_policy_version
 * @property float|null $commission_rate_snapshot
 * @property string|null $commission_type_snapshot
 * @property string|null $pdf_path
 * @property string|null $content_snapshot
 * @property \Carbon\Carbon $issue_date
 * @property \Carbon\Carbon $effective_date
 * @property \App\Enums\AgreementStatus $status
 * @property \Carbon\Carbon|null $accepted_at
 * @property string|null $accepted_ip
 * @property string|null $accepted_user_agent
 * @property int|null $created_by
 * @property int|null $revoked_by
 * @property \Carbon\Carbon|null $revoked_at
 * @property string|null $revocation_reason
 * @property int $version
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\AgreementTemplate|null $template
 * @property-read \App\Models\CommissionPolicy|null $commissionPolicy
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $revoker
 */
class PartnerAgreement extends Model
{
    use HasFactory;

    protected $fillable = [
        'agreement_number',
        'partner_id',
        'template_id',
        'template_version',
        'commission_policy_id',
        'commission_policy_version',
        'commission_rate_snapshot',
        'commission_type_snapshot',
        'pdf_path',
        'content_snapshot',
        'issue_date',
        'effective_date',
        'status',
        'accepted_at',
        'accepted_ip',
        'accepted_user_agent',
        'created_by',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'status'                   => AgreementStatus::class,
            'commission_rate_snapshot' => 'decimal:2',
            'issue_date'               => 'date',
            'effective_date'           => 'date',
            'accepted_at'              => 'datetime',
            'revoked_at'               => 'datetime',
            'version'                  => 'integer',
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
     * @return BelongsTo<AgreementTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(AgreementTemplate::class, 'template_id');
    }

    /**
     * @return BelongsTo<CommissionPolicy, $this>
     */
    public function commissionPolicy(): BelongsTo
    {
        return $this->belongsTo(CommissionPolicy::class, 'commission_policy_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function isAccepted(): bool
    {
        return $this->status === AgreementStatus::Accepted;
    }

    public function isPendingAcceptance(): bool
    {
        return $this->status === AgreementStatus::PendingAcceptance;
    }

    public function isRevoked(): bool
    {
        return $this->status === AgreementStatus::Revoked;
    }

    public function isSuperseded(): bool
    {
        return $this->status === AgreementStatus::Superseded;
    }
}
