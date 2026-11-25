<?php

namespace App\Services;

use App\Enums\AgreementStatus;
use App\Models\AgreementTemplate;
use App\Models\CommissionPolicy;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\SystemSetting;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PartnerAgreementService
{
    /**
     * Generate an official agreement for the partner.
     * Concurrency-safe, resolves active template and commission policy,
     * renders PDF, saves to private storage, and notifies partner.
     */
    public function generateAgreement(Partner $partner, ?int $createdBy = null, array $options = []): PartnerAgreement
    {
        $force = $options['force'] ?? false;

        // If not forcing new, return existing pending or accepted agreement
        if (!$force) {
            $existing = PartnerAgreement::where('partner_id', $partner->id)
                ->whereIn('status', [AgreementStatus::PendingAcceptance, AgreementStatus::Accepted])
                ->latest()
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return DB::transaction(function () use ($partner, $createdBy, $options) {
            // 1. Resolve Active Template (prefer active and effective)
            $template = AgreementTemplate::where('is_active', true)
                ->where('status', 'active')
                ->latest('id')
                ->first();

            if (!$template) {
                // Fallback to any active template or latest
                $template = AgreementTemplate::where('status', 'active')->latest('id')->first()
                    ?? AgreementTemplate::latest('id')->first();
            }

            // 2. Resolve Applicable Commission Policy
            $today = now()->toDateString();
            $policy = CommissionPolicy::where('status', 'active')
                ->where('effective_from', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('effective_until')
                      ->orWhere('effective_until', '>=', $today);
                })
                ->orderByDesc('is_default')
                ->first();

            if (!$policy) {
                $policy = CommissionPolicy::where('status', 'active')->first();
            }

            $commissionRate = $policy ? (float) $policy->commission_value : (float) ($partner->default_commission_rate ?? 10.00);
            $commissionType = $policy ? ($policy->commission_type instanceof \App\Enums\CommissionType ? $policy->commission_type->value : (string) $policy->commission_type) : 'percentage';
            $policyVersion = $policy ? (string) $policy->version : '1.0';

            // 3. Concurrency-Safe Agreement Number (TGPA-YYYY-XXXXXX)
            $agreementNumber = $this->generateNextAgreementNumber();

            // 4. Populate Placeholders in Template Content
            $rawContent = $template ? $template->content : $this->getDefaultDraftContent();
            $effectiveDate = now()->format('d M Y');
            $issueDate = now()->format('d M Y');

            // Format clean partner address without leading commas or blank segments
            $addressParts = array_filter([
                $partner->address_line1,
                $partner->city,
                $partner->state ? ($partner->state . ($partner->postal_code ? ' - ' . $partner->postal_code : '')) : $partner->postal_code,
            ], fn($part) => !empty(trim((string)$part)));

            $partnerAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'Address Recorded in Partner Profile';

            $partnerFullName = $partner->user ? $partner->user->name : 'Authorized Partner';

            $populatedContent = $this->replacePlaceholders($rawContent, [
                'Agreement_Number'          => $agreementNumber,
                'agreement_number'          => $agreementNumber,
                'Agreement_Date'            => $effectiveDate,
                'agreement_date'            => $effectiveDate,
                'effective_date'            => $effectiveDate,
                'issue_date'                => $issueDate,
                'Partner_Full_Name'         => $partnerFullName,
                'partner_full_name'         => $partnerFullName,
                'partner_name'              => $partnerFullName,
                'company_name'              => $partner->company_name ?: $partnerFullName,
                'Partner_Code'              => $partner->partner_code,
                'partner_code'              => $partner->partner_code,
                'Partner_Address'           => $partnerAddress,
                'partner_address'           => $partnerAddress,
                'address'                   => $partnerAddress,
                'Standard_Commission_Rate'  => number_format($commissionRate, 2) . '%',
                'standard_commission_rate'  => number_format($commissionRate, 2) . '%',
                'commission_rate'           => number_format($commissionRate, 2) . '%',
                'Commission_Type'           => ucfirst($commissionType),
                'commission_type'           => ucfirst($commissionType),
                'phone'                     => $partner->phone ?? '',
                'email'                     => $partner->user ? $partner->user->email : '',
                'city'                      => $partner->city ?? '',
                'state'                     => $partner->state ?? '',
                'postal_code'               => $partner->postal_code ?? '',
                'Acceptance_Timestamp'      => 'Pending Electronic Acceptance',
                'Partner_IP_Address'        => 'Pending Electronic Acceptance',
            ]);

            $pdfPath = "agreements/{$agreementNumber}.pdf";

            // 5. Create PartnerAgreement record
            $agreement = PartnerAgreement::create([
                'agreement_number'          => $agreementNumber,
                'partner_id'                => $partner->id,
                'template_id'               => $template?->id,
                'template_version'          => $template ? $template->version : '2.0',
                'commission_policy_id'      => $policy?->id,
                'commission_policy_version' => $policyVersion,
                'commission_rate_snapshot'  => $commissionRate,
                'commission_type_snapshot'  => $commissionType,
                'pdf_path'                  => $pdfPath,
                'content_snapshot'          => $populatedContent,
                'issue_date'                => now()->toDateString(),
                'effective_date'            => now()->toDateString(),
                'status'                    => AgreementStatus::PendingAcceptance,
                'created_by'                => $createdBy,
                'version'                   => ($options['version'] ?? 1),
            ]);

            // 6. Render and Save PDF in private storage
            $this->generateAndStorePdf($agreement);

            // 7. Dispatch in-app notification to the partner
            if ($partner->user_id) {
                Notification::send(
                    userId: $partner->user_id,
                    title: 'New Business Partner Agreement Issued',
                    message: "Official Partner Agreement #{$agreementNumber} is ready for your review and digital acceptance.",
                    type: 'info',
                    link: \Illuminate\Support\Facades\Route::has('partner.agreement.show') ? route('partner.agreement.show') : '/partner/agreement'
                );
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AgreementGenerated,
                module: \App\Enums\AuditLogModule::Agreement,
                description: "Partner Agreement #{$agreement->agreement_number} generated for Partner {$partner->company_name}.",
                subject: $agreement,
                metadata: ['agreement_number' => $agreementNumber, 'version' => $agreement->version],
                actor: $createdBy ? User::find($createdBy) : null
            );

            return $agreement;
        });
    }

    /**
     * Partner accepts the agreement.
     * Records timestamp in IST, IP, user-agent, regenerates signed PDF snapshot,
     * and sends confirmation notification.
     */
    public function acceptAgreement(PartnerAgreement $agreement, User $user, string $ip, string $userAgent): PartnerAgreement
    {
        if ($agreement->status !== AgreementStatus::PendingAcceptance) {
            throw new \InvalidArgumentException("Agreement #{$agreement->agreement_number} is not pending acceptance (status: {$agreement->status->value}).");
        }

        if ($agreement->partner->user_id !== $user->id) {
            throw new \Illuminate\Auth\Access\AuthorizationException("You are not authorized to accept this agreement.");
        }

        DB::transaction(function () use ($agreement, $user, $ip, $userAgent) {
            $formattedAcceptedAt = now()->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') . ' IST';
            $updatedContent = $this->replacePlaceholders($agreement->content_snapshot, [
                'Acceptance_Timestamp' => $formattedAcceptedAt,
                'acceptance_timestamp' => $formattedAcceptedAt,
                'Partner_IP_Address'   => $ip,
                'partner_ip_address'   => $ip,
            ]);

            $agreement->update([
                'status'              => AgreementStatus::Accepted,
                'accepted_at'         => now(),
                'accepted_ip'         => $ip,
                'accepted_user_agent' => substr($userAgent, 0, 500),
                'content_snapshot'    => $updatedContent,
            ]);

            // Preserve historical copy before re-rendering with digital acceptance stamp
            if (Storage::disk('local')->exists($agreement->pdf_path)) {
                $pendingPath = "agreements/{$agreement->agreement_number}_pending.pdf";
                Storage::disk('local')->copy($agreement->pdf_path, $pendingPath);
            }

            // Re-render PDF with electronic acceptance block stamped
            $this->generateAndStorePdf($agreement);

            // In-app confirmation notification
            Notification::send(
                userId: $agreement->partner->user_id,
                title: 'Partner Agreement Accepted',
                message: "You have successfully accepted Business Partner Agreement #{$agreement->agreement_number}. Your partnership is fully active.",
                type: 'success',
                link: \Illuminate\Support\Facades\Route::has('partner.agreement.show') ? route('partner.agreement.show') : '/partner/agreement'
            );

            // Record meaningful activity for partner
            app(\App\Services\InactivityService::class)->recordActivity($user, 'agreement_acceptance');

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AgreementAccepted,
                module: \App\Enums\AuditLogModule::Agreement,
                description: "Partner Agreement #{$agreement->agreement_number} digitally accepted by Partner {$user->name} ({$user->email}).",
                subject: $agreement,
                metadata: ['ip' => $ip, 'user_agent' => substr($userAgent, 0, 200)],
                actor: $user,
                ipAddress: $ip,
                userAgent: $userAgent
            );
        });

        return $agreement->fresh();
    }

    /**
     * Admin revokes an existing agreement with a mandatory reason.
     */
    public function revokeAgreement(PartnerAgreement $agreement, string $reason, int $revokedBy): PartnerAgreement
    {
        $cleanReason = trim($reason);
        if (empty($cleanReason)) {
            throw new \InvalidArgumentException("A valid revocation reason is mandatory.");
        }

        DB::transaction(function () use ($agreement, $cleanReason, $revokedBy) {
            $agreement->update([
                'status'            => AgreementStatus::Revoked,
                'revoked_at'        => now(),
                'revoked_by'        => $revokedBy,
                'revocation_reason' => $cleanReason,
            ]);

            // Re-render PDF with revoked watermark
            $this->generateAndStorePdf($agreement);

            // Send notification to partner
            if ($agreement->partner?->user_id) {
                Notification::send(
                    userId: $agreement->partner->user_id,
                    title: 'Partner Agreement Revoked',
                    message: "Partner Agreement #{$agreement->agreement_number} has been revoked: {$cleanReason}",
                    type: 'warning',
                    link: \Illuminate\Support\Facades\Route::has('partner.agreement.show') ? route('partner.agreement.show') : '/partner/agreement'
                );
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AgreementRevoked,
                module: \App\Enums\AuditLogModule::Agreement,
                description: "Partner Agreement #{$agreement->agreement_number} revoked by Admin. Reason: {$cleanReason}",
                subject: $agreement,
                metadata: ['reason' => $cleanReason],
                actor: User::find($revokedBy)
            );
        });

        return $agreement->fresh();
    }

    /**
     * Reissue an agreement for a partner (superseding old one).
     */
    public function reissueAgreement(PartnerAgreement $oldAgreement, int $adminId, ?string $reason = null): PartnerAgreement
    {
        return DB::transaction(function () use ($oldAgreement, $adminId, $reason) {
            // Mark old agreement as superseded
            $oldAgreement->update([
                'status' => AgreementStatus::Superseded,
            ]);
            $this->generateAndStorePdf($oldAgreement);

            // Generate new agreement with version increment
            $newAgreement = $this->generateAgreement(
                $oldAgreement->partner,
                $adminId,
                [
                    'force'   => true,
                    'version' => $oldAgreement->version + 1,
                    'reason'  => $reason,
                ]
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AgreementReissued,
                module: \App\Enums\AuditLogModule::Agreement,
                description: "Partner Agreement #{$oldAgreement->agreement_number} reissued as #{$newAgreement->agreement_number} by Admin." . ($reason ? " Reason: {$reason}" : ''),
                subject: $newAgreement,
                metadata: ['old_version' => $oldAgreement->version, 'new_version' => $newAgreement->version, 'reason' => $reason],
                actor: User::find($adminId)
            );

            return $newAgreement;
        });
    }

    /**
     * Generate PDF binary content and store securely on private disk.
     */
    public function generateAndStorePdf(PartnerAgreement $agreement): string
    {
        $pdfBytes = $this->renderPdf($agreement);

        // Ensure private agreements directory exists
        Storage::disk('local')->put($agreement->pdf_path, $pdfBytes);

        return $pdfBytes;
    }

    /**
     * Render agreement PDF binary using Dompdf and the branded Blade template.
     */
    public function renderPdf(PartnerAgreement $agreement): string
    {
        $partner = $agreement->partner;
        $partner->loadMissing('user');

        // Render Markdown content to safe HTML
        $renderedContent = Str::markdown($agreement->content_snapshot);

        // Ensure Clause 9 (Agreement Acceptance) groups cleanly with the signature section
        $renderedContent = str_replace(
            ['<h3>9. Agreement Acceptance</h3>', '<h2>9. Agreement Acceptance</h2>'],
            '<div style="page-break-before: always;"><h3>9. Agreement Acceptance</h3>',
            $renderedContent
        );
        if (str_contains($renderedContent, 'page-break-before: always;')) {
            $renderedContent .= '</div>';
        }

        // Load Letterhead assets as Base64 for 100% reliable Dompdf rendering
        $headerWaveBase64 = null;
        $headerWavePath = public_path('images/brand/letterhead_header_waves.png');
        if (file_exists($headerWavePath)) {
            $headerWaveBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($headerWavePath));
        }

        $footerWaveBase64 = null;
        $footerWavePath = public_path('images/brand/letterhead_footer_waves.png');
        if (file_exists($footerWavePath)) {
            $footerWaveBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($footerWavePath));
        }

        $logoBase64 = null;
        $letterheadLogoPath = public_path('images/brand/tech-googly-letterhead-logo.png');
        $defaultLogoPath = public_path('images/brand/tech-googly-logo.png');
        if (file_exists($letterheadLogoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($letterheadLogoPath));
        } elseif (file_exists($defaultLogoPath)) {
            $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($defaultLogoPath));
        }

        $phoneIconBase64 = null;
        $phoneIconPath = public_path('images/brand/icon_phone.png');
        if (file_exists($phoneIconPath)) {
            $phoneIconBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($phoneIconPath));
        }

        $emailIconBase64 = null;
        $emailIconPath = public_path('images/brand/icon_email.png');
        if (file_exists($emailIconPath)) {
            $emailIconBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($emailIconPath));
        }

        $locIconBase64 = null;
        $locIconPath = public_path('images/brand/icon_location.png');
        if (file_exists($locIconPath)) {
            $locIconBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($locIconPath));
        }

        $policyName = $agreement->commissionPolicy ? $agreement->commissionPolicy->name : 'Standard Partner Policy';
        $companySettings = $this->getCompanySettings();

        $html = view('pdf.agreement', [
            'agreement'        => $agreement,
            'partner'          => $partner,
            'renderedContent'  => $renderedContent,
            'headerWaveBase64' => $headerWaveBase64,
            'footerWaveBase64' => $footerWaveBase64,
            'logoBase64'       => $logoBase64,
            'phoneIconBase64'  => $phoneIconBase64,
            'emailIconBase64'  => $emailIconBase64,
            'locIconBase64'    => $locIconBase64,
            'policyName'       => $policyName,
            'companySettings'  => $companySettings,
        ])->render();

        $options = new Options();
        $options->set('defaultFont', 'Helvetica');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Affix exact page numbers dynamically to PDF canvas at footer
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->get_font("Helvetica", "normal");
        $canvas->page_text(485, 818, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 7.5, [0.35, 0.35, 0.35]);

        return $dompdf->output();
    }

    /**
     * Retrieve Admin-configured company letterhead details.
     */
    public function getCompanySettings(): array
    {
        return [
            'company_name'    => SystemSetting::get('company_name', 'Tech Googly'),
            'company_tagline' => SystemSetting::get('company_tagline', 'Innovation For You'),
            'company_website' => SystemSetting::get('company_website', 'www.techgoogly.com'),
            'company_address' => SystemSetting::get('company_address', '2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal - 741201'),
            'company_phone'   => SystemSetting::get('company_phone', '+91 6295710238'),
            'company_email'   => SystemSetting::get('company_email', 'info@techgoogly.com'),
        ];
    }

    /**
     * Concurrency-safe unique agreement number generator.
     * Format: TGPA-YYYY-XXXXXX (e.g. TGPA-2026-000001)
     */
    protected function generateNextAgreementNumber(): string
    {
        $year = now()->format('Y');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $lastAgreement = DB::table('partner_agreements')
                ->where('agreement_number', 'like', "TGPA-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $nextSequence = 1;
            if ($lastAgreement && preg_match("/TGPA-{$year}-(\\d+)/", $lastAgreement->agreement_number, $matches)) {
                $nextSequence = intval($matches[1]) + 1;
            }

            $candidate = sprintf("TGPA-%s-%06d", $year, $nextSequence);

            if (!DB::table('partner_agreements')->where('agreement_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // Fallback random hash if high contention
        return sprintf("TGPA-%s-%s", $year, strtoupper(Str::random(6)));
    }

    /**
     * Replace bracket placeholders in agreement template content.
     * Supports both {{Placeholder}} and {placeholder} forms case-insensitively.
     */
    public function replacePlaceholders(string $content, array $values): string
    {
        foreach ($values as $key => $val) {
            $valStr = (string) $val;
            $content = str_ireplace('{{' . $key . '}}', $valStr, $content);
            $content = str_ireplace('{' . $key . '}', $valStr, $content);
            $spacedKey = str_replace('_', ' ', $key);
            if ($spacedKey !== $key) {
                $content = str_ireplace('{{' . $spacedKey . '}}', $valStr, $content);
                $content = str_ireplace('{' . $spacedKey . '}', $valStr, $content);
            }
        }
        return $content;
    }

    /**
     * Baseline draft agreement content fallback (Part B simplified referral partner agreement).
     */
    protected function getDefaultDraftContent(): string
    {
        return <<<'MARKDOWN'
# REFERRAL PARTNER AGREEMENT

**Agreement Number:** {{Agreement_Number}}  
**Agreement Date:** {{Agreement_Date}}  

This Referral Partner Agreement is between **Tech Googly**, having its office at:  
2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal - 741201.  

And:  
**Partner Name:** {{Partner_Full_Name}}  
**Partner Code:** {{Partner_Code}}  
**Partner Address:** {{Partner_Address}}  

The Partner agrees to refer prospective customers to Tech Googly for its IT, software, website development, mobile application, digital marketing and other related services.

---

### 1. Purpose of Partnership
The Partner will introduce prospective customers to Tech Googly and help generate business opportunities.  
Tech Googly will communicate with referred customers, understand their requirements, prepare quotations, finalize project pricing and deliver the agreed services.  
The Partner acts as a referral partner and cannot independently finalize project prices or make unauthorized commitments on behalf of Tech Googly.

### 2. Commission Structure
The Partner will receive commission for eligible business generated through successful referrals.  
Tech Googly will establish a standard commission rate through its Admin Panel.  
The standard commission rate will be applicable unless a different commission rate or amount is specifically configured for a particular project.  
If a project has a higher or otherwise different commission arrangement, the project-specific commission terms will apply to that project.  
The applicable commission details will be recorded in the Partner Portal and against the relevant project.

### 3. Commission Generation and Customer Payments
Commission will be generated only when the referred customer makes a payment and the corresponding amount is successfully received and verified by Tech Googly.  
Commission will be calculated on the eligible amount actually received by Tech Googly, according to the applicable commission rate or fixed commission arrangement.  
For projects involving multiple instalments, milestones or monthly payments, commission will be calculated proportionately against each eligible payment received and verified by Tech Googly.  
A quotation, invoice, payment promise or pending transaction alone does not create payable commission.  
The Partner can view generated commissions and payment history through the Tech Googly Partner Portal.

### 4. Refunds and Commission Adjustments
If a customer payment is refunded, reversed, cancelled or charged back after commission has been generated, the corresponding commission will also be adjusted proportionately.  
If the related commission has not yet been paid to the Partner, the amount may be deducted from the Partner's available commission balance.  
If the commission has already been paid to the Partner, the corresponding amount may be adjusted against future commissions or returned by the Partner after reconciliation with Tech Googly.  
Only the commission related to the refunded or reversed customer payment will be adjusted.  
The relevant payment and commission adjustment records will be maintained in the Partner Portal.

### 5. Commission Withdrawal
The Partner can request withdrawal of eligible available commissions through the Partner Portal.  
Withdrawals will be processed according to Tech Googly's applicable withdrawal rules, including required KYC approval and bank account verification.  
The Partner must provide accurate payment details to receive commission payouts.

### 6. Partner Responsibilities
The Partner agrees to:
- Provide genuine customer referrals.
- Share accurate customer information with appropriate permission.
- Avoid false promises regarding pricing, delivery time or services.
- Represent Tech Googly professionally.
- Avoid submitting fake, duplicate or misleading leads.

### 7. Confidentiality
The Partner should not misuse or disclose confidential customer information, internal pricing information or business information received through the Tech Googly Partner Program.  
Customer information should only be used for legitimate referral and business communication purposes.

### 8. Partnership Discontinuation
Either the Partner or Tech Googly may discontinue the referral partnership by providing written communication.  
Eligible commissions earned from verified customer payments will be reconciled according to the applicable commission and refund adjustment records.  
Pending or disputed commission amounts will be reviewed before final settlement.

### 9. Agreement Acceptance
By accepting this Agreement through the Tech Googly Partner Portal, the Partner confirms that:
- They understand the referral-based partnership arrangement.
- They understand how commission is calculated.
- They understand that commission is generated only after the corresponding customer payment is received and verified by Tech Googly.
- They understand that project-specific commission rates may differ from the standard rate.
- They understand the refund and commission adjustment policy.
- They agree to follow the responsibilities described in this Agreement.
MARKDOWN;
    }
}
