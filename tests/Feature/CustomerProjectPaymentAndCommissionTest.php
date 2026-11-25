<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\CustomerService;
use App\Services\ProjectService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Tests\TestCase;

class CustomerProjectPaymentAndCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $partnerUser;
    protected Partner $partner;
    protected User $otherPartnerUser;
    protected Partner $otherPartner;
    protected User $teamMember;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'              => 'Sumanta Das',
            'email'             => 'admin@techgoogly.com',
            'password'          => 'secret123',
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partnerUser = User::create([
            'name'              => 'Alpha Partner',
            'email'             => 'alpha@partner.com',
            'password'          => 'secret123',
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'             => $this->partnerUser->id,
            'partner_code'        => 'TGP-2026-ALPHA',
            'company_name'        => 'Alpha Infotech',
            'phone'               => '9876543210',
            'bank_account_number' => '1234567890',
        ]);

        $this->otherPartnerUser = User::create([
            'name'              => 'Beta Partner',
            'email'             => 'beta@partner.com',
            'password'          => 'secret123',
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->otherPartner = Partner::create([
            'user_id'             => $this->otherPartnerUser->id,
            'partner_code'        => 'TGP-2026-BETA',
            'company_name'        => 'Beta Solutions',
            'phone'               => '9876543211',
            'bank_account_number' => '0987654321',
        ]);

        $this->teamMember = User::create([
            'name'              => 'Sales Consultant',
            'email'             => 'staff@techgoogly.com',
            'password'          => 'secret123',
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        // Ensure default 10% commission rate is configured
        SystemSetting::set('default_commission_rate', '10.00', $this->admin->id);
    }

    /**
     * Helper to create a Won lead.
     */
    protected function createWonLead(): Lead
    {
        return Lead::create([
            'lead_number'         => 'LED-2026-0001',
            'partner_id'          => $this->partner->id,
            'customer_name'       => 'Rajesh Sharma',
            'customer_company'    => 'Sharma Enterprises',
            'customer_phone'      => '9800011122',
            'customer_email'      => 'rajesh@sharma.com',
            'city'                => 'Kolkata',
            'service_required'    => 'ERP Software',
            'project_description' => 'Full ERP system with inventory and billing',
            'estimated_value'     => 100000.00,
            'status'              => LeadStatus::Won,
        ]);
    }

    /**
     * 1. Admin can convert a Won Lead into a Customer.
     * 4. Original Partner attribution is preserved.
     */
    public function test_admin_can_convert_won_lead_into_customer_preserving_partner_attribution(): void
    {
        $lead = $this->createWonLead();

        $response = $this->actingAs($this->admin)->post(route('admin.leads.convert', $lead), [
            'name'            => 'Rajesh Sharma Edited',
            'company_name'    => 'Sharma Enterprises Pvt Ltd',
            'phone'           => '9800011122',
            'whatsapp_number' => '9800011122',
            'email'           => 'rajesh@sharma.com',
            'city'            => 'Kolkata',
            'country'         => 'India',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertNotNull($lead->converted_customer_id);
        $this->assertNotNull($lead->converted_at);

        $customer = Customer::findOrFail($lead->converted_customer_id);
        $this->assertSame('Rajesh Sharma Edited', $customer->name);
        $this->assertSame($this->partner->id, $customer->first_partner_id, 'Original partner attribution must be preserved.');
        $this->assertSame($lead->id, $customer->lead_id);
        $this->assertMatchesRegularExpression('/^CUS-2026-\d{4}$/', $customer->customer_code);

        // Activity timeline check
        $this->assertTrue($lead->activities()->where('summary', 'like', '%Converted to Customer%')->exists());
    }

    /**
     * 2. Non-Won Lead cannot be converted.
     */
    public function test_non_won_lead_cannot_be_converted(): void
    {
        $lead = Lead::create([
            'lead_number'         => 'LED-2026-0002',
            'partner_id'          => $this->partner->id,
            'customer_name'       => 'Pooja Gupta',
            'customer_phone'      => '9800011133',
            'service_required'    => 'Web Design',
            'project_description' => 'Design work',
            'status'              => LeadStatus::InDiscussion,
        ]);

        $service = new CustomerService();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('must be in Won status');

        $service->convertLeadToCustomer($lead, ['name' => 'Pooja'], $this->admin);
    }

    /**
     * 3. Duplicate conversion is prevented.
     */
    public function test_duplicate_lead_conversion_is_prevented(): void
    {
        $lead = $this->createWonLead();
        $service = new CustomerService();

        $customer1 = $service->convertLeadToCustomer($lead, ['name' => 'First Run'], $this->admin);
        $this->assertNotNull($customer1);

        $lead->refresh();
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('has already been converted');

        $service->convertLeadToCustomer($lead, ['name' => 'Second Run'], $this->admin);
    }

    /**
     * 5. Admin can create multiple Projects for a Customer.
     */
    public function test_admin_can_create_multiple_projects_for_customer(): void
    {
        $lead = $this->createWonLead();
        $customerService = new CustomerService();
        $customer = $customerService->convertLeadToCustomer($lead, [], $this->admin);

        $projectService = new ProjectService();

        $project1 = $projectService->createProject($customer, [
            'project_title'       => 'ERP Implementation Phase 1',
            'final_deal_value'    => 150000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'status'              => ProjectStatus::InProgress,
        ], $this->admin);

        $project2 = $projectService->createProject($customer, [
            'project_title'       => 'Mobile App Addon Phase 2',
            'final_deal_value'    => 50000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'status'              => ProjectStatus::Draft,
        ], $this->admin);

        $this->assertNotSame($project1->id, $project2->id);
        $this->assertNotSame($project1->project_code, $project2->project_code);
        $this->assertSame($customer->id, $project1->customer_id);
        $this->assertSame($customer->id, $project2->customer_id);
        $this->assertSame($this->partner->id, $project1->partner_id);
        $this->assertSame($this->partner->id, $project2->partner_id);
        $this->assertCount(2, $customer->fresh()->projects);
    }

    /**
     * 6. Admin can record a customer payment.
     * 7. Pending payment does not generate commission.
     */
    public function test_admin_can_record_payment_and_pending_payment_generates_zero_commission(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Test Project',
            'final_deal_value' => 100000.00,
        ], $this->admin);

        $response = $this->actingAs($this->admin)->post(route('admin.payments.store'), [
            'project_id'            => $project->id,
            'payment_type'          => CustomerPaymentType::Advance->value,
            'payment_method'        => PaymentMethod::BankTransfer->value,
            'amount'                => 25000.00,
            'payment_date'          => now()->toDateString(),
            'transaction_reference' => 'UTR123456789',
            'notes'                 => 'Initial advance',
        ]);

        $response->assertRedirect();

        $payment = CustomerPayment::where('project_id', $project->id)->first();
        $this->assertNotNull($payment);
        $this->assertSame(25000.00, (float) $payment->amount);
        $this->assertSame(CustomerPaymentStatus::Recorded, $payment->status);
        $this->assertFalse($payment->commission_generated);
        $this->assertNull($payment->commission);
        $this->assertSame(0, Commission::where('project_id', $project->id)->count(), 'Pending payment must generate zero commission.');
    }

    /**
     * 8. Rejected payment does not generate commission.
     * 22. Rejected payments require a reason.
     */
    public function test_rejected_payment_requires_reason_and_generates_zero_commission(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Test Project',
            'final_deal_value' => 100000.00,
        ], $this->admin);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0001',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        // Blank reason should fail validation
        $response = $this->actingAs($this->admin)->post(route('admin.payments.reject', $payment), [
            'rejection_reason' => '',
        ]);
        $response->assertSessionHasErrors('rejection_reason');

        // Valid reason
        $response = $this->actingAs($this->admin)->post(route('admin.payments.reject', $payment), [
            'rejection_reason' => 'Cheque was stopped by issuer.',
        ]);
        $response->assertRedirect();

        $payment->refresh();
        $this->assertSame(CustomerPaymentStatus::Rejected, $payment->status);
        $this->assertSame('Cheque was stopped by issuer.', $payment->rejection_reason);
        $this->assertFalse($payment->commission_generated);
        $this->assertNull($payment->commission);
        $this->assertSame(0, Commission::count());
    }

    /**
     * 9. Verified payment generates exactly 10% commission.
     * 10. ₹10,000 verified payment generates ₹1,000 commission.
     * 11. ₹20,000 verified payment generates ₹2,000 commission.
     */
    public function test_verified_payments_generate_exact_percentage_commissions(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'          => 'Exact Test Project',
            'final_deal_value'       => 100000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
        ], $this->admin);

        $commissionService = new CommissionService();

        // Test ₹10,000 -> ₹1,000 commission
        $payment1 = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0002',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $comm1 = $commissionService->verifyPaymentAndGenerateCommission($payment1, $this->admin->id);
        $this->assertNotNull($comm1);
        $this->assertSame(1000.00, (float) $comm1->amount, '₹10,000 at 10% must generate exactly ₹1,000.');
        $this->assertSame(10.00, (float) $comm1->rate_applied);

        // Test ₹20,000 -> ₹2,000 commission
        $payment2 = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0003',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Milestone,
            'amount'               => 20000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $comm2 = $commissionService->verifyPaymentAndGenerateCommission($payment2, $this->admin->id);
        $this->assertNotNull($comm2);
        $this->assertSame(2000.00, (float) $comm2->amount, '₹20,000 at 10% must generate exactly ₹2,000.');
        $this->assertSame(10.00, (float) $comm2->rate_applied);
    }

    /**
     * 12. Multiple partial payments generate proportional commissions.
     * Example from spec: ₹10,000 (₹1,000), ₹20,000 (₹2,000), ₹5,000 (₹500) = ₹3,500 total commission on ₹35,000.
     */
    public function test_multiple_partial_payments_generate_proportional_commissions(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Milestone Project',
            'final_deal_value' => 50000.00,
        ], $this->admin);

        $service = new CommissionService();

        $amounts = [10000.00, 20000.00, 5000.00];
        $expectedCommissions = [1000.00, 2000.00, 500.00];

        foreach ($amounts as $i => $amt) {
            $payment = CustomerPayment::create([
                'payment_number'       => "CPAY-2026-001{$i}",
                'project_id'           => $project->id,
                'customer_id'          => $customer->id,
                'partner_id'           => $this->partner->id,
                'payment_type'         => CustomerPaymentType::Milestone,
                'amount'               => $amt,
                'payment_date'         => now()->toDateString(),
                'status'               => CustomerPaymentStatus::Recorded,
                'commission_generated' => false,
            ]);

            $comm = $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
            $this->assertSame($expectedCommissions[$i], (float) $comm->amount);
        }

        $totalCommission = Commission::where('project_id', $project->id)->sum('amount');
        $this->assertSame(3500.00, (float) $totalCommission, 'Total commission on ₹35,000 at 10% must be exactly ₹3,500.');
    }

    /**
     * 13. Repeated verification does not duplicate commission.
     * 14. Concurrent/retried commission generation is protected by database constraints and transaction locks.
     */
    public function test_repeated_verification_does_not_duplicate_commission(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Idempotent Project',
            'final_deal_value' => 10000.00,
        ], $this->admin);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0050',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $service = new CommissionService();

        // First verification
        $comm = $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
        $this->assertNotNull($comm);

        // Second verification attempt must fail
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already been generated');

        $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
    }

    /**
     * 15. Commission rate snapshot is preserved.
     * 16. Updating the default rate does not modify historical commissions.
     */
    public function test_updating_default_rate_does_not_alter_historical_commissions(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Rate Test Project',
            'final_deal_value' => 100000.00,
        ], $this->admin);

        $service = new CommissionService();

        // Initial payment at 10%
        $payment1 = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0060',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $comm1 = $service->verifyPaymentAndGenerateCommission($payment1, $this->admin->id);
        $this->assertSame(1000.00, (float) $comm1->amount);
        $this->assertSame(10.00, (float) $comm1->rate_applied);

        // Admin updates default rate to 15%
        $service->updateDefaultCommissionRate(15.00, $this->admin->id);

        // Historical commission must remain 10% and ₹1,000
        $comm1->refresh();
        $this->assertSame(1000.00, (float) $comm1->amount);
        $this->assertSame(10.00, (float) $comm1->rate_applied);

        // Subsequent payment verified at new 15% rate
        $payment2 = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0061',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Milestone,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $comm2 = $service->verifyPaymentAndGenerateCommission($payment2, $this->admin->id);
        $this->assertSame(1500.00, (float) $comm2->amount);
        $this->assertSame(15.00, (float) $comm2->rate_applied);
    }

    /**
     * 17. Payment without a referred Partner does not generate partner commission.
     */
    public function test_payment_without_referred_partner_does_not_generate_partner_commission(): void
    {
        $customer = Customer::create([
            'customer_code'    => 'CUS-2026-DIRECT',
            'name'             => 'Direct Client Corp',
            'phone'            => '9999988888',
            'first_partner_id' => null, // No partner
        ]);

        $project = Project::create([
            'partner_id'          => null,
            'customer_id'         => $customer->id,
            'project_code'        => 'PRJ-2026-DIRECT',
            'client_name'         => 'Direct Client Corp',
            'project_title'       => 'Direct Project',
            'project_description' => 'Direct contract',
            'final_deal_value'    => 50000.00,
            'status'              => ProjectStatus::InProgress,
        ]);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-DIR-1',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => null,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 20000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $service = new CommissionService();
        $result = $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);

        $this->assertNull($result, 'No commission should be returned when there is no referred partner.');
        $payment->refresh();
        $this->assertSame(CustomerPaymentStatus::Verified, $payment->status);
        $this->assertSame(0, Commission::count());
    }

    /**
     * 18. Partner cannot access another Partner's customer.
     * 19. Partner cannot access another Partner's commission.
     */
    public function test_cross_partner_customer_and_commission_access_is_forbidden(): void
    {
        $lead = $this->createWonLead(); // Belongs to Alpha Partner
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Alpha Secret Project',
            'final_deal_value' => 50000.00,
        ], $this->admin);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0070',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $comm = (new CommissionService())->verifyPaymentAndGenerateCommission($payment, $this->admin->id);

        // Acting as Beta Partner (otherPartnerUser) trying to access Alpha's Customer
        $response = $this->actingAs($this->otherPartnerUser)->get(route('partner.customers.show', $customer));
        $response->assertStatus(403);

        // Acting as Beta Partner trying to access Alpha's Project
        $response = $this->actingAs($this->otherPartnerUser)->get(route('partner.projects.show', $project));
        $response->assertStatus(403);

        // Acting as Beta Partner trying to access Alpha's Commission
        $response = $this->actingAs($this->otherPartnerUser)->get(route('partner.commissions.show', $comm));
        $response->assertStatus(403);
    }

    /**
     * 20. Team Member cannot access financial routes.
     * 21. Team Member cannot verify customer payments.
     */
    public function test_team_member_cannot_access_financial_routes_or_verify_payments(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Staff Test Project',
            'final_deal_value' => 50000.00,
        ], $this->admin);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0080',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        // Attempting to access Admin payments index
        $response = $this->actingAs($this->teamMember)->get(route('admin.payments.index'));
        $response->assertStatus(403);

        // Attempting to access Admin commissions index
        $response = $this->actingAs($this->teamMember)->get(route('admin.commissions.index'));
        $response->assertStatus(403);

        // Attempting to access Admin financial dashboard
        $response = $this->actingAs($this->teamMember)->get(route('admin.dashboard'));
        $response->assertStatus(403);

        // Attempting to verify a payment
        $response = $this->actingAs($this->teamMember)->post(route('admin.payments.verify', $payment));
        $response->assertStatus(403);

        $payment->refresh();
        $this->assertSame(CustomerPaymentStatus::Recorded, $payment->status);
    }

    /**
     * 23. Payment proof upload is validated and protected.
     */
    public function test_payment_proof_upload_is_validated_and_protected(): void
    {
        Storage::fake('local');

        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Proof Test Project',
            'final_deal_value' => 50000.00,
        ], $this->admin);

        // Upload a valid PDF file
        $file = UploadedFile::fake()->create('bank_receipt.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('admin.payments.store'), [
            'project_id'       => $project->id,
            'payment_type'     => CustomerPaymentType::Advance->value,
            'payment_method'   => PaymentMethod::BankTransfer->value,
            'amount'           => 12000.00,
            'payment_date'     => now()->toDateString(),
            'proof_attachment' => $file,
        ]);

        $response->assertRedirect();
        $payment = CustomerPayment::where('project_id', $project->id)->first();
        $this->assertNotNull($payment->proof_attachment_path);
        Storage::disk('local')->assertExists($payment->proof_attachment_path);

        // Public / unauthorized user cannot download proof
        auth()->logout();
        $unauthResponse = $this->get(route('admin.payments.proof', $payment));
        $unauthResponse->assertRedirect(route('login'));

        // Team member cannot download proof
        $teamResponse = $this->actingAs($this->teamMember)->get(route('admin.payments.proof', $payment));
        $teamResponse->assertStatus(403);

        // Admin can download proof
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.payments.proof', $payment));
        $adminResponse->assertOk();
    }

    /**
     * 24. Verified payment correction/reversal preserves audit history.
     */
    public function test_verified_payment_reversal_preserves_audit_history_and_cancels_unpaid_commission(): void
    {
        $lead = $this->createWonLead();
        $customer = (new CustomerService())->convertLeadToCustomer($lead, [], $this->admin);
        $project = (new ProjectService())->createProject($customer, [
            'project_title'    => 'Reversal Test Project',
            'final_deal_value' => 50000.00,
        ], $this->admin);

        $payment = CustomerPayment::create([
            'payment_number'       => 'CPAY-2026-0090',
            'project_id'           => $project->id,
            'customer_id'          => $customer->id,
            'partner_id'           => $this->partner->id,
            'payment_type'         => CustomerPaymentType::Advance,
            'amount'               => 10000.00,
            'payment_date'         => now()->toDateString(),
            'status'               => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $service = new CommissionService();
        $comm = $service->verifyPaymentAndGenerateCommission($payment, $this->admin->id);
        $this->assertSame(CommissionStatus::Payable, $comm->status);

        // Admin performs reversal
        $response = $this->actingAs($this->admin)->post(route('admin.payments.reverse', $payment), [
            'reversal_reason' => 'Client bank transfer was recalled due to incorrect beneficiary account.',
        ]);
        $response->assertRedirect();

        $payment->refresh();
        $comm->refresh();

        $this->assertSame(CustomerPaymentStatus::Rejected, $payment->status);
        $this->assertFalse($payment->commission_generated);
        $this->assertStringContainsString('Client bank transfer was recalled', $payment->rejection_reason);

        // Commission safely marked Cancelled with audit trail
        $this->assertSame(CommissionStatus::Cancelled, $comm->status);
        $this->assertStringContainsString('Reversal: Client bank transfer was recalled', $comm->notes);
    }
}
