<?php

namespace Tests\Feature;

use App\Enums\CommissionStatus;
use App\Enums\CustomerPaymentStatus;
use App\Enums\LeadInterest;
use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileAppUiUxTransformationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $teamMember;
    private User $partnerUser;
    private Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->admin = User::create([
            'name'              => 'Sumanta Das (Admin)',
            'email'             => 'admin_test@techgoogly.com',
            'password'          => Hash::make('AdminPass123!'),
            'role'              => UserRole::Admin,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        // 2. Team Member
        $this->teamMember = User::create([
            'name'              => 'Rohan Sen (Sales)',
            'email'             => 'rohan_test@techgoogly.com',
            'phone'             => '9830011223',
            'employee_id'       => 'TG-TM-99',
            'password'          => Hash::make('StaffPass123!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'department'        => 'Sales',
            'email_verified_at' => now(),
        ]);

        // 3. Partner
        $this->partnerUser = User::create([
            'name'              => 'Partner Director',
            'email'             => 'partner_dir@agency.com',
            'password'          => Hash::make('PartnerPass123!'),
            'role'              => UserRole::Partner,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-2026-APP',
            'company_name'            => 'Apex Business Solutions',
            'phone'                   => '9876543210',
            'default_commission_rate' => 10.00,
            'country'                 => 'India',
            'approved_at'             => now(),
            'approved_by'             => $this->admin->id,
        ]);
    }

    /**
     * 1. Test PWA Manifest and Static Shell Assets are available in public/.
     */
    public function test_pwa_manifest_and_offline_shell_are_accessible(): void
    {
        $this->assertFileExists(public_path('manifest.json'));
        $manifestContent = file_get_contents(public_path('manifest.json'));
        $this->assertStringContainsString('TG Partner Program', $manifestContent);
        $this->assertStringContainsString('#6B3D95', $manifestContent);
        $this->assertStringContainsString('standalone', $manifestContent);

        $this->assertFileExists(public_path('sw.js'));
        $swContent = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('tg-partner-static', $swContent);
        $this->assertStringContainsString('NEVER cache or serve authenticated financial/KYC', $swContent);

        $this->assertFileExists(public_path('offline.html'));
        $offlineContent = file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('You are Offline', $offlineContent);
    }

    /**
     * 2. Test Admin Portal renders identical Desktop & Mobile Sidebar, AND original 5-item Mobile Bottom Nav.
     */
    public function test_admin_portal_renders_identical_sidebar_and_bottom_nav(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();
        
        // Mobile header brand and role mark
        $response->assertSee('Admin Control');
        $response->assertSee('open-drawer-btn', false);
        $response->assertSee('close-drawer-btn', false);

        // Verify mobile drawer panel exists and uses identical shared structure
        $response->assertSee('mobile-drawer-panel', false);
        $response->assertSee('mobile-drawer-backdrop', false);

        // Core admin menu items (present in both desktop and mobile through shared component)
        $response->assertSee('Financial Dashboard');
        $response->assertSee('Partners');
        $response->assertSee('All Partners');
        $response->assertSee('+ Add New Partner');
        $response->assertSee('Team Members');
        $response->assertSee('All Team Members');
        $response->assertSee('+ Add Team Member');
        $response->assertSee('KYC Verification');
        $response->assertSee('KYC Dashboard');
        $response->assertSee('Pending KYC');
        $response->assertSee('Leads');
        $response->assertSee('All Leads');
        $response->assertSee('Add New Lead');
        $response->assertSee('Lead Pipeline');
        $response->assertSee('Customers');
        $response->assertSee('All Customers');
        $response->assertSee('+ Add Customer');
        $response->assertSee('Projects');
        $response->assertSee('All Projects');
        $response->assertSee('+ Add Project');
        $response->assertSee('Payments');
        $response->assertSee('All Payments');
        $response->assertSee('+ Record Payment');
        $response->assertSee('Commissions');
        $response->assertSee('Commission Ledger');
        $response->assertSee('Payouts &amp; Withdrawals', false);
        $response->assertSee('Reports &amp; Analytics', false);
        $response->assertSee('System Settings');

        // STRICT REQUIREMENT: Original 5-item Mobile Bottom Navigation Bar is restored
        $response->assertSee('mobile-bottom-nav', false);
        $response->assertSee('bottom-0 inset-x-0', false);
        $response->assertSee('grid grid-cols-5', false);
        $response->assertSee('Dashboard');
        $response->assertSee('Partners');
        $response->assertSee('Leads');
        $response->assertSee('Finance');
        $response->assertSee('More');

        // STRICT REQUIREMENT: NO Install App Items in Sidebar
        $response->assertDontSee('Install TG Partner App');
        $response->assertDontSee('Install App');
        $response->assertDontSee('pwa-install-trigger', false);
        $response->assertDontSee('Add to Home Screen');
        $response->assertDontSee('Download App');
    }

    /**
     * 3. Test Staff Portal renders identical CRM Sidebar, AND original 5-item Mobile Bottom Nav.
     */
    public function test_staff_portal_renders_identical_sidebar_and_bottom_nav(): void
    {
        // Seed an assigned lead
        Lead::create([
            'partner_id'          => $this->partner->id,
            'assigned_to'         => $this->teamMember->id,
            'lead_number'         => 'LD-CRM-001',
            'customer_name'       => 'Priya Roy Enterprises',
            'customer_phone'      => '9830099887',
            'service_required'    => 'Custom CRM Application',
            'project_description' => 'Enterprise CRM workflow',
            'status'              => LeadStatus::InDiscussion,
            'interest_level'      => LeadInterest::Hot,
            'next_followup_date'  => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->teamMember)->get(route('team.leads.index'));

        $response->assertOk();
        
        // Mobile Header role mark
        $response->assertSee('Sales CRM');
        $response->assertSee('open-drawer-btn', false);
        $response->assertSee('close-drawer-btn', false);

        // Shared navigation links (present in both desktop and mobile drawer)
        $response->assertSee('CRM Dashboard');
        $response->assertSee('My Assigned Leads');
        $response->assertSee("Today's Follow-ups", false);
        $response->assertSee('Active Pipeline');

        // Mobile card click-to-call and WhatsApp links
        $response->assertSee('tel:9830099887');
        $response->assertSee('https://wa.me/919830099887', false);
        $response->assertSee('Priya Roy Enterprises');

        // STRICT REQUIREMENT: Original 5-item Mobile Bottom Navigation Bar is restored for Staff
        $response->assertSee('mobile-bottom-nav', false);
        $response->assertSee('bottom-0 inset-x-0', false);
        $response->assertSee('grid grid-cols-5', false);
        $response->assertSee('Home');
        $response->assertSee('My Leads');
        $response->assertSee('Calls Today');
        $response->assertSee('Pipeline');
        $response->assertSee('More');

        // STRICT REQUIREMENT: NO Install App Items in Sidebar
        $response->assertDontSee('Install TG Partner App');
        $response->assertDontSee('Install App');
        $response->assertDontSee('pwa-install-trigger', false);
        $response->assertDontSee('Add to Home Screen');
        $response->assertDontSee('Download App');
    }

    /**
     * 4. Test Partner Portal renders identical Partner Sidebar, AND original 5-item Mobile Bottom Nav.
     */
    public function test_partner_portal_renders_identical_sidebar_and_bottom_nav(): void
    {
        // Create an approved customer and verified payment to test live balance
        $customer = Customer::create([
            'first_partner_id' => $this->partner->id,
            'customer_code'    => 'CUST-2026-TEST',
            'name'             => 'Enterprise Client Corp',
            'email'            => 'client@corp.com',
            'phone'            => '9830001122',
        ]);

        $project = Project::create([
            'partner_id'          => $this->partner->id,
            'customer_id'         => $customer->id,
            'client_name'         => $customer->name,
            'project_title'       => 'Cloud Data Pipeline',
            'project_code'        => 'PRJ-2026-001',
            'project_description' => 'Complete enterprise cloud migration',
            'final_deal_value'    => 200000.00,
            'status'              => ProjectStatus::InProgress,
        ]);

        Commission::create([
            'partner_id'          => $this->partner->id,
            'customer_id'         => $customer->id,
            'project_id'          => $project->id,
            'commission_number'   => 'COM-2026-001',
            'amount'              => 20000.00,
            'calculation_base'    => 200000.00,
            'commission_rate'     => 10.00,
            'status'              => CommissionStatus::Payable,
        ]);

        $response = $this->actingAs($this->partnerUser)->get(route('partner.workspace'));

        $response->assertOk();
        
        // Mobile header role mark
        $response->assertSee('Partner Portal');
        $response->assertSee('open-drawer-btn', false);
        $response->assertSee('close-drawer-btn', false);

        // Shared navigation links (present in both desktop and mobile drawer)
        $response->assertSee('Dashboard Overview');
        $response->assertSee('My Submitted Leads');
        $response->assertSee('Converted Customers');
        $response->assertSee('Active Projects');
        $response->assertSee('Commission Earnings');
        $response->assertSee('Withdrawals &amp; Payouts', false);
        $response->assertSee('KYC &amp; Verification', false);
        $response->assertSee('Bank &amp; Payout Settings', false);
        $response->assertSee('Notifications');
        $response->assertSee('Submit New Lead');

        // Real balance display
        $response->assertSee('20,000.00');
        $response->assertSee('Available For Withdrawal');
        $response->assertSee('TGP-2026-APP');

        // STRICT REQUIREMENT: Original 5-item Mobile Bottom Navigation Bar is restored for Partner
        $response->assertSee('mobile-bottom-nav', false);
        $response->assertSee('bottom-0 inset-x-0', false);
        $response->assertSee('grid grid-cols-5', false);
        $response->assertSee('Home');
        $response->assertSee('My Leads');
        $response->assertSee('Earnings');
        $response->assertSee('Withdraw');
        $response->assertSee('More');

        // STRICT REQUIREMENT: NO Install App Items in Sidebar
        $response->assertDontSee('Install TG Partner App');
        $response->assertDontSee('Install App');
        $response->assertDontSee('pwa-install-trigger', false);
        $response->assertDontSee('Add to Home Screen');
        $response->assertDontSee('Download App');
    }

    /**
     * 5. Test Strict Security: Team Member cannot access Partner or Financial data.
     */
    public function test_strict_security_team_member_cannot_access_financials_or_kyc(): void
    {
        $this->actingAs($this->teamMember);

        // Cannot view admin KYC or Payouts
        $this->get(route('admin.payouts.index'))->assertStatus(403);
        $this->get(route('admin.kyc.index'))->assertStatus(403);

        // Cannot view partner withdrawals or commissions
        $this->get(route('partner.withdrawals.index'))->assertStatus(403);
        $this->get(route('partner.commissions.index'))->assertStatus(403);
    }
}
