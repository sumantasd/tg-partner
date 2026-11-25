<?php

namespace Tests\Feature;

use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\KycStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerProfileAndDashboardAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $partnerUser;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
            'email' => 'partner.audit@techgoogly.com',
            'phone' => '9876543210',
            'password' => Hash::make('CurrentPassword123!'),
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-AUDIT-001',
            'company_name' => 'Audit Partner Inc',
            'phone' => '9876543210',
            'kyc_status' => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'default_commission_rate' => 15.00,
            'city' => 'Kolkata',
            'state' => 'West Bengal',
            'address_line1' => 'Salt Lake Sector V',
            'postal_code' => '700091',
        ]);
    }

    public function test_guest_cannot_access_partner_profile(): void
    {
        $response = $this->get(route('partner.profile.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_cannot_access_partner_profile(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($admin)->get(route('partner.profile.edit'));
        $response->assertForbidden();
    }

    public function test_team_member_cannot_access_partner_profile(): void
    {
        $staff = User::factory()->create([
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $response = $this->actingAs($staff)->get(route('partner.profile.edit'));
        $response->assertForbidden();
    }

    public function test_partner_can_view_profile_page_with_correct_data(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('partner.profile.edit'));

        $response->assertOk();
        $response->assertSee('TGP-AUDIT-001');
        $response->assertSee('partner.audit@techgoogly.com');
        $response->assertSee('9876543210');
        $response->assertSee('Salt Lake Sector V');
        $response->assertSee('700091');
        $response->assertSee('Kolkata');
        $response->assertSee('Current Password');
        $response->assertSee('New Password');
    }

    public function test_partner_can_update_profile_editable_fields(): void
    {
        $response = $this->actingAs($this->partnerUser)->put(route('partner.profile.update'), [
            'name' => 'Updated Legal Business Name',
            'company_name' => 'Tech Innovations LLP',
            'website' => 'https://partner-innovations.com',
            'address_line1' => 'New Town Action Area II',
            'city' => 'Rajarhat',
            'state' => 'West Bengal',
            'postal_code' => '700156',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->partnerUser->refresh();
        $this->partner->refresh();

        $this->assertEquals('Updated Legal Business Name', $this->partnerUser->name);
        $this->assertEquals('Tech Innovations LLP', $this->partner->company_name);
        $this->assertEquals('https://partner-innovations.com', $this->partner->website);
        $this->assertEquals('New Town Action Area II', $this->partner->address_line1);
        $this->assertEquals('Rajarhat', $this->partner->city);
        $this->assertEquals('700156', $this->partner->postal_code);
    }

    public function test_partner_cannot_tamper_with_immutable_fields(): void
    {
        // Try maliciously tampering with role, status, partner_code, email, phone, commission_rate
        $response = $this->actingAs($this->partnerUser)->put(route('partner.profile.update'), [
            'name' => 'Legit Name Update',
            'company_name' => 'Audit Partner Inc',
            'email' => 'hacked@evil.com',
            'phone' => '1111111111',
            'role' => 'admin',
            'status' => 'suspended',
            'partner_code' => 'TGP-HACKED',
            'default_commission_rate' => 99.00,
            'special_commission_rate' => 99.00,
        ]);

        $response->assertRedirect();

        $this->partnerUser->refresh();
        $this->partner->refresh();

        $this->assertEquals(UserRole::Partner, $this->partnerUser->role);
        $this->assertEquals('partner.audit@techgoogly.com', $this->partnerUser->email);
        $this->assertEquals('9876543210', $this->partnerUser->phone);
        $this->assertEquals(UserStatus::Active, $this->partnerUser->status);
        $this->assertEquals('TGP-AUDIT-001', $this->partner->partner_code);
        $this->assertEquals(15.00, (float) $this->partner->default_commission_rate);
    }

    public function test_partner_can_change_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->partnerUser)->put(route('partner.profile.password'), [
            'current_password' => 'CurrentPassword123!',
            'password' => 'BrandNewStrongPassword@2026',
            'password_confirmation' => 'BrandNewStrongPassword@2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->partnerUser->refresh();
        $this->assertTrue(Hash::check('BrandNewStrongPassword@2026', $this->partnerUser->password));
    }

    public function test_partner_cannot_change_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->partnerUser)->put(route('partner.profile.password'), [
            'current_password' => 'WrongPassword999!',
            'password' => 'BrandNewStrongPassword@2026',
            'password_confirmation' => 'BrandNewStrongPassword@2026',
        ]);

        $response->assertSessionHasErrors(['current_password']);

        $this->partnerUser->refresh();
        $this->assertTrue(Hash::check('CurrentPassword123!', $this->partnerUser->password));
    }

    public function test_partner_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('avatar.jpg', 400, 400)->size(1024);

        $response = $this->actingAs($this->partnerUser)->post(route('partner.profile.photo'), [
            'photo' => $photo,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->partnerUser->refresh();
        $this->assertNotNull($this->partnerUser->profile_photo_path);
        Storage::disk('public')->assertExists($this->partnerUser->profile_photo_path);
    }

    public function test_partner_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');

        $pdf = UploadedFile::fake()->create('malicious.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->partnerUser)->post(route('partner.profile.photo'), [
            'photo' => $pdf,
        ]);

        $response->assertSessionHasErrors(['photo']);
    }

    public function test_partner_dashboard_kpis_strictly_scoped_to_partner(): void
    {
        // Create second partner with separate data
        $otherUser = User::factory()->create(['role' => UserRole::Partner, 'status' => UserStatus::Active]);
        $otherPartner = Partner::create([
            'user_id' => $otherUser->id,
            'partner_code' => 'TGP-OTHER',
            'company_name' => 'Other Partner',
            'phone' => '9876543219',
        ]);

        // Partner 1 data: 2 leads, 1 customer, 1 project
        Lead::create([
            'partner_id' => $this->partner->id,
            'lead_number' => 'LD-TEST-P1-01',
            'customer_name' => 'Lead P1 1',
            'customer_email' => 'p1_1@client.com',
            'customer_phone' => '9000000001',
            'service_required' => 'Custom ERP Development',
            'project_description' => 'ERP development scope',
            'status' => LeadStatus::Submitted,
        ]);
        Lead::create([
            'partner_id' => $this->partner->id,
            'lead_number' => 'LD-TEST-P1-02',
            'customer_name' => 'Lead P1 2',
            'customer_email' => 'p1_2@client.com',
            'customer_phone' => '9000000002',
            'service_required' => 'E-Commerce Solution',
            'project_description' => 'Online shop scope',
            'status' => LeadStatus::Submitted,
        ]);

        $customer1 = Customer::create([
            'customer_code' => 'CUST-TEST-P1',
            'first_partner_id' => $this->partner->id,
            'name' => 'Client One',
            'company_name' => 'Customer P1',
            'email' => 'client1@p1.com',
            'phone' => '9000000003',
        ]);

        Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $customer1->id,
            'project_code' => 'PRJ-TEST-P1',
            'project_title' => 'Project P1',
            'client_name' => 'Client One',
            'project_description' => 'Test project description',
            'total_value' => 50000,
            'status' => \App\Enums\ProjectStatus::InProgress,
        ]);

        // Other partner data: 3 leads, 1 customer, 2 projects
        Lead::create([
            'partner_id' => $otherPartner->id,
            'lead_number' => 'LD-TEST-P2-01',
            'customer_name' => 'Lead P2 1',
            'customer_email' => 'p2_1@client.com',
            'customer_phone' => '9000000004',
            'service_required' => 'Mobile App Development',
            'project_description' => 'Mobile app scope',
            'status' => LeadStatus::Submitted,
        ]);

        $otherCustomer = Customer::create([
            'customer_code' => 'CUST-TEST-P2',
            'first_partner_id' => $otherPartner->id,
            'name' => 'Client Two',
            'company_name' => 'Customer P2',
            'email' => 'client2@p2.com',
            'phone' => '9000000005',
        ]);

        Project::create([
            'partner_id' => $otherPartner->id,
            'customer_id' => $otherCustomer->id,
            'project_code' => 'PRJ-TEST-P2',
            'project_title' => 'Project P2',
            'client_name' => 'Client Two',
            'project_description' => 'Test other project description',
            'total_value' => 70000,
            'status' => \App\Enums\ProjectStatus::InProgress,
        ]);

        $response = $this->actingAs($this->partnerUser)->get(route('partner.workspace'));

        $response->assertOk();
        $this->assertEquals(2, $response->viewData('leadsCount'));
        $this->assertEquals(1, $response->viewData('customersCount'));
        $this->assertEquals(1, $response->viewData('activeProjectsCount'));
    }

    public function test_commissions_payable_filter_aligns_with_available_balance(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-COMM-01',
            'first_partner_id' => $this->partner->id,
            'name' => 'Comm Client',
            'company_name' => 'Commission Customer',
            'email' => 'comm@client.com',
            'phone' => '9000000010',
        ]);

        $project = Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-COMM-01',
            'project_title' => 'Project Commission',
            'client_name' => 'Comm Client',
            'project_description' => 'Commission project test',
            'total_value' => 100000,
            'status' => \App\Enums\ProjectStatus::InProgress,
        ]);

        // 1 Approved commission (available for withdrawal)
        Commission::create([
            'commission_number' => 'COM-AUDIT-001',
            'partner_id' => $this->partner->id,
            'project_id' => $project->id,
            'milestone_sequence' => 1,
            'status' => CommissionStatus::Approved,
            'amount' => 5000,
            'rate_applied' => 10.00,
            'calculation_basis' => 50000,
            'commission_type' => \App\Enums\CommissionType::Percentage,
            'withdrawal_request_id' => null,
            'payout_id' => null,
        ]);

        // 1 Approved commission reserved in pending withdrawal
        $withdrawal = WithdrawalRequest::create([
            'request_number' => 'WR-TEST-001',
            'partner_id' => $this->partner->id,
            'amount' => 3000,
            'currency' => 'INR',
            'payout_method' => 'bank_transfer',
            'recipient_snapshot' => ['bank_name' => 'HDFC Bank', 'account_number' => '1234567890'],
            'status' => WithdrawalStatus::Pending,
        ]);

        Commission::create([
            'commission_number' => 'COM-AUDIT-002',
            'partner_id' => $this->partner->id,
            'project_id' => $project->id,
            'milestone_sequence' => 2,
            'status' => CommissionStatus::Approved,
            'amount' => 3000,
            'rate_applied' => 10.00,
            'calculation_basis' => 30000,
            'commission_type' => \App\Enums\CommissionType::Percentage,
            'withdrawal_request_id' => $withdrawal->id,
            'payout_id' => null,
        ]);

        // Check Partner Commission index
        $response = $this->actingAs($this->partnerUser)->get(route('partner.commissions.index'));
        $response->assertOk();

        // The payable metric must ONLY include unrequested/unpaid commissions (5000), not the reserved 3000
        $metrics = $response->viewData('metrics');
        $this->assertEquals(5000.00, (float) $metrics['payable']);
    }
}
