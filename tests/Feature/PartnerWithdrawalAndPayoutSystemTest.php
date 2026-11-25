<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\PayoutStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class PartnerWithdrawalAndPayoutSystemTest extends TestCase
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

        // Admin
        $this->admin = User::factory()->create([
            'email' => 'admin_test@techgoogly.com',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // Team Member
        $this->teamUser = User::factory()->create([
            'email' => 'team_test@techgoogly.com',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        // Partner 1
        $this->partnerUser1 = User::factory()->create([
            'email' => 'partner1_test@techgoogly.com',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner1 = Partner::create([
            'user_id' => $this->partnerUser1->id,
            'partner_code' => 'TGP-TEST01',
            'company_name' => 'Acme Referral Solutions',
            'phone' => '9876543210',
            'bank_name' => 'HDFC Bank',
            'bank_account_name' => 'Acme Referral Solutions',
            'bank_account_number' => '123456789012',
            'bank_routing_code' => 'HDFC0001234',
            'payout_method' => 'bank_transfer',
            'payout_details' => 'acme@okhdfcbank',
            'kyc_status' => \App\Enums\KycStatus::Approved,
            'bank_verification_status' => \App\Enums\BankVerificationStatus::Verified,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        // Partner 2
        $this->partnerUser2 = User::factory()->create([
            'email' => 'partner2_test@techgoogly.com',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner2 = Partner::create([
            'user_id' => $this->partnerUser2->id,
            'partner_code' => 'TGP-TEST02',
            'company_name' => 'Beta Tech Partners',
            'phone' => '9876543211',
            'bank_name' => 'ICICI Bank',
            'bank_account_name' => 'Beta Tech Partners',
            'bank_account_number' => '987654321098',
            'bank_routing_code' => 'ICIC0001234',
            'payout_method' => 'bank_transfer',
            'kyc_status' => \App\Enums\KycStatus::Approved,
            'bank_verification_status' => \App\Enums\BankVerificationStatus::Verified,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        // Customer & Project for Partner 1
        $this->customer1 = Customer::create([
            'customer_code' => 'CUST-001',
            'name' => 'John Doe',
            'company_name' => 'Client One Corp',
            'contact_person' => 'John Doe',
            'email' => 'john@clientone.com',
            'phone' => '9123456780',
            'partner_id' => $this->partner1->id,
            'status' => 'active',
        ]);

        $this->project1 = Project::create([
            'project_code' => 'PRJ-001',
            'client_name' => 'Client One Corp',
            'project_title' => 'Cloud ERP Implementation',
            'project_description' => 'Implementation of Cloud ERP for Client One Corp',
            'customer_id' => $this->customer1->id,
            'partner_id' => $this->partner1->id,
            'estimated_budget' => 50000.00,
            'final_deal_value' => 50000.00,
            'status' => ProjectStatus::InProgress,
        ]);
    }

    protected function createCommission(Partner $partner, float $amount, CommissionStatus $status = CommissionStatus::Payable): Commission
    {
        return Commission::create([
            'commission_number' => 'COM-' . strtoupper(bin2hex(random_bytes(4))),
            'project_id' => $this->project1->id,
            'partner_id' => $partner->id,
            'milestone_name' => 'Milestone Check',
            'milestone_sequence' => (int) (Commission::where('project_id', $this->project1->id)->max('milestone_sequence') ?? 0) + 1,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => $amount * 10,
            'rate_applied' => 10.00,
            'amount' => $amount,
            'currency' => 'INR',
            'status' => $status,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
            'payable_at' => now(),
        ]);
    }

    /**
     * Requirement 1: Partner can view own available balance.
     */
    public function test_partner_can_view_own_available_balance(): void
    {
        $this->createCommission($this->partner1, 1500.00, CommissionStatus::Payable);
        $this->createCommission($this->partner1, 2500.00, CommissionStatus::Payable);

        $response = $this->actingAs($this->partnerUser1)
            ->get(route('partner.withdrawals.index'));

        $response->assertOk();
        $response->assertSee('4,000.00'); // Total available balance
    }

    /**
     * Requirement 2: Partner cannot view another Partner's balance or withdrawal request.
     */
    public function test_partner_cannot_view_another_partners_balance_or_request(): void
    {
        $this->createCommission($this->partner1, 5000.00, CommissionStatus::Payable);
        $this->createCommission($this->partner2, 3000.00, CommissionStatus::Payable);

        $service = app(WithdrawalService::class);
        $withdrawal1 = $service->requestWithdrawal($this->partner1, 2000.00);

        // Partner 2 attempts to view Partner 1's withdrawal detail
        $forbiddenResponse = $this->actingAs($this->partnerUser2)
            ->get(route('partner.withdrawals.show', $withdrawal1));

        $forbiddenResponse->assertForbidden();

        // Partner 2 visits their own withdrawals index: must NOT see Partner 1's request # or amount
        $indexResponse = $this->actingAs($this->partnerUser2)
            ->get(route('partner.withdrawals.index'));

        $indexResponse->assertOk();
        $indexResponse->assertDontSee($withdrawal1->request_number);
    }

    /**
     * Requirement 3: Withdrawal below ₹500 is rejected.
     */
    public function test_withdrawal_below_500_is_rejected(): void
    {
        $this->createCommission($this->partner1, 2000.00, CommissionStatus::Payable);

        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.store'), [
                'amount' => 450.00,
                'payout_method' => 'bank_transfer',
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    /**
     * Requirement 4: Withdrawal exceeding available balance is rejected.
     */
    public function test_withdrawal_exceeding_available_balance_is_rejected(): void
    {
        $this->createCommission($this->partner1, 1000.00, CommissionStatus::Payable);

        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.store'), [
                'amount' => 2500.00,
                'payout_method' => 'bank_transfer',
            ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseCount('withdrawal_requests', 0);
    }

    /**
     * Requirement 5: Pending commissions cannot be withdrawn.
     */
    public function test_pending_commissions_cannot_be_withdrawn(): void
    {
        // Commission in Pending status (unverified payment)
        $this->createCommission($this->partner1, 2000.00, CommissionStatus::Pending);

        $service = app(WithdrawalService::class);
        $summary = $service->getPartnerBalanceSummary($this->partner1);

        $this->assertSame(0.00, $summary['available_balance']);
        $this->assertSame(2000.00, $summary['pending_commission']);

        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.store'), [
                'amount' => 1000.00,
                'payout_method' => 'bank_transfer',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * Requirement 6: Already-paid commissions cannot be withdrawn again.
     */
    public function test_already_paid_commissions_cannot_be_withdrawn_again(): void
    {
        $this->createCommission($this->partner1, 3000.00, CommissionStatus::Paid);

        $service = app(WithdrawalService::class);
        $summary = $service->getPartnerBalanceSummary($this->partner1);

        $this->assertSame(0.00, $summary['available_balance']);
        $this->assertSame(3000.00, $summary['total_paid']);

        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.store'), [
                'amount' => 1000.00,
                'payout_method' => 'bank_transfer',
            ]);

        $response->assertSessionHasErrors('amount');
    }

    /**
     * Requirement 7: Successful withdrawal reserves eligible commissions atomically.
     */
    public function test_successful_withdrawal_reserves_eligible_commissions(): void
    {
        $comm1 = $this->createCommission($this->partner1, 1000.00, CommissionStatus::Payable);
        $comm2 = $this->createCommission($this->partner1, 1500.00, CommissionStatus::Payable);

        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 2500.00);

        $this->assertNotNull($withdrawal);
        $this->assertSame(WithdrawalStatus::Pending, $withdrawal->status);
        $this->assertSame(2500.00, (float) $withdrawal->amount);

        // Check linked commissions status changed to InWithdrawal
        $comm1->refresh();
        $comm2->refresh();
        $this->assertSame(CommissionStatus::InWithdrawal, $comm1->status);
        $this->assertSame(CommissionStatus::InWithdrawal, $comm2->status);
        $this->assertSame($withdrawal->id, $comm1->withdrawal_request_id);
        $this->assertSame($withdrawal->id, $comm2->withdrawal_request_id);

        // Available balance is now 0
        $summary = $service->getPartnerBalanceSummary($this->partner1);
        $this->assertSame(0.00, $summary['available_balance']);
        $this->assertSame(2500.00, $summary['reserved_in_pending']);
    }

    /**
     * Requirement 8 & 9: Concurrent withdrawal requests and duplicate reservations are prevented.
     */
    public function test_duplicate_and_overdraw_withdrawals_are_prevented(): void
    {
        $this->createCommission($this->partner1, 1000.00, CommissionStatus::Payable);

        $service = app(WithdrawalService::class);
        $w1 = $service->requestWithdrawal($this->partner1, 1000.00);
        $this->assertNotNull($w1);

        // Attempting to withdraw again from the now-exhausted balance must fail
        $this->expectException(DomainException::class);
        $service->requestWithdrawal($this->partner1, 1000.00);
    }

    /**
     * Requirement 10: Pending withdrawal can be cancelled by its owner.
     */
    public function test_pending_withdrawal_can_be_cancelled_by_owner(): void
    {
        $comm = $this->createCommission($this->partner1, 2000.00, CommissionStatus::Payable);

        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 2000.00);

        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.cancel', $withdrawal), [
                'reason' => 'Need to change account details',
            ]);

        $response->assertRedirect();
        $withdrawal->refresh();
        $comm->refresh();

        $this->assertSame(WithdrawalStatus::Cancelled, $withdrawal->status);
        $this->assertSame(CommissionStatus::Payable, $comm->status);
        $this->assertNull($comm->withdrawal_request_id);

        // Balance is restored
        $summary = $service->getPartnerBalanceSummary($this->partner1);
        $this->assertSame(2000.00, $summary['available_balance']);
    }

    /**
     * Requirement 11: Approved withdrawal cannot be cancelled by Partner.
     */
    public function test_approved_withdrawal_cannot_be_cancelled_by_partner(): void
    {
        $this->createCommission($this->partner1, 2000.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 2000.00);

        // Admin approves
        $service->approveWithdrawal($withdrawal, $this->admin->id);

        // Partner attempts to cancel approved request
        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.withdrawals.cancel', $withdrawal));

        $response->assertSessionHas('error');
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Approved, $withdrawal->status);
    }

    /**
     * Requirement 12: Admin can approve a request.
     */
    public function test_admin_can_approve_a_request(): void
    {
        $this->createCommission($this->partner1, 1500.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 1500.00);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.payouts.approve', $withdrawal), [
                'admin_notes' => 'Verified KYC and bank details',
            ]);

        $response->assertRedirect(route('admin.payouts.show', $withdrawal));
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Approved, $withdrawal->status);
        $this->assertSame($this->admin->id, $withdrawal->reviewed_by);
    }

    /**
     * Requirement 13 & 14: Admin can reject with a reason and rejection releases reserved commissions.
     */
    public function test_admin_can_reject_with_reason_releasing_commissions(): void
    {
        $comm = $this->createCommission($this->partner1, 3000.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 3000.00);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.payouts.reject', $withdrawal), [
                'reason' => 'IFSC code mismatch with bank name',
            ]);

        $response->assertRedirect(route('admin.payouts.show', $withdrawal));
        $withdrawal->refresh();
        $comm->refresh();

        $this->assertSame(WithdrawalStatus::Rejected, $withdrawal->status);
        $this->assertSame('IFSC code mismatch with bank name', $withdrawal->rejection_reason);

        // Commission is released back to Payable
        $this->assertSame(CommissionStatus::Payable, $comm->status);
        $this->assertNull($comm->withdrawal_request_id);

        $summary = $service->getPartnerBalanceSummary($this->partner1);
        $this->assertSame(3000.00, $summary['available_balance']);
    }

    /**
     * Requirement 15: Admin can mark payout as Processing.
     */
    public function test_admin_can_mark_payout_as_processing(): void
    {
        $this->createCommission($this->partner1, 2000.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 2000.00);
        $service->approveWithdrawal($withdrawal, $this->admin->id);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.payouts.process', $withdrawal), [
                'admin_notes' => 'Sent to payment gateway',
            ]);

        $response->assertRedirect(route('admin.payouts.show', $withdrawal));
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Processing, $withdrawal->status);
    }

    /**
     * Requirement 16 & 17: Paid payout requires UTR and updates commission statuses correctly.
     */
    public function test_paid_payout_requires_utr_and_updates_commissions_correctly(): void
    {
        $comm = $this->createCommission($this->partner1, 2500.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 2500.00);
        $service->approveWithdrawal($withdrawal, $this->admin->id);

        // Missing UTR must fail validation
        $failResponse = $this->actingAs($this->admin)
            ->post(route('admin.payouts.complete', $withdrawal), [
                'payment_date' => now()->toDateString(),
            ]);
        $failResponse->assertSessionHasErrors('transaction_reference');

        // With valid UTR and receipt file
        $file = UploadedFile::fake()->create('bank_receipt.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->admin)
            ->post(route('admin.payouts.complete', $withdrawal), [
                'transaction_reference' => 'CMS-UTR-99887766',
                'payment_date' => now()->toDateString(),
                'receipt' => $file,
                'admin_notes' => 'Disbursed through HDFC Corporate portal',
            ]);

        $response->assertRedirect(route('admin.payouts.show', $withdrawal));
        $withdrawal->refresh();
        $comm->refresh();

        $this->assertTrue($withdrawal->isPaid());
        $this->assertNotNull($withdrawal->settled_at);

        // Commission is marked Paid
        $this->assertSame(CommissionStatus::Paid, $comm->status);
        $this->assertNotNull($comm->paid_at);

        // Payout record is created with UTR and receipt
        $payout = $withdrawal->payout;
        $this->assertNotNull($payout);
        $this->assertSame('CMS-UTR-99887766', $payout->transaction_reference);
        $this->assertSame(PayoutStatus::Completed, $payout->payment_status);
        $this->assertNotNull($payout->receipt_path);
        Storage::disk('local')->assertExists($payout->receipt_path);
    }

    /**
     * Requirement 18: Duplicate payout completion is prevented.
     */
    public function test_duplicate_payout_completion_is_prevented(): void
    {
        $this->createCommission($this->partner1, 1000.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $withdrawal = $service->requestWithdrawal($this->partner1, 1000.00);
        $service->approveWithdrawal($withdrawal, $this->admin->id);
        $service->completePayout($withdrawal, 'UTR001', now()->toDateString(), $this->admin->id);

        // Calling completePayout again throws exception
        $this->expectException(DomainException::class);
        $service->completePayout($withdrawal, 'UTR002', now()->toDateString(), $this->admin->id);
    }

    /**
     * Requirement 19: Team Member cannot access financial routes or bank details.
     */
    public function test_team_member_cannot_access_financial_routes(): void
    {
        $this->actingAs($this->teamUser)
            ->get(route('admin.payouts.index'))
            ->assertForbidden();

        $this->actingAs($this->teamUser)
            ->get(route('partner.withdrawals.index'))
            ->assertForbidden();

        $this->actingAs($this->teamUser)
            ->get(route('partner.payout-settings.edit'))
            ->assertForbidden();
    }

    /**
     * Requirement 20: Partner cannot access Admin payout routes.
     */
    public function test_partner_cannot_access_admin_payout_routes(): void
    {
        $this->actingAs($this->partnerUser1)
            ->get(route('admin.payouts.index'))
            ->assertForbidden();

        $this->actingAs($this->partnerUser1)
            ->get(route('admin.payouts.pending'))
            ->assertForbidden();
    }

    /**
     * Requirement 21: Partner cannot download another Partner's payment proof.
     */
    public function test_partner_cannot_download_another_partners_payment_proof(): void
    {
        $this->createCommission($this->partner1, 2000.00, CommissionStatus::Payable);
        $service = app(WithdrawalService::class);
        $w1 = $service->requestWithdrawal($this->partner1, 2000.00);
        $service->approveWithdrawal($w1, $this->admin->id);

        $fakePath = 'private/payout_receipts/sample.pdf';
        Storage::disk('local')->put($fakePath, 'PDF Content');
        $service->completePayout($w1, 'UTR123', now()->toDateString(), $this->admin->id, $fakePath);

        // Partner 2 attempts to download Partner 1's proof
        $response = $this->actingAs($this->partnerUser2)
            ->get(route('partner.withdrawals.proof', $w1));

        $response->assertForbidden();

        // Partner 1 can download own proof
        $ownerResponse = $this->actingAs($this->partnerUser1)
            ->get(route('partner.withdrawals.proof', $w1));

        $ownerResponse->assertOk();
    }

    /**
     * Requirement 20: Withdrawal fails if partner has no valid payout configuration.
     */
    public function test_withdrawal_fails_if_no_payout_details_configured(): void
    {
        $unconfiguredUser = User::factory()->create([
            'email' => 'unconfigured@techgoogly.com',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $unconfiguredPartner = Partner::create([
            'user_id' => $unconfiguredUser->id,
            'partner_code' => 'TGP-NOCONF',
            'company_name' => 'Unconfigured Partner',
            'phone' => '9998887776',
            'payout_method' => 'bank_transfer',
            'bank_account_name' => null,
            'bank_account_number' => null,
            'kyc_status' => \App\Enums\KycStatus::Approved,
            'bank_verification_status' => \App\Enums\BankVerificationStatus::Verified,
            'approved_at' => now(),
            'approved_by' => $this->admin->id,
        ]);

        $this->createCommission($unconfiguredPartner, 2000.00, CommissionStatus::Payable);

        $response = $this->actingAs($unconfiguredUser)
            ->post(route('partner.withdrawals.store'), [
                'amount' => 1000.00,
                'payout_method' => 'bank_transfer',
            ]);

        $response->assertSessionHasErrors(['amount']);
    }

    /**
     * Requirement 21: Partner can view withdrawal timeline and details.
     */
    public function test_partner_can_view_withdrawal_timeline_and_details(): void
    {
        $this->createCommission($this->partner1, 3000.00, CommissionStatus::Payable);
        $w = app(WithdrawalService::class)->requestWithdrawal($this->partner1, 1500.00);

        $response = $this->actingAs($this->partnerUser1)
            ->get(route('partner.withdrawals.show', $w));

        $response->assertOk();
        $response->assertSee($w->request_number);
        $response->assertSee('1,500.00');
        $response->assertSee('Request Submitted');
        $response->assertSee('Acme Referral Solutions');
    }

    /**
     * Requirement 22: Admin can view withdrawal requests list and pending queue.
     */
    public function test_admin_can_view_withdrawal_requests_list(): void
    {
        $this->createCommission($this->partner1, 3000.00, CommissionStatus::Payable);
        $w = app(WithdrawalService::class)->requestWithdrawal($this->partner1, 1500.00);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.payouts.index'));

        $response->assertOk();
        $response->assertSee('Payouts & Partner Withdrawals', false);
        $response->assertSee($w->request_number);

        $pendingResponse = $this->actingAs($this->admin)
            ->get(route('admin.payouts.pending'));

        $pendingResponse->assertOk();
        $pendingResponse->assertSee($w->request_number);
    }

    /**
     * Requirement 23: All amounts are correctly calculated in INR.
     */
    public function test_all_amounts_are_correctly_calculated_in_inr(): void
    {
        $this->createCommission($this->partner1, 1250.75, CommissionStatus::Payable);
        $this->createCommission($this->partner1, 3749.25, CommissionStatus::Payable);

        $service = app(WithdrawalService::class);
        $summary = $service->getPartnerBalanceSummary($this->partner1);

        $this->assertSame(5000.00, $summary['available_balance']);
        $this->assertSame(5000.00, $summary['total_earned']);

        $w = $service->requestWithdrawal($this->partner1, 5000.00);
        $this->assertSame('INR', $w->currency);
        $this->assertSame(5000.00, (float) $w->amount);
    }
}

