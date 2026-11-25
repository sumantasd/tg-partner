<?php

namespace App\Models;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $user_id
 * @property string $partner_code
 * @property string $company_name
 * @property string|null $phone
 * @property string|null $website
 * @property string|null $tax_id
 * @property string|null $address_line1
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $bank_name
 * @property string|null $bank_account_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_last4
 * @property string|null $bank_routing_code
 * @property string|null $payout_method
 * @property string|null $payout_details
 * @property \App\Enums\KycStatus $kyc_status
 * @property \App\Enums\BankVerificationStatus $bank_verification_status
 * @property \Carbon\Carbon|null $kyc_verified_at
 * @property float|null $default_commission_rate
 * @property \Carbon\Carbon|null $approved_at
 * @property int|null $approved_by
 * @property int|null $created_by
 * @property string|null $internal_notes
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property-read \App\Models\PartnerKyc|null $kyc
 * @property-read \App\Models\User|null $user
 * @property-read \App\Models\User|null $approver
 * @property-read \App\Models\User|null $creator
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Lead> $leads
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Project> $projects
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\WithdrawalRequest> $withdrawalRequests
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payout> $payouts
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PartnerAgreement> $agreements
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeadDispute> $disputes
 * @property-read \App\Models\PartnerAgreement|null $activeAgreement
 * @property-read \App\Models\PartnerAgreement|null $pendingAgreement
 * @property-read \App\Models\PartnerAgreement|null $latestAgreement
 */
class Partner extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'partner_code',
        'company_name',
        'phone',
        'website',
        'tax_id',
        'address_line1',
        'city',
        'state',
        'postal_code',
        'country',
        'bank_name',
        'bank_account_name',
        'bank_account_number',
        'bank_account_last4',
        'bank_routing_code',
        'payout_method',
        'payout_details',
        'kyc_status',
        'bank_verification_status',
        'kyc_verified_at',
        'default_commission_rate',
        'approved_at',
        'approved_by',
        'created_by',
        'internal_notes',
    ];

    protected function casts(): array
    {
        return [
            'kyc_status' => KycStatus::class,
            'bank_verification_status' => BankVerificationStatus::class,
            'kyc_verified_at' => 'datetime',
            'default_commission_rate' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Safely decrypt encrypted attribute or return legacy plain text.
     */
    protected function safeDecrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * Encrypt attribute with AES-256 at rest.
     */
    protected function safeEncrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }

    public function getBankAccountNumberAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setBankAccountNumberAttribute(?string $value): void
    {
        $this->attributes['bank_account_number'] = $this->safeEncrypt($value);
        if ($value) {
            $clean = preg_replace('/\D/', '', $value);
            $this->attributes['bank_account_last4'] = strlen($clean) >= 4 ? substr($clean, -4) : $clean;
        } else {
            $this->attributes['bank_account_last4'] = null;
        }
    }

    public function getBankRoutingCodeAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setBankRoutingCodeAttribute(?string $value): void
    {
        $clean = $value ? strtoupper(trim($value)) : null;
        $this->attributes['bank_routing_code'] = $this->safeEncrypt($clean);
    }

    public function getPayoutDetailsAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setPayoutDetailsAttribute(?string $value): void
    {
        $clean = $value ? trim($value) : null;
        $this->attributes['payout_details'] = $this->safeEncrypt($clean);
    }

    public function getTaxIdAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setTaxIdAttribute(?string $value): void
    {
        $clean = $value ? trim($value) : null;
        $this->attributes['tax_id'] = $this->safeEncrypt($clean);
    }

    /**
     * Masked account number for partner-facing UI display.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        return $this->bank_account_last4
            ? '•••• •••• •••• ' . $this->bank_account_last4
            : 'Not Configured';
    }

    public function getIfscCodeAttribute(): ?string
    {
        return $this->bank_routing_code;
    }

    public function setIfscCodeAttribute(?string $value): void
    {
        $this->bank_routing_code = $value;
    }

    public function getUpiIdAttribute(): ?string
    {
        return $this->payout_details;
    }

    public function setUpiIdAttribute(?string $value): void
    {
        $this->payout_details = $value;
    }

    /**
     * Check if the partner has completed minimum payout configuration.
     */
    public function hasValidPayoutDetails(?string $method = null): bool
    {
        $targetMethod = $method ?: $this->payout_method;

        if ($targetMethod === 'upi') {
            return ! empty($this->upi_id);
        }

        return ! empty($this->bank_account_name)
            && ! empty($this->bank_name)
            && ! empty($this->bank_account_number)
            && ! empty($this->bank_routing_code);
    }

    public function isKycApproved(): bool
    {
        if ($this->kyc !== null) {
            return $this->kyc->isKycApproved() && $this->kyc_status === KycStatus::Approved;
        }

        return $this->kyc_status === KycStatus::Approved;
    }

    public function isBankVerified(): bool
    {
        if ($this->kyc !== null) {
            return $this->kyc->isBankVerified() && $this->bank_verification_status === BankVerificationStatus::Verified;
        }

        return $this->bank_verification_status === BankVerificationStatus::Verified;
    }

    public function isFullyKycVerified(): bool
    {
        return $this->isKycApproved() && $this->isBankVerified();
    }

    /**
     * @return HasOne<PartnerKyc, $this>
     */
    public function kyc(): HasOne
    {
        return $this->hasOne(PartnerKyc::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class);
    }

    /**
     * @return HasMany<WithdrawalRequest, $this>
     */
    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * @return HasMany<Payout, $this>
     */
    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class);
    }

    /**
     * @return HasMany<SupportTicket, $this>
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * @return HasMany<PartnerAgreement, $this>
     */
    public function agreements(): HasMany
    {
        return $this->hasMany(PartnerAgreement::class);
    }

    /**
     * @return HasMany<LeadDispute, $this>
     */
    public function disputes(): HasMany
    {
        return $this->hasMany(LeadDispute::class)->orderByDesc('created_at');
    }

    /**
     * @return HasMany<Lead, $this>
     */
    public function originalLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'original_partner_id');
    }

    /**
     * @return HasOne<PartnerAgreement, $this>
     */
    public function activeAgreement(): HasOne
    {
        return $this->hasOne(PartnerAgreement::class)->where('status', \App\Enums\AgreementStatus::Accepted)->latestOfMany();
    }

    /**
     * @return HasOne<PartnerAgreement, $this>
     */
    public function pendingAgreement(): HasOne
    {
        return $this->hasOne(PartnerAgreement::class)->where('status', \App\Enums\AgreementStatus::PendingAcceptance)->latestOfMany();
    }

    /**
     * @return HasOne<PartnerAgreement, $this>
     */
    public function latestAgreement(): HasOne
    {
        return $this->hasOne(PartnerAgreement::class)->latestOfMany();
    }

    public function hasAcceptedAgreement(): bool
    {
        return $this->agreements()->where('status', \App\Enums\AgreementStatus::Accepted)->exists();
    }

    public function hasPendingAgreement(): bool
    {
        return $this->agreements()->where('status', \App\Enums\AgreementStatus::PendingAcceptance)->exists();
    }
}
