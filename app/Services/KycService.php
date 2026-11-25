<?php

namespace App\Services;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerKyc;
use App\Models\PartnerKycHistory;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class KycService
{
    /**
     * Submit initial KYC and Bank Verification application.
     * Enforces strict submission lock.
     *
     * @param Partner $partner
     * @param array $data
     * @param array<string, UploadedFile> $files
     * @param string|null $ip
     * @param string|null $userAgent
     * @return PartnerKyc
     */
    public function submitKyc(
        Partner $partner,
        array $data,
        array $files = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): PartnerKyc {
        $existing = $partner->kyc;

        if ($existing && $existing->isLocked()) {
            throw new DomainException(
                'Your KYC application has already been submitted and is locked for review. You cannot edit or submit another application while under review.'
            );
        }

        return DB::transaction(function () use ($partner, $existing, $data, $files, $ip, $userAgent) {
            // Store uploaded documents privately
            $panPath = isset($files['pan_document'])
                ? $this->storePrivateDocument($partner->id, $files['pan_document'], 'pan')
                : $existing?->pan_document_path;

            $identityPath = isset($files['identity_document'])
                ? $this->storePrivateDocument($partner->id, $files['identity_document'], 'identity')
                : $existing?->identity_document_path;

            $identityBackPath = isset($files['identity_document_back'])
                ? $this->storePrivateDocument($partner->id, $files['identity_document_back'], 'identity_back')
                : $existing?->identity_document_back_path;

            $addressPath = isset($files['address_document'])
                ? $this->storePrivateDocument($partner->id, $files['address_document'], 'address')
                : $existing?->address_document_path;

            $bankProofPath = isset($files['bank_proof_document'])
                ? $this->storePrivateDocument($partner->id, $files['bank_proof_document'], 'bank_proof')
                : $existing?->bank_proof_document_path;

            $kycData = [
                'partner_id'                   => $partner->id,
                'kyc_status'                   => KycStatus::Pending,
                'bank_verification_status'     => BankVerificationStatus::Pending,
                'legal_name'                   => trim($data['legal_name']),
                'authorized_signatory_name'    => ! empty($data['authorized_signatory_name']) ? trim($data['authorized_signatory_name']) : null,
                'business_registration_number' => ! empty($data['business_registration_number']) ? trim($data['business_registration_number']) : null,
                'gstin'                        => ! empty($data['gstin']) ? strtoupper(trim($data['gstin'])) : null,
                'partner_type'                 => $data['partner_type'] ?? 'individual',
                'dob_or_incorporation_date'    => $data['dob_or_incorporation_date'] ?? null,
                'pan_number'                   => strtoupper(trim($data['pan_number'])),
                'registered_address'           => trim($data['registered_address']),
                'city'                         => trim($data['city']),
                'state'                        => trim($data['state']),
                'postal_code'                  => trim($data['postal_code']),
                'country'                      => $data['country'] ?? 'India',
                'pan_document_path'            => $panPath,
                'identity_document_type'       => $data['identity_document_type'] ?? 'aadhaar',
                'identity_document_path'       => $identityPath,
                'identity_document_back_path'  => $identityBackPath,
                'id_contains_current_address'  => isset($data['id_contains_current_address']) ? (bool) $data['id_contains_current_address'] : true,
                'address_document_path'        => $addressPath,
                'bank_proof_document_path'     => $bankProofPath,
                'bank_account_holder_name'     => trim($data['bank_account_holder_name']),
                'bank_name'                    => trim($data['bank_name']),
                'bank_account_number'          => trim($data['bank_account_number']),
                'bank_ifsc_code'               => strtoupper(trim($data['bank_ifsc_code'])),
                'upi_id'                       => ! empty($data['upi_id']) ? trim($data['upi_id']) : null,
                'preferred_payout_method'      => $data['preferred_payout_method'] ?? 'bank_transfer',
                'declaration_accepted'         => ! empty($data['declaration_accepted']),
                'declaration_accepted_at'      => ! empty($data['declaration_accepted']) ? now() : null,
                'submitted_at'                 => now(),
                'rejection_reason'             => null,
                'correction_reason'            => null,
                'correction_fields'            => null,
            ];

            if ($existing) {
                $kycData['current_version'] = $existing->current_version + 1;
                $existing->update($kycData);
                $kyc = $existing->fresh();
            } else {
                $kycData['current_version'] = 1;
                $kyc = PartnerKyc::create($kycData);
            }

            // Update partner cached statuses and bank details
            $partner->update([
                'kyc_status'               => KycStatus::Pending,
                'bank_verification_status' => BankVerificationStatus::Pending,
                'bank_name'                => $kyc->bank_name,
                'bank_account_name'        => $kyc->bank_account_holder_name,
                'bank_account_number'      => $kyc->bank_account_number,
                'bank_routing_code'        => $kyc->bank_ifsc_code,
                'payout_method'            => $kyc->preferred_payout_method,
                'payout_details'           => $kyc->upi_id,
            ]);

            // Create immutable audit history record
            $this->recordHistory(
                kyc: $kyc,
                action: $existing ? 'resubmitted' : 'submitted',
                adminId: null,
                ip: $ip,
                userAgent: $userAgent
            );

            // Notify partner
            Notification::send(
                userId: $partner->user_id,
                title: 'KYC & Bank Verification Submitted',
                message: 'Your KYC and bank verification documents have been submitted and are pending Admin verification.',
                type: 'kyc_submitted',
                link: route('partner.kyc.show')
            );

            // Notify all admins
            $adminUsers = User::where('role', UserRole::Admin)->get();
            foreach ($adminUsers as $admin) {
                Notification::send(
                    userId: $admin->id,
                    title: 'New Partner KYC Submission',
                    message: "Partner {$partner->company_name} ({$partner->partner_code}) submitted KYC & Bank details for verification.",
                    type: 'admin_kyc_submitted',
                    link: route('admin.kyc.show', $kyc)
                );
            }

            return $kyc;
        });
    }

    /**
     * Resubmit KYC in response to an authorized correction request.
     * Enforces that only authorized fields can be modified.
     *
     * @param Partner $partner
     * @param array $data
     * @param array<string, UploadedFile> $files
     * @param string|null $ip
     * @param string|null $userAgent
     * @return PartnerKyc
     */
    public function resubmitCorrection(
        Partner $partner,
        array $data,
        array $files = [],
        ?string $ip = null,
        ?string $userAgent = null
    ): PartnerKyc {
        $kyc = $partner->kyc;

        if (! $kyc || $kyc->kyc_status !== KycStatus::CorrectionRequested) {
            throw new DomainException('Correction is not authorized for this KYC application.');
        }

        return DB::transaction(function () use ($partner, $kyc, $data, $files, $ip, $userAgent) {
            $allowedFields = $kyc->correction_fields ?? [];
            $updateData = [];

            // Personal Fields
            if (empty($allowedFields) || in_array('legal_name', $allowedFields, true)) {
                if (isset($data['legal_name'])) $updateData['legal_name'] = trim($data['legal_name']);
            }
            if (empty($allowedFields) || in_array('authorized_signatory_name', $allowedFields, true)) {
                if (isset($data['authorized_signatory_name'])) $updateData['authorized_signatory_name'] = trim($data['authorized_signatory_name']);
            }
            if (empty($allowedFields) || in_array('business_registration_number', $allowedFields, true)) {
                if (isset($data['business_registration_number'])) $updateData['business_registration_number'] = trim($data['business_registration_number']);
            }
            if (empty($allowedFields) || in_array('gstin', $allowedFields, true)) {
                if (isset($data['gstin'])) $updateData['gstin'] = strtoupper(trim($data['gstin']));
            }
            if (empty($allowedFields) || in_array('dob_or_incorporation_date', $allowedFields, true)) {
                if (isset($data['dob_or_incorporation_date'])) $updateData['dob_or_incorporation_date'] = $data['dob_or_incorporation_date'];
            }
            if (empty($allowedFields) || in_array('pan_number', $allowedFields, true)) {
                if (isset($data['pan_number'])) $updateData['pan_number'] = strtoupper(trim($data['pan_number']));
            }
            if (empty($allowedFields) || in_array('registered_address', $allowedFields, true)) {
                if (isset($data['registered_address'])) $updateData['registered_address'] = trim($data['registered_address']);
            }
            if (empty($allowedFields) || in_array('city', $allowedFields, true) || in_array('registered_address', $allowedFields, true)) {
                if (isset($data['city'])) $updateData['city'] = trim($data['city']);
            }
            if (empty($allowedFields) || in_array('state', $allowedFields, true) || in_array('registered_address', $allowedFields, true)) {
                if (isset($data['state'])) $updateData['state'] = trim($data['state']);
            }
            if (empty($allowedFields) || in_array('postal_code', $allowedFields, true) || in_array('registered_address', $allowedFields, true)) {
                if (isset($data['postal_code'])) $updateData['postal_code'] = trim($data['postal_code']);
            }
            if (isset($data['id_contains_current_address'])) {
                $updateData['id_contains_current_address'] = (bool) $data['id_contains_current_address'];
            }
            if (isset($data['declaration_accepted'])) {
                $updateData['declaration_accepted'] = (bool) $data['declaration_accepted'];
                $updateData['declaration_accepted_at'] = now();
            }

            // Bank Fields
            if (empty($allowedFields) || in_array('bank_account_holder_name', $allowedFields, true)) {
                if (isset($data['bank_account_holder_name'])) $updateData['bank_account_holder_name'] = trim($data['bank_account_holder_name']);
            }
            if (empty($allowedFields) || in_array('bank_name', $allowedFields, true)) {
                if (isset($data['bank_name'])) $updateData['bank_name'] = trim($data['bank_name']);
            }
            if (empty($allowedFields) || in_array('bank_account_number', $allowedFields, true)) {
                if (isset($data['bank_account_number'])) $updateData['bank_account_number'] = trim($data['bank_account_number']);
            }
            if (empty($allowedFields) || in_array('bank_ifsc_code', $allowedFields, true)) {
                if (isset($data['bank_ifsc_code'])) $updateData['bank_ifsc_code'] = strtoupper(trim($data['bank_ifsc_code']));
            }
            if (empty($allowedFields) || in_array('upi_id', $allowedFields, true)) {
                if (isset($data['upi_id'])) $updateData['upi_id'] = trim($data['upi_id']);
            }
            if (empty($allowedFields) || in_array('preferred_payout_method', $allowedFields, true)) {
                if (isset($data['preferred_payout_method'])) $updateData['preferred_payout_method'] = $data['preferred_payout_method'];
            }

            // Documents
            if (isset($files['pan_document']) && (empty($allowedFields) || in_array('pan_document', $allowedFields, true))) {
                $updateData['pan_document_path'] = $this->storePrivateDocument($partner->id, $files['pan_document'], 'pan');
            }
            if (isset($files['identity_document']) && (empty($allowedFields) || in_array('identity_document', $allowedFields, true))) {
                $updateData['identity_document_path'] = $this->storePrivateDocument($partner->id, $files['identity_document'], 'identity');
            }
            if (isset($files['identity_document_back']) && (empty($allowedFields) || in_array('identity_document_back', $allowedFields, true) || in_array('identity_document', $allowedFields, true))) {
                $updateData['identity_document_back_path'] = $this->storePrivateDocument($partner->id, $files['identity_document_back'], 'identity_back');
            }
            if (isset($files['address_document']) && (empty($allowedFields) || in_array('address_document', $allowedFields, true))) {
                $updateData['address_document_path'] = $this->storePrivateDocument($partner->id, $files['address_document'], 'address');
            }
            if (isset($files['bank_proof_document']) && (empty($allowedFields) || in_array('bank_proof_document', $allowedFields, true))) {
                $updateData['bank_proof_document_path'] = $this->storePrivateDocument($partner->id, $files['bank_proof_document'], 'bank_proof');
            }

            // Create immutable audit history record preserving the prior submission snapshot
            $this->recordHistory(
                kyc: $kyc,
                action: 'resubmitted_correction',
                adminId: null,
                ip: $ip,
                userAgent: $userAgent
            );

            // Reset statuses to Pending and lock again
            $updateData['kyc_status'] = KycStatus::Pending;
            $updateData['bank_verification_status'] = BankVerificationStatus::Pending;
            $updateData['current_version'] = $kyc->current_version + 1;
            $updateData['submitted_at'] = now();
            $updateData['correction_reason'] = null;
            $updateData['correction_fields'] = null;

            $kyc->update($updateData);

            // Update partner cached statuses
            $partner->update([
                'kyc_status'               => KycStatus::Pending,
                'bank_verification_status' => BankVerificationStatus::Pending,
                'bank_name'                => $kyc->bank_name,
                'bank_account_name'        => $kyc->bank_account_holder_name,
                'bank_account_number'      => $kyc->bank_account_number,
                'bank_routing_code'        => $kyc->bank_ifsc_code,
                'payout_method'            => $kyc->preferred_payout_method,
                'payout_details'           => $kyc->upi_id,
            ]);

            // Notify partner
            Notification::send(
                userId: $partner->user_id,
                title: 'Correction Submitted & Locked',
                message: 'Your corrected KYC application has been locked and resubmitted to Admin for verification.',
                type: 'kyc_resubmitted',
                link: route('partner.kyc.show')
            );

            // Notify admins
            $adminUsers = User::where('role', UserRole::Admin)->get();
            foreach ($adminUsers as $admin) {
                Notification::send(
                    userId: $admin->id,
                    title: 'Partner Resubmitted KYC Correction',
                    message: "Partner {$partner->company_name} ({$partner->partner_code}) resubmitted KYC corrections.",
                    type: 'admin_kyc_correction_resubmitted',
                    link: route('admin.kyc.show', $kyc)
                );
            }

            return $kyc;
        });
    }

    /**
     * Admin marks KYC application as Under Review.
     */
    public function startReview(PartnerKyc $kyc, User $admin): PartnerKyc
    {
        if ($kyc->kyc_status === KycStatus::Approved) {
            throw new DomainException('Cannot change review status of already approved KYC.');
        }

        $kyc->update([
            'kyc_status'  => KycStatus::UnderReview,
            'reviewed_at' => now(),
            'reviewed_by' => $admin->id,
        ]);

        $kyc->partner->update(['kyc_status' => KycStatus::UnderReview]);

        $this->recordHistory(
            kyc: $kyc,
            action: 'review_started',
            adminId: $admin->id
        );

        Notification::send(
            userId: $kyc->partner->user_id,
            title: 'KYC Under Review',
            message: 'Your KYC and Bank details are actively being reviewed by our compliance team.',
            type: 'kyc_under_review',
            link: route('partner.kyc.show')
        );

        return $kyc;
    }

    /**
     * Admin approves KYC documents & personal identity.
     */
    public function approveKyc(PartnerKyc $kyc, User $admin, ?string $internalNotes = null): PartnerKyc
    {
        $kyc->update([
            'kyc_status'           => KycStatus::Approved,
            'kyc_approved_at'      => now(),
            'kyc_approved_by'      => $admin->id,
            'internal_admin_notes' => $internalNotes ?: $kyc->internal_admin_notes,
        ]);

        $partner = $kyc->partner;
        $partner->update(['kyc_status' => KycStatus::Approved]);

        if ($kyc->isBankVerified()) {
            $partner->update(['kyc_verified_at' => now()]);
        }

        $this->recordHistory(
            kyc: $kyc,
            action: 'kyc_approved',
            adminId: $admin->id,
            internalNotes: $internalNotes
        );

        Notification::send(
            userId: $partner->user_id,
            title: 'Identity & PAN KYC Approved',
            message: 'Your KYC documents and PAN information have been verified successfully.',
            type: 'kyc_approved',
            link: route('partner.kyc.show')
        );

        return $kyc;
    }

    /**
     * Admin verifies bank account details against bank proof and legal name.
     */
    public function verifyBank(PartnerKyc $kyc, User $admin, ?string $internalNotes = null): PartnerKyc
    {
        $kyc->update([
            'bank_verification_status' => BankVerificationStatus::Verified,
            'bank_verified_at'         => now(),
            'bank_verified_by'         => $admin->id,
            'internal_admin_notes'     => $internalNotes ?: $kyc->internal_admin_notes,
        ]);

        $partner = $kyc->partner;
        $partner->update([
            'bank_verification_status' => BankVerificationStatus::Verified,
            'bank_name'                => $kyc->bank_name,
            'bank_account_name'        => $kyc->bank_account_holder_name,
            'bank_account_number'      => $kyc->bank_account_number,
            'bank_routing_code'        => $kyc->bank_ifsc_code,
            'payout_method'            => $kyc->preferred_payout_method,
            'payout_details'           => $kyc->upi_id,
        ]);

        if ($kyc->isKycApproved()) {
            $partner->update(['kyc_verified_at' => now()]);
        }

        $this->recordHistory(
            kyc: $kyc,
            action: 'bank_verified',
            adminId: $admin->id,
            internalNotes: $internalNotes
        );

        Notification::send(
            userId: $partner->user_id,
            title: 'Bank Account Verified',
            message: 'Your receiving bank account and disbursement details have been verified.',
            type: 'bank_verified',
            link: route('partner.kyc.show')
        );

        return $kyc;
    }

    /**
     * Admin rejects KYC with mandatory reason.
     */
    public function rejectKyc(PartnerKyc $kyc, User $admin, string $reason, ?string $internalNotes = null): PartnerKyc
    {
        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('A mandatory rejection reason is required.');
        }

        $kyc->update([
            'kyc_status'           => KycStatus::Rejected,
            'rejection_reason'     => $reason,
            'reviewed_at'          => now(),
            'reviewed_by'          => $admin->id,
            'internal_admin_notes' => $internalNotes ?: $kyc->internal_admin_notes,
        ]);

        $kyc->partner->update(['kyc_status' => KycStatus::Rejected]);

        $this->recordHistory(
            kyc: $kyc,
            action: 'kyc_rejected',
            adminId: $admin->id,
            reason: $reason,
            internalNotes: $internalNotes
        );

        Notification::send(
            userId: $kyc->partner->user_id,
            title: 'KYC Application Rejected',
            message: "Your KYC application was rejected. Reason: {$reason}",
            type: 'kyc_rejected',
            link: route('partner.kyc.show')
        );

        return $kyc;
    }

    /**
     * Admin rejects Bank Verification with mandatory reason.
     */
    public function rejectBank(PartnerKyc $kyc, User $admin, string $reason, ?string $internalNotes = null): PartnerKyc
    {
        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('A mandatory rejection reason is required.');
        }

        $kyc->update([
            'bank_verification_status' => BankVerificationStatus::Rejected,
            'rejection_reason'         => $reason,
            'reviewed_at'              => now(),
            'reviewed_by'              => $admin->id,
            'internal_admin_notes'     => $internalNotes ?: $kyc->internal_admin_notes,
        ]);

        $kyc->partner->update(['bank_verification_status' => BankVerificationStatus::Rejected]);

        $this->recordHistory(
            kyc: $kyc,
            action: 'bank_rejected',
            adminId: $admin->id,
            reason: $reason,
            internalNotes: $internalNotes
        );

        Notification::send(
            userId: $kyc->partner->user_id,
            title: 'Bank Account Verification Rejected',
            message: "Your bank account verification was rejected. Reason: {$reason}",
            type: 'bank_rejected',
            link: route('partner.kyc.show')
        );

        return $kyc;
    }

    /**
     * Admin requests correction with mandatory instructions and allowed fields.
     */
    public function requestCorrection(
        PartnerKyc $kyc,
        User $admin,
        string $reason,
        ?array $allowedFields = null,
        ?string $internalNotes = null
    ): PartnerKyc {
        $reason = trim($reason);
        if (empty($reason)) {
            throw new InvalidArgumentException('A mandatory correction instruction reason is required.');
        }

        $kyc->update([
            'kyc_status'               => KycStatus::CorrectionRequested,
            'bank_verification_status' => BankVerificationStatus::CorrectionRequested,
            'correction_reason'        => $reason,
            'correction_fields'        => $allowedFields,
            'reviewed_at'              => now(),
            'reviewed_by'              => $admin->id,
            'internal_admin_notes'     => $internalNotes ?: $kyc->internal_admin_notes,
        ]);

        $kyc->partner->update([
            'kyc_status'               => KycStatus::CorrectionRequested,
            'bank_verification_status' => BankVerificationStatus::CorrectionRequested,
        ]);

        $this->recordHistory(
            kyc: $kyc,
            action: 'correction_requested',
            adminId: $admin->id,
            reason: $reason,
            correctionFields: $allowedFields,
            internalNotes: $internalNotes
        );

        Notification::send(
            userId: $kyc->partner->user_id,
            title: 'KYC Correction Requested',
            message: "Action required: Please update your KYC details as requested. Reason: {$reason}",
            type: 'kyc_correction_requested',
            link: route('partner.kyc.correction')
        );

        return $kyc;
    }

    /**
     * Record an immutable audit log entry.
     */
    protected function recordHistory(
        PartnerKyc $kyc,
        string $action,
        ?int $adminId = null,
        ?string $reason = null,
        ?array $correctionFields = null,
        ?string $internalNotes = null,
        ?string $ip = null,
        ?string $userAgent = null
    ): PartnerKycHistory {
        $snapshot = [
            'legal_name'                   => $kyc->legal_name,
            'authorized_signatory_name'    => $kyc->authorized_signatory_name,
            'business_registration_number' => $kyc->business_registration_number,
            'gstin'                        => $kyc->gstin,
            'partner_type'                 => $kyc->partner_type,
            'dob_or_incorporation_date'    => $kyc->dob_or_incorporation_date?->toDateString(),
            'pan_number_masked'            => $kyc->masked_pan,
            'registered_address'           => $kyc->registered_address,
            'city'                         => $kyc->city,
            'state'                        => $kyc->state,
            'postal_code'                  => $kyc->postal_code,
            'country'                      => $kyc->country,
            'pan_document_path'            => $kyc->pan_document_path,
            'identity_document_type'       => $kyc->identity_document_type,
            'identity_document_path'       => $kyc->identity_document_path,
            'identity_document_back_path'  => $kyc->identity_document_back_path,
            'id_contains_current_address'  => $kyc->id_contains_current_address,
            'address_document_path'        => $kyc->address_document_path,
            'bank_proof_document_path'     => $kyc->bank_proof_document_path,
            'bank_account_holder_name'     => $kyc->bank_account_holder_name,
            'bank_name'                    => $kyc->bank_name,
            'bank_account_last4'           => $kyc->bank_account_last4,
            'preferred_payout_method'      => $kyc->preferred_payout_method,
            'declaration_accepted'         => $kyc->declaration_accepted,
            'declaration_accepted_at'      => $kyc->declaration_accepted_at?->toIso8601String(),
            'submitted_at'                 => $kyc->submitted_at?->toIso8601String(),
        ];

        $history = PartnerKycHistory::create([
            'partner_id'               => $kyc->partner_id,
            'partner_kyc_id'           => $kyc->id,
            'version'                  => $kyc->current_version,
            'action'                   => $action,
            'kyc_status'               => $kyc->kyc_status,
            'bank_verification_status' => $kyc->bank_verification_status,
            'submission_snapshot'      => $snapshot,
            'admin_id'                 => $adminId,
            'reason'                   => $reason,
            'correction_fields'        => $correctionFields,
            'internal_notes'           => $internalNotes,
            'ip_address'               => $ip,
            'user_agent'               => $userAgent,
            'created_at'               => now(),
        ]);

        $auditAction = match ($action) {
            'review_started' => \App\Enums\AuditLogAction::KycReviewStarted,
            'kyc_approved' => \App\Enums\AuditLogAction::KycApproved,
            'bank_verified' => \App\Enums\AuditLogAction::BankVerified,
            'kyc_rejected' => \App\Enums\AuditLogAction::KycRejected,
            'bank_rejected' => \App\Enums\AuditLogAction::BankRejected,
            'correction_requested' => \App\Enums\AuditLogAction::KycCorrectionRequested,
            default => \App\Enums\AuditLogAction::KycSubmitted,
        };

        $actorUser = $adminId ? User::find($adminId) : $kyc->partner?->user;

        app(\App\Services\AuditLogService::class)->record(
            action: $auditAction,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner {$kyc->partner?->company_name} KYC: {$auditAction->label()}." . ($reason ? " Reason: {$reason}" : ''),
            subject: $kyc,
            metadata: [
                'action' => $action,
                'kyc_status' => $kyc->kyc_status->value,
                'bank_verification_status' => $kyc->bank_verification_status->value,
                'reason' => $reason,
            ],
            actor: $actorUser,
            ipAddress: $ip,
            userAgent: $userAgent
        );

        return $history;
    }

    /**
     * Store file safely in private storage outside public root.
     */
    protected function storePrivateDocument(int $partnerId, UploadedFile $file, string $prefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $safeExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

        if (! in_array($extension, $safeExtensions, true)) {
            throw new InvalidArgumentException("File extension '{$extension}' is not permitted. Only PDF, JPG, and PNG are allowed.");
        }

        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png'];
        $mime = $file->getMimeType();
        if ($mime && ! in_array($mime, $allowedMimes, true)) {
            throw new InvalidArgumentException("File MIME type '{$mime}' is not permitted. Only PDF, JPG, and PNG files are allowed.");
        }

        if ($file->getSize() > 5242880) { // 5MB
            throw new InvalidArgumentException("File size exceeds 5MB limit.");
        }

        $folder = "kyc_documents/{$partnerId}";
        $filename = "{$prefix}_" . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . ".{$extension}";

        $storedPath = $file->storeAs($folder, $filename, 'local');

        Log::info('KYC document stored securely on private disk', [
            'partner_id' => $partnerId,
            'doc_prefix' => $prefix,
            'mime'       => $mime,
            'size_bytes' => $file->getSize(),
        ]);

        return $storedPath;
    }
}
