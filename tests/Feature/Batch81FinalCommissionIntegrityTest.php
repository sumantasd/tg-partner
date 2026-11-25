<?php

namespace Tests\Feature;

use App\Enums\AuditLogAction;
use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\FixedCommissionFrequency;
use App\Enums\KycStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheduleStatus;
use App\Enums\ProjectPaymentType;
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
use App\Models\ProjectPaymentSchedule;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CommissionService;
use App\Services\PaymentScheduleService;
use App\Services\ProjectService;
use App\Services\WithdrawalService;
use Carbon\Carbon;
use DomainException;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Batch81FinalCommissionIntegrityTest extends TestCase
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
    protected PaymentScheduleService $scheduleService;
    protected WithdrawalService $withdrawalService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->adminUser = User::create([
            'name'     => 'Super Administrator',
            'email'    => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Active,
        ]);

        // 2. Partner A (Verified KYC & Bank for withdrawal tests)
        $this->partnerUserA = User::create([
            'name'     => 'Aditya Sharma',
            'email'    => 'aditya@sharmatech.in',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->partnerA = Partner::create([
            'user_id'                 => $this->partnerUserA->id,
            'partner_code'            => 'TGP-2026-ADIT',
            'company_name'            => 'Sharma Tech Solutions',
            'phone'                   => '9811100001',
            'partner_type'            => 'referral',
            'kyc_status'              => KycStatus::Approved,
            'bank_verification_status'=> BankVerificationStatus::Verified,
            'bank_account_name'       => 'Sharma Tech Solutions',
            'bank_account_number'     => '123456789012',
            'bank_ifsc'               => 'HDFC0001234',
            'default_commission_rate' => 10.00,
        ]);

        // 3. Partner B
        $this->partnerUserB = User::create([
            'name'     => 'Sneha Kulkarni',
            'email'    => 'sneha@kulkarnidigital.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->partnerB = Partner::create([
            'user_id'                 => $this->partnerUserB->id,
            'partner_code'            => 'TGP-2026-SNEH',
            'company_name'            => 'Kulkarni Digital Media',
            'phone'                   => '9822200002',
            'partner_type'            => 'referral',
            'kyc_status'              => KycStatus::Approved,
            'bank_verification_status'=> BankVerificationStatus::Verified,
            'bank_account_name'       => 'Kulkarni Digital Media',
            'bank_account_number'     => '987654321098',
            'bank_ifsc'               => 'ICIC0005678',
            'default_commission_rate' => 12.00,
        ]);

        // 4. Staff User
        $this->staffUser = User::create([
            'name'     => 'Rohan Staff',
            'email'    => 'rohan@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::TeamMember,
            'status'   => UserStatus::Active,
        ]);

        // 5. Customers
        $this->customerA = Customer::create([
            'customer_code'   => 'CUS-2026-AAAA',
            'name'            => 'Horizon Retail Ltd',
            'email'           => 'contact@horizonretail.in',
            'phone'           => '9800000001',
            'first_partner_id'=> $this->partnerA->id,
            'created_by'      => $this->adminUser->id,
        ]);

        $this->customerB = Customer::create([
            'customer_code'   => 'CUS-2026-BBBB',
            'name'            => 'Zenith Logistics Ltd',
            'email'           => 'finance@zenithlogistics.in',
            'phone'           => '9800000002',
            'first_partner_id'=> $this->partnerB->id,
            'created_by'      => $this->adminUser->id,
        ]);

        // 6. Global Policy v1 (10% default)
        $this->globalPolicyV1 = CommissionPolicy::updateOrCreate(
            ['policy_code' => 'POL-DEF-V1'],
            [
                'name'            => 'Standard Global Commission Policy',
                'description'     => 'Default platform policy v1',
                'commission_type' => CommissionType::Percentage,
                'commission_value'=> 10.00,
                'effective_from'  => '2026-01-01',
                'status'          => 'active',
                'is_default'      => true,
                'version'         => 1,
                'created_by'      => $this->adminUser->id,
                'updated_by'      => $this->adminUser->id,
            ]
        );

        $this->commissionService = app(CommissionService::class);
        $this->scheduleService = app(PaymentScheduleService::class);
        $this->withdrawalService = app(WithdrawalService::class);
    }

    protected function makeProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'partner_id'          => $this->partnerA->id,
            'customer_id'         => $this->customerA->id,
            'project_code'        => 'PRJ-' . uniqid(),
            'client_name'         => $this->customerA->name,
            'project_title'       => 'ERP Cloud Modernization',
            'project_description' => 'Comprehensive ERP setup and cloud data migration.',
            'payment_type'        => ProjectPaymentType::Single,
            'final_deal_value'    => 60000.00,
            'currency'            => 'INR',
            'status'              => ProjectStatus::InProgress,
            'commission_model'    => 'global_default',
            'start_date'          => '2026-01-01',
        ], $attributes));
    }

    protected function makePayment(Project $project, array $attributes = []): CustomerPayment
    {
        return CustomerPayment::create(array_merge([
            'payment_number'        => 'CPAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
            'project_id'            => $project->id,
            'customer_id'           => $project->customer_id,
            'partner_id'            => $project->partner_id,
            'payment_type'          => CustomerPaymentType::Final,
            'amount'                => 60000.00,
            'currency'              => 'INR',
            'payment_method'        => PaymentMethod::BankTransfer,
            'transaction_reference' => 'TXN-' . strtoupper(bin2hex(random_bytes(4))),
            'payment_date'          => '2026-01-10',
            'billing_month'         => '2026-01',
            'billing_period'        => 'January 2026',
            'recorded_by'           => $this->adminUser->id,
            'status'                => CustomerPaymentStatus::Recorded,
            'commission_generated'  => false,
        ], $attributes));
    }

    /**
     * 1. Single Payment project creation.
     */
    public function test_01_single_payment_project_creation(): void
    {
        $project = (new ProjectService())->createProject($this->customerA, [
            'project_title'    => 'Single Payment Portal',
            'payment_type'     => ProjectPaymentType::Single,
            'final_deal_value' => 60000.00,
        ], $this->adminUser);

        $this->assertEquals(ProjectPaymentType::Single, $project->payment_type);
        $this->assertTrue($project->isSinglePayment());
        $this->assertFalse($project->isMonthlyPayment());
        $this->assertEquals(0, $project->paymentSchedules()->count());
    }

    /**
     * 2. Monthly Payment project creation.
     */
    public function test_02_monthly_payment_project_creation(): void
    {
        $project = (new ProjectService())->createProject($this->customerA, [
            'project_title'      => 'Annual SaaS Retainer',
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
            'monthly_due_day'    => 5,
            'first_payment_due_date' => '2026-01-05',
            'final_deal_value'   => 60000.00,
        ], $this->adminUser);

        $this->assertEquals(ProjectPaymentType::Monthly, $project->payment_type);
        $this->assertTrue($project->isMonthlyPayment());
        $this->assertEquals(12, $project->paymentSchedules()->count());
    }

    /**
     * 3. Monthly schedule generation.
     */
    public function test_03_monthly_schedule_generation(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
            'monthly_due_day'    => 10,
        ]);

        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);

        $this->assertCount(12, $schedules);
        $first = $schedules->first();
        $last = $schedules->last();

        $this->assertEquals('2026-01', $first->billing_month);
        $this->assertEquals('January 2026', $first->billing_period);
        $this->assertEquals(5000.00, (float) $first->expected_amount);
        $this->assertEquals(0.00, (float) $first->received_amount);
        $this->assertEquals(5000.00, (float) $first->outstanding_amount);

        $this->assertEquals('2026-12', $last->billing_month);
        $this->assertEquals('December 2026', $last->billing_period);
        $this->assertEquals(5000.00, (float) $last->expected_amount);
    }

    /**
     * 4. Duplicate schedule prevention (idempotent).
     */
    public function test_04_duplicate_schedule_prevention(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);

        $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $this->assertEquals(12, ProjectPaymentSchedule::where('project_id', $project->id)->count());

        // Re-run schedule generation
        $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $this->assertEquals(12, ProjectPaymentSchedule::where('project_id', $project->id)->count());
    }

    /**
     * 5. Single payment pending verification creates no approved commission.
     */
    public function test_05_single_payment_pending_verification_creates_no_approved_commission(): void
    {
        $project = $this->makeProject(['final_deal_value' => 60000.00]);
        $payment = $this->makePayment($project, ['amount' => 60000.00, 'status' => CustomerPaymentStatus::Recorded]);

        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());

        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);
        $this->assertEquals(0.00, $balance['available_balance']);
        $this->assertEquals(0.00, $balance['approved_commission']);
    }

    /**
     * 6. Single payment verification creates approved commission immediately withdrawable.
     */
    public function test_06_single_payment_verification_creates_approved_commission(): void
    {
        $project = $this->makeProject(['final_deal_value' => 60000.00]);
        $payment = $this->makePayment($project, ['amount' => 60000.00]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertNotNull($commission);
        $this->assertEquals(6000.00, (float) $commission->amount);
        $this->assertTrue(in_array($commission->status, [CommissionStatus::Payable, CommissionStatus::Approved], true));

        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);
        $this->assertEquals(6000.00, $balance['available_balance']);
        $this->assertEquals(6000.00, $balance['approved_commission']);
    }

    /**
     * 7. Monthly payment pending verification creates no approved commission.
     */
    public function test_07_monthly_payment_pending_verification_creates_no_approved_commission(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $scheduleJan = $schedules->first();

        $payment = $this->scheduleService->recordPaymentForSchedule($scheduleJan, [
            'amount' => 5000.00,
        ], $this->adminUser->id);

        $this->assertEquals(CustomerPaymentStatus::Recorded, $payment->status);
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());

        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);
        $this->assertEquals(0.00, $balance['available_balance']);
    }

    /**
     * 8. Each verified monthly payment creates its corresponding commission.
     */
    public function test_08_each_verified_monthly_payment_creates_its_corresponding_commission(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $scheduleJan = $schedules->first();

        $payment = $this->scheduleService->recordPaymentForSchedule($scheduleJan, [
            'amount' => 5000.00,
        ], $this->adminUser->id);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertNotNull($comm);
        $this->assertEquals(500.00, (float) $comm->amount);
        $this->assertEquals('2026-01', $comm->billing_month);

        $scheduleJan->refresh();
        $this->assertEquals(5000.00, (float) $scheduleJan->received_amount);
        $this->assertEquals(0.00, (float) $scheduleJan->outstanding_amount);
        $this->assertEquals(PaymentScheduleStatus::Verified, $scheduleJan->status);
    }

    /**
     * 9. Three verified monthly payments create three correct commission entries.
     */
    public function test_09_three_verified_monthly_payments_create_three_correct_commission_entries(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);

        for ($i = 0; $i < 3; $i++) {
            $sch = $schedules[$i];
            $payment = $this->scheduleService->recordPaymentForSchedule($sch, ['amount' => 5000.00], $this->adminUser->id);
            $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        }

        $commissions = Commission::where('project_id', $project->id)->get();
        $this->assertCount(3, $commissions);
        $this->assertEquals(1500.00, (float) $commissions->sum('amount'));

        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);
        $this->assertEquals(1500.00, $balance['available_balance']);
    }

    /**
     * 10. Partial payment calculation.
     */
    public function test_10_partial_payment_calculation(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $sch = $schedules->first();

        // Customer pays partial ₹3,000 against ₹5,000 expected
        $payment = $this->scheduleService->recordPaymentForSchedule($sch, ['amount' => 3000.00], $this->adminUser->id);
        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(300.00, (float) $comm->amount); // 10% of 3,000

        $sch->refresh();
        $this->assertEquals(3000.00, (float) $sch->received_amount);
        $this->assertEquals(2000.00, (float) $sch->outstanding_amount);
        $this->assertEquals(PaymentScheduleStatus::PartiallyPaid, $sch->status);
    }

    /**
     * 11. Multiple payments against one billing month.
     */
    public function test_11_multiple_payments_against_one_billing_month(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 12,
            'billing_start_date' => '2026-01-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $sch = $schedules->first();

        // Part 1: ₹3,000
        $pay1 = $this->scheduleService->recordPaymentForSchedule($sch, ['amount' => 3000.00], $this->adminUser->id);
        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($pay1, $this->adminUser->id);

        // Part 2: ₹2,000
        $pay2 = $this->scheduleService->recordPaymentForSchedule($sch, ['amount' => 2000.00], $this->adminUser->id);
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($pay2, $this->adminUser->id);

        $this->assertNotEquals($comm1->id, $comm2->id);
        $this->assertEquals(300.00, (float) $comm1->amount);
        $this->assertEquals(200.00, (float) $comm2->amount);

        $sch->refresh();
        $this->assertEquals(5000.00, (float) $sch->received_amount);
        $this->assertEquals(0.00, (float) $sch->outstanding_amount);
        $this->assertEquals(PaymentScheduleStatus::Verified, $sch->status);
    }

    /**
     * 12. Overdue schedule calculation.
     */
    public function test_12_overdue_schedule_calculation(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 6,
            'billing_start_date' => '2025-01-01', // Due dates in the past
            'grace_period_days'  => 5,
        ]);

        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $sch = $schedules->first();

        $this->assertTrue($sch->isOverdue());
        $this->assertEquals(PaymentScheduleStatus::Overdue, $sch->status);
    }

    /**
     * 13. Pause and resume monthly schedules.
     */
    public function test_13_pause_and_resume_monthly_schedules(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 6,
            'billing_start_date' => '2026-06-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $sch = $schedules->first();

        $this->scheduleService->pauseSchedule($sch, $this->adminUser->id);
        $this->assertTrue($sch->fresh()->is_paused);

        $this->scheduleService->resumeSchedule($sch->fresh(), $this->adminUser->id);
        $this->assertFalse($sch->fresh()->is_paused);
    }

    /**
     * 14. Cancel future schedules with reason.
     */
    public function test_14_cancel_future_schedules_with_reason(): void
    {
        $project = $this->makeProject([
            'payment_type'       => ProjectPaymentType::Monthly,
            'monthly_amount'     => 5000.00,
            'duration_months'    => 6,
            'billing_start_date' => '2026-06-01',
        ]);
        $schedules = $this->scheduleService->generateSchedule($project, $this->adminUser->id);
        $sch = $schedules->last();

        $this->scheduleService->cancelSchedule($sch, 'Client requested scope reduction for month 6.', $this->adminUser->id);

        $this->assertEquals(PaymentScheduleStatus::Cancelled, $sch->fresh()->status);
        $this->assertStringContainsString('scope reduction', $sch->fresh()->cancellation_reason);
    }

    /**
     * 15. Project-specific commission override.
     */
    public function test_15_project_specific_commission_override(): void
    {
        $project = $this->makeProject([
            'commission_model'       => 'custom',
            'custom_commission_type' => CommissionType::Percentage,
            'custom_commission_rate' => 15.00,
            'commission_status'      => 'active',
        ]);
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(1500.00, (float) $comm->amount);
        $this->assertEquals('project_override', $comm->policy_source);
    }

    /**
     * 16. Global commission fallback.
     */
    public function test_16_global_commission_fallback(): void
    {
        $project = $this->makeProject([
            'commission_model' => 'global_default',
        ]);
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->assertEquals(1000.00, (float) $comm->amount);
        $this->assertEquals('global_policy', $comm->policy_source);
        $this->assertEquals('POL-DEF-V1', $comm->policy_code);
    }

    /**
     * 17. Historical policy snapshot preservation.
     */
    public function test_17_historical_policy_snapshot_preservation(): void
    {
        $project = $this->makeProject(['commission_model' => 'global_default']);
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        $this->assertEquals(1000.00, (float) $comm->amount);

        // Revise global policy to 15%
        $this->globalPolicyV1->update(['commission_value' => 15.00]);

        // Existing commission snapshot MUST remain 10%
        $this->assertEquals(1000.00, (float) $comm->fresh()->amount);
        $this->assertEquals(10.00, (float) $comm->fresh()->rate_applied);
    }

    /**
     * 18. Concurrent duplicate commission prevention.
     */
    public function test_18_concurrent_duplicate_commission_prevention(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $this->expectException(DomainException::class);
        $this->commissionService->verifyPaymentAndGenerateCommission($payment->fresh(), $this->adminUser->id);
    }

    /**
     * 19. Fixed per-project duplicate prevention.
     */
    public function test_19_fixed_per_project_duplicate_prevention(): void
    {
        $project = $this->makeProject([
            'commission_model'       => 'custom',
            'custom_commission_type' => CommissionType::Fixed,
            'custom_commission_rate' => 5000.00,
            'custom_fixed_frequency' => FixedCommissionFrequency::PerProject,
            'commission_status'      => 'active',
        ]);

        $pay1 = $this->makePayment($project, ['amount' => 20000.00]);
        $comm1 = $this->commissionService->verifyPaymentAndGenerateCommission($pay1, $this->adminUser->id);
        $this->assertEquals(5000.00, (float) $comm1->amount);

        // Payment 2 on same project
        $pay2 = $this->makePayment($project, ['amount' => 20000.00]);
        $comm2 = $this->commissionService->verifyPaymentAndGenerateCommission($pay2, $this->adminUser->id);

        // Same commission reused; no duplicate created
        $this->assertEquals($comm1->id, $comm2->id);
        $this->assertEquals(1, Commission::where('project_id', $project->id)->count());
    }

    /**
     * 20. Partial and full refund adjustment.
     */
    public function test_20_partial_and_full_refund_adjustment(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        $this->assertEquals(1000.00, (float) $comm->amount);

        // Reverse payment
        $this->commissionService->reverseVerifiedPayment($payment->fresh(), 'Customer disputed charge', $this->adminUser->id);

        $this->assertEquals(CommissionStatus::Cancelled, $comm->fresh()->status);
        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);
        $this->assertEquals(0.00, $balance['available_balance']);
    }

    /**
     * 21. Paid commission recovery.
     */
    public function test_21_paid_commission_recovery(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        $comm = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        // Mark as Paid
        $comm->update(['status' => CommissionStatus::Paid, 'paid_at' => now()]);

        // Reversal of already-paid commission creates recovery debit entry
        $this->commissionService->reverseVerifiedPayment($payment->fresh(), 'Disputed after payout', $this->adminUser->id);

        $recovery = Commission::where('parent_commission_id', $comm->id)->firstOrFail();
        $this->assertEquals(-1000.00, (float) $recovery->amount);
        $this->assertEquals('recovery', $recovery->adjustment_type);
    }

    /**
     * 22. Withdrawal balance calculation.
     */
    public function test_22_withdrawal_balance_calculation(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 20000.00]);

        $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $balance = $this->withdrawalService->getPartnerBalanceSummary($this->partnerA);

        $this->assertArrayHasKey('total_earned', $balance);
        $this->assertArrayHasKey('approved_commission', $balance);
        $this->assertArrayHasKey('available_balance', $balance);
        $this->assertArrayHasKey('available_commission', $balance);
        $this->assertArrayHasKey('paid_commission', $balance);
        $this->assertArrayHasKey('recovery_adjustments', $balance);
        $this->assertArrayHasKey('final_withdrawable_balance', $balance);

        $this->assertEquals(2000.00, $balance['available_balance']);
        $this->assertEquals(2000.00, $balance['final_withdrawable_balance']);
    }

    /**
     * 23. KYC and Bank Verification restrictions.
     */
    public function test_23_kyc_and_bank_verification_restrictions(): void
    {
        // Unapproved partner
        $unapprovedUser = User::create([
            'name'     => 'Unverified Partner',
            'email'    => 'unverified@partner.com',
            'password' => Hash::make('secret123'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);
        $unapprovedPartner = Partner::create([
            'user_id'                 => $unapprovedUser->id,
            'partner_code'            => 'TGP-UNVER-01',
            'company_name'            => 'Unverified Co',
            'phone'                   => '9800099999',
            'partner_type'            => 'referral',
            'kyc_status'              => KycStatus::Pending,
            'bank_verification_status'=> BankVerificationStatus::Pending,
        ]);

        $this->assertFalse($unapprovedPartner->isFullyKycVerified());

        $this->expectException(DomainException::class);
        $this->withdrawalService->requestWithdrawal($unapprovedPartner, 1000.00, 'bank_transfer');
    }

    /**
     * 24. Partner ownership protection.
     */
    public function test_24_partner_ownership_protection(): void
    {
        $projectA = $this->makeProject(['partner_id' => $this->partnerA->id]);
        $projectB = $this->makeProject(['partner_id' => $this->partnerB->id]);

        $payA = $this->makePayment($projectA, ['amount' => 10000.00, 'partner_id' => $this->partnerA->id]);
        $payB = $this->makePayment($projectB, ['amount' => 10000.00, 'partner_id' => $this->partnerB->id]);

        $this->commissionService->verifyPaymentAndGenerateCommission($payA, $this->adminUser->id);
        $this->commissionService->verifyPaymentAndGenerateCommission($payB, $this->adminUser->id);

        // Partner A requests their commission index
        $response = $this->actingAs($this->partnerUserA)->get(route('partner.commissions.index'));
        $response->assertOk();
        $response->assertSee($projectA->project_code);
        $response->assertDontSee($projectB->project_code);
    }

    /**
     * 25. Staff financial access restrictions.
     */
    public function test_25_staff_financial_access_restrictions(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project);

        // Staff cannot access commission ledger
        $respComm = $this->actingAs($this->staffUser)->get(route('admin.commissions.index'));
        $respComm->assertForbidden();

        // Staff cannot verify payment
        $respVerify = $this->actingAs($this->staffUser)->post(route('admin.payments.verify', $payment));
        $respVerify->assertForbidden();
    }

    /**
     * 26. Audit log failure rollback for financial operations.
     */
    public function test_26_audit_log_failure_rollback_for_financial_operations(): void
    {
        $project = $this->makeProject();
        $payment = $this->makePayment($project, ['amount' => 10000.00]);

        // Mock AuditLogService to throw an exception on record
        $mockAudit = $this->createMock(AuditLogService::class);
        $mockAudit->method('record')->willThrowException(new Exception("Database audit constraint error"));
        $this->app->instance(AuditLogService::class, $mockAudit);

        try {
            $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
            $this->fail("Expected Exception was not thrown.");
        } catch (Exception $e) {
            $this->assertStringContainsString("audit constraint error", $e->getMessage());
        }

        // Verify transaction rolled back completely
        $payment->refresh();
        $this->assertEquals(CustomerPaymentStatus::Recorded, $payment->status);
        $this->assertFalse($payment->commission_generated);
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());
    }
}

