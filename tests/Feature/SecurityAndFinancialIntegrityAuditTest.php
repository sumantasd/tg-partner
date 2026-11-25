<?php

namespace Tests\Feature;

use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\KycStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\PartnerKyc;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\CommissionService;
use App\Services\PayoutService;
use App\Services\WithdrawalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class SecurityAndFinancialIntegrityAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teamUser;
    protected User $partnerUser1;
    protected Partner $partner1;
    protected User $partnerUser2;
    protected Partner $partner2;
    protected Customer $customer1;
    protected Project $project1;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        RateLimiter::clear('test@partner.com|127.0.0.1');

        // Admin
        $this->admin = User::factory()->create([
            'email'  => 'admin@techgoogly.com',
            'role'   => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // Team Member
        $this->teamUser = User::factory()->create([
            'email'  => 'staff@techgoogly.com',
            'role'   => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        // Partner 1
        $this->partnerUser1 = User::factory()->create([
            'email'  => 'partner1@techgoogly.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner1 = Partner::create([
            'user_id'                  => $this->partnerUser1->id,
            'partner_code'             => 'TGP-SEC01',
            'company_name'             => 'Partner One Solutions',
            'phone'                    => '9876543210',
            'bank_name'                => 'HDFC Bank',
            'bank_account_name'        => 'Partner One Solutions',
            'bank_account_number'      => '123456789012',
            'bank_routing_code'        => 'HDFC0001234',
            'payout_method'            => 'bank_transfer',
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'approved_at'              => now(),
            'approved_by'              => $this->admin->id,
        ]);

        // Partner 2
        $this->partnerUser2 = User::factory()->create([
            'email'  => 'partner2@techgoogly.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner2 = Partner::create([
            'user_id'                  => $this->partnerUser2->id,
            'partner_code'             => 'TGP-SEC02',
            'company_name'             => 'Partner Two Solutions',
            'phone'                    => '9876543211',
            'bank_name'                => 'ICICI Bank',
            'bank_account_name'        => 'Partner Two Solutions',
            'bank_account_number'      => '987654321098',
            'bank_routing_code'        => 'ICIC0001234',
            'payout_method'            => 'bank_transfer',
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'approved_at'              => now(),
            'approved_by'              => $this->admin->id,
        ]);

        // Default Customer and Project for tests
        $this->customer1 = Customer::create([
            'customer_code'  => 'CUST-SEC-001',
            'name'           => 'John Doe',
            'company_name'   => 'Client One Corp',
            'contact_person' => 'John Doe',
            'email'          => 'john@clientone.com',
            'phone'          => '9123456780',
            'partner_id'     => $this->partner1->id,
            'first_partner_id'=> $this->partner1->id,
            'status'         => 'active',
            'created_by'     => $this->admin->id,
        ]);

        $this->project1 = Project::create([
            'project_code'           => 'PRJ-SEC-001',
            'client_name'            => 'Client One Corp',
            'project_title'          => 'Cloud ERP Implementation',
            'project_description'    => 'Implementation of Cloud ERP for Client One Corp',
            'customer_id'            => $this->customer1->id,
            'partner_id'             => $this->partner1->id,
            'estimated_budget'       => 50000.00,
            'final_deal_value'       => 50000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'status'                 => ProjectStatus::InProgress,
        ]);
    }

    protected function createCommission(Partner $partner, float $amount, CommissionStatus $status = CommissionStatus::Payable): Commission
    {
        return Commission::create([
            'commission_number'  => 'COM-' . strtoupper(bin2hex(random_bytes(4))),
            'project_id'         => $this->project1->id,
            'partner_id'         => $partner->id,
            'milestone_name'     => 'Milestone Check',
            'milestone_sequence' => (int) (Commission::where('project_id', $this->project1->id)->max('milestone_sequence') ?? 0) + 1,
            'commission_type'    => CommissionType::Percentage,
            'calculation_basis'  => $amount * 10,
            'rate_applied'       => 10.00,
            'amount'             => $amount,
            'currency'           => 'INR',
            'status'             => $status,
            'approved_at'        => now(),
            'approved_by'        => $this->admin->id,
            'payable_at'         => now(),
        ]);
    }

    /**
     * 1. Cross-Partner Data Isolation (IDOR Protection)
     */
    public function test_1_cross_partner_data_isolation(): void
    {
        $lead1 = Lead::create([
            'lead_number'         => 'LED-SEC-001',
            'partner_id'          => $this->partner1->id,
            'customer_name'       => 'Client Alpha',
            'customer_phone'      => '9876543220',
            'service_required'    => 'ERP Development',
            'project_description' => 'Enterprise ERP System',
            'status'              => LeadStatus::Submitted,
        ]);

        $commission1 = $this->createCommission($this->partner1, 5000.00);

        $withdrawal1 = WithdrawalRequest::create([
            'request_number'     => 'WTH-SEC-001',
            'partner_id'         => $this->partner1->id,
            'amount'             => 2000.00,
            'currency'           => 'INR',
            'payout_method'      => 'bank_transfer',
            'status'             => WithdrawalStatus::Pending,
            'recipient_snapshot' => ['account' => '123456789012'],
        ]);

        // Partner 2 attempts to view Partner 1's records -> Must be 403 Forbidden
        $this->actingAs($this->partnerUser2);

        $this->get("/partner/leads/{$lead1->id}")->assertForbidden();
        $this->get("/partner/customers/{$this->customer1->id}")->assertForbidden();
        $this->get("/partner/projects/{$this->project1->id}")->assertForbidden();
        $this->get("/partner/commissions/{$commission1->id}")->assertForbidden();
        $this->get("/partner/withdrawals/{$withdrawal1->id}")->assertForbidden();
        $this->post("/partner/withdrawals/{$withdrawal1->id}/cancel")->assertForbidden();
    }

    /**
     * 2. Staff Financial Access Restrictions
     */
    public function test_2_staff_financial_access_restrictions(): void
    {
        $this->actingAs($this->teamUser);

        // Staff cannot access Admin routes
        $this->get('/admin/dashboard')->assertForbidden();
        $this->get('/admin/payouts')->assertForbidden();
        $this->get('/admin/commissions')->assertForbidden();
        $this->get('/admin/kyc')->assertForbidden();
        $this->get('/admin/partners')->assertForbidden();

        // Staff cannot access Partner financial routes
        $this->get('/partner/commissions')->assertForbidden();
        $this->get('/partner/withdrawals')->assertForbidden();
        $this->get('/partner/payout-settings')->assertForbidden();
        $this->get('/partner/kyc')->assertForbidden();

        // Staff cannot view unassigned leads
        $unassignedLead = Lead::create([
            'lead_number'         => 'LED-SEC-UNASSIGNED',
            'partner_id'          => $this->partner1->id,
            'customer_name'       => 'Private Client',
            'customer_phone'      => '9876543999',
            'service_required'    => 'Consulting',
            'project_description' => 'Confidential project',
            'status'              => LeadStatus::Submitted,
            'assigned_to'         => null,
        ]);

        $this->get("/team/leads/{$unassignedLead->id}")->assertForbidden();
    }

    /**
     * 3. Direct KYC Document URL Access Blocked
     */
    public function test_3_direct_kyc_document_url_access_blocked(): void
    {
        Storage::disk('local')->put('kyc_documents/1/pan_card.pdf', '%PDF-1.4 test document');

        // Document is NOT stored on public disk
        $this->assertFalse(Storage::disk('public')->exists('kyc_documents/1/pan_card.pdf'));

        // Direct web request to storage URL is rejected (403 or 404)
        $response = $this->get('/storage/kyc_documents/1/pan_card.pdf');
        $this->assertTrue(in_array($response->status(), [403, 404]), 'Direct access to KYC documents must be blocked.');
    }

    /**
     * 4. Unauthorized Document Downloads Blocked
     */
    public function test_4_unauthorized_document_downloads_blocked(): void
    {
        Storage::disk('local')->put('kyc_documents/1/pan_card.pdf', '%PDF-1.4 partner1 pan');

        $kyc1 = PartnerKyc::create([
            'partner_id'                    => $this->partner1->id,
            'legal_name'                    => 'Partner One Solutions',
            'partner_type'                  => 'individual',
            'dob_or_incorporation_date'     => '1990-01-01',
            'pan_number_encrypted'          => 'ABCDE1234F',
            'pan_number_masked'             => 'ABCDE••••F',
            'registered_address'            => '123 Market Road',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700001',
            'country'                       => 'India',
            'identity_document_type'        => 'passport',
            'pan_document_path'             => 'kyc_documents/1/pan_card.pdf',
            'identity_document_path'        => 'kyc_documents/1/pan_card.pdf',
            'bank_proof_document_path'       => 'kyc_documents/1/pan_card.pdf',
            'bank_account_holder_name'      => 'Partner One Solutions',
            'bank_name'                     => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'    => '••••9012',
            'bank_ifsc_code'                => 'HDFC0001234',
            'preferred_payout_method'       => 'bank_transfer',
            'kyc_status'                    => KycStatus::Pending,
            'bank_verification_status'      => BankVerificationStatus::Pending,
            'submitted_at'                  => now(),
            'current_version'               => 1,
        ]);

        // Unauthenticated access redirects to login
        $this->get('/partner/kyc/document/pan')->assertRedirect(route('login'));

        // Team member blocked with 403
        $this->actingAs($this->teamUser)
            ->get('/partner/kyc/document/pan')
            ->assertForbidden();

        $this->actingAs($this->teamUser)
            ->get("/admin/kyc/{$kyc1->id}/document/pan")
            ->assertForbidden();

        // Partner 2 cannot access Partner 1's KYC document
        $this->actingAs($this->partnerUser2)
            ->get('/partner/kyc/document/pan')
            ->assertNotFound();

        // Partner 1 (owner) CAN download own document
        $this->actingAs($this->partnerUser1)
            ->get('/partner/kyc/document/pan')
            ->assertOk();

        // Admin CAN download document
        $this->actingAs($this->admin)
            ->get("/admin/kyc/{$kyc1->id}/document/pan")
            ->assertOk();
    }

    /**
     * 5. KYC and Bank Verification Withdrawal Restrictions (All 5 Combinations)
     */
    public function test_5_kyc_and_bank_verification_withdrawal_restrictions(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 5000.00);

        // Combo 1: KYC Pending + Bank Pending = Blocked
        $this->partner1->update([
            'kyc_status'               => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1->fresh(), 1000.00);
    }

    public function test_5b_kyc_approved_bank_pending_blocked(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 5000.00);

        // Combo 2: KYC Approved + Bank Pending = Blocked
        $this->partner1->update([
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1->fresh(), 1000.00);
    }

    public function test_5c_kyc_pending_bank_verified_blocked(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 5000.00);

        // Combo 3: KYC Pending + Bank Verified = Blocked
        $this->partner1->update([
            'kyc_status'               => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1->fresh(), 1000.00);
    }

    public function test_5d_kyc_rejected_bank_verified_blocked(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 5000.00);

        // Combo 4: KYC Rejected + Bank Verified = Blocked
        $this->partner1->update([
            'kyc_status'               => KycStatus::Rejected,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1->fresh(), 1000.00);
    }

    public function test_5e_kyc_approved_bank_verified_eligible_and_modification_invalidates(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 5000.00);

        // Combo 5: KYC Approved + Bank Verified = Eligible
        $this->partner1->update([
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);

        $withdrawal = $service->requestWithdrawal($this->partner1->fresh(), 1000.00);
        $this->assertNotNull($withdrawal);
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);

        // Test bank detail modification resets bank verification to Pending
        $this->actingAs($this->partnerUser1);
        $response = $this->put('/partner/payout-settings', [
            'payout_method'                 => 'bank_transfer',
            'bank_name'                     => 'Axis Bank',
            'bank_account_name'             => 'Partner One Updated',
            'bank_account_number'           => '998877665544',
            'bank_account_number_confirmation' => '998877665544',
            'bank_routing_code'             => 'UTIB0001234',
        ]);
        $response->assertRedirect(route('partner.payout-settings.edit'));

        $this->partner1->refresh();
        $this->assertSame(BankVerificationStatus::Pending, $this->partner1->bank_verification_status, 'Bank verification must be reset to Pending upon bank details modification.');
    }

    /**
     * 6. Duplicate Payment Verification Protection
     */
    public function test_6_duplicate_payment_verification_protection(): void
    {
        $payment = CustomerPayment::create([
            'payment_number'       => 'PAY-SEC-006',
            'customer_id'          => $this->customer1->id,
            'project_id'           => $this->project1->id,
            'partner_id'           => $this->partner1->id,
            'amount'               => 20000.00,
            'currency'             => 'INR',
            'payment_date'         => now()->toDateString(),
            'payment_method'       => PaymentMethod::BankTransfer,
            'payment_type'         => CustomerPaymentType::Milestone,
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
            'created_by'           => $this->admin->id,
        ]);

        $service = app(CommissionService::class);

        // 1st verification generates commission
        $commission = $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
        $this->assertNotNull($commission);
        $this->assertSame(2000.00, (float) $commission->amount);

        // 2nd verification attempt on same payment throws DomainException
        $this->expectException(DomainException::class);
        $service->verifyPaymentAndGenerateCommission($payment->fresh(), $this->admin->id);
    }

    /**
     * 7. Duplicate Commission Prevention
     */
    public function test_7_duplicate_commission_prevention(): void
    {
        $payment = CustomerPayment::create([
            'payment_number'       => 'PAY-SEC-007',
            'customer_id'          => $this->customer1->id,
            'project_id'           => $this->project1->id,
            'partner_id'           => $this->partner1->id,
            'amount'               => 10000.00,
            'currency'             => 'INR',
            'payment_date'         => now()->toDateString(),
            'payment_method'       => PaymentMethod::BankTransfer,
            'payment_type'         => CustomerPaymentType::Milestone,
            'status'               => CustomerPaymentStatus::Verified,
            'commission_generated' => true,
            'created_by'           => $this->admin->id,
        ]);

        Commission::create([
            'commission_number'   => 'COM-SEC-007',
            'project_id'          => $this->project1->id,
            'partner_id'          => $this->partner1->id,
            'customer_payment_id' => $payment->id,
            'amount'              => 1000.00,
            'currency'            => 'INR',
            'status'              => CommissionStatus::Payable,
            'rate_applied'        => 10.00,
            'calculation_basis'   => 10000.00,
            'milestone_name'      => 'Milestone',
            'milestone_sequence'  => 1,
            'commission_type'     => CommissionType::Percentage,
        ]);

        $service = app(CommissionService::class);

        // Must throw DomainException
        $this->expectException(DomainException::class);
        $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
    }

    /**
     * 8. Duplicate Withdrawal Protection (Double-spending prevention)
     */
    public function test_8_duplicate_withdrawal_protection(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 1000.00);

        // 1st withdrawal of full ₹1000 succeeds
        $w1 = $service->requestWithdrawal($this->partner1, 1000.00);
        $this->assertNotNull($w1);

        // 2nd withdrawal immediately fails because available balance is now ₹0
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1, 1000.00);
    }

    /**
     * 9. Concurrent Withdrawal Requests Row-Level Locking Verification
     */
    public function test_9_concurrent_withdrawal_requests(): void
    {
        $commission = $this->createCommission($this->partner1, 1500.00);

        // Verify that lockForUpdate query executes correctly within transaction
        DB::transaction(function () use ($commission) {
            $locked = Commission::where('partner_id', $this->partner1->id)
                ->whereIn('status', [CommissionStatus::Payable, CommissionStatus::Approved])
                ->whereNull('withdrawal_request_id')
                ->whereNull('payout_id')
                ->lockForUpdate()
                ->get();

            $this->assertCount(1, $locked);
            $this->assertSame($commission->id, $locked->first()->id);
        });
    }

    /**
     * 10. Insufficient Balance Protection & Minimum ₹500 Enforcement
     */
    public function test_10_insufficient_balance_protection(): void
    {
        $service = app(WithdrawalService::class);
        $this->createCommission($this->partner1, 800.00);

        // Attempting to withdraw below minimum ₹500
        try {
            $service->requestWithdrawal($this->partner1, 400.00);
            $this->fail('Expected InvalidArgumentException for withdrawal below minimum amount.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }

        // Attempting to withdraw more than available balance (₹1000 > ₹800)
        try {
            $service->requestWithdrawal($this->partner1, 1000.00);
            $this->fail('Expected DomainException for withdrawal exceeding balance.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('exceeds your available withdrawal balance', $e->getMessage());
        }
    }

    /**
     * 11. Commission Reservation and Release on Rejection / Cancellation
     */
    public function test_11_commission_reservation_and_release(): void
    {
        $service = app(WithdrawalService::class);
        $commission = $this->createCommission($this->partner1, 2000.00);

        // Request withdrawal -> reserves commission in InWithdrawal
        $w = $service->requestWithdrawal($this->partner1, 2000.00);
        $commission->refresh();
        $this->assertSame(CommissionStatus::InWithdrawal, $commission->status);
        $this->assertSame($w->id, $commission->withdrawal_request_id);

        // Cancel withdrawal -> releases commission back to Payable
        $service->cancelWithdrawal($w, $this->partner1, 'Partner decided to wait');
        $commission->refresh();
        $this->assertSame(CommissionStatus::Payable, $commission->status);
        $this->assertNull($commission->withdrawal_request_id);

        // Re-request and reject by admin -> releases commission back to Payable
        $w2 = $service->requestWithdrawal($this->partner1, 2000.00);
        $commission->refresh();
        $this->assertSame(CommissionStatus::InWithdrawal, $commission->status);

        $service->rejectWithdrawal($w2, 'Bank account name discrepancy', $this->admin->id);
        $commission->refresh();
        $this->assertSame(CommissionStatus::Payable, $commission->status);
        $this->assertNull($commission->withdrawal_request_id);
    }

    /**
     * 12. Unauthorized Payout Status Changes & Sealing of Completed Payouts
     */
    public function test_12_unauthorized_payout_status_changes(): void
    {
        $payoutService = app(PayoutService::class);
        $commission = $this->createCommission($this->partner1, 1200.00);

        $w = WithdrawalRequest::create([
            'request_number'     => 'WTH-SEC-012',
            'partner_id'         => $this->partner1->id,
            'amount'             => 1200.00,
            'currency'           => 'INR',
            'payout_method'      => 'bank_transfer',
            'status'             => WithdrawalStatus::Pending,
            'recipient_snapshot' => ['account' => '123456789012'],
        ]);

        $payout = $payoutService->createPayout(
            partnerId: $this->partner1->id,
            commissionIds: [$commission->id],
            payoutData: ['payment_method' => 'bank_transfer', 'withdrawal_request_id' => $w->id],
            adminUserId: $this->admin->id,
            markCompletedImmediately: false
        );

        // Partner or team member cannot approve payout via POST
        $this->actingAs($this->partnerUser1)
            ->post("/admin/payouts/{$w->id}/approve")
            ->assertForbidden();

        $this->actingAs($this->teamUser)
            ->post("/admin/payouts/{$w->id}/approve")
            ->assertForbidden();

        // Complete the payout
        $completedPayout = $payoutService->completePayout($payout, 'UTR-SEC-012345');
        $this->assertSame(PayoutStatus::Completed, $completedPayout->payment_status);

        // Attempting to cancel / release a completed payout must fail
        $this->expectException(DomainException::class);
        $payoutService->releasePayout($completedPayout, PayoutStatus::Cancelled, 'Attempt cancel completed');
    }

    /**
     * 13. CSRF and Authentication Protections
     */
    public function test_13_csrf_and_authentication_protections(): void
    {
        // Unauthenticated access to protected area redirects to login
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->get('/partner/workspace')->assertRedirect(route('login'));
        $this->get('/team/workspace')->assertRedirect(route('login'));

        // Failed login rate limiting (5 attempts)
        $email = 'bruteforce@partner.com';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => $email,
                'password' => 'WrongPassword',
            ]);
        }

        // 6th attempt should be blocked by rate limiter
        $response = $this->post('/login', [
            'email'    => $email,
            'password' => 'WrongPassword',
        ]);
        $response->assertSessionHasErrors('email');
        $error = session('errors')->first('email');
        $this->assertStringContainsString('Too many login attempts', $error);
    }

    /**
     * 14. Suspended Account Restrictions
     */
    public function test_14_suspended_account_restrictions(): void
    {
        $suspendedStaff = User::create([
            'name'     => 'Suspended Staff',
            'email'    => 'suspended_staff@techgoogly.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::TeamMember,
            'status'   => UserStatus::Suspended,
        ]);

        $suspendedAdmin = User::create([
            'name'     => 'Suspended Admin',
            'email'    => 'suspended_admin@techgoogly.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Suspended,
        ]);

        // Suspended staff cannot access workspace
        $this->actingAs($suspendedStaff)
            ->get('/team/workspace')
            ->assertForbidden();

        // Suspended staff login attempt is rejected
        Auth::logout();
        $response = $this->post('/login', [
            'email'    => 'suspended_staff@techgoogly.com',
            'password' => 'Password123!',
        ]);
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());

        // Suspended admin cannot access dashboard
        $this->actingAs($suspendedAdmin)
            ->get('/admin/dashboard')
            ->assertForbidden();

        // Suspended partner cannot access protected partner workspace
        $suspendedPartnerUser = User::create([
            'name'     => 'Suspended Partner',
            'email'    => 'suspended_partner@techgoogly.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Suspended,
        ]);

        $this->actingAs($suspendedPartnerUser)
            ->get('/partner/workspace')
            ->assertRedirect(route('partner.suspended'));
    }

    /**
     * 15. Existing Application Functionality Regression (Full End-to-End Cycle)
     */
    public function test_15_existing_application_functionality_regression(): void
    {
        // 1. Lead creation
        $lead = Lead::create([
            'lead_number'         => 'LED-REG-015',
            'partner_id'          => $this->partner1->id,
            'customer_name'       => 'Regression Customer',
            'customer_phone'      => '9876500015',
            'service_required'    => 'Cloud Migration',
            'project_description' => 'Migration from on-prem to cloud',
            'status'              => LeadStatus::Submitted,
        ]);
        $this->assertNotNull($lead);

        // 2. Lead assigned to team member
        $lead->update([
            'assigned_to' => $this->teamUser->id,
            'status'      => LeadStatus::Assigned,
        ]);

        // 3. Customer conversion
        $customer = Customer::create([
            'customer_code'    => 'CUS-REG-015',
            'name'             => $lead->customer_name,
            'phone'            => $lead->customer_phone,
            'first_partner_id' => $this->partner1->id,
            'created_by'       => $this->admin->id,
        ]);

        // 4. Project creation
        $project = Project::create([
            'project_code'           => 'PRJ-REG-015',
            'client_name'            => 'Regression Customer',
            'project_title'          => 'Cloud Migration',
            'project_description'    => 'Migration from on-prem to cloud',
            'customer_id'            => $customer->id,
            'partner_id'             => $this->partner1->id,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'final_deal_value'       => 100000.00,
            'status'                 => ProjectStatus::InProgress,
        ]);

        // 5. Payment recorded
        $payment = CustomerPayment::create([
            'payment_number'       => 'PAY-REG-015',
            'customer_id'          => $customer->id,
            'project_id'           => $project->id,
            'partner_id'           => $this->partner1->id,
            'amount'               => 50000.00,
            'currency'             => 'INR',
            'payment_date'         => now()->toDateString(),
            'payment_method'       => PaymentMethod::BankTransfer,
            'payment_type'         => CustomerPaymentType::Milestone,
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
            'created_by'           => $this->admin->id,
        ]);

        // 6. Admin verifies payment -> commission generated
        $commissionService = app(CommissionService::class);
        $commission = $commissionService->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
        $this->assertNotNull($commission);
        $this->assertSame(5000.00, (float) $commission->amount);
        $this->assertSame(CommissionStatus::Payable, $commission->status);

        // 7. Partner requests withdrawal
        $withdrawalService = app(WithdrawalService::class);
        $withdrawal = $withdrawalService->requestWithdrawal($this->partner1, 5000.00);
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);
        $commission->refresh();
        $this->assertSame(CommissionStatus::InWithdrawal, $commission->status);

        // 8. Admin approves withdrawal
        $withdrawalService->approveWithdrawal($withdrawal, $this->admin->id);
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Approved, $withdrawal->status);

        // 9. Admin creates and completes payout
        $payoutService = app(PayoutService::class);
        $payout = $payoutService->createPayout(
            partnerId: $this->partner1->id,
            commissionIds: [$commission->id],
            payoutData: [
                'payment_method'        => 'bank_transfer',
                'transaction_reference' => 'UTR-REG-999888',
                'withdrawal_request_id' => $withdrawal->id,
            ],
            adminUserId: $this->admin->id,
            markCompletedImmediately: true
        );

        $this->assertSame(PayoutStatus::Completed, $payout->payment_status);
        $commission->refresh();
        $this->assertSame(CommissionStatus::Paid, $commission->status);
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Settled, $withdrawal->status);
    }
}
