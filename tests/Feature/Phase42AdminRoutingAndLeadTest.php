<?php

namespace Tests\Feature;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase42AdminRoutingAndLeadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $partnerUser;
    private Partner $partner;
    private User $teamMemberUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'name'              => 'Sumanta Das',
            'email'             => 'admin@techgoogly.com',
            'password'          => Hash::make('AdminSecret123!'),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partnerUser = User::create([
            'name'              => 'Rohit Verma',
            'email'             => 'partner@techgoogly.com',
            'password'          => Hash::make('PartnerSecret123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-2026-TEST',
            'company_name'            => 'Verma Digital Solutions',
            'phone'                   => '9876543210',
            'default_commission_rate' => 15.00,
            'country'                 => 'India',
            'approved_at'             => now(),
            'approved_by'             => $this->admin->id,
        ]);

        $this->teamMemberUser = User::create([
            'name'              => 'Pooja Sharma',
            'email'             => 'pooja@techgoogly.com',
            'password'          => Hash::make('StaffSecret123!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'department'        => 'Sales & Operations',
            'email_verified_at' => now(),
        ]);
    }

    /**
     * Point 1: GET /admin redirects authenticated admin to /admin/dashboard.
     */
    public function test_admin_home_redirects_admin_to_dashboard(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin');

        $response->assertRedirect('/admin/dashboard');
    }

    /**
     * Point 2: GET /admin redirects authenticated partner to /partner/workspace.
     */
    public function test_admin_home_redirects_partner_to_workspace(): void
    {
        $response = $this->actingAs($this->partnerUser)->get('/admin');

        $response->assertRedirect('/partner/workspace');
    }

    /**
     * Point 3: GET /admin redirects authenticated team member to /team/workspace.
     */
    public function test_admin_home_redirects_team_member_to_workspace(): void
    {
        $response = $this->actingAs($this->teamMemberUser)->get('/admin');

        $response->assertRedirect('/team/workspace');
    }

    /**
     * Point 4: GET /admin redirects guest to /login.
     */
    public function test_admin_home_redirects_guest_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /**
     * Point 5: GET /admin/dashboard returns 200 for authenticated admin.
     */
    public function test_admin_dashboard_renders_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertOk();
    }

    /**
     * Point 6: Verify every route named in Admin sidebar exists via Route::has().
     */
    public function test_every_admin_sidebar_route_name_exists(): void
    {
        $expectedSidebarRoutes = [
            'admin.home',
            'admin.dashboard',
            'admin.leads.index',
            'admin.leads.create',
            'admin.partners.index',
            'admin.partners.create',
            'admin.team-members.index',
            'admin.team-members.create',
            'admin.customers.index',
            'admin.customers.create',
            'admin.projects.index',
            'admin.projects.create',
            'admin.payments.index',
            'admin.payments.create',
            'admin.commissions.index',
            'admin.payouts.index',
            'admin.reports.index',
            'admin.settings.commission.index',
        ];

        foreach ($expectedSidebarRoutes as $routeName) {
            $this->assertTrue(
                Route::has($routeName),
                "Expected sidebar route [{$routeName}] is not registered in RouteCollection."
            );
        }
    }

    /**
     * Point 7: GET /admin/leads/create returns 200 with all form fields.
     */
    public function test_admin_can_open_add_lead_form(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/leads/create');

        $response->assertOk();
        $response->assertSee('Add New Lead');
        $response->assertSee('Customer Name');
        $response->assertSee('Business / Company Name');
        $response->assertSee('Phone Number');
        $response->assertSee('WhatsApp Number');
        $response->assertSee('Email Address');
        $response->assertSee('City');
        $response->assertSee('State');
        $response->assertSee('Service Category');
        $response->assertSee('Estimated Budget');
        $response->assertSee('Requirements / Project Description');
        $response->assertSee('Assign Referral Partner');
        $response->assertSee('Assign Internal Team Member');
        $response->assertSee('Lead Source');
        $response->assertSee('Priority');
    }

    /**
     * Point 8: POST /admin/leads creates lead with LED-YYYY-XXXX format, status submitted, and records lead_activities.
     */
    public function test_admin_can_create_lead(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'       => 'Suresh Roy',
            'customer_company'    => 'Roy Enterprises',
            'customer_phone'      => '9831122334',
            'whatsapp_number'     => '9831122334',
            'customer_email'      => 'suresh@royenterprises.in',
            'city'                => 'Kolkata',
            'state'               => 'West Bengal',
            'service_required'    => 'E-Commerce Solution',
            'estimated_value'     => 150000.00,
            'project_description' => 'Multi-vendor marketplace portal with payment gateway.',
            'lead_source'         => 'Direct',
            'priority'            => 'High',
            'next_followup_date'  => now()->addDays(2)->toDateString(),
        ]);

        $lead = Lead::where('customer_phone', '9831122334')->firstOrFail();
        $this->assertMatchesRegularExpression('/^LED-\d{4}-\d{4}$/', $lead->lead_number);
        $this->assertSame(LeadStatus::Submitted, $lead->status);
        $this->assertNull($lead->partner_id);
        $this->assertNull($lead->assigned_to);
        $this->assertSame($this->admin->id, $lead->created_by);
        $this->assertSame('High', $lead->priority);
        $this->assertSame('Direct', $lead->lead_source);
        $this->assertSame('West Bengal', $lead->state);
        $this->assertSame('9831122334', $lead->whatsapp_number);

        // Check canonical activity log
        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::LeadSubmitted)
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->user_id);

        $response->assertRedirect(route('admin.leads.show', $lead));
    }

    /**
     * Point 9: Creating two leads generates distinct unique sequential IDs.
     */
    public function test_admin_created_lead_id_is_unique(): void
    {
        $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Lead Alpha',
            'customer_phone'   => '9811111111',
            'service_required' => 'Custom Web App',
            'lead_source'      => 'Website',
            'priority'         => 'Medium',
        ]);

        $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Lead Beta',
            'customer_phone'   => '9822222222',
            'service_required' => 'Mobile App Development',
            'lead_source'      => 'Google',
            'priority'         => 'Urgent',
        ]);

        $leadA = Lead::where('customer_phone', '9811111111')->firstOrFail();
        $leadB = Lead::where('customer_phone', '9822222222')->firstOrFail();

        $this->assertNotEquals($leadA->lead_number, $leadB->lead_number);
        $this->assertMatchesRegularExpression('/^LED-\d{4}-\d{4}$/', $leadA->lead_number);
        $this->assertMatchesRegularExpression('/^LED-\d{4}-\d{4}$/', $leadB->lead_number);
    }

    /**
     * Point 10: Selecting a partner associates partner_id with the lead.
     */
    public function test_admin_lead_preserves_partner_attribution(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Partner Attributed Client',
            'customer_phone'   => '9833333333',
            'service_required' => 'ERP Development',
            'partner_id'       => $this->partner->id,
            'lead_source'      => 'Referral',
            'priority'         => 'High',
        ]);

        $lead = Lead::where('customer_phone', '9833333333')->firstOrFail();
        $this->assertSame($this->partner->id, $lead->partner_id);
        $this->assertSame($this->partner->user_id, $lead->partner->user_id);
        $response->assertRedirect(route('admin.leads.show', $lead));
    }

    /**
     * Point 11: Assigning team member sets status to assigned and logs assignment activity.
     */
    public function test_admin_lead_supports_team_member_assignment(): void
    {
        $response = $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Assigned Client',
            'customer_phone'   => '9844444444',
            'service_required' => 'SEO & Digital Marketing',
            'assigned_to'      => $this->teamMemberUser->id,
            'lead_source'      => 'Instagram',
            'priority'         => 'Urgent',
        ]);

        $lead = Lead::where('customer_phone', '9844444444')->firstOrFail();
        $this->assertSame(LeadStatus::Assigned, $lead->status);
        $this->assertSame($this->teamMemberUser->id, $lead->assigned_to);
        $this->assertSame($this->admin->id, $lead->assigned_by);
        $this->assertNotNull($lead->assigned_at);

        // Check assignment activity
        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::LeadAssigned)
            ->first();
        $this->assertNotNull($activity);
        $this->assertSame($this->admin->id, $activity->user_id);
        $this->assertStringContainsString($this->teamMemberUser->name, $activity->summary);

        $response->assertRedirect(route('admin.leads.show', $lead));
    }

    /**
     * Point 12: Submitting phone/email within 90 days sets is_duplicate_flag and warns admin.
     */
    public function test_admin_lead_duplicate_handling(): void
    {
        // First lead
        $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Original Prospect',
            'customer_phone'   => '9855555555',
            'customer_email'   => 'prospect@duplicate-test.com',
            'service_required' => 'Custom Software',
            'lead_source'      => 'Direct',
            'priority'         => 'Medium',
        ]);

        $originalLead = Lead::where('customer_phone', '9855555555')->firstOrFail();
        $this->assertFalse($originalLead->is_duplicate_flag);

        // Duplicate submission with same phone
        $dupResponse = $this->actingAs($this->admin)->post('/admin/leads', [
            'customer_name'    => 'Duplicate Prospect',
            'customer_phone'   => '9855555555',
            'customer_email'   => 'different@duplicate-test.com',
            'service_required' => 'Web App',
            'lead_source'      => 'Facebook',
            'priority'         => 'High',
        ]);

        $dupLead = Lead::where('customer_name', 'Duplicate Prospect')->firstOrFail();
        $this->assertTrue($dupLead->is_duplicate_flag);
        $this->assertSame($originalLead->id, $dupLead->duplicate_matched_lead_id);

        $dupResponse->assertRedirect(route('admin.leads.show', $dupLead));
        $dupResponse->assertSessionHas('warning');
    }

    /**
     * Point 13: Partner accessing POST /admin/leads gets 403 Forbidden.
     */
    public function test_partner_cannot_create_leads_through_admin_routes(): void
    {
        $response = $this->actingAs($this->partnerUser)->post('/admin/leads', [
            'customer_name'    => 'Unauthorized Lead',
            'customer_phone'   => '9866666666',
            'service_required' => 'E-Commerce',
            'lead_source'      => 'Direct',
            'priority'         => 'Medium',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('leads', [
            'customer_phone' => '9866666666',
        ]);
    }

    /**
     * Point 14: Team member accessing POST /admin/leads gets 403 Forbidden.
     */
    public function test_team_member_cannot_create_leads_through_admin_routes(): void
    {
        $response = $this->actingAs($this->teamMemberUser)->post('/admin/leads', [
            'customer_name'    => 'Unauthorized Staff Lead',
            'customer_phone'   => '9877777777',
            'service_required' => 'Cloud Migration',
            'lead_source'      => 'Direct',
            'priority'         => 'Low',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('leads', [
            'customer_phone' => '9877777777',
        ]);
    }

    /**
     * Point 15: Admin dashboard renders with zero RouteNotFoundException and all sidebar links resolve.
     */
    public function test_admin_dashboard_renders_without_route_exceptions(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertSee('Tech Googly');
        $response->assertSee('₹');
    }
}
