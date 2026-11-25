<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\FixedCommissionFrequency;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AgreementTemplate;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CommissionService;
use App\Support\DateTimeHelper;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Batch1TimezoneAndCommissionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teamMember;
    private User $partnerUser;
    private Partner $partner;
    private CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);

        $this->admin = User::create([
            'name'              => 'Admin Lead Officer',
            'email'             => 'admin_batch1@techgoogly.com',
            'password'          => Hash::make('SecretAdmin123!'),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->teamMember = User::create([
            'name'              => 'Staff Member',
            'email'             => 'staff_batch1@techgoogly.com',
            'password'          => Hash::make('SecretStaff123!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partnerUser = User::create([
            'name'              => 'Channel Partner Lead',
            'email'             => 'partner_batch1@techgoogly.com',
            'password'          => Hash::make('SecretPartner123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-2026-B1',
            'company_name'            => 'Alpha Cloud Solutions',
            'phone'                   => '9876543210',
            'default_commission_rate' => 10.00,
        ]);
    }

    /**
     * 1. Test application timezone is configured to Asia/Kolkata.
     */
    public function test_application_timezone_is_asia_kolkata(): void
    {
        $this->assertEquals('Asia/Kolkata', config('app.timezone'));
        $this->assertEquals('Asia/Kolkata', date_default_timezone_get());

        $now = now();
        $this->assertEquals('Asia/Kolkata', $now->timezoneName);
    }

    /**
     * 2. Test IST date and time formatting via DateTimeHelper.
     */
    public function test_ist_date_time_formatting_works_correctly(): void
    {
        $dt = Carbon::create(2026, 11, 23, 17, 30, 0, 'Asia/Kolkata');

        $this->assertEquals('23 Nov 2026', DateTimeHelper::formatDate($dt));
        $this->assertEquals('23/11/2026', DateTimeHelper::formatNumericDate($dt));
        $this->assertEquals('05:30 PM', DateTimeHelper::formatTime($dt));
        $this->assertEquals('05:30 PM IST', DateTimeHelper::formatTime($dt, true));
        $this->assertEquals('23 Nov 2026, 05:30 PM IST', DateTimeHelper::formatDateTime($dt, true));
        $this->assertEquals('23 Nov 2026, 05:30 PM', DateTimeHelper::formatDateTime($dt, false));

        // Indian Currency Formatting
        $this->assertEquals('₹2,50,000.00', DateTimeHelper::formatIndianCurrency(250000));
        $this->assertEquals('₹10,00,000.00', DateTimeHelper::formatIndianCurrency(1000000));
        $this->assertEquals('₹1,500.50', DateTimeHelper::formatIndianCurrency(1500.50));
    }

    /**
     * 3. Test that historical timestamps are not unintentionally shifted.
     */
    public function test_historical_timestamps_are_safely_preserved(): void
    {
        $historicalUtc = Carbon::create(2025, 6, 15, 12, 0, 0, 'UTC');
        $istEquivalent = DateTimeHelper::toIst($historicalUtc);

        $this->assertNotNull($istEquivalent);
        $this->assertEquals('Asia/Kolkata', $istEquivalent->timezoneName);
        // UTC 12:00 + 5:30 = IST 17:30
        $this->assertEquals('17:30', $istEquivalent->format('H:i'));
        $this->assertEquals('15 Jun 2025', DateTimeHelper::formatDate($historicalUtc));
    }

    /**
     * 4. Test baseline 10% default commission policy is seeded and preserved.
     */
    public function test_baseline_default_commission_policy_is_preserved(): void
    {
        $activeDefault = CommissionPolicy::getActiveDefault();

        $this->assertNotNull($activeDefault);
        $this->assertEquals('POL-DEF-V1', $activeDefault->policy_code);
        $this->assertEquals(CommissionType::Percentage, $activeDefault->commission_type);
        $this->assertEquals(10.00, $activeDefault->commission_value);
        $this->assertTrue($activeDefault->is_default);
        $this->assertEquals('active', $activeDefault->status);

        // CommissionService returns 10.00%
        $this->assertEquals(10.00, $this->commissionService->getDefaultCommissionRate());
    }

    /**
     * 5. Test Admin can create a new Commission Policy (Percentage and Fixed).
     */
    public function test_admin_can_create_commission_policies(): void
    {
        // A. Percentage Policy
        $response = $this->actingAs($this->admin)->post(route('admin.settings.commission-policies.store'), [
            'policy_code'      => 'POL-PARTNER-SILVER',
            'name'             => 'Silver Partner Tier Commission',
            'description'      => 'Standard tier for new partners.',
            'commission_type'  => 'percentage',
            'commission_value' => 12.50,
            'effective_from'   => '2026-11-01',
            'status'           => 'active',
            'is_default'       => '0',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('commission_policies', [
            'policy_code'      => 'POL-PARTNER-SILVER',
            'commission_value' => 12.50,
            'commission_type'  => 'percentage',
        ]);

        // B. Fixed Commission Policy with frequency
        $responseFixed = $this->actingAs($this->admin)->post(route('admin.settings.commission-policies.store'), [
            'policy_code'      => 'POL-FIXED-RETAINER',
            'name'             => 'Fixed Monthly Retainer Commission',
            'description'      => 'Fixed fee per verified payment milestone.',
            'commission_type'  => 'fixed',
            'commission_value' => 5000.00,
            'fixed_frequency'  => 'per_payment',
            'effective_from'   => '2026-11-01',
            'status'           => 'active',
            'is_default'       => '0',
        ]);

        $responseFixed->assertRedirect();
        $this->assertDatabaseHas('commission_policies', [
            'policy_code'      => 'POL-FIXED-RETAINER',
            'commission_value' => 5000.00,
            'fixed_frequency'  => 'per_payment',
        ]);
    }

    /**
     * 6. Test Policy version history and revision lineage.
     */
    public function test_policy_revision_lineage_and_versioning(): void
    {
        $v1 = CommissionPolicy::where('policy_code', 'POL-DEF-V1')->firstOrFail();

        $response = $this->actingAs($this->admin)->post(route('admin.settings.commission-policies.revision.store', $v1), [
            'name'             => 'Global Default Policy (v2)',
            'description'      => 'Upgraded standard commission rate to 12% for year 2027.',
            'commission_type'  => 'percentage',
            'commission_value' => 12.00,
            'effective_from'   => '2027-01-01',
            'is_default'       => '1',
            'notes'            => 'Annual inflation adjustment.',
        ]);

        $response->assertRedirect();

        $v1->refresh();
        $this->assertEquals('superseded', $v1->status);
        $this->assertEquals('2026-12-31', $v1->effective_until->toDateString());

        $v2 = CommissionPolicy::where('parent_policy_id', $v1->id)->firstOrFail();
        $this->assertEquals(2, $v2->version);
        $this->assertEquals(12.00, $v2->commission_value);
        $this->assertEquals('POL-DEF-V1-V2', $v2->policy_code);
    }

    /**
     * 7. Test effective date handling and isCurrentlyEffective check.
     */
    public function test_policy_effective_date_handling(): void
    {
        $futurePolicy = CommissionPolicy::create([
            'policy_code'      => 'POL-FUTURE-2030',
            'name'             => 'Future 2030 Tier',
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 15.00,
            'effective_from'   => '2030-01-01',
            'effective_until'  => '2030-12-31',
            'status'           => 'active',
            'is_default'       => false,
            'version'          => 1,
        ]);

        $this->assertFalse($futurePolicy->isCurrentlyEffective());

        $currentPolicy = CommissionPolicy::create([
            'policy_code'      => 'POL-CURRENT',
            'name'             => 'Current Active Promo',
            'commission_type'  => CommissionType::Percentage,
            'commission_value' => 11.00,
            'effective_from'   => now()->subDays(5)->toDateString(),
            'effective_until'  => now()->addDays(5)->toDateString(),
            'status'           => 'active',
            'is_default'       => false,
            'version'          => 1,
        ]);

        $this->assertTrue($currentPolicy->isCurrentlyEffective());
    }

    /**
     * 8. Test that overlapping active global default policies are strictly blocked.
     */
    public function test_overlapping_active_global_default_policies_are_blocked(): void
    {
        $this->expectException(DomainException::class);

        // Attempt to create another default policy starting today overlapping with existing v1
        $this->commissionService->createPolicy([
            'policy_code'      => 'POL-OVERLAP-ERR',
            'name'             => 'Conflicting Default Policy',
            'commission_type'  => 'percentage',
            'commission_value' => 15.00,
            'effective_from'   => now()->toDateString(),
            'is_default'       => true,
            'status'           => 'active',
        ], $this->admin->id);
    }

    /**
     * 9. Test historical commission records remain unchanged after default rate is updated.
     */
    public function test_historical_commission_records_remain_unchanged_after_rate_change(): void
    {
        // 1. Create Customer and Project
        $customer = Customer::create([
            'customer_code'    => 'CUST-B1-001',
            'name'             => 'Zenith Logistics',
            'phone'            => '9876543211',
            'first_partner_id' => $this->partner->id,
            'status'           => 'active',
        ]);

        $project = Project::create([
            'project_code'           => 'PRJ-B1-001',
            'customer_id'            => $customer->id,
            'partner_id'             => $this->partner->id,
            'client_name'            => $customer->name,
            'project_title'          => 'Cloud Fleet ERP',
            'project_description'    => 'Custom logistics ERP',
            'final_deal_value'       => 100000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 0.00, // 0 means use global default policy (10%)
            'status'                 => ProjectStatus::InProgress,
        ]);

        $payment1 = CustomerPayment::create([
            'payment_number' => 'CPAY-B1-0001',
            'project_id'     => $project->id,
            'customer_id'    => $customer->id,
            'partner_id'     => $this->partner->id,
            'payment_type'   => CustomerPaymentType::Milestone,
            'amount'         => 50000.00,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_date'   => now()->toDateString(),
            'status'         => CustomerPaymentStatus::Recorded,
        ]);

        // Verify Payment 1 -> Generates 10% commission = 5,000 INR
        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment1, $this->admin->id);
        $this->assertNotNull($comm1);
        $this->assertEquals(5000.00, $comm1->amount);
        $this->assertEquals(10.00, $comm1->rate_applied);

        // 2. Now Update the default commission rate to 15%
        $this->commissionService->updateDefaultCommissionRate(15.00, $this->admin->id);

        // Verify historical Commission 1 was NOT modified!
        $comm1->refresh();
        $this->assertEquals(5000.00, $comm1->amount);
        $this->assertEquals(10.00, $comm1->rate_applied);

        // 3. New payment under the new default rate generates 15% commission = 7,500 INR
        $payment2 = CustomerPayment::create([
            'payment_number' => 'CPAY-B1-0002',
            'project_id'     => $project->id,
            'customer_id'    => $customer->id,
            'partner_id'     => $this->partner->id,
            'payment_type'   => CustomerPaymentType::Final,
            'amount'         => 50000.00,
            'payment_method' => PaymentMethod::BankTransfer,
            'payment_date'   => now()->toDateString(),
            'status'         => CustomerPaymentStatus::Recorded,
        ]);

        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->admin->id);
        $this->assertNotNull($comm2);
        $this->assertEquals(7500.00, $comm2->amount);
        $this->assertEquals(15.00, $comm2->rate_applied);
    }

    /**
     * 10. Test fixed commission frequency configuration validation.
     */
    public function test_fixed_commission_requires_explicit_frequency(): void
    {
        // When Fixed commission is selected, omitting fixed_frequency is rejected
        $response = $this->actingAs($this->admin)->post(route('admin.settings.commission-policies.store'), [
            'policy_code'      => 'POL-FIXED-INVALID',
            'name'             => 'Fixed Fee Missing Frequency',
            'commission_type'  => 'fixed',
            'commission_value' => 5000.00,
            'fixed_frequency'  => '', // omitted
            'effective_from'   => '2026-11-01',
            'status'           => 'active',
        ]);

        $response->assertSessionHasErrors('fixed_frequency');
    }

    /**
     * 11. Test Agreement Template versioning, rendering and active toggle.
     */
    public function test_agreement_template_versioning_and_rendering(): void
    {
        $activeTpl = AgreementTemplate::getActiveTemplate();
        $this->assertNotNull($activeTpl);
        $this->assertEquals('TPL-AGR-V1', $activeTpl->template_code);
        $this->assertEquals('1.0', $activeTpl->version);

        // Test rendering with dynamic placeholders
        $rendered = $activeTpl->render([
            'agreement_number' => 'TGA-2026-9999',
            'effective_date'   => '23 Nov 2026',
            'company_name'     => 'Acme Software LLP',
            'partner_name'     => 'Sunil Roy',
            'partner_code'     => 'TGP-2026-0099',
            'commission_rate'  => '10.00%',
            'commission_type'  => 'Percentage',
        ]);

        $this->assertStringContainsString('TGA-2026-9999', $rendered);
        $this->assertStringContainsString('Acme Software LLP', $rendered);
        $this->assertStringContainsString('Sunil Roy', $rendered);
        $this->assertStringContainsString('10.00%', $rendered);

        // Create new template version 2.0
        $response = $this->actingAs($this->admin)->post(route('admin.settings.agreement-templates.store'), [
            'template_code'   => 'TPL-AGR-V2',
            'title'           => 'Enhanced Partnership Agreement 2027',
            'version'         => '2.0',
            'content'         => 'Agreement between Tech Googly and {partner_name}. Remuneration rate: {commission_rate}.',
            'status'          => 'active',
            'is_active'       => '1',
            'effective_from'  => '2027-01-01',
        ]);

        $response->assertRedirect();

        $activeTpl->refresh();
        $this->assertFalse($activeTpl->is_active);

        $newActive = AgreementTemplate::where('template_code', 'TPL-AGR-V2')->firstOrFail();
        $this->assertTrue($newActive->is_active);
    }

    /**
     * 12. Test Authorization: Staff and Partners cannot manage policies or templates.
     */
    public function test_staff_and_partner_cannot_access_policy_or_template_management(): void
    {
        // Staff Attempt
        $staffResponse = $this->actingAs($this->teamMember)->get(route('admin.settings.commission-policies.index'));
        $this->assertEquals(403, $staffResponse->status());

        $staffTplResponse = $this->actingAs($this->teamMember)->get(route('admin.settings.agreement-templates.index'));
        $this->assertEquals(403, $staffTplResponse->status());

        // Partner Attempt
        $partnerResponse = $this->actingAs($this->partnerUser)->get(route('admin.settings.commission-policies.index'));
        $this->assertEquals(403, $partnerResponse->status());

        $partnerTplResponse = $this->actingAs($this->partnerUser)->get(route('admin.settings.agreement-templates.index'));
        $this->assertEquals(403, $partnerTplResponse->status());
    }

    /**
     * 13. Test existing payment verification and payout functionality remains 100% operational.
     */
    public function test_existing_payment_verification_and_commission_ledger_works(): void
    {
        $customer = Customer::create([
            'customer_code'    => 'CUST-B1-002',
            'name'             => 'Prime Retailers',
            'phone'            => '9876543212',
            'first_partner_id' => $this->partner->id,
            'status'           => 'active',
        ]);

        $project = Project::create([
            'project_code'           => 'PRJ-B1-002',
            'customer_id'            => $customer->id,
            'partner_id'             => $this->partner->id,
            'client_name'            => $customer->name,
            'project_title'          => 'POS & Inventory Cloud',
            'project_description'    => 'Retail POS automation',
            'final_deal_value'       => 200000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 12.00, // Explicit project commission rate
            'status'                 => ProjectStatus::InProgress,
        ]);

        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-B1-0003',
            'project_id'     => $project->id,
            'customer_id'    => $customer->id,
            'partner_id'     => $this->partner->id,
            'payment_type'   => CustomerPaymentType::Milestone,
            'amount'         => 100000.00,
            'payment_method' => PaymentMethod::Upi,
            'payment_date'   => now()->toDateString(),
            'status'         => CustomerPaymentStatus::Recorded,
        ]);

        // Admin verifies payment via HTTP POST
        $response = $this->actingAs($this->admin)->post(route('admin.payments.verify', $payment));
        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals(CustomerPaymentStatus::Verified, $payment->status);
        $this->assertTrue($payment->commission_generated);

        $commission = Commission::where('customer_payment_id', $payment->id)->firstOrFail();
        $this->assertEquals(12000.00, $commission->amount);
        $this->assertEquals(12.00, $commission->rate_applied);
        $this->assertEquals(CommissionStatus::Payable, $commission->status);
    }
}
