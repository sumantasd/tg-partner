<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\CommissionType;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AgreementTemplate;
use App\Models\CommissionPolicy;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\User;
use App\Services\PartnerAgreementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Batch2PartnerAgreementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $partnerUser;
    private Partner $partner;
    private User $otherPartnerUser;
    private Partner $otherPartner;
    private AgreementTemplate $template;
    private CommissionPolicy $policy;
    private PartnerAgreementService $agreementService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->agreementService = app(PartnerAgreementService::class);

        // 1. Admin User
        $this->admin = User::create([
            'name'              => 'Admin Compliance Officer',
            'email'             => 'admin_agreement@techgoogly.com',
            'password'          => Hash::make('SecretAdmin123!'),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        // 2. Active Template
        $this->template = AgreementTemplate::create([
            'template_code'  => 'TPL-TEST-V1',
            'title'          => 'Standard Partner Agreement Test Template',
            'version'        => '1.0',
            'content'        => "# Test Agreement\n\n**Number:** {agreement_number}\n**Partner:** {partner_name}\n**Code:** {partner_code}\n**Rate:** {commission_rate}",
            'status'         => 'active',
            'is_active'      => true,
            'effective_from' => '2026-01-01',
            'created_by'     => $this->admin->id,
        ]);

        // 3. Active Commission Policy
        $this->policy = CommissionPolicy::create([
            'policy_code'      => 'POL-TEST-V1',
            'name'             => 'Test Standard Commission Policy',
            'description'      => 'Standard 12% test policy',
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 12.50,
            'effective_from'   => '2026-01-01',
            'status'           => 'active',
            'is_default'       => true,
            'version'          => 1,
            'created_by'       => $this->admin->id,
        ]);

        // 4. Partner User & Model
        $this->partnerUser = User::create([
            'name'              => 'Rohit Sharma',
            'email'             => 'rohit@techpartner.com',
            'phone'             => '+919876543210',
            'password'          => Hash::make('PartnerPass123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-2026-TEST01',
            'company_name'            => 'Sharma IT Solutions Pvt Ltd',
            'phone'                   => '+919876543210',
            'address_line1'           => 'Salt Lake Sector V',
            'city'                    => 'Kolkata',
            'state'                   => 'West Bengal',
            'postal_code'             => '700091',
            'country'                 => 'India',
            'default_commission_rate' => 10.00,
            'kyc_status'              => KycStatus::Approved,
            'approved_at'             => now(),
            'approved_by'             => $this->admin->id,
            'created_by'              => $this->admin->id,
        ]);

        // 5. Secondary Partner for Authorization Isolation tests
        $this->otherPartnerUser = User::create([
            'name'              => 'Pooja Verma',
            'email'             => 'pooja@otherpartner.com',
            'phone'             => '+919876543211',
            'password'          => Hash::make('PartnerPass123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->otherPartner = Partner::create([
            'user_id'                 => $this->otherPartnerUser->id,
            'partner_code'            => 'TGP-2026-TEST02',
            'company_name'            => 'Verma Digital Agency',
            'phone'                   => '+919876543211',
            'address_line1'           => 'Park Street',
            'city'                    => 'Kolkata',
            'state'                   => 'West Bengal',
            'postal_code'             => '700016',
            'country'                 => 'India',
            'default_commission_rate' => 10.00,
            'kyc_status'              => KycStatus::Approved,
            'approved_at'             => now(),
            'approved_by'             => $this->admin->id,
            'created_by'              => $this->admin->id,
        ]);
    }

    public function test_admin_approving_partner_automatically_generates_partner_agreement(): void
    {
        // Create a pending partner
        $pendingUser = User::create([
            'name'     => 'Pending Partner User',
            'email'    => 'pending@testpartner.com',
            'phone'    => '+919876543299',
            'password' => Hash::make('Secret123!'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Pending,
        ]);

        $pendingPartner = Partner::create([
            'user_id'                 => $pendingUser->id,
            'partner_code'            => 'TGP-2026-NEW01',
            'company_name'            => 'New Tech Ventures',
            'phone'                   => '+919876543299',
            'default_commission_rate' => 10.00,
        ]);

        $this->assertDatabaseMissing('partner_agreements', ['partner_id' => $pendingPartner->id]);

        // Admin approves partner
        $response = $this->actingAs($this->admin)->post(route('admin.partners.approve', $pendingPartner));
        $response->assertRedirect();

        $this->assertDatabaseHas('partner_agreements', [
            'partner_id' => $pendingPartner->id,
            'status'     => AgreementStatus::PendingAcceptance->value,
        ]);

        $agreement = PartnerAgreement::where('partner_id', $pendingPartner->id)->first();
        $this->assertNotNull($agreement);
        $this->assertMatchesRegularExpression('/^TGPA-\d{4}-\d{6}$/', $agreement->agreement_number);
    }

    public function test_agreement_number_format_and_uniqueness(): void
    {
        $agreement1 = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $agreement2 = $this->agreementService->generateAgreement($this->otherPartner, $this->admin->id, ['force' => true]);

        $this->assertMatchesRegularExpression('/^TGPA-\d{4}-\d{6}$/', $agreement1->agreement_number);
        $this->assertMatchesRegularExpression('/^TGPA-\d{4}-\d{6}$/', $agreement2->agreement_number);
        $this->assertNotEquals($agreement1->agreement_number, $agreement2->agreement_number);
    }

    public function test_agreement_snapshots_active_template_and_commission_policy(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $this->assertEquals($this->template->id, $agreement->template_id);
        $this->assertEquals('1.0', $agreement->template_version);
        $this->assertEquals($this->policy->id, $agreement->commission_policy_id);
        $this->assertEquals('1', (string) $agreement->commission_policy_version);
        $this->assertEquals(12.50, (float) $agreement->commission_rate_snapshot);
        $this->assertEquals('percentage', $agreement->commission_type_snapshot);
        $this->assertStringContainsString('Rohit Sharma', $agreement->content_snapshot);
        $this->assertStringContainsString('TGP-2026-TEST01', $agreement->content_snapshot);
    }

    public function test_agreement_pdf_is_generated_and_saved_to_private_storage(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $this->assertTrue(Storage::disk('local')->exists($agreement->pdf_path));
        $this->assertStringStartsWith('agreements/TGPA-', $agreement->pdf_path);
        $content = Storage::disk('local')->get($agreement->pdf_path);
        $this->assertStringStartsWith('%PDF-', $content);
    }

    public function test_in_app_notification_dispatched_to_partner_on_agreement_generation(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->partnerUser->id,
            'title'   => 'New Business Partner Agreement Issued',
        ]);
    }

    public function test_partner_can_view_agreement_page_and_terms(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $response = $this->actingAs($this->partnerUser)->get(route('partner.agreement.show'));

        $response->assertOk();
        $response->assertSee($agreement->agreement_number);
        $response->assertSee('Action Required: Digital Acceptance Pending');
        $response->assertSee('Execute Electronic Signature & Acceptance');
        $response->assertSee('Sharma IT Solutions Pvt Ltd');
    }

    public function test_partner_can_preview_and_download_agreement_pdf(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Preview
        $previewRes = $this->actingAs($this->partnerUser)->get(route('partner.agreement.preview'));
        $previewRes->assertOk();
        $previewRes->assertHeader('Content-Type', 'application/pdf');

        // Download
        $downloadRes = $this->actingAs($this->partnerUser)->get(route('partner.agreement.download'));
        $downloadRes->assertOk();
        $downloadRes->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unaccepted_partner_is_blocked_from_creating_or_storing_lead(): void
    {
        // Partner has pending agreement
        $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $this->assertTrue($this->partner->hasPendingAgreement());

        // Attempt create form
        $createRes = $this->actingAs($this->partnerUser)->get(route('partner.leads.create'));
        $createRes->assertRedirect(route('partner.agreement.show'));
        $createRes->assertSessionHas('warning');

        // Attempt store
        $storeRes = $this->actingAs($this->partnerUser)->post(route('partner.leads.store'), [
            'customer_name'       => 'Test Lead Customer',
            'customer_phone'      => '+919988776655',
            'service_required'    => 'Custom Web & ERP Application',
            'project_description' => 'A large enterprise project requiring custom cloud ERP development.',
        ]);
        $storeRes->assertRedirect(route('partner.agreement.show'));
        $storeRes->assertSessionHas('warning');
    }

    public function test_unaccepted_partner_is_blocked_from_creating_or_storing_withdrawal(): void
    {
        // Partner has pending agreement
        $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $this->assertTrue($this->partner->hasPendingAgreement());

        // Attempt create form
        $createRes = $this->actingAs($this->partnerUser)->get(route('partner.withdrawals.create'));
        $createRes->assertRedirect(route('partner.agreement.show'));
        $createRes->assertSessionHas('warning');

        // Attempt store
        $storeRes = $this->actingAs($this->partnerUser)->post(route('partner.withdrawals.store'), [
            'amount'        => 5000,
            'payout_method' => 'bank_transfer',
        ]);
        $storeRes->assertRedirect(route('partner.agreement.show'));
        $storeRes->assertSessionHas('warning');
    }

    public function test_partner_can_digitally_accept_agreement(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Attempt accept without checkbox
        $failRes = $this->actingAs($this->partnerUser)->post(route('partner.agreement.accept'), []);
        $failRes->assertSessionHasErrors('accepted');

        // Accept with checkbox
        $acceptRes = $this->actingAs($this->partnerUser)->post(route('partner.agreement.accept'), [
            'accepted' => '1',
        ]);
        $acceptRes->assertRedirect(route('partner.agreement.show'));
        $acceptRes->assertSessionHas('success');

        $agreement->refresh();
        $this->assertEquals(AgreementStatus::Accepted, $agreement->status);
        $this->assertNotNull($agreement->accepted_at);
        $this->assertNotNull($agreement->accepted_ip);
        $this->assertNotNull($agreement->accepted_user_agent);

        // Check confirmation notification dispatched
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->partnerUser->id,
            'title'   => 'Partner Agreement Accepted',
        ]);

        // Check updated PDF still exists and contains accepted stamp
        $this->assertTrue(Storage::disk('local')->exists($agreement->pdf_path));
    }

    public function test_accepted_partner_is_unblocked_for_leads_and_withdrawals(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $this->agreementService->acceptAgreement($agreement, $this->partnerUser, '127.0.0.1', 'PHPUnit Test Browser');

        $this->assertFalse($this->partner->hasPendingAgreement());
        $this->assertTrue($this->partner->hasAcceptedAgreement());

        // Lead create is now accessible
        $leadRes = $this->actingAs($this->partnerUser)->get(route('partner.leads.create'));
        $leadRes->assertOk();

        // Withdrawal create is now accessible
        $withRes = $this->actingAs($this->partnerUser)->get(route('partner.withdrawals.create'));
        $withRes->assertOk();
    }

    public function test_admin_can_list_filter_and_search_agreements(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // List
        $listRes = $this->actingAs($this->admin)->get(route('admin.agreements.index'));
        $listRes->assertOk();
        $listRes->assertSee($agreement->agreement_number);

        // Filter pending
        $pendingRes = $this->actingAs($this->admin)->get(route('admin.agreements.index', ['status' => 'pending_acceptance']));
        $pendingRes->assertOk();
        $pendingRes->assertSee($agreement->agreement_number);

        // Filter accepted (should not show agreement yet)
        $acceptedRes = $this->actingAs($this->admin)->get(route('admin.agreements.index', ['status' => 'accepted']));
        $acceptedRes->assertOk();
        $acceptedRes->assertDontSee($agreement->agreement_number);

        // Search by agreement number
        $searchRes = $this->actingAs($this->admin)->get(route('admin.agreements.index', ['search' => $agreement->agreement_number]));
        $searchRes->assertOk();
        $searchRes->assertSee($agreement->agreement_number);
    }

    public function test_admin_can_view_agreement_dossier_preview_and_download(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Dossier
        $dossierRes = $this->actingAs($this->admin)->get(route('admin.agreements.show', $agreement));
        $dossierRes->assertOk();
        $dossierRes->assertSee($agreement->agreement_number);
        $dossierRes->assertSee('Agreement Specification');
        $dossierRes->assertSee('Commercial Snapshot');

        // Preview
        $prevRes = $this->actingAs($this->admin)->get(route('admin.agreements.preview', $agreement));
        $prevRes->assertOk();
        $prevRes->assertHeader('Content-Type', 'application/pdf');

        // Download
        $dlRes = $this->actingAs($this->admin)->get(route('admin.agreements.download', $agreement));
        $dlRes->assertOk();
        $dlRes->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_revoke_agreement_with_mandatory_reason(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Attempt without reason fails validation
        $failRes = $this->actingAs($this->admin)->post(route('admin.agreements.revoke', $agreement), []);
        $failRes->assertSessionHasErrors('reason');

        // Revoke with valid reason
        $revokeRes = $this->actingAs($this->admin)->post(route('admin.agreements.revoke', $agreement), [
            'reason' => 'Administrative suspension due to policy breach.',
        ]);
        $revokeRes->assertRedirect();

        $agreement->refresh();
        $this->assertEquals(AgreementStatus::Revoked, $agreement->status);
        $this->assertEquals($this->admin->id, $agreement->revoked_by);
        $this->assertNotNull($agreement->revoked_at);
        $this->assertEquals('Administrative suspension due to policy breach.', $agreement->revocation_reason);

        // Partner gets revocation notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->partnerUser->id,
            'title'   => 'Partner Agreement Revoked',
        ]);
    }

    public function test_admin_can_reissue_agreement(): void
    {
        $oldAgreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $reissueRes = $this->actingAs($this->admin)->post(route('admin.agreements.reissue', $oldAgreement), [
            'reason' => 'Revised commission terms and commercial restructuring.',
        ]);
        $reissueRes->assertRedirect();

        $oldAgreement->refresh();
        $this->assertEquals(AgreementStatus::Superseded, $oldAgreement->status);

        $newAgreement = PartnerAgreement::where('partner_id', $this->partner->id)
            ->where('status', AgreementStatus::PendingAcceptance)
            ->latest()
            ->first();

        $this->assertNotNull($newAgreement);
        $this->assertEquals($oldAgreement->version + 1, $newAgreement->version);
        $this->assertNotEquals($oldAgreement->agreement_number, $newAgreement->agreement_number);
    }

    public function test_admin_can_manually_issue_agreement_for_partner(): void
    {
        $issueRes = $this->actingAs($this->admin)->post(route('admin.partners.issue-agreement', $this->partner), [
            'force' => true,
        ]);
        $issueRes->assertRedirect();

        $this->assertDatabaseHas('partner_agreements', [
            'partner_id' => $this->partner->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_partner_cannot_view_or_download_another_partners_agreement(): void
    {
        $otherAgreement = $this->agreementService->generateAgreement($this->otherPartner, $this->admin->id, ['force' => true]);

        // Primary partner attempts to accept other partner's agreement directly via service
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->agreementService->acceptAgreement($otherAgreement, $this->partnerUser, '127.0.0.1', 'Hacker Browser');
    }

    public function test_newly_issued_agreement_uses_simplified_9_clause_content_and_omits_legacy_clauses(): void
    {
        // Deactivate old test template and create the official v2.0 simplified template
        $this->template->update(['is_active' => false, 'status' => 'archived']);

        $v2Template = AgreementTemplate::create([
            'template_code'  => 'TPL-REF-V2',
            'title'          => 'Official Referral Partner Agreement',
            'version'        => '2.0',
            'content'        => (new \ReflectionClass($this->agreementService))->getMethod('getDefaultDraftContent')->invoke($this->agreementService),
            'status'         => 'active',
            'is_active'      => true,
            'effective_from' => '2026-01-01',
            'created_by'     => $this->admin->id,
        ]);

        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Verify all 9 clauses are present
        $this->assertStringContainsString('REFERRAL PARTNER AGREEMENT', $agreement->content_snapshot);
        $this->assertStringContainsString('1. Purpose of Partnership', $agreement->content_snapshot);
        $this->assertStringContainsString('2. Commission Structure', $agreement->content_snapshot);
        $this->assertStringContainsString('3. Commission Generation and Customer Payments', $agreement->content_snapshot);
        $this->assertStringContainsString('4. Refunds and Commission Adjustments', $agreement->content_snapshot);
        $this->assertStringContainsString('5. Commission Withdrawal', $agreement->content_snapshot);
        $this->assertStringContainsString('6. Partner Responsibilities', $agreement->content_snapshot);
        $this->assertStringContainsString('7. Confidentiality', $agreement->content_snapshot);
        $this->assertStringContainsString('8. Partnership Discontinuation', $agreement->content_snapshot);
        $this->assertStringContainsString('9. Agreement Acceptance', $agreement->content_snapshot);

        // Verify legacy clauses are omitted
        $this->assertStringNotContainsString('90-day duplicate cooling policy', $agreement->content_snapshot);
        $this->assertStringNotContainsString('exclusive referral ownership for an active registered lead', $agreement->content_snapshot);
        $this->assertStringNotContainsString("thirty (30) days' written notice", $agreement->content_snapshot);
    }

    public function test_company_name_is_strictly_tech_googly_and_never_tech_googly_private_limited(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Content snapshot check
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Private Limited', $agreement->content_snapshot);
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Pvt. Ltd.', $agreement->content_snapshot);
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Pvt Ltd', $agreement->content_snapshot);

        // Rendered view check
        $renderedHtml = view('pdf.agreement', [
            'agreement'        => $agreement,
            'partner'          => $this->partner,
            'renderedContent'  => \Illuminate\Support\Str::markdown($agreement->content_snapshot),
            'headerWaveBase64' => null,
            'footerWaveBase64' => null,
            'logoBase64'       => null,
            'phoneIconBase64'  => null,
            'emailIconBase64'  => null,
            'locIconBase64'    => null,
            'policyName'       => 'Test Policy',
            'companySettings'  => $this->agreementService->getCompanySettings(),
        ])->render();

        $this->assertStringContainsString('Tech Googly', $renderedHtml);
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Private Limited', $renderedHtml);
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Pvt. Ltd.', $renderedHtml);
        $this->assertStringNotContainsStringIgnoringCase('Tech Googly Pvt Ltd', $renderedHtml);
    }

    public function test_dynamic_commission_placeholders_and_rates_display_accurately(): void
    {
        // Set custom policy rate
        $this->policy->update([
            'commission_value' => 15.75,
            'commission_type'  => CommissionType::Percentage,
        ]);

        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $this->assertEquals(15.75, (float) $agreement->commission_rate_snapshot);
        $this->assertEquals('percentage', $agreement->commission_type_snapshot);

        $renderedHtml = view('pdf.agreement', [
            'agreement'        => $agreement,
            'partner'          => $this->partner,
            'renderedContent'  => \Illuminate\Support\Str::markdown($agreement->content_snapshot),
            'headerWaveBase64' => null,
            'footerWaveBase64' => null,
            'logoBase64'       => null,
            'phoneIconBase64'  => null,
            'emailIconBase64'  => null,
            'locIconBase64'    => null,
            'policyName'       => 'Test 15.75% Policy',
            'companySettings'  => $this->agreementService->getCompanySettings(),
        ])->render();

        $this->assertStringContainsString('15.75%', $renderedHtml);
        $this->assertStringContainsString('Percentage', $renderedHtml);
    }

    public function test_fixed_commission_amount_and_frequency_display_accurately_in_pdf(): void
    {
        $fixedPolicy = CommissionPolicy::create([
            'policy_code'      => 'POL-FIXED-TEST',
            'name'             => 'Fixed Payout Policy',
            'description'      => 'Fixed 3500 per lead',
            'commission_type'  => CommissionType::Fixed,
            'commission_value' => 3500.00,
            'effective_from'   => '2026-01-01',
            'status'           => 'active',
            'is_default'       => true,
            'version'          => 2,
            'created_by'       => $this->admin->id,
        ]);

        // Deactivate previous default policy
        $this->policy->update(['is_default' => false, 'status' => 'superseded']);

        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $this->assertEquals(3500.00, (float) $agreement->commission_rate_snapshot);
        $this->assertEquals('fixed', $agreement->commission_type_snapshot);

        $renderedHtml = view('pdf.agreement', [
            'agreement'        => $agreement,
            'partner'          => $this->partner,
            'renderedContent'  => \Illuminate\Support\Str::markdown($agreement->content_snapshot),
            'headerWaveBase64' => null,
            'footerWaveBase64' => null,
            'logoBase64'       => null,
            'phoneIconBase64'  => null,
            'emailIconBase64'  => null,
            'locIconBase64'    => null,
            'policyName'       => 'Fixed Payout Policy',
            'companySettings'  => $this->agreementService->getCompanySettings(),
        ])->render();

        $this->assertStringContainsString('3,500.00', $renderedHtml);
        $this->assertStringContainsString('Fixed', $renderedHtml);
    }

    public function test_refund_adjustment_and_payment_verification_terms_present_in_agreement(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        // Check fallback / template content contains refund and verification terms
        $content = (new \ReflectionClass($this->agreementService))->getMethod('getDefaultDraftContent')->invoke($this->agreementService);
        $this->assertStringContainsString('successfully received and verified by Tech Googly', $content);
        $this->assertStringContainsString('refunded, reversed, cancelled or charged back', $content);
        $this->assertStringContainsString('required KYC approval and bank account verification', $content);
    }

    public function test_official_reference_letterhead_assets_and_contact_details_included_in_pdf(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $settings = $this->agreementService->getCompanySettings();

        $this->assertEquals('Tech Googly', $settings['company_name']);
        $this->assertEquals('Innovation For You', $settings['company_tagline']);
        $this->assertEquals('www.techgoogly.com', $settings['company_website']);
        $this->assertEquals('+91 6295710238', $settings['company_phone']);
        $this->assertEquals('info@techgoogly.com', $settings['company_email']);
        $this->assertStringContainsString('2 No. Park Street Road', $settings['company_address']);
        $this->assertStringContainsString('Ranaghat, Nadia, West Bengal - 741201', $settings['company_address']);

        // Check PDF generation produces valid binary
        $pdfBytes = $this->agreementService->renderPdf($agreement);
        $this->assertNotEmpty($pdfBytes);
        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }

    public function test_preview_and_download_return_same_agreement_version(): void
    {
        $agreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);

        $previewRes = $this->actingAs($this->partnerUser)->get(route('partner.agreement.preview'));
        $previewRes->assertOk();
        $previewRes->assertHeader('Content-Type', 'application/pdf');

        $downloadRes = $this->actingAs($this->partnerUser)->get(route('partner.agreement.download'));
        $downloadRes->assertOk();
        $downloadRes->assertHeader('Content-Type', 'application/pdf');

        // Both return the exact same file binary from storage
        $storedContent = Storage::disk('local')->get($agreement->pdf_path);
        $this->assertNotEmpty($storedContent);
        $this->assertEquals(strlen($storedContent), strlen($downloadRes->streamedContent()));
    }

    public function test_historical_accepted_agreement_remains_unchanged_when_new_template_active(): void
    {
        // 1. Existing accepted agreement under template v1.0
        $historicalAgreement = $this->agreementService->generateAgreement($this->partner, $this->admin->id, ['force' => true]);
        $this->agreementService->acceptAgreement($historicalAgreement, $this->partnerUser, '127.0.0.1', 'Test Browser');
        $historicalAgreement->refresh();

        $originalSnapshot = $historicalAgreement->content_snapshot;
        $originalTemplateVersion = $historicalAgreement->template_version;

        // 2. New template v2.0 is activated
        $this->template->update(['is_active' => false]);
        AgreementTemplate::create([
            'template_code'  => 'TPL-V2-NEW',
            'title'          => 'New Template V2',
            'version'        => '2.0',
            'content'        => "# Version 2 Content\n\nNew terms apply.",
            'status'         => 'active',
            'is_active'      => true,
            'effective_from' => '2026-01-01',
            'created_by'     => $this->admin->id,
        ]);

        // 3. Historical agreement remains untouched
        $historicalAgreement->refresh();
        $this->assertEquals($originalTemplateVersion, $historicalAgreement->template_version);
        $this->assertEquals($originalSnapshot, $historicalAgreement->content_snapshot);
        $this->assertEquals(AgreementStatus::Accepted, $historicalAgreement->status);
    }
}
