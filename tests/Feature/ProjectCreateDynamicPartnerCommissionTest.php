<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\FixedCommissionFrequency;
use App\Enums\KycStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectPaymentType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AgreementTemplate;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProjectCreateDynamicPartnerCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUserA;
    protected Partner $partnerA;
    protected User $partnerUserB;
    protected Partner $partnerB;
    protected Customer $customer;
    protected CommissionPolicy $globalPolicy;
    protected CommissionService $commissionService;
    protected ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);
        $this->projectService = app(ProjectService::class);

        SystemSetting::set('default_commission_rate', '10.00');

        // Admin User
        $this->adminUser = User::create([
            'name'     => 'Admin User',
            'email'    => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Active,
        ]);

        // Global Active Policy
        $this->globalPolicy = CommissionPolicy::updateOrCreate(
            ['policy_code' => 'POL-DEF-V1'],
            [
                'name'             => 'Platform Default Policy V1',
                'version'          => 1,
                'commission_type'  => CommissionType::Percentage,
                'commission_value' => 10.00,
                'effective_from'   => '2026-01-01',
                'is_default'       => true,
                'status'           => 'active',
                'created_by'       => $this->adminUser->id,
            ]
        );

        // Partner A (Standard Partner with default 10%)
        $this->partnerUserA = User::create([
            'name'     => 'Partner A User',
            'email'    => 'partnera@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->partnerA = Partner::create([
            'user_id'                 => $this->partnerUserA->id,
            'partner_code'            => 'TGP-2026-PARTA',
            'company_name'            => 'Partner Alpha Services',
            'phone'                   => '9800000001',
            'partner_type'            => 'referral',
            'kyc_status'              => KycStatus::Approved,
            'bank_verification_status'=> BankVerificationStatus::Verified,
            'bank_account_name'       => 'Partner Alpha Services',
            'bank_account_number'     => '111122223333',
            'bank_ifsc'               => 'HDFC0001111',
            'default_commission_rate' => 10.00,
        ]);

        // Partner B (Has assigned custom agreement policy with 15%)
        $this->partnerUserB = User::create([
            'name'     => 'Partner B User',
            'email'    => 'partnerb@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->partnerB = Partner::create([
            'user_id'                 => $this->partnerUserB->id,
            'partner_code'            => 'TGP-2026-PARTB',
            'company_name'            => 'Partner Beta Digital',
            'phone'                   => '9800000002',
            'partner_type'            => 'referral',
            'kyc_status'              => KycStatus::Approved,
            'bank_verification_status'=> BankVerificationStatus::Verified,
            'bank_account_name'       => 'Partner Beta Digital',
            'bank_account_number'     => '444455556666',
            'bank_ifsc'               => 'ICIC0002222',
            'default_commission_rate' => 15.00,
        ]);

        // Customer
        $this->customer = Customer::create([
            'customer_code'    => 'CUST-001',
            'name'             => 'John Doe',
            'company_name'     => 'Acme Enterprises',
            'email'            => 'john@acme.com',
            'phone'            => '9876543210',
            'first_partner_id' => $this->partnerA->id,
        ]);
    }

    /**
     * Helper to create an accepted partner agreement
     */
    protected function createAcceptedAgreement(Partner $partner, CommissionPolicy $policy): PartnerAgreement
    {
        $template = AgreementTemplate::firstOrCreate(
            ['template_code' => 'TPL-STD-001'],
            [
                'title'          => 'Standard Referral Agreement',
                'version'        => 1,
                'content'        => '<p>Standard Agreement Terms</p>',
                'status'         => 'active',
                'is_active'      => true,
                'effective_from' => '2026-01-01',
                'created_by'     => $this->adminUser->id,
            ]
        );

        return PartnerAgreement::create([
            'agreement_number'          => 'AGR-' . $partner->partner_code . '-V1',
            'partner_id'                => $partner->id,
            'template_id'               => $template->id,
            'template_version'          => 1,
            'commission_policy_id'      => $policy->id,
            'commission_policy_version' => $policy->version,
            'commission_rate_snapshot'  => $policy->commission_value,
            'commission_type_snapshot'  => $policy->commission_type->value,
            'pdf_path'                  => 'agreements/dummy.pdf',
            'content_snapshot'          => '<p>Standard Agreement Terms Snapshot</p>',
            'status'                    => AgreementStatus::Accepted,
            'issue_date'                => now()->toDateString(),
            'effective_date'            => now()->toDateString(),
            'accepted_at'               => now(),
            'accepted_ip'               => '127.0.0.1',
            'created_by'                => $this->adminUser->id,
            'version'                   => 1,
        ]);
    }

    /**
     * Test 1: AJAX endpoint returns partner commission details.
     */
    public function test_ajax_endpoint_returns_applicable_commission_details(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.projects.partner-commission', ['partner_id' => $this->partnerA->id]));

        $response->assertOk()
            ->assertJson([
                'status'     => 'success',
                'partner_id' => $this->partnerA->id,
                'source'     => 'global_policy',
            ])
            ->assertJsonStructure([
                'status',
                'partner_id',
                'commission_type',
                'commission_rate',
                'commission_rate_formatted',
                'source',
                'source_label',
                'policy_id',
                'policy_code',
                'policy_name',
            ]);
    }

    /**
     * Test 2: AJAX endpoint returns global default when no partner_id is given.
     */
    public function test_ajax_endpoint_returns_global_default_when_no_partner_id(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.projects.partner-commission'));

        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'partner_id'      => null,
                'commission_rate' => 10.00,
                'source'          => 'global_policy',
            ]);
    }

    /**
     * Test 3: Partner-specific agreement policy takes precedence over global policy.
     */
    public function test_partner_specific_agreement_policy_takes_precedence(): void
    {
        $customPolicy = CommissionPolicy::create([
            'policy_code'      => 'POL-SPEC-V1',
            'name'             => 'Special Partner Tier Policy',
            'version'          => 1,
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 15.00,
            'effective_from'   => '2026-01-01',
            'is_default'       => false,
            'status'           => 'active',
            'created_by'       => $this->adminUser->id,
        ]);

        $this->createAcceptedAgreement($this->partnerB, $customPolicy);

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('admin.projects.partner-commission', ['partner_id' => $this->partnerB->id]));

        $response->assertOk()
            ->assertJson([
                'status'          => 'success',
                'partner_id'      => $this->partnerB->id,
                'commission_rate' => 15.00,
                'source'          => 'partner_policy',
                'policy_code'     => 'POL-SPEC-V1',
            ]);

        // Creating project in default mode assigns this authoritative 15%
        $postData = [
            'customer_id'        => $this->customer->id,
            'partner_id'         => $this->partnerB->id,
            'project_title'      => 'Beta Cloud Migration',
            'payment_type'       => 'single',
            'final_deal_value'   => 100000.00,
            'status'             => 'in_progress',
            'project_description'=> 'Full infrastructure migration',
            'commission_model'   => 'global_default',
        ];

        $postResponse = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $postResponse->assertRedirect();

        $project = Project::where('project_title', 'Beta Cloud Migration')->firstOrFail();
        $resolved = $this->commissionService->resolveCommissionPolicyForProject($project);
        $this->assertEquals(15.00, (float) $resolved['rate_applied']);
        $this->assertEquals($customPolicy->id, $project->commission_policy_id);
        $this->assertEquals('global_default', $project->commission_model);
    }

    /**
     * Test 4: Partner agreement with snapshot rate is used when agreement has snapshot terms.
     */
    public function test_partner_agreement_snapshot_rate_is_used(): void
    {
        $template = AgreementTemplate::firstOrCreate(
            ['template_code' => 'TPL-STD-001'],
            [
                'title'          => 'Standard Referral Agreement',
                'version'        => 1,
                'content'        => '<p>Standard Agreement Terms</p>',
                'status'         => 'active',
                'is_active'      => true,
                'effective_from' => '2026-01-01',
                'created_by'     => $this->adminUser->id,
            ]
        );

        PartnerAgreement::create([
            'agreement_number'          => 'AGR-' . $this->partnerB->partner_code . '-SNAP',
            'partner_id'                => $this->partnerB->id,
            'template_id'               => $template->id,
            'template_version'          => 1,
            'commission_policy_id'      => null,
            'commission_policy_version' => null,
            'commission_rate_snapshot'  => 14.50,
            'commission_type_snapshot'  => 'percentage',
            'pdf_path'                  => 'agreements/dummy.pdf',
            'content_snapshot'          => '<p>Snapshot Terms</p>',
            'status'                    => AgreementStatus::Accepted,
            'issue_date'                => now()->toDateString(),
            'effective_date'            => now()->toDateString(),
            'accepted_at'               => now(),
            'accepted_ip'               => '127.0.0.1',
            'created_by'                => $this->adminUser->id,
            'version'                   => 1,
        ]);

        $resolved = $this->commissionService->resolveDefaultPolicyForPartner($this->partnerB);

        $this->assertEquals(14.50, (float) $resolved['rate_applied']);
        $this->assertEquals('partner_policy', $resolved['source']);
        $this->assertEquals('Partner Agreement Snapshot', $resolved['policy_name']);

        $postData = [
            'customer_id'        => $this->customer->id,
            'partner_id'         => $this->partnerB->id,
            'project_title'      => 'Partner B Direct Rate Project',
            'payment_type'       => 'single',
            'final_deal_value'   => 50000.00,
            'status'             => 'in_progress',
            'project_description'=> 'Explicit partner rate project',
            'commission_model'   => 'global_default',
        ];

        $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $project = Project::where('project_title', 'Partner B Direct Rate Project')->firstOrFail();

        $resolved = $this->commissionService->resolveCommissionPolicyForProject($project);
        $this->assertEquals(14.50, (float) $resolved['rate_applied']);
    }

    /**
     * Test 5: Default mode ignores frontend-submitted custom rate and enforces authoritative resolution.
     */
    public function test_default_mode_enforces_authoritative_resolution_ignoring_frontend_tampering(): void
    {
        // Admin creates project in global_default mode, but malicious/tampered form submits 25% custom rate
        $postData = [
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'project_title'          => 'Tamper Test Project',
            'payment_type'           => 'single',
            'final_deal_value'       => 50000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Authoritative rate enforcement test',
            'commission_model'       => 'global_default',
            'custom_commission_rate' => 25.00, // Should be ignored because model is global_default
        ];

        $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $project = Project::where('project_title', 'Tamper Test Project')->firstOrFail();

        // Must be partner A's authoritative 10%, NOT the 25%
        $resolved = $this->commissionService->resolveCommissionPolicyForProject($project);
        $this->assertEquals(10.00, (float) $resolved['rate_applied']);
        $this->assertEquals($this->globalPolicy->id, $project->commission_policy_id);
        $this->assertNull($project->custom_commission_rate);
    }

    /**
     * Test 6: Custom percentage override saves correctly and sets frequency to null.
     */
    public function test_custom_percentage_override_saves_correctly_and_nulls_frequency(): void
    {
        $postData = [
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'project_title'          => 'Custom Percentage Project',
            'payment_type'           => 'single',
            'final_deal_value'       => 80000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Custom percentage terms test',
            'commission_model'       => 'custom',
            'custom_commission_type' => 'percentage',
            'custom_commission_rate' => 18.50,
            'custom_fixed_frequency' => 'per_payment', // Should be cleaned to null for percentage
            'commission_notes'       => 'Negotiated strategic override',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $response->assertRedirect();

        $project = Project::where('project_title', 'Custom Percentage Project')->firstOrFail();

        $this->assertEquals('custom', $project->commission_model);
        $this->assertEquals(CommissionType::Percentage, $project->agreed_commission_type);
        $this->assertEquals(18.50, (float) $project->agreed_commission_rate);
        $this->assertEquals(18.50, (float) $project->custom_commission_rate);
        $this->assertNull($project->custom_fixed_frequency);
        $this->assertEquals('Negotiated strategic override', $project->commission_notes);
    }

    /**
     * Test 7: Custom percentage override rejects rates greater than 100%.
     */
    public function test_custom_percentage_override_rejects_rate_over_100(): void
    {
        $postData = [
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'project_title'          => 'Invalid High Rate Project',
            'payment_type'           => 'single',
            'final_deal_value'       => 80000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Validation error test',
            'commission_model'       => 'custom',
            'custom_commission_type' => 'percentage',
            'custom_commission_rate' => 125.00, // Invalid!
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $response->assertSessionHasErrors(['custom_commission_rate']);
    }

    /**
     * Test 8: Custom fixed amount override saves correctly with frequency.
     */
    public function test_custom_fixed_amount_override_saves_correctly(): void
    {
        $postData = [
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'project_title'          => 'Custom Fixed Project',
            'payment_type'           => 'single',
            'final_deal_value'       => 200000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Custom fixed terms test',
            'commission_model'       => 'custom',
            'custom_commission_type' => 'fixed',
            'custom_commission_rate' => 7500.00,
            'custom_fixed_frequency' => 'per_project',
            'commission_notes'       => 'Lump sum ₹7,500 referral bonus',
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $response->assertRedirect();

        $project = Project::where('project_title', 'Custom Fixed Project')->firstOrFail();

        $this->assertEquals('custom', $project->commission_model);
        $this->assertEquals(CommissionType::Fixed, $project->agreed_commission_type);
        $this->assertEquals(7500.00, (float) $project->agreed_commission_rate);
        $this->assertEquals(7500.00, (float) $project->custom_commission_rate);
        $this->assertEquals(FixedCommissionFrequency::PerProject, $project->custom_fixed_frequency);
    }

    /**
     * Test 9: Custom fixed amount override requires valid frequency.
     */
    public function test_custom_fixed_amount_override_requires_frequency(): void
    {
        $postData = [
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'project_title'          => 'Missing Frequency Fixed Project',
            'payment_type'           => 'single',
            'final_deal_value'       => 200000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Missing frequency validation test',
            'commission_model'       => 'custom',
            'custom_commission_type' => 'fixed',
            'custom_commission_rate' => 5000.00,
            // Omitting custom_fixed_frequency
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $response->assertSessionHasErrors(['custom_fixed_frequency']);
    }

    /**
     * Test 10: Inactive or non-existent commission policy reference is rejected.
     */
    public function test_inactive_commission_policy_is_rejected(): void
    {
        $inactivePolicy = CommissionPolicy::create([
            'policy_code'      => 'POL-INACT-V1',
            'name'             => 'Archived Inactive Policy',
            'version'          => 1,
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 8.00,
            'effective_from'   => '2025-01-01',
            'is_default'       => false,
            'status'           => 'archived',
            'created_by'       => $this->adminUser->id,
        ]);

        $postData = [
            'customer_id'          => $this->customer->id,
            'partner_id'           => $this->partnerA->id,
            'project_title'        => 'Inactive Policy Project',
            'payment_type'         => 'single',
            'final_deal_value'     => 50000.00,
            'status'               => 'in_progress',
            'project_description'  => 'Reject inactive policy test',
            'commission_policy_id' => $inactivePolicy->id,
        ];

        $response = $this->actingAs($this->adminUser)->post(route('admin.projects.store'), $postData);
        $response->assertSessionHasErrors(['commission_policy_id']);
    }

    /**
     * Test 11: Project Edit retains configuration or updates on partner change.
     */
    public function test_project_edit_updates_policy_on_partner_change(): void
    {
        // 1. Create project with Partner A (10%)
        $project = Project::create([
            'project_code'           => 'PRJ-2026-EDIT1',
            'project_title'          => 'Original Partner A Project',
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'client_name'            => 'John Doe',
            'client_company'         => 'Acme Enterprises',
            'payment_type'           => ProjectPaymentType::Single,
            'final_deal_value'       => 100000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'commission_model'       => 'global_default',
            'status'                 => ProjectStatus::InProgress,
            'project_description'    => 'Initial setup',
        ]);

        // Partner B has a 16% accepted agreement
        $policyB = CommissionPolicy::create([
            'policy_code'      => 'POL-B-16',
            'name'             => 'Partner B 16% Policy',
            'version'          => 1,
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 16.00,
            'effective_from'   => '2026-01-01',
            'is_default'       => false,
            'status'           => 'active',
            'created_by'       => $this->adminUser->id,
        ]);
        $this->createAcceptedAgreement($this->partnerB, $policyB);

        // Edit project to assign Partner B
        $updateData = [
            'project_title'       => 'Updated Partner B Project',
            'partner_id'          => $this->partnerB->id,
            'payment_type'        => 'single',
            'final_deal_value'    => 100000.00,
            'status'              => 'in_progress',
            'project_description' => 'Updated setup',
            'commission_model'    => 'global_default',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('admin.projects.update', $project), $updateData);

        $response->assertRedirect();

        $project->refresh();
        $this->assertEquals($this->partnerB->id, $project->partner_id);
        $resolved = $this->commissionService->resolveCommissionPolicyForProject($project);
        $this->assertEquals(16.00, (float) $resolved['rate_applied']);
        $this->assertEquals($policyB->id, $project->commission_policy_id);
    }

    /**
     * Test 12: Historical commissions remain unchanged when project terms change.
     */
    public function test_historical_commissions_remain_unchanged_when_terms_change(): void
    {
        // 1. Create project with 10%
        $project = Project::create([
            'project_code'           => 'PRJ-2026-HIST1',
            'project_title'          => 'Historical Integrity Project',
            'customer_id'            => $this->customer->id,
            'partner_id'             => $this->partnerA->id,
            'client_name'            => 'John Doe',
            'client_company'         => 'Acme Enterprises',
            'payment_type'           => ProjectPaymentType::Single,
            'final_deal_value'       => 100000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'commission_model'       => 'global_default',
            'status'                 => ProjectStatus::InProgress,
            'project_description'    => 'Testing commission immutability',
        ]);

        // 2. Customer payment of ₹50,000 verified
        $payment = CustomerPayment::create([
            'payment_number' => 'PAY-HIST-001',
            'customer_id'    => $this->customer->id,
            'project_id'     => $project->id,
            'partner_id'     => $project->partner_id,
            'payment_type'   => CustomerPaymentType::Milestone,
            'amount'         => 50000.00,
            'currency'       => 'INR',
            'payment_date'   => now()->toDateString(),
            'payment_method' => PaymentMethod::BankTransfer,
            'status'         => CustomerPaymentStatus::Recorded,
        ]);

        // 3. Generate commission (10% of 50,000 = ₹5,000)
        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(5000.00, (float) $commission->amount);
        $this->assertEquals(10.00, (float) $commission->rate_applied);
        $this->assertEquals(CommissionStatus::Payable, $commission->status);

        // 4. Update project terms to 20% custom override
        $updateData = [
            'project_title'          => 'Historical Integrity Project',
            'partner_id'             => $this->partnerA->id,
            'payment_type'           => 'single',
            'final_deal_value'       => 100000.00,
            'status'                 => 'in_progress',
            'project_description'    => 'Testing commission immutability updated',
            'commission_model'       => 'custom',
            'custom_commission_type' => 'percentage',
            'custom_commission_rate' => 20.00,
        ];

        $this->actingAs($this->adminUser)->put(route('admin.projects.update', $project), $updateData);

        // 5. Verify the historical commission record is untouched!
        $commission->refresh();
        $this->assertEquals(5000.00, (float) $commission->amount);
        $this->assertEquals(10.00, (float) $commission->rate_applied);
        $this->assertEquals(CommissionStatus::Payable, $commission->status);

        // 6. New payment receives the updated 20%
        $payment2 = CustomerPayment::create([
            'payment_number' => 'PAY-HIST-002',
            'customer_id'    => $this->customer->id,
            'project_id'     => $project->id,
            'partner_id'     => $project->partner_id,
            'payment_type'   => CustomerPaymentType::Milestone,
            'amount'         => 50000.00,
            'currency'       => 'INR',
            'payment_date'   => now()->toDateString(),
            'payment_method' => PaymentMethod::BankTransfer,
            'status'         => CustomerPaymentStatus::Recorded,
        ]);

        $commission2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->adminUser->id);
        $this->assertEquals(10000.00, (float) $commission2->amount); // 20% of 50,000
        $this->assertEquals(20.00, (float) $commission2->rate_applied);
    }

    /**
     * Test 13: Non-admins cannot access partner commission resolution AJAX endpoint.
     */
    public function test_non_admins_cannot_access_ajax_endpoint(): void
    {
        // Unauthenticated guest
        $this->getJson(route('admin.projects.partner-commission'))
            ->assertUnauthorized();

        // Partner user
        $this->actingAs($this->partnerUserA)
            ->getJson(route('admin.projects.partner-commission'))
            ->assertForbidden();
    }
}
