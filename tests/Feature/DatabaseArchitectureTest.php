<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadStatus;
use App\Enums\PayoutStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\CommissionService;
use App\Services\PayoutService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_and_rollback_safety(): void
    {
        // Migrations run automatically via RefreshDatabase.
        // Test rollback and re-migrate to guarantee 100% rollback safety and zero circular foreign key locks.
        $rollbackExitCode = Artisan::call('migrate:rollback');
        $this->assertSame(0, $rollbackExitCode, 'Rollback should execute cleanly without foreign key lock errors.');

        $migrateExitCode = Artisan::call('migrate');
        $this->assertSame(0, $migrateExitCode, 'Re-migration should execute cleanly in forward order.');
    }

    public function test_user_roles_include_team_member(): void
    {
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin_test@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $team = User::create([
            'name' => 'Team User',
            'email' => 'team_test@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $partnerUser = User::create([
            'name' => 'Partner User',
            'email' => 'partner_test@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Pending,
        ]);

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($team->isTeamMember());
        $this->assertTrue($partnerUser->isPartner());
        $this->assertTrue($partnerUser->isPending());
    }

    public function test_partner_lead_and_activity_relationships(): void
    {
        $partnerUser = User::create([
            'name' => 'Partner One',
            'email' => 'p1@partner.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::create([
            'user_id' => $partnerUser->id,
            'partner_code' => 'TGP-2026-TEST',
            'company_name' => 'Acme Tech',
            'phone' => '9876543210',
            'bank_account_number' => '123456789012',
        ]);

        $teamMember = User::create([
            'name' => 'Sales Exec',
            'email' => 'sales@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $lead = Lead::create([
            'lead_number' => 'LED-2026-0001',
            'partner_id' => $partner->id,
            'assigned_to' => $teamMember->id,
            'assigned_at' => now(),
            'customer_name' => 'John Doe',
            'customer_phone' => '9988776655',
            'customer_email' => 'john@client.com',
            'service_required' => 'Custom ERP Development',
            'project_description' => 'Need Laravel portal',
            'status' => LeadStatus::Assigned,
            'interest_level' => LeadInterest::Warm,
        ]);

        // Test LeadActivity as canonical timeline
        $activity = LeadActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $teamMember->id,
            'activity_type' => LeadActivityType::Call,
            'from_status' => LeadStatus::Assigned->value,
            'to_status' => LeadStatus::InDiscussion->value,
            'customer_interest' => LeadInterest::Hot,
            'summary' => 'Initial discovery call conducted',
            'details' => 'Customer confirmed budget of 2L.',
            'call_duration_seconds' => 450,
            'next_followup_date' => now()->addDays(2)->toDateString(),
        ]);

        $this->assertCount(1, $partner->leads);
        $this->assertSame($lead->id, $partner->leads->first()->id);
        $this->assertSame($teamMember->id, $lead->assignedTo->id);
        $this->assertCount(1, $lead->activities);
        $this->assertSame('Initial discovery call conducted', $lead->activities->first()->summary);
        $this->assertSame(LeadStatus::Assigned->value, $lead->activities->first()->from_status);
        $this->assertSame(LeadStatus::InDiscussion->value, $lead->activities->first()->to_status);
    }

    public function test_duplicate_payment_to_commission_prevention(): void
    {
        $admin = User::create([
            'name' => 'Admin Boss',
            'email' => 'admin_pay@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $partnerUser = User::create([
            'name' => 'Partner Biz',
            'email' => 'biz@partner.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::create([
            'user_id' => $partnerUser->id,
            'partner_code' => 'TGP-2026-BIZ',
            'company_name' => 'Biz Corp',
            'phone' => '9123456789',
            'bank_account_number' => '987654321098',
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUST-2026-0001',
            'name' => 'Acme Client',
            'phone' => '9000000000',
            'first_partner_id' => $partner->id,
        ]);

        $project = Project::create([
            'partner_id' => $partner->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-2026-0001',
            'client_name' => 'Acme Client',
            'project_title' => 'Portal Project',
            'project_description' => 'Full stack project',
            'final_deal_value' => 100000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'status' => ProjectStatus::InProgress,
        ]);

        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-2026-0001',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'partner_id' => $partner->id,
            'payment_type' => CustomerPaymentType::Advance,
            'amount' => 40000.00,
            'payment_date' => now()->toDateString(),
            'status' => CustomerPaymentStatus::Verified,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'commission_generated' => false,
        ]);

        $service = new CommissionService();

        // 1. Generate commission first time
        $commission = $service->generateFromCustomerPayment($payment, $admin->id);

        $this->assertNotNull($commission);
        $this->assertSame(4000.00, (float) $commission->amount); // 10% of 40,000 = 4,000
        $this->assertSame($payment->id, $commission->customer_payment_id);
        $this->assertSame(CommissionStatus::Payable, $commission->status);

        // Refresh payment
        $payment->refresh();
        $this->assertTrue($payment->commission_generated);
        $this->assertSame($commission->id, $payment->commission->id);

        // 2. Attempt duplicate commission generation - must throw DomainException
        $this->expectException(DomainException::class);
        $service->generateFromCustomerPayment($payment, $admin->id);
    }

    public function test_withdrawal_and_payout_lifecycle(): void
    {
        $admin = User::create([
            'name' => 'Admin Finance',
            'email' => 'finance@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $partnerUser = User::create([
            'name' => 'Partner Ledger',
            'email' => 'ledger@partner.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::create([
            'user_id' => $partnerUser->id,
            'partner_code' => 'TGP-2026-LEDGER',
            'company_name' => 'Ledger Tech',
            'phone' => '9888877777',
            'bank_account_number' => '112233445566',
            'bank_name' => 'HDFC Bank',
            'bank_routing_code' => 'HDFC0001234',
        ]);

        $project = Project::create([
            'partner_id' => $partner->id,
            'project_code' => 'PRJ-2026-LEDGER',
            'client_name' => 'Client X',
            'project_title' => 'E-Commerce Platform',
            'project_description' => 'Delivery in 3 months',
            'final_deal_value' => 50000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'status' => ProjectStatus::InProgress,
        ]);

        $commission1 = Commission::create([
            'commission_number' => 'COM-TEST-001',
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'milestone_name' => 'Milestone 1',
            'milestone_sequence' => 1,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 20000.00,
            'rate_applied' => 10.00,
            'amount' => 2000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Payable,
        ]);

        $commission2 = Commission::create([
            'commission_number' => 'COM-TEST-002',
            'project_id' => $project->id,
            'partner_id' => $partner->id,
            'milestone_name' => 'Milestone 2',
            'milestone_sequence' => 2,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 30000.00,
            'rate_applied' => 10.00,
            'amount' => 3000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Payable,
        ]);

        // Partner requests withdrawal
        $withdrawal = WithdrawalRequest::create([
            'request_number' => 'WTH-2026-0001',
            'partner_id' => $partner->id,
            'amount' => 5000.00,
            'currency' => 'INR',
            'payout_method' => 'bank_transfer',
            'recipient_snapshot' => [
                'bank_name' => 'HDFC Bank',
                'masked_account' => $partner->masked_account_number,
            ],
            'status' => WithdrawalStatus::Pending,
        ]);

        // Link commissions to withdrawal
        $commission1->update(['status' => CommissionStatus::InWithdrawal, 'withdrawal_request_id' => $withdrawal->id]);
        $commission2->update(['status' => CommissionStatus::InWithdrawal, 'withdrawal_request_id' => $withdrawal->id]);

        $payoutService = new PayoutService();

        // Admin approves and disburses payout with UTR
        $payout = $payoutService->createPayout(
            partnerId: $partner->id,
            commissionIds: [$commission1->id, $commission2->id],
            payoutData: [
                'payment_method' => 'bank_transfer',
                'transaction_reference' => 'UTR123456789',
                'withdrawal_request_id' => $withdrawal->id,
                'expected_amount' => 5000.00,
            ],
            adminUserId: $admin->id,
            markCompletedImmediately: true
        );

        $this->assertNotNull($payout);
        $this->assertSame(5000.00, (float) $payout->amount);
        $this->assertSame(PayoutStatus::Completed, $payout->payment_status);
        $this->assertSame('UTR123456789', $payout->transaction_reference);

        // Check commissions are marked Paid
        $commission1->refresh();
        $commission2->refresh();
        $this->assertSame(CommissionStatus::Paid, $commission1->status);
        $this->assertSame(CommissionStatus::Paid, $commission2->status);

        // Check withdrawal request is Settled
        $withdrawal->refresh();
        $this->assertSame(WithdrawalStatus::Settled, $withdrawal->status);
        $this->assertSame($payout->id, $withdrawal->payout->id);
    }
}
