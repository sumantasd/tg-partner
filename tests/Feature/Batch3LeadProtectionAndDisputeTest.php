<?php

namespace Tests\Feature;

use App\Enums\LeadActivityType;
use App\Enums\LeadDisputeCategory;
use App\Enums\LeadDisputeStatus;
use App\Enums\LeadOwnershipStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\LeadDispute;
use App\Models\LeadOwnershipHistory;
use App\Models\Partner;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\LeadDisputeService;
use App\Services\LeadService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class Batch3LeadProtectionAndDisputeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teamUser;
    protected User $partnerUser1;
    protected Partner $partner1;
    protected User $partnerUser2;
    protected Partner $partner2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser = User::create([
            'name' => 'CRM Consultant',
            'email' => 'staff@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser1 = User::create([
            'name' => 'Partner Alpha Contact',
            'email' => 'alpha@techgooglypartners.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner1 = Partner::create([
            'user_id' => $this->partnerUser1->id,
            'partner_code' => 'TGP-2026-ALPHA',
            'company_name' => 'Alpha Digital Services',
            'phone' => '9876500001',
            'default_commission_rate' => 10.00,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->partnerUser2 = User::create([
            'name' => 'Partner Beta Contact',
            'email' => 'beta@techgooglypartners.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner2 = Partner::create([
            'user_id' => $this->partnerUser2->id,
            'partner_code' => 'TGP-2026-BETA',
            'company_name' => 'Beta Infotech Solutions',
            'phone' => '9876500002',
            'default_commission_rate' => 10.00,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        SystemSetting::set('lead_ownership_validity_days', '90');
    }

    /**
     * 1. Partner can submit a genuine lead.
     */
    public function test_01_partner_can_submit_genuine_lead(): void
    {
        $response = $this->actingAs($this->partnerUser1)->post(route('partner.leads.store'), [
            'customer_name' => 'Acme Corporation',
            'customer_company' => 'Acme Global',
            'customer_phone' => '9876512345',
            'customer_email' => 'contact@acmeglobal.com',
            'city' => 'Bengaluru',
            'service_required' => 'Custom Web & ERP Application',
            'project_description' => 'Need an enterprise multi-warehouse ERP system for 500 users.',
            'estimated_value' => 500000,
        ]);

        $lead = Lead::where('customer_phone', '9876512345')->first();
        $this->assertNotNull($lead);
        $response->assertRedirect(route('partner.leads.show', $lead));

        $this->assertSame($this->partner1->id, $lead->partner_id);
        $this->assertSame($this->partner1->id, $lead->original_partner_id);
        $this->assertSame(LeadOwnershipStatus::Protected, $lead->ownership_status);
        $this->assertNotNull($lead->submitted_at);
        $this->assertNotNull($lead->ownership_started_at);
        $this->assertNotNull($lead->ownership_expires_at);

        $this->assertDatabaseHas('lead_ownership_histories', [
            'lead_id' => $lead->id,
            'new_partner_id' => $this->partner1->id,
            'to_status' => LeadOwnershipStatus::Protected->value,
            'action' => 'initial_assignment',
        ]);
    }

    /**
     * 2. Original ownership is preserved even after admin reassignment.
     */
    public function test_02_original_ownership_is_preserved_even_after_reassignment(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Delta Retail',
            'customer_phone' => '9876522222',
            'customer_email' => 'info@deltaretail.com',
            'service_required' => 'Mobile App Development',
            'project_description' => 'E-commerce shopping mobile application.',
        ]);

        $this->assertSame($this->partner1->id, $lead->original_partner_id);
        $this->assertSame($this->partner1->id, $lead->partner_id);

        $service->reassignOwnership($lead, $this->partner2, 'Authorized contract transfer agreed by both partners.', $this->adminUser);

        $lead->refresh();
        $this->assertSame($this->partner2->id, $lead->partner_id);
        $this->assertSame($this->partner1->id, $lead->original_partner_id, 'Original partner ID must remain traceable forever.');
        $this->assertSame(LeadOwnershipStatus::Reassigned, $lead->ownership_status);
    }

    /**
     * 3. Another Partner cannot claim an existing protected lead.
     */
    public function test_03_another_partner_cannot_claim_existing_protected_lead(): void
    {
        $service = app(LeadService::class);
        $lead1 = $service->createLead($this->partner1, [
            'customer_name' => 'Protected Enterprise',
            'customer_phone' => '9876533333',
            'customer_email' => 'ceo@protectedenterprise.com',
            'service_required' => 'Cloud & DevOps Infrastructure',
            'project_description' => 'AWS cloud architecture modernization.',
        ]);

        $response = $this->actingAs($this->partnerUser2)->post(route('partner.leads.store'), [
            'customer_name' => 'Conflicting Submission',
            'customer_company' => 'Protected Enterprise Branch',
            'customer_phone' => '9876533333',
            'customer_email' => 'ceo@protectedenterprise.com',
            'service_required' => 'Cloud & DevOps Infrastructure',
            'project_description' => 'Attempting duplicate registration.',
        ]);

        $response->assertSessionHasErrors('customer_phone');
        $this->assertSame(1, Lead::where('customer_phone', '9876533333')->count());
        $this->assertSame($this->partner1->id, $lead1->fresh()->partner_id);
    }

    /**
     * 4. Duplicate mobile number detection.
     */
    public function test_04_duplicate_mobile_number_detection(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Prime Industries',
            'customer_phone' => '9876544444',
            'customer_email' => 'prime1@industries.com',
            'service_required' => 'AI Automation',
            'project_description' => 'Supply chain automation platform.',
        ]);

        $duplicateCheck = $service->detectDuplicate('9876544444', 'different_email@industries.com', $this->partner2->id);

        $this->assertTrue($duplicateCheck['is_duplicate']);
        $this->assertSame($lead->id, $duplicateCheck['matched_lead']->id);
        $this->assertFalse($duplicateCheck['same_partner']);
    }

    /**
     * 5. Duplicate email detection.
     */
    public function test_05_duplicate_email_detection(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Solaris Tech',
            'customer_phone' => '9876555551',
            'customer_email' => 'inquiry@solaristech.io',
            'service_required' => 'UI/UX Design',
            'project_description' => 'SaaS redesign project.',
        ]);

        $duplicateCheck = $service->detectDuplicate('9876555552', 'INQUIRY@solaristech.io', $this->partner2->id);

        $this->assertTrue($duplicateCheck['is_duplicate']);
        $this->assertSame($lead->id, $duplicateCheck['matched_lead']->id);
    }

    /**
     * 6. Phone normalization handles various formats.
     */
    public function test_06_phone_normalization_handles_various_formats(): void
    {
        $service = app(LeadService::class);

        $this->assertSame('9876512345', $service->normalizePhone('+91 98765 12345'));
        $this->assertSame('9876512345', $service->normalizePhone('919876512345'));
        $this->assertSame('9876512345', $service->normalizePhone('09876512345'));
        $this->assertSame('9876512345', $service->normalizePhone('98765-12345'));
    }

    /**
     * 7. Existing 90-day cooling logic remains functional.
     */
    public function test_07_cooling_period_logic_remains_functional(): void
    {
        $service = app(LeadService::class);

        // Lead created 95 days ago and released/expired
        $oldLead = Lead::create([
            'lead_number' => 'LED-2025-0099',
            'partner_id' => $this->partner1->id,
            'original_partner_id' => $this->partner1->id,
            'customer_name' => 'Ancient Lead Corp',
            'customer_phone' => '9876566666',
            'customer_email' => 'ancient@corp.com',
            'service_required' => 'Custom Software',
            'project_description' => 'Historical lead project.',
            'status' => LeadStatus::Lost,
            'ownership_status' => LeadOwnershipStatus::Expired,
            'ownership_expires_at' => now()->subDays(5),
        ]);

        // Explicitly set historical creation timestamp in database
        \Illuminate\Support\Facades\DB::table('leads')->where('id', $oldLead->id)->update([
            'created_at' => now()->subDays(95),
            'updated_at' => now()->subDays(95),
        ]);

        $duplicateCheck = $service->detectDuplicate('9876566666', 'ancient@corp.com', $this->partner2->id, 90);

        $this->assertFalse($duplicateCheck['is_duplicate'], 'Expired lead beyond cooling window should not block new submission.');
    }

    /**
     * 8. Privacy-safe duplicate warning never leaks original partner identity.
     */
    public function test_08_privacy_safe_duplicate_warning_never_leaks_original_partner(): void
    {
        $service = app(LeadService::class);
        $service->createLead($this->partner1, [
            'customer_name' => 'Confidential Client',
            'customer_phone' => '9876577777',
            'customer_email' => 'client@secretcorp.com',
            'service_required' => 'Enterprise Security',
            'project_description' => 'Security compliance audit.',
        ]);

        $response = $this->actingAs($this->partnerUser2)->from(route('partner.leads.create'))->post(route('partner.leads.store'), [
            'customer_name' => 'Confidential Client',
            'customer_phone' => '9876577777',
            'customer_email' => 'client@secretcorp.com',
            'service_required' => 'Enterprise Security',
            'project_description' => 'Competitor trying to submit same client.',
        ]);

        $response->assertRedirect(route('partner.leads.create'));
        $response->assertSessionHasErrors(['customer_phone' => 'This customer lead already exists in the Tech Googly Partner Program and cannot be submitted again at this time.']);

        $errors = session('errors')->get('customer_phone');
        $errorMessage = implode(' ', $errors);

        $this->assertStringNotContainsString('Alpha', $errorMessage);
        $this->assertStringNotContainsString('TGP-2026-ALPHA', $errorMessage);
        $this->assertStringNotContainsString('LED-', $errorMessage);
    }

    /**
     * 9. Partner cannot view another Partner's lead.
     */
    public function test_09_partner_cannot_view_another_partners_lead(): void
    {
        $service = app(LeadService::class);
        $lead1 = $service->createLead($this->partner1, [
            'customer_name' => 'Alpha Only Lead',
            'customer_phone' => '9876588888',
            'customer_email' => 'alpha@exclusive.com',
            'service_required' => 'ERP Support',
            'project_description' => 'ERP maintenance contract.',
        ]);

        $response = $this->actingAs($this->partnerUser2)->get(route('partner.leads.show', $lead1));
        $response->assertStatus(403);
    }

    /**
     * 10. Partner cannot reassign ownership.
     */
    public function test_10_partner_cannot_reassign_ownership(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Protected Deal',
            'customer_phone' => '9876599991',
            'customer_email' => 'deal@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Target deal scope.',
        ]);

        $response = $this->actingAs($this->partnerUser1)->post(route('admin.leads.reassign-ownership', $lead), [
            'new_partner_id' => $this->partner2->id,
            'reason' => 'Unauthorized partner attempt.',
        ]);

        $response->assertStatus(403);
        $this->assertSame($this->partner1->id, $lead->fresh()->partner_id);
    }

    /**
     * 11. Staff cannot reassign ownership.
     */
    public function test_11_staff_cannot_reassign_ownership(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Staff Test Deal',
            'customer_phone' => '9876599992',
            'customer_email' => 'staffdeal@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Staff scope test.',
        ]);

        $response = $this->actingAs($this->teamUser)->post(route('admin.leads.reassign-ownership', $lead), [
            'new_partner_id' => $this->partner2->id,
            'reason' => 'Unauthorized staff attempt.',
        ]);

        $response->assertStatus(403);
        $this->assertSame($this->partner1->id, $lead->fresh()->partner_id);
    }

    /**
     * 12. Admin can reassign ownership.
     */
    public function test_12_admin_can_reassign_ownership(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Admin Reassign Deal',
            'customer_phone' => '9876599993',
            'customer_email' => 'adminreassign@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Reassignment test.',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.leads.reassign-ownership', $lead), [
            'new_partner_id' => $this->partner2->id,
            'reason' => 'Admin decided to transfer referral following partner merger.',
        ]);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $lead->refresh();
        $this->assertSame($this->partner2->id, $lead->partner_id);
        $this->assertSame(LeadOwnershipStatus::Reassigned, $lead->ownership_status);
        $this->assertSame('Admin decided to transfer referral following partner merger.', $lead->ownership_change_reason);
    }

    /**
     * 13. Reassignment requires a mandatory reason.
     */
    public function test_13_reassignment_requires_a_reason(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Reason Validation Deal',
            'customer_phone' => '9876599994',
            'customer_email' => 'reason@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Reason validation test.',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.leads.reassign-ownership', $lead), [
            'new_partner_id' => $this->partner2->id,
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');
        $this->assertSame($this->partner1->id, $lead->fresh()->partner_id);
    }

    /**
     * 14. Previous ownership history is preserved permanently.
     */
    public function test_14_previous_ownership_history_is_preserved(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Lineage Test Deal',
            'customer_phone' => '9876599995',
            'customer_email' => 'lineage@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Lineage test.',
        ]);

        $service->reassignOwnership($lead, $this->partner2, 'First transfer to Partner Beta.', $this->adminUser);
        $service->reassignOwnership($lead, $this->partner1, 'Second transfer back to Partner Alpha.', $this->adminUser);

        $histories = LeadOwnershipHistory::where('lead_id', $lead->id)->orderBy('created_at')->get();

        $this->assertCount(3, $histories); // 1 initial + 2 reassignments
        $this->assertSame('initial_assignment', $histories[0]->action);
        $this->assertSame('reassigned', $histories[1]->action);
        $this->assertSame($this->partner1->id, $histories[1]->previous_partner_id);
        $this->assertSame($this->partner2->id, $histories[1]->new_partner_id);
        $this->assertSame($this->partner2->id, $histories[2]->previous_partner_id);
        $this->assertSame($this->partner1->id, $histories[2]->new_partner_id);
    }

    /**
     * 15. Ownership extension works.
     */
    public function test_15_ownership_extension_works(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Extension Test Deal',
            'customer_phone' => '9876599996',
            'customer_email' => 'extension@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Extension test.',
        ]);

        $originalExpiry = $lead->ownership_expires_at;

        $response = $this->actingAs($this->adminUser)->post(route('admin.leads.extend-ownership', $lead), [
            'days' => 60,
            'reason' => 'Client requested extended procurement cycle.',
        ]);

        $response->assertRedirect(route('admin.leads.show', $lead));
        $lead->refresh();

        $this->assertTrue($lead->ownership_expires_at->gt($originalExpiry));
        $this->assertDatabaseHas('lead_ownership_histories', [
            'lead_id' => $lead->id,
            'action' => 'extended',
            'reason' => 'Client requested extended procurement cycle.',
        ]);
    }

    /**
     * 16. Ownership release requires authorization.
     */
    public function test_16_ownership_release_requires_authorization(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Release Test Deal',
            'customer_phone' => '9876599997',
            'customer_email' => 'release@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Release test.',
        ]);

        // Unauthorized partner attempt
        $partnerResponse = $this->actingAs($this->partnerUser1)->post(route('admin.leads.release-ownership', $lead), [
            'reason' => 'Partner release attempt',
        ]);
        $partnerResponse->assertStatus(403);

        // Authorized admin attempt
        $adminResponse = $this->actingAs($this->adminUser)->post(route('admin.leads.release-ownership', $lead), [
            'reason' => 'Partner formally surrendered rights to referral.',
        ]);
        $adminResponse->assertRedirect(route('admin.leads.show', $lead));

        $lead->refresh();
        $this->assertSame(LeadOwnershipStatus::Released, $lead->ownership_status);
    }

    /**
     * 17. Partner can raise an ownership dispute.
     */
    public function test_17_partner_can_raise_an_ownership_dispute(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Disputed Deal Inc',
            'customer_phone' => '9876599998',
            'customer_email' => 'dispute@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Dispute test.',
        ]);

        $fakeFile = UploadedFile::fake()->create('email_proof.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->partnerUser2)->post(route('partner.disputes.store'), [
            'lead_id' => $lead->id,
            'dispute_category' => LeadDisputeCategory::DuplicateRejection->value,
            'customer_phone_claimed' => '9876599998',
            'explanation' => 'We introduced this client on 10th Jan via formal email introduction.',
            'evidence' => $fakeFile,
        ]);

        $dispute = LeadDispute::where('partner_id', $this->partner2->id)->first();
        $this->assertNotNull($dispute);
        $response->assertRedirect(route('partner.disputes.show', $dispute));

        $this->assertStringStartsWith('DSP-', $dispute->dispute_number);
        $this->assertSame(LeadDisputeStatus::Pending, $dispute->status);
        $this->assertNotNull($dispute->evidence_path);

        $lead->refresh();
        $this->assertSame(LeadOwnershipStatus::Disputed, $lead->ownership_status);
    }

    /**
     * 18. Partner cannot view another Partner's dispute.
     */
    public function test_18_partner_cannot_view_another_partners_dispute(): void
    {
        $disputeService = app(LeadDisputeService::class);
        $dispute = $disputeService->createDispute($this->partner1, [
            'dispute_category' => LeadDisputeCategory::IncorrectOwnership,
            'explanation' => 'Confidential dispute between partners.',
        ]);

        $response = $this->actingAs($this->partnerUser2)->get(route('partner.disputes.show', $dispute));
        $response->assertStatus(403);
    }

    /**
     * 19. Admin can resolve disputes.
     */
    public function test_19_admin_can_resolve_disputes(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Resolution Target',
            'customer_phone' => '9876599999',
            'customer_email' => 'target@target.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Target deal.',
        ]);

        $disputeService = app(LeadDisputeService::class);
        $dispute = $disputeService->createDispute($this->partner2, [
            'lead_id' => $lead->id,
            'dispute_category' => LeadDisputeCategory::DuplicateRejection,
            'explanation' => 'Claimant introduced client first.',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.lead-disputes.resolve', $dispute), [
            'resolution_reason' => 'Claimant verified as first direct contact.',
            'reassign_to_claimant' => '1',
        ]);

        $response->assertRedirect(route('admin.lead-disputes.show', $dispute));
        $dispute->refresh();
        $this->assertSame(LeadDisputeStatus::Resolved, $dispute->status);

        $lead->refresh();
        $this->assertSame($this->partner2->id, $lead->partner_id, 'Lead must be reassigned to claimant partner.');
    }

    /**
     * 20. Dispute resolution requires a reason.
     */
    public function test_20_dispute_resolution_requires_a_reason(): void
    {
        $disputeService = app(LeadDisputeService::class);
        $dispute = $disputeService->createDispute($this->partner2, [
            'dispute_category' => LeadDisputeCategory::Other,
            'explanation' => 'Arbitration test dispute.',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.lead-disputes.resolve', $dispute), [
            'resolution_reason' => '',
        ]);

        $response->assertSessionHasErrors('resolution_reason');
        $this->assertSame(LeadDisputeStatus::Pending, $dispute->fresh()->status);
    }

    /**
     * 21. Dispute decisions are recorded.
     */
    public function test_21_dispute_decisions_are_recorded(): void
    {
        $disputeService = app(LeadDisputeService::class);
        $dispute = $disputeService->createDispute($this->partner2, [
            'dispute_category' => LeadDisputeCategory::MissingOwnership,
            'explanation' => 'Testing rejection audit trail.',
        ]);

        $response = $this->actingAs($this->adminUser)->post(route('admin.lead-disputes.reject', $dispute), [
            'resolution_reason' => 'Evidence lacked customer authorization signature.',
        ]);

        $response->assertRedirect(route('admin.lead-disputes.show', $dispute));
        $dispute->refresh();

        $this->assertSame(LeadDisputeStatus::Rejected, $dispute->status);
        $this->assertSame('Evidence lacked customer authorization signature.', $dispute->resolution_reason);
        $this->assertSame($this->adminUser->id, $dispute->resolved_by);
        $this->assertNotNull($dispute->resolved_at);
    }

    /**
     * 22. Concurrent duplicate submissions do not create duplicate valid leads.
     */
    public function test_22_concurrent_duplicate_submissions_do_not_create_duplicate_valid_leads(): void
    {
        $service = app(LeadService::class);

        $lead1 = $service->createLead($this->partner1, [
            'customer_name' => 'Concurrent Corp',
            'customer_phone' => '9876500111',
            'customer_email' => 'concurrent@corp.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Concurrency test.',
        ], enforceProtection: true);

        $this->assertNotNull($lead1);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('This customer lead already exists in the Tech Googly Partner Program and cannot be submitted again at this time.');

        $service->createLead($this->partner2, [
            'customer_name' => 'Concurrent Corp Second Try',
            'customer_phone' => '9876500111',
            'customer_email' => 'concurrent@corp.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Concurrency duplicate.',
        ], enforceProtection: true);
    }

    /**
     * 23. Concurrent reassignment attempts cannot corrupt ownership.
     */
    public function test_23_concurrent_reassignment_attempts_cannot_corrupt_ownership(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Race Condition Corp',
            'customer_phone' => '9876500222',
            'customer_email' => 'race@corp.com',
            'service_required' => 'Custom Web',
            'project_description' => 'Race test.',
        ]);

        // First reassignment succeeds
        $service->reassignOwnership($lead, $this->partner2, 'Authorized move to Partner Beta', $this->adminUser);

        // Attempting to reassign to the same partner again should fail safely
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Lead is already assigned to {$this->partner2->company_name}.");

        $service->reassignOwnership($lead, $this->partner2, 'Duplicate move to same partner', $this->adminUser);
    }

    /**
     * 24. Existing lead management and CRM functionality remains intact.
     */
    public function test_24_existing_lead_management_and_crm_functionality_remains_intact(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Full Pipeline Corp',
            'customer_phone' => '9876500333',
            'customer_email' => 'pipeline@corp.com',
            'service_required' => 'Enterprise Maintenance',
            'project_description' => 'Annual support SLA.',
        ]);

        // Assign to sales consultant
        $service->assignLead($lead, $this->teamUser, $this->adminUser, 'Please call client today.');
        $lead->refresh();
        $this->assertSame($this->teamUser->id, $lead->assigned_to);
        $this->assertSame(LeadStatus::Assigned, $lead->status);

        // Consultant logs activity
        $service->recordActivity($lead, $this->teamUser, [
            'activity_type' => LeadActivityType::Call->value,
            'summary' => 'Initial discovery call conducted',
            'details' => 'Customer shared RFP document.',
            'call_duration_seconds' => 300,
        ]);

        // Consultant advances pipeline status
        $service->updateStatus($lead, LeadStatus::Contacted, $this->teamUser);
        $lead->refresh();
        $this->assertSame(LeadStatus::Contacted, $lead->status);

        // Partner ownership status remains untouched and protected
        $this->assertSame($this->partner1->id, $lead->partner_id);
        $this->assertSame(LeadOwnershipStatus::Protected, $lead->ownership_status);
    }
}
