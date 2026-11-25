<?php

namespace Tests\Feature;

use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffDashboardAndCrmAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $staffUser1;
    private User $staffUser2;
    private User $adminUser;
    private User $partnerUser;
    private Partner $partner;
    private Lead $staff1Lead;
    private Lead $staff2Lead;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Staff Member 1
        $this->staffUser1 = User::factory()->create([
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
            'name' => 'Amit Sharma (Staff 1)',
            'email' => 'staff1.audit@techgoogly.com',
            'phone' => '9830011111',
            'employee_id' => 'TG-TM-101',
            'designation' => 'Senior CRM Specialist',
            'department' => 'Enterprise Sales',
            'joining_date' => now()->subMonths(6),
            'password' => Hash::make('StaffPassword123!'),
        ]);

        // 2. Staff Member 2
        $this->staffUser2 = User::factory()->create([
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
            'name' => 'Priya Patel (Staff 2)',
            'email' => 'staff2.audit@techgoogly.com',
            'phone' => '9830022222',
            'employee_id' => 'TG-TM-102',
            'designation' => 'Telecalling Agent',
            'department' => 'Inside Sales',
            'joining_date' => now()->subMonths(3),
            'password' => Hash::make('StaffPassword123!'),
        ]);

        // 3. Admin User
        $this->adminUser = User::factory()->create([
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'email' => 'admin.audit@techgoogly.com',
        ]);

        // 4. Partner User
        $this->partnerUser = User::factory()->create([
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
            'email' => 'partner.audit@techgoogly.com',
        ]);
        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-AUDIT-STF',
            'company_name' => 'Audit Partner LLP',
            'phone' => '9830099999',
            'bank_name' => 'HDFC Bank',
            'bank_account_name' => 'Audit Partner Secret Account',
            'bank_account_number' => '50100999999999',
            'bank_routing_code' => 'HDFC0009999',
            'payout_method' => 'bank_transfer',
            'default_commission_rate' => 12.50,
        ]);

        // 5. Leads: 1 assigned to Staff 1, 1 assigned to Staff 2
        $this->staff1Lead = Lead::create([
            'partner_id' => $this->partner->id,
            'assigned_to' => $this->staffUser1->id,
            'lead_number' => 'LD-STF-001',
            'customer_name' => 'Ramesh Gupta',
            'customer_company' => 'Gupta Trading Co',
            'customer_email' => 'ramesh@guptatrading.com',
            'customer_phone' => '9876543210',
            'city' => 'Kolkata',
            'service_required' => 'Custom ERP Portal',
            'project_description' => 'Requirement for multi-warehouse inventory ERP.',
            'estimated_value' => 250000.00,
            'currency' => 'INR',
            'status' => LeadStatus::Assigned,
            'interest_level' => LeadInterest::Hot,
            'next_followup_date' => now()->toDateString(),
        ]);

        $this->staff2Lead = Lead::create([
            'partner_id' => $this->partner->id,
            'assigned_to' => $this->staffUser2->id,
            'lead_number' => 'LD-STF-002',
            'customer_name' => 'Suresh Verma',
            'customer_company' => 'Verma Logistics',
            'customer_email' => 'suresh@vermalogistics.com',
            'customer_phone' => '9876543211',
            'city' => 'Durgapur',
            'service_required' => 'Transport Management System',
            'project_description' => 'Fleet management web software.',
            'estimated_value' => 180000.00,
            'currency' => 'INR',
            'status' => LeadStatus::Contacted,
            'interest_level' => LeadInterest::Warm,
            'next_followup_date' => now()->addDays(2)->toDateString(),
        ]);
    }

    public function test_guest_cannot_access_staff_workspace_leads_or_profile(): void
    {
        $this->get(route('team.workspace'))->assertRedirect(route('login'));
        $this->get(route('team.leads.index'))->assertRedirect(route('login'));
        $this->get(route('team.leads.show', $this->staff1Lead))->assertRedirect(route('login'));
        $this->get(route('team.profile.edit'))->assertRedirect(route('login'));
    }

    public function test_partner_cannot_access_staff_workspace_leads_or_profile(): void
    {
        $this->actingAs($this->partnerUser)->get(route('team.workspace'))->assertForbidden();
        $this->actingAs($this->partnerUser)->get(route('team.leads.index'))->assertForbidden();
        $this->actingAs($this->partnerUser)->get(route('team.leads.show', $this->staff1Lead))->assertForbidden();
        $this->actingAs($this->partnerUser)->get(route('team.profile.edit'))->assertForbidden();
    }

    public function test_staff_dashboard_kpis_strictly_scoped_to_authenticated_team_member(): void
    {
        // Add 1 won lead for Staff 1
        Lead::create([
            'partner_id' => $this->partner->id,
            'assigned_to' => $this->staffUser1->id,
            'lead_number' => 'LD-STF-003',
            'customer_name' => 'Kunal Roy',
            'customer_phone' => '9876543212',
            'service_required' => 'Mobile App',
            'project_description' => 'Delivery app',
            'status' => LeadStatus::Won,
            'estimated_value' => 100000.00,
        ]);

        $response = $this->actingAs($this->staffUser1)->get(route('team.workspace'));

        $response->assertOk();
        $response->assertSee('Welcome back, Amit Sharma (Staff 1)!');
        $response->assertSee('TG-TM-101');
        $response->assertSee('Senior CRM Specialist');

        $stats = $response->viewData('stats');
        // Staff 1 has 2 leads (1 assigned, 1 won); Staff 2 has 1 lead
        $this->assertEquals(2, $stats['total_assigned']);
        $this->assertEquals(1, $stats['won']);
        $this->assertEquals(0, $stats['lost']);
        $this->assertEquals(50.0, $stats['conversion_rate']);
        $this->assertEquals(1, $stats['today_followup']);

        // Check view variables
        $todayLeads = $response->viewData('todayLeads');
        $this->assertCount(1, $todayLeads);
        $this->assertEquals('LD-STF-001', $todayLeads->first()->lead_number);
    }

    public function test_staff_cannot_view_another_staff_members_assigned_lead(): void
    {
        // Staff 1 tries accessing Staff 2's lead -> 403 Forbidden
        $response = $this->actingAs($this->staffUser1)->get(route('team.leads.show', $this->staff2Lead));
        $response->assertForbidden();

        // Staff 2 tries accessing Staff 1's lead -> 403 Forbidden
        $response = $this->actingAs($this->staffUser2)->get(route('team.leads.show', $this->staff1Lead));
        $response->assertForbidden();
    }

    public function test_staff_cannot_log_activity_on_another_staff_members_assigned_lead(): void
    {
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.activity', $this->staff2Lead), [
            'activity_type' => 'call',
            'summary' => 'Sneaky call on rival lead',
        ]);

        $response->assertForbidden();
    }

    public function test_staff_cannot_update_status_on_another_staff_members_assigned_lead(): void
    {
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff2Lead), [
            'status' => 'won',
        ]);

        $response->assertForbidden();
    }

    public function test_staff_can_view_own_assigned_lead(): void
    {
        $response = $this->actingAs($this->staffUser1)->get(route('team.leads.show', $this->staff1Lead));

        $response->assertOk();
        $response->assertSee('LD-STF-001');
        $response->assertSee('Ramesh Gupta');
        $response->assertSee('Gupta Trading Co');
        $response->assertSee('Custom ERP Portal');
    }

    public function test_staff_lead_dossier_never_exposes_partner_financial_data(): void
    {
        $response = $this->actingAs($this->staffUser1)->get(route('team.leads.show', $this->staff1Lead));

        $response->assertOk();
        // Partner bank info and commission rate MUST NOT be rendered to staff
        $response->assertDontSee('50100999999999');
        $response->assertDontSee('Audit Partner Secret Account');
        $response->assertDontSee('HDFC0009999');
        $response->assertDontSee('12.50%');
    }

    public function test_staff_can_log_telecalling_activity_and_it_records_in_timeline(): void
    {
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.activity', $this->staff1Lead), [
            'activity_type' => 'call',
            'summary' => 'Introduction call completed with client.',
            'details' => 'Discussed ERP software scope and budget timeline.',
            'customer_interest' => 'hot',
            'next_followup_date' => now()->addDays(3)->toDateString(),
            'call_duration_seconds' => 420,
            'quotation_value' => 220000.00,
            'is_internal' => 0,
        ]);

        $response->assertRedirect(route('team.leads.show', $this->staff1Lead));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lead_activities', [
            'lead_id' => $this->staff1Lead->id,
            'user_id' => $this->staffUser1->id,
            'activity_type' => LeadActivityType::Call->value,
            'summary' => 'Introduction call completed with client.',
            'call_duration_seconds' => 420,
        ]);

        $this->staff1Lead->refresh();
        $this->assertEquals(220000.00, (float) $this->staff1Lead->quotation_value);
        $this->assertEquals(LeadInterest::Hot, $this->staff1Lead->interest_level);
        $this->assertEquals(now()->addDays(3)->toDateString(), $this->staff1Lead->next_followup_date->toDateString());
    }

    public function test_staff_can_progress_lead_status_through_pipeline(): void
    {
        // Assigned -> Contacted
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff1Lead), [
            'status' => 'contacted',
            'reason' => 'First call connected successfully.',
        ]);

        $response->assertRedirect(route('team.leads.show', $this->staff1Lead));
        $response->assertSessionHas('success');

        $this->staff1Lead->refresh();
        $this->assertEquals(LeadStatus::Contacted, $this->staff1Lead->status);

        // Contacted -> In Discussion
        $response2 = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff1Lead), [
            'status' => 'in_discussion',
            'reason' => 'Sent technical architecture document for review.',
        ]);

        $response2->assertRedirect(route('team.leads.show', $this->staff1Lead));
        $this->staff1Lead->refresh();
        $this->assertEquals(LeadStatus::InDiscussion, $this->staff1Lead->status);
    }

    public function test_staff_cannot_perform_invalid_status_transition(): void
    {
        // Move lead to Won
        $this->staff1Lead->update(['status' => LeadStatus::Won]);

        // Attempting to move a Won lead back to Contacted is an illegal transition
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff1Lead), [
            'status' => 'contacted',
        ]);

        $response->assertSessionHasErrors(['status']);

        $this->staff1Lead->refresh();
        $this->assertEquals(LeadStatus::Won, $this->staff1Lead->status);
    }

    public function test_staff_marking_lost_requires_mandatory_reason(): void
    {
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff1Lead), [
            'status' => 'lost',
            'reason' => '', // empty reason
        ]);

        $response->assertSessionHasErrors(['status']);

        $this->staff1Lead->refresh();
        $this->assertNotEquals(LeadStatus::Lost, $this->staff1Lead->status);
    }

    public function test_staff_updating_status_persists_quotation_value(): void
    {
        $response = $this->actingAs($this->staffUser1)->post(route('team.leads.status', $this->staff1Lead), [
            'status' => 'quotation_sent',
            'quotation_value' => 245000.00,
            'reason' => 'Official proposal sent via email.',
        ]);

        $response->assertRedirect(route('team.leads.show', $this->staff1Lead));

        $this->staff1Lead->refresh();
        $this->assertEquals(LeadStatus::QuotationSent, $this->staff1Lead->status);
        $this->assertEquals(245000.00, (float) $this->staff1Lead->quotation_value);
    }

    public function test_staff_can_view_profile_page_with_correct_data(): void
    {
        $response = $this->actingAs($this->staffUser1)->get(route('team.profile.edit'));

        $response->assertOk();
        $response->assertSee('Amit Sharma (Staff 1)');
        $response->assertSee('TG-TM-101');
        $response->assertSee('staff1.audit@techgoogly.com');
        $response->assertSee('9830011111');
        $response->assertSee('Senior CRM Specialist');
        $response->assertSee('Enterprise Sales');
    }

    public function test_staff_can_update_editable_profile_fields(): void
    {
        $response = $this->actingAs($this->staffUser1)->put(route('team.profile.update'), [
            'name' => 'Amit K. Sharma',
            'phone' => '9830099887',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->staffUser1->refresh();
        $this->assertEquals('Amit K. Sharma', $this->staffUser1->name);
        $this->assertEquals('9830099887', $this->staffUser1->phone);
    }

    public function test_staff_cannot_tamper_with_immutable_fields(): void
    {
        // Try maliciously tampering with role, status, employee_id, email, designation, department
        $response = $this->actingAs($this->staffUser1)->put(route('team.profile.update'), [
            'name' => 'Legit Name',
            'phone' => '9830011111',
            'role' => 'admin',
            'status' => 'suspended',
            'employee_id' => 'TG-ADMIN-001',
            'email' => 'hacked@evil.com',
            'designation' => 'Managing Director',
            'department' => 'Executive Office',
        ]);

        $response->assertRedirect();

        $this->staffUser1->refresh();
        $this->assertEquals(UserRole::TeamMember, $this->staffUser1->role);
        $this->assertEquals(UserStatus::Active, $this->staffUser1->status);
        $this->assertEquals('TG-TM-101', $this->staffUser1->employee_id);
        $this->assertEquals('staff1.audit@techgoogly.com', $this->staffUser1->email);
        $this->assertEquals('Senior CRM Specialist', $this->staffUser1->designation);
        $this->assertEquals('Enterprise Sales', $this->staffUser1->department);
    }

    public function test_staff_can_change_password_with_valid_current_password(): void
    {
        $response = $this->actingAs($this->staffUser1)->put(route('team.profile.password'), [
            'current_password' => 'StaffPassword123!',
            'password' => 'BrandNewStaffSecret@2026',
            'password_confirmation' => 'BrandNewStaffSecret@2026',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->staffUser1->refresh();
        $this->assertTrue(Hash::check('BrandNewStaffSecret@2026', $this->staffUser1->password));
    }

    public function test_staff_cannot_change_password_with_incorrect_current_password(): void
    {
        $response = $this->actingAs($this->staffUser1)->put(route('team.profile.password'), [
            'current_password' => 'WrongPassword999!',
            'password' => 'BrandNewStaffSecret@2026',
            'password_confirmation' => 'BrandNewStaffSecret@2026',
        ]);

        $response->assertSessionHasErrors(['current_password']);

        $this->staffUser1->refresh();
        $this->assertTrue(Hash::check('StaffPassword123!', $this->staffUser1->password));
    }

    public function test_staff_can_upload_profile_photo(): void
    {
        Storage::fake('public');

        $photo = UploadedFile::fake()->image('staff_avatar.jpg', 300, 300)->size(512);

        $response = $this->actingAs($this->staffUser1)->post(route('team.profile.photo'), [
            'photo' => $photo,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->staffUser1->refresh();
        $this->assertNotNull($this->staffUser1->profile_photo_path);
        Storage::disk('public')->assertExists($this->staffUser1->profile_photo_path);
    }

    public function test_staff_cannot_access_admin_routes(): void
    {
        $this->actingAs($this->staffUser1)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('admin.partners.index'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('admin.team-members.index'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('admin.commissions.index'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('admin.payouts.index'))->assertForbidden();
    }

    public function test_staff_cannot_access_partner_routes(): void
    {
        $this->actingAs($this->staffUser1)->get(route('partner.workspace'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('partner.commissions.index'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('partner.withdrawals.index'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('partner.kyc.show'))->assertForbidden();
        $this->actingAs($this->staffUser1)->get(route('partner.payout-settings.edit'))->assertForbidden();
    }
}
