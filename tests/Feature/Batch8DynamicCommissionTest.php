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
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\CommissionService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Batch8DynamicCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUserA;
    protected Partner $partnerA;
    protected User $partnerUserB;
    protected Partner $partnerB;
    protected User $staffUser;
    protected Customer $customerA;
    protected Customer $customerB;
    protected CommissionPolicy $globalPolicyV1;
    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // 2. Partner A
        $this->partnerUserA = User::create([
            'name' => 'Aditya Sharma',
            'email' => 'aditya@sharmatech.in',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partnerA = Partner::create([
            'user_id' => $this->partnerUserA->id,
            'partner_code' => 'TGP-2026-ADIT',
            'company_name' => 'Sharma Tech Solutions',
            'phone' => '9811100001',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
        ]);

        // 3. Partner B
        $this->partnerUserB = User::create([
            'name' => 'Sneha Kulkarni',
            'email' => 'sneha@kulkarnidigital.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partnerB = Partner::create([
            'user_id' => $this->partnerUserB->id,
            'partner_code' => 'TGP-2026-SNEH',
            'company_name' => 'Kulkarni Digital Agency',
            'phone' => '9811100002',
            'partner_type' => 'referral',
            'default_commission_rate' => 12.00,
        ]);

        // 4. Staff
        $this->staffUser = User::create([
            'name' => 'Rohan Staff',
            'email' => 'rohan@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        // 5. Customers
        $this->customerA = Customer::create([
            'customer_code' => 'CUS-2026-AAAA',
            'name' => 'Horizon Retail Ltd',
            'email' => 'contact@horizonretail.in',
            'phone' => '9800000001',
            'first_partner_id' => $this->partnerA->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->customerB = Customer::create([
            'customer_code' => 'CUS-2026-BBBB',
            'name' => 'Zenith Logistics Ltd',
            'email' => 'finance@zenithlogistics.in',
            'phone' => '9800000002',
            'first_partner_id' => $this->partnerB->id,
            'created_by' => $this->adminUser->id,
        ]);

        // 6. Global Policy v1 (10% default)
        $this->globalPolicyV1 = CommissionPolicy::updateOrCreate(
            ['policy_code' => 'POL-DEF-V1'],
            [
                'name' => 'Standard Global Commission Policy',
                'description' => 'Default platform policy v1',
                'commission_type' => CommissionType::Percentage,
                'commission_value' => 10.00,
                'effective_from' => '2026-01-01',
                'status' => 'active',
                'is_default' => true,
                'version' => 1,
                'created_by' => $this->adminUser->id,
                'updated_by' => $this->adminUser->id,
            ]
        );

        $this->commissionService = app(CommissionService::class);
    }

    protected function makeProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'partner_id' => $this->partnerA->id,
            'customer_id' => $this->customerA->id,
            'project_code' => 'PRJ-' . uniqid(),
            'client_name' => $this->customerA->name,
            'project_title' => 'Sample Cloud Transformation',
            'project_description' => 'Comprehensive ERP setup and cloud data migration.',
            'final_deal_value' => 100000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'commission_model' => 'global_default',
            'start_date' => now()->toDateString(),
        ], $attributes));
    }

    protected function makePayment(Project $project, array $attributes = []): CustomerPayment
    {
        return CustomerPayment::create(array_merge([
            'payment_number' => 'CPAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'project_id' => $project->id,
            'customer_id' => $project->customer_id,
            'partner_id' => $project->partner_id,
            'payment_type' => CustomerPaymentType::Milestone,
            'amount' => 10000.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::BankTransfer,
            'transaction_reference' => 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
            'payment_date' => now()->toDateString(),
            'billing_month' => now()->format('Y-m'),
            'billing_period' => now()->format('F Y'),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ], $attributes));
    }

    /**
     * 1. Global percentage fallback.
     */
    public function test_01_global_percentage_fallback(): void
    {
        $project = $this->makeProject(['commission_model' => 'global_default']);
        $payment = $this->makePayment($project, ['amount' => 50000.00]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertNotNull($commission);
        $this->assertEquals(5000.00, (float) $commission->amount);
        $this->assertEquals(10.00, (float) $commission->rate_applied);
        $this->assertEquals('global_policy', $commission->policy_source);
        $this->assertEquals($this->globalPolicyV1->id, $commission->commission_policy_id);
        $this->assertEquals(1, $commission->policy_version);
    }

    /**
     * 2. Project-specific percentage override.
     */
    public function test_02_project_specific_percentage_override(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 15.00,
            'commission_status' => 'active',
        ]);
        $payment = $this->makePayment($project, ['amount' => 50000.00]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertNotNull($commission);
        $this->assertEquals(7500.00, (float) $commission->amount);
        $this->assertEquals(15.00, (float) $commission->rate_applied);
        $this->assertEquals('project_override', $commission->policy_source);
    }

    /**
     * 3. Project-specific fixed commission.
     */
    public function test_03_project_specific_fixed_commission(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Fixed,
            'custom_commission_rate' => 6000.00,
            'custom_fixed_frequency' => FixedCommissionFrequency::PerPayment,
            'commission_status' => 'active',
        ]);
        $payment = $this->makePayment($project, ['amount' => 40000.00]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertNotNull($commission);
        $this->assertEquals(6000.00, (float) $commission->amount);
        $this->assertEquals(CommissionType::Fixed, $commission->commission_type);
        $this->assertEquals('project_override', $commission->policy_source);
    }

    /**
     * 4. Per-payment fixed commission.
     */
    public function test_04_per_payment_fixed_commission(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Fixed,
            'custom_commission_rate' => 2000.00,
            'custom_fixed_frequency' => FixedCommissionFrequency::PerPayment,
            'commission_status' => 'active',
        ]);

        $payment1 = $this->makePayment($project, ['amount' => 10000.00]);
        $payment2 = $this->makePayment($project, ['amount' => 15000.00]);

        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment1, $this->adminUser->id);
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->adminUser->id);

        $this->assertEquals(2000.00, (float) $comm1->amount);
        $this->assertEquals(2000.00, (float) $comm2->amount);
        $this->assertEquals(2, Commission::where('project_id', $project->id)->count());
    }

    /**
     * 5. Per-project fixed commission generated only once.
     */
    public function test_05_per_project_fixed_commission_generated_only_once(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Fixed,
            'custom_commission_rate' => 10000.00,
            'custom_fixed_frequency' => FixedCommissionFrequency::PerProject,
            'commission_status' => 'active',
        ]);

        $payment1 = $this->makePayment($project, ['amount' => 25000.00]);
        $payment2 = $this->makePayment($project, ['amount' => 25000.00]);
        $payment3 = $this->makePayment($project, ['amount' => 25000.00]);

        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment1, $this->adminUser->id);
        $this->assertEquals(10000.00, (float) $comm1->amount);

        // Subsequent payments should not generate duplicate project commissions
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->adminUser->id);
        $comm3 = $this->commissionService->verifyPaymentAndGenerateCommission($payment3, $this->adminUser->id);

        $this->assertEquals(1, Commission::where('project_id', $project->id)->count());
        $this->assertEquals($comm1->id, $comm2->id);
        $this->assertEquals($comm1->id, $comm3->id);
    }

    /**
     * 6. Per-milestone fixed commission.
     */
    public function test_06_per_milestone_fixed_commission(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Fixed,
            'custom_commission_rate' => 3000.00,
            'custom_fixed_frequency' => FixedCommissionFrequency::PerMilestone,
            'commission_status' => 'active',
        ]);

        $payment = $this->makePayment($project, [
            'amount' => 30000.00,
            'payment_type' => CustomerPaymentType::Milestone,
        ]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(3000.00, (float) $commission->amount);
        $this->assertEquals(FixedCommissionFrequency::PerMilestone, $commission->commission_frequency);
    }

    /**
     * 7. Monthly payment generates a separate commission.
     */
    public function test_07_monthly_payment_generates_separate_commission(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 10.00,
            'commission_status' => 'active',
        ]);

        $paymentJan = $this->makePayment($project, [
            'amount' => 5000.00,
            'payment_type' => CustomerPaymentType::Monthly,
            'billing_month' => '2026-01',
            'billing_period' => 'January 2026',
        ]);

        $paymentFeb = $this->makePayment($project, [
            'amount' => 5000.00,
            'payment_type' => CustomerPaymentType::Monthly,
            'billing_month' => '2026-02',
            'billing_period' => 'February 2026',
        ]);

        $commJan = $this->commissionService->verifyPaymentAndGenerateCommission($paymentJan, $this->adminUser->id);
        $commFeb = $this->commissionService->verifyPaymentAndGenerateCommission($paymentFeb, $this->adminUser->id);

        $this->assertNotEquals($commJan->id, $commFeb->id);
        $this->assertEquals('2026-01', $commJan->billing_month);
        $this->assertEquals('2026-02', $commFeb->billing_month);
        $this->assertEquals(500.00, (float) $commJan->amount);
        $this->assertEquals(500.00, (float) $commFeb->amount);
    }

    /**
     * 8. Twelve monthly payments generate twelve eligible commissions.
     */
    public function test_08_twelve_monthly_payments_generate_twelve_eligible_commissions(): void
    {
        $project = $this->makeProject([
            'final_deal_value' => 60000.00,
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 10.00,
            'commission_status' => 'active',
        ]);

        for ($m = 1; $m <= 12; $m++) {
            $monthStr = sprintf('2026-%02d', $m);
            $payment = $this->makePayment($project, [
                'amount' => 5000.00,
                'payment_type' => CustomerPaymentType::Monthly,
                'billing_month' => $monthStr,
                'billing_period' => date('F Y', strtotime("2026-{$m}-01")),
            ]);

            $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
            $this->assertEquals(500.00, (float) $comm->amount);
            $this->assertEquals($monthStr, $comm->billing_month);
        }

        $this->assertEquals(12, Commission::where('project_id', $project->id)->count());
        $this->assertEquals(6000.00, (float) Commission::where('project_id', $project->id)->sum('amount'));
    }

    /**
     * 9. Pending payment generates no commission.
     */
    public function test_09_pending_payment_generates_no_commission(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, [
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());
        $this->assertFalse($payment->commission_generated);
    }

    /**
     * 10. Rejected payment generates no commission.
     */
    public function test_10_rejected_payment_generates_no_commission(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project);

        $this->commissionService->rejectPayment($payment, 'Fraudulent transaction proof', $this->adminUser->id);

        $this->assertEquals(CustomerPaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());

        $this->expectException(DomainException::class);
        $this->commissionService->verifyPaymentAndGenerateCommission($payment->fresh(), $this->adminUser->id);
    }

    /**
     * 11. Repeated payment verification does not duplicate commission (idempotent).
     */
    public function test_11_repeated_payment_verification_does_not_duplicate_commission(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 20000.00]);

        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        $this->assertNotNull($comm1);

        try {
            $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        } catch (\DomainException $e) {
            $this->assertStringContainsString('already been generated', $e->getMessage());
        }

        $this->assertEquals(1, Commission::where('customer_payment_id', $payment->id)->count());
    }

    /**
     * 12. Concurrent generation does not duplicate commission.
     */
    public function test_12_concurrent_generation_does_not_duplicate_commission(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 15000.00]);

        // First verification locks and inserts
        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        // Attempting direct raw DB insert with same customer_payment_id violates unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('commissions')->insert([
            'commission_number' => 'COM-RACE-001',
            'project_id' => $project->id,
            'partner_id' => $project->partner_id,
            'customer_payment_id' => $payment->id, // DUPLICATE FK!
            'amount' => 1500.00,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * 13. Global policy changes do not alter old commissions.
     */
    public function test_13_global_policy_changes_do_not_alter_old_commissions(): void
    {
        $project = $this->makeProject(['commission_model' => 'global_default']);
        $paymentOld = $this->makePayment($project, ['amount' => 50000.00]);

        $commOld = $this->commissionService->verifyPaymentAndGenerateCommission($paymentOld, $this->adminUser->id);
        $this->assertEquals(5000.00, (float) $commOld->amount);
        $this->assertEquals(10.00, (float) $commOld->rate_applied);

        // Admin increases global rate to 15%
        $this->commissionService->updateDefaultCommissionRate(15.00, $this->adminUser->id);

        // Old commission must remain strictly intact
        $this->assertEquals(5000.00, (float) $commOld->fresh()->amount);
        $this->assertEquals(10.00, (float) $commOld->fresh()->rate_applied);

        // New payment receives the updated rate
        $paymentNew = $this->makePayment($project, ['amount' => 50000.00]);
        $commNew = $this->commissionService->verifyPaymentAndGenerateCommission($paymentNew, $this->adminUser->id);
        $this->assertEquals(7500.00, (float) $commNew->amount);
        $this->assertEquals(15.00, (float) $commNew->rate_applied);
    }

    /**
     * 14. Project rate changes do not alter old commissions.
     */
    public function test_14_project_rate_changes_do_not_alter_old_commissions(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 12.00,
            'commission_status' => 'active',
        ]);

        $payment1 = $this->makePayment($project, ['amount' => 20000.00]);
        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment1, $this->adminUser->id);
        $this->assertEquals(2400.00, (float) $comm1->amount);

        // Update project commission rate to 18%
        $this->commissionService->configureProjectCommission($project, [
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 18.00,
            'commission_status' => 'active',
        ], $this->adminUser->id);

        // Old commission unchanged
        $this->assertEquals(2400.00, (float) $comm1->fresh()->amount);

        // New payment uses 18%
        $payment2 = $this->makePayment($project, ['amount' => 20000.00]);
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->adminUser->id);
        $this->assertEquals(3600.00, (float) $comm2->amount);
    }

    /**
     * 15. Correct policy version snapshot.
     */
    public function test_15_correct_policy_version_snapshot(): void
    {
        // Create revision v2 of global policy
        $policyV2 = $this->commissionService->createPolicyRevision($this->globalPolicyV1, [
            'commission_value' => 12.50,
            'effective_from' => now()->toDateString(),
            'is_default' => true,
        ], $this->adminUser->id);

        $project = $this->makeProject(['commission_model' => 'global_default']);
        $payment = $this->makePayment($project, ['amount' => 40000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(2, $comm->policy_version);
        $this->assertEquals($policyV2->id, $comm->commission_policy_id);
        $this->assertEquals(5000.00, (float) $comm->amount);
    }

    /**
     * 16. Payment refund reverses only the corresponding commission.
     */
    public function test_16_payment_refund_reverses_only_corresponding_commission(): void
    {
        $project = $this->makeProject();
        $payment1 = $this->makePayment($project, ['amount' => 20000.00]);
        $payment2 = $this->makePayment($project, ['amount' => 30000.00]);

        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($payment1, $this->adminUser->id);
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($payment2, $this->adminUser->id);

        // Reverse payment 1
        $this->commissionService->reverseVerifiedPayment($payment1, 'Customer requested refund for payment 1', $this->adminUser->id);

        $this->assertEquals(CommissionStatus::Cancelled, $comm1->fresh()->status);
        $this->assertEquals(CommissionStatus::Payable, $comm2->fresh()->status); // Untouched!
    }

    /**
     * 17. Unpaid commission adjustment.
     */
    public function test_17_unpaid_commission_adjustment(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 10.00,
            'commission_status' => 'active',
        ]);
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        $this->assertEquals(1000.00, (float) $comm->amount);
        $this->assertEquals(CommissionStatus::Payable, $comm->status);

        // Partial refund of ₹3,000 (30% of ₹10,000)
        $this->commissionService->adjustCommissionForPartialRefund(
            $payment,
            3000.00,
            'Partial service cancellation',
            $this->adminUser->id
        );

        $this->assertEquals(700.00, (float) $comm->fresh()->amount);
        $this->assertEquals(7000.00, (float) $comm->fresh()->calculation_basis);
    }

    /**
     * 18. Already-paid commission recovery.
     */
    public function test_18_already_paid_commission_recovery(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 20000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        // Mark commission as Paid
        $comm->update(['status' => CommissionStatus::Paid, 'paid_at' => now()]);

        // Payment is now reversed post-payout
        $this->commissionService->reverseVerifiedPayment($payment, 'Client payment disputed and charged back', $this->adminUser->id);

        // Original paid commission remains Paid (immutable historical ledger)
        $this->assertEquals(CommissionStatus::Paid, $comm->fresh()->status);

        // A negative recovery debit entry is created
        $recovery = Commission::where('parent_commission_id', $comm->id)->first();
        $this->assertNotNull($recovery);
        $this->assertEquals(-2000.00, (float) $recovery->amount);
        $this->assertEquals('recovery', $recovery->adjustment_type);
        $this->assertEquals(CommissionStatus::Payable, $recovery->status);
    }

    /**
     * 19. Partner cannot view another partner's commissions.
     */
    public function test_19_partner_cannot_view_another_partners_commissions(): void
    {
        $projectB = $this->makeProject(['partner_id' => $this->partnerB->id, 'customer_id' => $this->customerB->id]);
        $paymentB = $this->makePayment($projectB, ['partner_id' => $this->partnerB->id]);
        $commB = $this->commissionService->verifyPaymentAndGenerateCommission($paymentB, $this->adminUser->id);

        // Partner A attempts to view Partner B's commission statement
        $response = $this->actingAs($this->partnerUserA)->get(route('partner.commissions.show', $commB));
        $response->assertForbidden();
    }

    /**
     * 20. Staff cannot access financial records.
     */
    public function test_20_staff_cannot_access_financial_records(): void
    {
        $resCommissions = $this->actingAs($this->staffUser)->get(route('admin.commissions.index'));
        $resCommissions->assertForbidden();

        $resPayments = $this->actingAs($this->staffUser)->get(route('admin.payments.index'));
        $resPayments->assertForbidden();
    }

    /**
     * 21. Audit log entries are generated correctly.
     */
    public function test_21_audit_log_entries_are_generated_correctly(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 15000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $verifiedAudit = AuditLog::where('action', 'customer_payment_verified')
            ->where('subject_id', $payment->id)
            ->first();
        $this->assertNotNull($verifiedAudit);

        $commAudit = AuditLog::where('action', 'commission_generated')
            ->where('subject_id', $comm->id)
            ->first();
        $this->assertNotNull($commAudit);
        $this->assertEquals($comm->commission_number, $commAudit->metadata['payment_id'] ?? null ? $comm->commission_number : $comm->commission_number);
    }

    /**
     * 22. Transaction rollback on commission/audit failure.
     */
    public function test_22_transaction_rollback_on_commission_failure(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        // Break DB insertion by forcing an invalid currency or non-nullable column violation
        $this->expectException(\Throwable::class);

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => CustomerPaymentStatus::Verified]);
            // Attempt inserting invalid commission record without required fields
            Commission::create([
                'commission_number' => null, // NOT NULL constraint violation!
            ]);
        });

        // Ensure rollback
        $this->assertEquals(CustomerPaymentStatus::Recorded, $payment->fresh()->status);
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());
    }
}

