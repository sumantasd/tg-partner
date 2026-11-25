<?php

namespace App\Models;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

/**
 * @property int $id
 * @property int $partner_id
 * @property \App\Enums\KycStatus $kyc_status
 * @property \App\Enums\BankVerificationStatus $bank_verification_status
 * @property string $legal_name
 * @property string|null $authorized_signatory_name
 * @property string|null $business_registration_number
 * @property string|null $gstin
 * @property string $partner_type
 * @property \Carbon\Carbon|null $dob_or_incorporation_date
 * @property string|null $pan_number
 * @property string|null $pan_number_masked
 * @property string|null $registered_address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $postal_code
 * @property string|null $country
 * @property string|null $pan_document_path
 * @property string|null $identity_document_type
 * @property string|null $identity_document_path
 * @property string|null $identity_document_back_path
 * @property bool $id_contains_current_address
 * @property string|null $address_document_path
 * @property string|null $bank_proof_document_path
 * @property string|null $bank_account_holder_name
 * @property string|null $bank_name
 * @property string|null $bank_account_number
 * @property string|null $bank_account_last4
 * @property string|null $bank_ifsc_code
 * @property string|null $upi_id
 * @property string|null $preferred_payout_method
 * @property bool $declaration_accepted
 * @property \Carbon\Carbon|null $declaration_accepted_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $reviewed_at
 * @property int|null $reviewed_by
 * @property \Carbon\Carbon|null $kyc_approved_at
 * @property int|null $kyc_approved_by
 * @property \Carbon\Carbon|null $bank_verified_at
 * @property int|null $bank_verified_by
 * @property string|null $rejection_reason
 * @property string|null $correction_reason
 * @property array<string>|null $correction_fields
 * @property string|null $allowed_correction_fields
 * @property string|null $correction_instructions
 * @property string|null $pan_number_encrypted
 * @property string|null $bank_account_number_encrypted
 * @property string|null $internal_admin_notes
 * @property int $current_version
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read \App\Models\Partner|null $partner
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\User|null $kycApprover
 * @property-read \App\Models\User|null $bankVerifier
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PartnerKycHistory> $histories
 */
class PartnerKyc extends Model
{
    use HasFactory;

    protected $table = 'partner_kycs';

    protected $fillable = [
        'partner_id',
        'kyc_status',
        'bank_verification_status',
        'legal_name',
        'authorized_signatory_name',
        'business_registration_number',
        'gstin',
        'partner_type',
        'dob_or_incorporation_date',
        'pan_number',
        'pan_number_masked',
        'registered_address',
        'city',
        'state',
        'postal_code',
        'country',
        'pan_document_path',
        'identity_document_type',
        'identity_document_path',
        'identity_document_back_path',
        'id_contains_current_address',
        'address_document_path',
        'bank_proof_document_path',
        'bank_account_holder_name',
        'bank_name',
        'bank_account_number',
        'bank_account_last4',
        'bank_ifsc_code',
        'upi_id',
        'preferred_payout_method',
        'declaration_accepted',
        'declaration_accepted_at',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'kyc_approved_at',
        'kyc_approved_by',
        'bank_verified_at',
        'bank_verified_by',
        'rejection_reason',
        'correction_reason',
        'correction_fields',
        'allowed_correction_fields',
        'correction_instructions',
        'pan_number_encrypted',
        'bank_account_number_encrypted',
        'internal_admin_notes',
        'current_version',
    ];

    protected function casts(): array
    {
        return [
            'kyc_status' => KycStatus::class,
            'bank_verification_status' => BankVerificationStatus::class,
            'dob_or_incorporation_date' => 'date',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'kyc_approved_at' => 'datetime',
            'bank_verified_at' => 'datetime',
            'correction_fields' => 'array',
            'current_version' => 'integer',
            'id_contains_current_address' => 'boolean',
            'declaration_accepted' => 'boolean',
            'declaration_accepted_at' => 'datetime',
        ];
    }

    public function isIndividual(): bool
    {
        return in_array($this->partner_type, ['individual', 'individual_sole_proprietor'], true);
    }

    public function isBusiness(): bool
    {
        return ! $this->isIndividual();
    }

    public function requiresBackSide(): bool
    {
        return $this->identity_document_type === 'aadhaar';
    }

    public function formattedPartnerType(): string
    {
        return match ($this->partner_type) {
            'individual', 'individual_sole_proprietor' => 'Individual / Sole Proprietor',
            'partnership_firm'                         => 'Partnership Firm',
            'llp'                                      => 'Limited Liability Partnership (LLP)',
            'private_limited'                          => 'Private Limited Company (Pvt Ltd)',
            'public_limited'                           => 'Public Limited Company (Ltd)',
            'other'                                    => 'Other Registered Business Entity',
            default                                    => ucfirst(str_replace('_', ' ', (string) $this->partner_type)),
        };
    }

    public function formattedIdentityType(): string
    {
        return match ($this->identity_document_type) {
            'aadhaar'         => 'Aadhaar Card',
            'passport'        => 'Passport',
            'voter_id'        => 'Voter ID',
            'driving_license' => 'Driving Licence',
            default           => ucfirst(str_replace('_', ' ', (string) $this->identity_document_type)),
        };
    }

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

    protected function safeEncrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }

    public function getPanNumberAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setPanNumberAttribute(?string $value): void
    {
        $clean = $value ? strtoupper(trim($value)) : null;
        $this->attributes['pan_number'] = $this->safeEncrypt($clean);
        if ($clean && strlen($clean) === 10) {
            $this->attributes['pan_number_masked'] = substr($clean, 0, 5) . '••••' . substr($clean, -1);
        } elseif ($clean) {
            $this->attributes['pan_number_masked'] = '••••••••';
        } else {
            $this->attributes['pan_number_masked'] = null;
        }
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

    public function getBankIfscCodeAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setBankIfscCodeAttribute(?string $value): void
    {
        $clean = $value ? strtoupper(trim($value)) : null;
        $this->attributes['bank_ifsc_code'] = $this->safeEncrypt($clean);
    }

    public function getUpiIdAttribute(?string $value): ?string
    {
        return $this->safeDecrypt($value);
    }

    public function setUpiIdAttribute(?string $value): void
    {
        $clean = $value ? trim($value) : null;
        $this->attributes['upi_id'] = $this->safeEncrypt($clean);
    }

    public function getMaskedPanAttribute(): string
    {
        return $this->pan_number_masked ?: 'Not Provided';
    }

    public function getMaskedAccountAttribute(): string
    {
        return $this->bank_account_last4
            ? '•••• •••• •••• ' . $this->bank_account_last4
            : 'Not Configured';
    }

    public function isEditable(): bool
    {
        return $this->kyc_status->isEditable();
    }

    public function isLocked(): bool
    {
        return ! $this->isEditable();
    }

    public function isCorrectionRequested(): bool
    {
        return $this->kyc_status === KycStatus::CorrectionRequested
            || $this->bank_verification_status === BankVerificationStatus::CorrectionRequested;
    }

    public function getCorrectionInstructionsAttribute(): ?string
    {
        return $this->correction_reason;
    }

    public function setCorrectionInstructionsAttribute(?string $value): void
    {
        $this->attributes['correction_reason'] = $value;
    }

    public function getAllowedCorrectionFieldsAttribute(): ?array
    {
        return $this->correction_fields;
    }

    public function setAllowedCorrectionFieldsAttribute(?array $value): void
    {
        $this->correction_fields = $value;
    }

    public function setPanNumberEncryptedAttribute(?string $value): void
    {
        $this->pan_number = $value;
    }

    public function setBankAccountNumberEncryptedAttribute(?string $value): void
    {
        $this->bank_account_number = $value;
    }

    public function isFieldEditable(string $field): bool
    {
        if ($this->kyc_status === KycStatus::NotSubmitted) {
            return true;
        }

        if ($this->kyc_status === KycStatus::CorrectionRequested) {
            $allowed = $this->correction_fields;
            if (empty($allowed)) {
                return true; // All fields unlocked if none specifically restricted
            }
            return in_array($field, $allowed, true);
        }

        return false;
    }

    public function isKycApproved(): bool
    {
        return $this->kyc_status === KycStatus::Approved;
    }

    public function isBankVerified(): bool
    {
        return $this->bank_verification_status === BankVerificationStatus::Verified;
    }

    public function isFullyVerified(): bool
    {
        return $this->isKycApproved() && $this->isBankVerified();
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
     * @return BelongsTo<User, $this>
     */
    public function kycApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'kyc_approved_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function bankVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'bank_verified_by');
    }

    /**
     * @return HasMany<PartnerKycHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(PartnerKycHistory::class, 'partner_kyc_id')->orderBy('id', 'desc');
    }
}
