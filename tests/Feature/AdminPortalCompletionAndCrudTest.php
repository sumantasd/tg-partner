<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPortalCompletionAndCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teamMember;
    private User $partnerUser;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'              => 'Admin Officer',
            'email'             => 'admin_portal@tg.com',
            'password'          => Hash::make('AdminPass123!'),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->teamMember = User::create([
            'name'              => 'CRM Staff',
            'email'             => 'staff_portal@tg.com',
            'password'          => Hash::make('StaffPass123!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'department'        => 'Sales',
            'email_verified_at' => now(),
        ]);

        $this->partnerUser = User::create([
            'name'              => 'Partner Contact',
            'email'             => 'partner_portal@tg.com',
            'password'          => Hash::make('PartnerPass123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-2026-TEST',
            'company_name'            => 'Test Partner Enterprise',
            'phone'                   => '9876543210',
            'default_commission_rate' => 12.50,
            'country'                 => 'India',
            'approved_at'             => now(),
            'approved_by'             => $this->admin->id,
        ]);
    }

    /**
     * Test 1: All Admin sidebar and portal links return HTTP 200 and never redirect to login for admin.
     */
    public function test_admin_sidebar_links_do_not_redirect_to_login(): void
    {
        $this->actingAs($this->admin);

        $urls = [
            '/admin/dashboard',
            '/admin/partners',
            '/admin/partners/create',
            '/admin/team-members',
            '/admin/team-members/create',
            '/admin/leads',
            '/admin/customers',
            '/admin/customers/create',
            '/admin/projects',
            '/admin/projects/create',
            '/admin/payments',
            '/admin/payments/create',
            '/admin/commissions',
            '/admin/payouts',
            '/admin/reports',
            '/admin/settings/commission',
        ];

        foreach ($urls as $url) {
            $response = $this->get($url);
            $response->assertOk();
            $this->assertStringNotContainsString('login', $response->headers->get('Location') ?? '');
        }
    }

    /**
     * Test 2: Admin can create partner with strict partner role, hashed password, and creator recorded.
     */
    public function test_admin_can_create_partner_with_strict_partner_role(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/partners', [
            'name'                    => 'Ananya Sen',
            'email'                   => 'ananya@senassociates.com',
            'phone'                   => '9830099887',
            'password'                => 'SecurePartnerPass1!',
            'password_confirmation'   => 'SecurePartnerPass1!',
            'company_name'            => 'Sen & Associates Consulting',
            'address_line1'           => '12 Park Street',
            'city'                    => 'Kolkata',
            'state'                   => 'West Bengal',
            'postal_code'             => '700016',
            'status'                  => 'active',
            'default_commission_rate' => 15.00,
            'internal_notes'          => 'Onboarded via executive meeting',
        ]);

        $createdUser = User::where('email', 'ananya@senassociates.com')->firstOrFail();
        $this->assertSame(UserRole::Partner, $createdUser->role);
        $this->assertSame(UserStatus::Active, $createdUser->status);
        $this->assertTrue(Hash::check('SecurePartnerPass1!', $createdUser->password));

        $createdPartner = Partner::where('user_id', $createdUser->id)->firstOrFail();
        $this->assertMatchesRegularExpression('/^TGP-\d{4}-[A-Z0-9]{4}$/', $createdPartner->partner_code);
        $this->assertSame($this->admin->id, $createdPartner->created_by);
        $this->assertEquals(15.00, $createdPartner->default_commission_rate);
        $this->assertNotNull($createdPartner->approved_at);

        $response->assertRedirect(route('admin.partners.show', $createdPartner));
    }

    /**
     * Test 3: Admin cannot escalate partner to admin role during partner creation.
     */
    public function test_admin_cannot_escalate_partner_to_admin(): void
    {
        $this->actingAs($this->admin);

        // Attacker or forged form attempts role=admin
        $this->post('/admin/partners', [
            'name'                  => 'Exploit Test',
            'email'                 => 'exploit@test.com',
            'phone'                 => '9830011223',
            'password'              => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'company_name'          => 'Exploit Corp',
            'status'                => 'active',
            'role'                  => 'admin', // Forged field
        ]);

        $user = User::where('email', 'exploit@test.com')->firstOrFail();
        $this->assertSame(UserRole::Partner, $user->role, 'Role must strictly remain partner');
    }

    /**
     * Test 4: Admin can update partner profile, commission rate, and status.
     */
    public function test_admin_can_update_partner_profile_and_status(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('admin.partners.update', $this->partner), [
            'name'                    => 'Partner Contact Updated',
            'email'                   => 'partner_portal@tg.com',
            'phone'                   => '9111222333',
            'company_name'            => 'Updated Enterprise Group',
            'status'                  => 'suspended',
            'default_commission_rate' => 18.50,
            'address_line1'           => '45 MG Road',
            'city'                    => 'Bengaluru',
            'state'                   => 'Karnataka',
            'postal_code'             => '560001',
        ]);

        $response->assertRedirect(route('admin.partners.show', $this->partner));

        $this->partnerUser->refresh();
        $this->partner->refresh();

        $this->assertSame('Partner Contact Updated', $this->partnerUser->name);
        $this->assertSame(UserStatus::Suspended, $this->partnerUser->status);
        $this->assertSame('Updated Enterprise Group', $this->partner->company_name);
        $this->assertEquals(18.50, $this->partner->default_commission_rate);
    }

    /**
     * Test 5: Admin partner lifecycle actions (Approve, Reject, Suspend, Activate).
     */
    public function test_admin_partner_lifecycle_actions(): void
    {
        $this->actingAs($this->admin);

        $pendingUser = User::create([
            'name'     => 'Pending Partner',
            'email'    => 'pending@partner.com',
            'password' => Hash::make('Pass123!'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Pending,
        ]);
        $pendingPartner = Partner::create([
            'user_id'                 => $pendingUser->id,
            'partner_code'            => 'TGP-2026-PEND',
            'company_name'            => 'Pending Corp',
            'phone'                   => '9000000001',
            'default_commission_rate' => 10.00,
        ]);

        // 1. Approve
        $this->post(route('admin.partners.approve', $pendingPartner))->assertRedirect();
        $this->assertSame(UserStatus::Active, $pendingUser->fresh()->status);
        $this->assertNotNull($pendingPartner->fresh()->approved_at);

        // 2. Suspend
        $this->post(route('admin.partners.suspend', $pendingPartner), ['reason' => 'Compliance review required'])->assertRedirect();
        $this->assertSame(UserStatus::Suspended, $pendingUser->fresh()->status);
        $this->assertStringContainsString('Compliance review required', $pendingPartner->fresh()->internal_notes);

        // 3. Reactivate
        $this->post(route('admin.partners.activate', $pendingPartner))->assertRedirect();
        $this->assertSame(UserStatus::Active, $pendingUser->fresh()->status);
    }

    /**
     * Test 6: Admin can create team member with strict role isolation and password hashing.
     */
    public function test_admin_can_create_team_member_with_strict_team_member_role(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/team-members', [
            'name'                  => 'Rohan Roy',
            'email'                 => 'rohan@techgoogly.com',
            'phone'                 => '9831200000',
            'designation'           => 'Senior Account Specialist',
            'department'            => 'Sales',
            'status'                => 'active',
            'password'              => 'TeamPassSecure1!',
            'password_confirmation' => 'TeamPassSecure1!',
            'role'                  => 'admin', // Attack attempt
        ]);

        $created = User::where('email', 'rohan@techgoogly.com')->firstOrFail();
        $this->assertSame(UserRole::TeamMember, $created->role, 'Must strictly enforce team_member role');
        $this->assertSame(UserStatus::Active, $created->status);
        $this->assertSame('Sales', $created->department);
        $this->assertSame('Senior Account Specialist', $created->designation);
        $this->assertTrue(Hash::check('TeamPassSecure1!', $created->password));

        $response->assertRedirect(route('admin.team-members.show', $created));
    }

    /**
     * Test 7: Admin can update team member details.
     */
    public function test_admin_can_update_team_member(): void
    {
        $this->actingAs($this->admin);

        $response = $this->put(route('admin.team-members.update', $this->teamMember), [
            'name'        => 'CRM Staff Lead',
            'email'       => 'staff_portal@tg.com',
            'phone'       => '9988776655',
            'designation' => 'Team Lead Operations',
            'department'  => 'Operations',
            'status'      => 'active',
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember));

        $this->teamMember->refresh();
        $this->assertSame('CRM Staff Lead', $this->teamMember->name);
        $this->assertSame('Team Lead Operations', $this->teamMember->designation);
        $this->assertSame('Operations', $this->teamMember->department);
    }

    /**
     * Test 8: Admin can toggle team member status and reset their password.
     */
    public function test_admin_can_toggle_team_member_status_and_reset_password(): void
    {
        $this->actingAs($this->admin);

        // Toggle to suspended
        $this->post(route('admin.team-members.toggle-status', $this->teamMember))->assertRedirect();
        $this->assertSame(UserStatus::Suspended, $this->teamMember->fresh()->status);

        // Toggle back to active
        $this->post(route('admin.team-members.toggle-status', $this->teamMember))->assertRedirect();
        $this->assertSame(UserStatus::Active, $this->teamMember->fresh()->status);

        // Reset password
        $this->post(route('admin.team-members.reset-password', $this->teamMember), [
            'password'              => 'BrandNewPass2026!',
            'password_confirmation' => 'BrandNewPass2026!',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('BrandNewPass2026!', $this->teamMember->fresh()->password));
    }

    /**
     * Test 9: Admin can create customer directly without a prior won lead (NO premature commission).
     */
    public function test_admin_can_create_customer_directly_without_lead(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/customers', [
            'name'             => 'Direct Client Solutions',
            'company_name'     => 'Direct Solutions Pvt Ltd',
            'phone'            => '9830088776',
            'email'            => 'direct@client.com',
            'city'             => 'Kolkata',
            'status'           => 'active',
            'first_partner_id' => null, // No partner
            'notes'            => 'Directly onboarded corporate client',
        ]);

        $customer = Customer::where('email', 'direct@client.com')->firstOrFail();
        $this->assertMatchesRegularExpression('/^CUS-\d{4}-\d{4}$/', $customer->customer_code);
        $this->assertNull($customer->first_partner_id);
        $this->assertNull($customer->lead_id);
        $this->assertSame($this->admin->id, $customer->created_by);
        $this->assertSame('active', $customer->status);

        // Crucial test: Customer creation MUST NEVER generate commission
        $this->assertDatabaseCount('commissions', 0);

        $response->assertRedirect(route('admin.customers.show', $customer));
    }

    /**
     * Test 10: Admin can create customer with attributed referral partner.
     */
    public function test_admin_can_create_customer_with_attributed_partner(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post('/admin/customers', [
            'name'             => 'Referred Client Enterprise',
            'company_name'     => 'Referred Corp',
            'phone'            => '9830055112',
            'email'            => 'referred@client.com',
            'status'           => 'active',
            'first_partner_id' => $this->partner->id,
            'notes'            => 'Partner brought in direct contract',
        ]);

        $customer = Customer::where('email', 'referred@client.com')->firstOrFail();
        $this->assertSame($this->partner->id, $customer->first_partner_id);
        $this->assertDatabaseCount('commissions', 0); // No premature commission

        $response->assertRedirect(route('admin.customers.show', $customer));
    }

    /**
     * Test 11: Admin can update customer profile and contact details.
     */
    public function test_admin_can_update_customer_profile(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'customer_code'    => 'CUS-2026-9999',
            'name'             => 'Initial Name',
            'phone'            => '9000000000',
            'first_partner_id' => $this->partner->id,
            'created_by'       => $this->admin->id,
            'status'           => 'active',
        ]);

        $response = $this->put(route('admin.customers.update', $customer), [
            'name'            => 'Updated Customer Name',
            'company_name'    => 'Updated Company Ltd',
            'phone'           => '9111111111',
            'email'           => 'updated@client.com',
            'city'            => 'Mumbai',
            'status'          => 'inactive',
        ]);

        $response->assertRedirect(route('admin.customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Updated Customer Name', $customer->name);
        $this->assertSame('Updated Company Ltd', $customer->company_name);
        $this->assertSame('inactive', $customer->status);
    }

    /**
     * Test 12: Customer referral attribution cannot be stripped or altered during profile updates.
     */
    public function test_customer_referral_attribution_is_preserved_on_update(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'customer_code'    => 'CUS-2026-8888',
            'name'             => 'Attributed Client',
            'phone'            => '9888888888',
            'first_partner_id' => $this->partner->id,
            'created_by'       => $this->admin->id,
            'status'           => 'active',
        ]);

        // Attempting to send null or forged partner in update
        $this->put(route('admin.customers.update', $customer), [
            'name'             => 'Attributed Client Modified',
            'phone'            => '9888888888',
            'first_partner_id' => null, // Trying to strip attribution
            'status'           => 'active',
        ]);

        $customer->refresh();
        $this->assertSame($this->partner->id, $customer->first_partner_id, 'Attribution must be permanent');
    }

    /**
     * Test 13: Team member and partner cannot access admin CRUD routes (403 Forbidden).
     */
    public function test_team_member_and_partner_cannot_access_admin_crud_routes(): void
    {
        // 1. Partner access
        $this->actingAs($this->partnerUser);
        $this->get('/admin/partners')->assertForbidden();
        $this->get('/admin/partners/create')->assertForbidden();
        $this->get('/admin/team-members')->assertForbidden();
        $this->get('/admin/customers/create')->assertForbidden();
        $this->get('/admin/payouts')->assertForbidden();
        $this->get('/admin/reports')->assertForbidden();

        // 2. Team member access
        $this->actingAs($this->teamMember);
        $this->get('/admin/partners')->assertForbidden();
        $this->get('/admin/team-members')->assertForbidden();
        $this->get('/admin/payouts')->assertForbidden();
        $this->get('/admin/reports')->assertForbidden();
    }

    /**
     * Test 14: Unauthenticated user is redirected to login from admin routes.
     */
    public function test_unauthenticated_user_redirected_to_login_from_admin_crud(): void
    {
        $this->get('/admin/partners')->assertRedirect(route('login'));
        $this->get('/admin/partners/create')->assertRedirect(route('login'));
        $this->get('/admin/team-members')->assertRedirect(route('login'));
        $this->get('/admin/customers/create')->assertRedirect(route('login'));
        $this->get('/admin/payouts')->assertRedirect(route('login'));
        $this->get('/admin/reports')->assertRedirect(route('login'));
    }

    /**
     * Test 15: Payouts and Reports views render correctly with computed metrics.
     */
    public function test_reports_and_payouts_pages_render_correctly(): void
    {
        $this->actingAs($this->admin);

        $payoutsResponse = $this->get('/admin/payouts');
        $payoutsResponse->assertOk();
        $payoutsResponse->assertSee('Payouts & Partner Withdrawals', false);
        $payoutsResponse->assertSee('Total Settled Disbursements');

        $reportsResponse = $this->get('/admin/reports');
        $reportsResponse->assertOk();
        $reportsResponse->assertSee('Executive Reports & Analytics', false);
        $reportsResponse->assertSee('Total Verified Collections');
        $reportsResponse->assertSee('Pipeline Conversion Rate');
    }
}
