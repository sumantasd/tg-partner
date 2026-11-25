<?php

namespace Tests\Feature;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\KycStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Exceptions\AuditLogWriteException;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\AuditLogService;
use App\Services\CommissionService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Batch62FinancialAuditConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUser;
    protected Partner $partner;
    protected User $staffUser;
    protected Customer $customer;
    protected Project $project;
    protected CommissionService $commissionService;
    protected WithdrawalService $withdrawalService;
    protected AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser = User::create([
            'name' => 'Vikram Singhania',
            'email' => 'vikram@singhaniadigital.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-2026-VIKR',
            'company_name' => 'Singhania Digital Media',
            'phone' => '9876500002',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
            'kyc_status' => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'bank_name' => 'HDFC Bank',
            'bank_account_name' => 'Singhania Digital Media',
            'bank_account_number' => '50100223344556',
            'bank_routing_code' => 'HDFC0001234',
            'payout_method' => 'bank_transfer',
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->staffUser = User::create([
            'name' => 'Pooja Verma',
            'email' => 'pooja@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->customer = Customer::create([
            'customer_code' => 'CUS-2026-FIN01',
            'name' => 'Rajesh Sharma',
            'email' => 'rajesh@sharmatrading.in',
            'phone' => '9811122233',
            'company_name' => 'Sharma Trading Co',
            'status' => 'active',
            'first_partner_id' => $this->partner->id,
            'created_by' => $this->adminUser->id,
        ]);

        $this->project = Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $this->customer->id,
            'project_code' => 'PRJ-2026-FIN01',
            'client_name' => $this->customer->name,
            'client_company' => $this->customer->company_name,
            'client_email' => $this->customer->email,
            'client_phone' => $this->customer->phone,
            'project_title' => 'E-Commerce Platform Deployment',
            'service_category' => 'Web Development',
            'project_description' => 'Full eCommerce solution.',
            'final_deal_value' => 200000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ]);

        $this->commissionService = app(CommissionService::class);
        $this->withdrawalService = app(WithdrawalService::class);
        $this->auditLogService = app(AuditLogService::class);
    }

    /**
     * 1. Audit insertion failure during payment verification rolls back payment state change.
     */
    public function test_01_audit_failure_during_payment_verification_rolls_back_transaction(): void
    {
        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-2026-TEST01',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'partner_id' => $this->partner->id,
            'payment_type' => CustomerPaymentType::Milestone,
            'amount' => 50000.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::BankTransfer,
            'transaction_reference' => 'TXN-BANK-112233',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        // Intercept AuditLog creation to simulate DB write failure during CustomerPaymentVerified action
        AuditLog::saving(function ($log) {
            if ($log->action === AuditLogAction::CustomerPaymentVerified || $log->action === AuditLogAction::CustomerPaymentVerified->value) {
                throw new \RuntimeException('Simulated disk/database failure writing CustomerPaymentVerified audit log.');
            }
        });

        $exceptionThrown = false;
        try {
            $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        } catch (AuditLogWriteException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('CustomerPaymentVerified', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'AuditLogWriteException was expected but not thrown.');

        // Verify Database State: Transaction rolled back completely
        $freshPayment = $payment->fresh();
        $this->assertEquals(CustomerPaymentStatus::Recorded, $freshPayment->status);
        $this->assertFalse((bool) $freshPayment->commission_generated);
        $this->assertNull($freshPayment->verified_at);
        $this->assertNull($freshPayment->verified_by);

        // Verify no orphaned commission was created
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());

        // Verify no audit log remained committed
        $this->assertEquals(0, AuditLog::where('action', AuditLogAction::CustomerPaymentVerified->value)->count());
    }

    /**
     * 2. Audit insertion failure during commission generation rolls back both payment and commission.
     */
    public function test_02_audit_failure_during_commission_generation_rolls_back_transaction(): void
    {
        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-2026-TEST02',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'partner_id' => $this->partner->id,
            'payment_type' => CustomerPaymentType::Milestone,
            'amount' => 40000.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::BankTransfer,
            'transaction_reference' => 'TXN-BANK-445566',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        // Simulate failure on the second audit log call (CommissionGenerated)
        AuditLog::saving(function ($log) {
            if ($log->action === AuditLogAction::CommissionGenerated || $log->action === AuditLogAction::CommissionGenerated->value) {
                throw new \RuntimeException('Simulated failure during CommissionGenerated audit log write.');
            }
        });

        $exceptionThrown = false;
        try {
            $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);
        } catch (AuditLogWriteException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('CommissionGenerated', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'AuditLogWriteException was expected on CommissionGenerated.');

        // Verify Database State: Whole transaction rolled back
        $freshPayment = $payment->fresh();
        $this->assertEquals(CustomerPaymentStatus::Recorded, $freshPayment->status);
        $this->assertFalse((bool) $freshPayment->commission_generated);

        // Commission rolled back
        $this->assertEquals(0, Commission::where('customer_payment_id', $payment->id)->count());

        // Both audit records rolled back
        $this->assertEquals(0, AuditLog::where('action', AuditLogAction::CustomerPaymentVerified->value)->count());
        $this->assertEquals(0, AuditLog::where('action', AuditLogAction::CommissionGenerated->value)->count());
    }

    /**
     * 3. Audit insertion failure during withdrawal approval rolls back approval state.
     */
    public function test_03_audit_failure_during_withdrawal_approval_rolls_back_transaction(): void
    {
        $withdrawal = WithdrawalRequest::create([
            'request_number' => 'WDR-2026-TEST01',
            'partner_id' => $this->partner->id,
            'amount' => 5000.00,
            'currency' => 'INR',
            'payout_method' => 'bank_transfer',
            'status' => WithdrawalStatus::Pending,
            'requested_at' => now(),
            'recipient_snapshot' => [
                'bank_name' => 'HDFC Bank',
                'account_number' => '50100223344556',
                'ifsc' => 'HDFC0001234',
            ],
        ]);

        // Simulate audit insertion failure on WithdrawalApproved
        AuditLog::saving(function ($log) {
            if ($log->action === AuditLogAction::WithdrawalApproved || $log->action === AuditLogAction::WithdrawalApproved->value) {
                throw new \RuntimeException('Simulated audit DB outage during withdrawal approval.');
            }
        });

        $exceptionThrown = false;
        try {
            $this->withdrawalService->approveWithdrawal($withdrawal, $this->adminUser->id, 'Approving request.');
        } catch (AuditLogWriteException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('withdrawal_approved', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'AuditLogWriteException was expected during withdrawal approval.');

        // Verify Database State: Withdrawal remains strictly Pending
        $freshWithdrawal = $withdrawal->fresh();
        $this->assertEquals(WithdrawalStatus::Pending, $freshWithdrawal->status);
        $this->assertNull($freshWithdrawal->approved_at);
        $this->assertNull($freshWithdrawal->reviewed_by);

        // Verify no approval audit log committed
        $this->assertEquals(0, AuditLog::where('action', AuditLogAction::WithdrawalApproved->value)->count());
    }

    /**
     * 4. Audit insertion failure during payout completion rolls back disbursement state.
     */
    public function test_04_audit_failure_during_payout_completion_rolls_back_transaction(): void
    {
        // Setup an approved withdrawal with a reserved commission
        $commission = Commission::create([
            'commission_number' => 'COM-2026-WDR01',
            'project_id' => $this->project->id,
            'partner_id' => $this->partner->id,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 50000.00,
            'rate_applied' => 10.00,
            'amount' => 5000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Approved,
        ]);

        $withdrawal = WithdrawalRequest::create([
            'request_number' => 'WDR-2026-TEST02',
            'partner_id' => $this->partner->id,
            'amount' => 5000.00,
            'currency' => 'INR',
            'payout_method' => 'bank_transfer',
            'status' => WithdrawalStatus::Approved,
            'requested_at' => now()->subDay(),
            'approved_at' => now(),
            'reviewed_by' => $this->adminUser->id,
            'recipient_snapshot' => [
                'bank_name' => 'HDFC Bank',
                'account_number' => '50100223344556',
                'ifsc' => 'HDFC0001234',
            ],
        ]);

        $commission->update(['withdrawal_request_id' => $withdrawal->id]);

        // Simulate audit insertion failure on PayoutCompleted
        AuditLog::saving(function ($log) {
            if ($log->action === AuditLogAction::PayoutCompleted || $log->action === AuditLogAction::PayoutCompleted->value) {
                throw new \RuntimeException('Simulated audit storage failure during payout voucher creation.');
            }
        });

        $exceptionThrown = false;
        try {
            $this->withdrawalService->completePayout(
                withdrawal: $withdrawal,
                transactionReference: 'HDFCN2611240001',
                paymentDate: now()->toDateString(),
                adminUserId: $this->adminUser->id,
                adminNotes: 'Disbursed via corporate banking.'
            );
        } catch (AuditLogWriteException $e) {
            $exceptionThrown = true;
            $this->assertStringContainsString('payout_completed', $e->getMessage());
        }

        $this->assertTrue($exceptionThrown, 'AuditLogWriteException was expected during payout completion.');

        // Verify Database State: Withdrawal remains Approved (NOT Paid / Settled)
        $freshWithdrawal = $withdrawal->fresh();
        $this->assertEquals(WithdrawalStatus::Approved, $freshWithdrawal->status);
        $this->assertNull($freshWithdrawal->settled_at);

        // Verify Commission remains Approved (NOT marked Paid)
        $freshCommission = $commission->fresh();
        $this->assertEquals(CommissionStatus::Approved, $freshCommission->status);
        $this->assertNull($freshCommission->paid_at);
        $this->assertNull($freshCommission->payout_id);

        // Verify no Payout record was committed
        $this->assertEquals(0, Payout::where('withdrawal_request_id', $withdrawal->id)->count());

        // Verify no payout audit record was committed
        $this->assertEquals(0, AuditLog::where('action', AuditLogAction::PayoutCompleted->value)->count());
    }

    /**
     * 5. Successful financial operation commits matching state changes and audit records.
     */
    public function test_05_successful_financial_operation_commits_state_and_audit_log_atomically(): void
    {
        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-2026-TEST05',
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'partner_id' => $this->partner->id,
            'payment_type' => CustomerPaymentType::Milestone,
            'amount' => 75000.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::Upi,
            'transaction_reference' => 'UPI-REF-998877',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $commission = $this->commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        // 1. Verify Payment state
        $freshPayment = $payment->fresh();
        $this->assertEquals(CustomerPaymentStatus::Verified, $freshPayment->status);
        $this->assertTrue((bool) $freshPayment->commission_generated);
        $this->assertNotNull($freshPayment->verified_at);
        $this->assertEquals($this->adminUser->id, $freshPayment->verified_by);

        // 2. Verify Commission state (10% of 75,000 = 7,500)
        $this->assertNotNull($commission);
        $this->assertEquals(7500.00, (float) $commission->amount);
        $this->assertEquals($this->partner->id, $commission->partner_id);
        $this->assertEquals($this->project->id, $commission->project_id);

        // 3. Verify Payment audit record
        $paymentLog = AuditLog::where('action', AuditLogAction::CustomerPaymentVerified->value)
            ->where('subject_type', CustomerPayment::class)
            ->where('subject_id', $payment->id)
            ->first();

        $this->assertNotNull($paymentLog);
        $this->assertEquals(AuditLogModule::Financial->value, $paymentLog->module);
        $this->assertEquals($this->adminUser->id, $paymentLog->actor_user_id);
        $this->assertEquals(CustomerPaymentStatus::Recorded->value, $paymentLog->old_values['status']);
        $this->assertEquals(CustomerPaymentStatus::Verified->value, $paymentLog->new_values['status']);

        // 4. Verify Commission audit record
        $commissionLog = AuditLog::where('action', AuditLogAction::CommissionGenerated->value)
            ->where('subject_type', Commission::class)
            ->where('subject_id', $commission->id)
            ->first();

        $this->assertNotNull($commissionLog);
        $this->assertEquals(AuditLogModule::Financial->value, $commissionLog->module);
        $this->assertEquals(7500.00, (float) $commissionLog->new_values['amount']);
        $this->assertStringContainsString($commission->commission_number, $commissionLog->description);
        $this->assertEquals($payment->id, $commissionLog->metadata['payment_id']);
    }

    /**
     * 6. Attempts to modify or delete audit records through application paths are strictly blocked.
     */
    public function test_06_attempts_to_modify_or_delete_audit_records_are_strictly_blocked(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => 'Super Administrator',
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::CustomerPaymentVerified->value,
            'module' => AuditLogModule::Financial->value,
            'description' => 'Tamper test audit record.',
        ]);

        // Path A: Eloquent update attempt throws RuntimeException
        try {
            $log->update(['description' => 'Fraudulent description edit']);
            $this->fail('Expected RuntimeException on Eloquent update was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Audit logs are immutable and cannot be updated.', $e->getMessage());
        }

        // Path B: Eloquent delete attempt throws RuntimeException
        try {
            $log->delete();
            $this->fail('Expected RuntimeException on Eloquent delete was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Audit logs are append-only and cannot be deleted.', $e->getMessage());
        }

        // Path C: HTTP mutation routes (PUT/PATCH/DELETE) do not exist
        $responsePut = $this->actingAs($this->adminUser)->put("/admin/audit-logs/{$log->id}", [
            'description' => 'Direct HTTP PUT attempt',
        ]);
        $this->assertTrue(in_array($responsePut->status(), [404, 405]), "Expected 404 or 405 on PUT, got {$responsePut->status()}");

        $responseDelete = $this->actingAs($this->adminUser)->delete("/admin/audit-logs/{$log->id}");
        $this->assertTrue(in_array($responseDelete->status(), [404, 405]), "Expected 404 or 405 on DELETE, got {$responseDelete->status()}");

        // Path D: Non-admin role denial (Partner & Staff get 403 Forbidden)
        $responsePartner = $this->actingAs($this->partnerUser)->get('/admin/audit-logs');
        $responsePartner->assertForbidden();

        $responseStaff = $this->actingAs($this->staffUser)->get('/admin/audit-logs');
        $responseStaff->assertForbidden();
    }
}
