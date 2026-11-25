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
use App\Services\LeadService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadManagementAndTeamWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teamUser1;
    protected User $teamUser2;
    protected User $partnerUser1;
    protected Partner $partner1;
    protected User $partnerUser2;
    protected Partner $partner2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'System Admin',
            'email' => 'admin@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser1 = User::create([
            'name' => 'Sales Consultant One',
            'email' => 'sales1@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser2 = User::create([
            'name' => 'Sales Consultant Two',
            'email' => 'sales2@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser1 = User::create([
            'name' => 'Partner Alpha',
            'email' => 'alpha@partner.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner1 = Partner::create([
            'user_id' => $this->partnerUser1->id,
            'partner_code' => 'TGP-2026-ALPHA',
            'company_name' => 'Alpha Solutions',
            'phone' => '9811111111',
            'default_commission_rate' => 10.00,
            'bank_account_number' => '123456789012',
        ]);

        $this->partnerUser2 = User::create([
            'name' => 'Partner Beta',
            'email' => 'beta@partner.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner2 = Partner::create([
            'user_id' => $this->partnerUser2->id,
            'partner_code' => 'TGP-2026-BETA',
            'company_name' => 'Beta Ventures',
            'phone' => '9822222222',
            'default_commission_rate' => 12.00,
            'bank_account_number' => '987654321098',
        ]);
    }

    public function test_partner_can_submit_a_lead(): void
    {
        $response = $this->actingAs($this->partnerUser1)
            ->post(route('partner.leads.store'), [
                'customer_name' => 'Rajesh Sharma',
                'customer_company' => 'Apex Logistics',
                'customer_phone' => '9876543210',
                'customer_email' => 'rajesh@apexlogistics.in',
                'city' => 'Mumbai',
                'service_required' => 'Custom Web & ERP Application',
                'project_description' => 'Need warehouse ERP and tracking system built with Laravel.',
                'estimated_value' => 250000.00,
            ]);

        $lead = Lead::where('customer_phone', '9876543210')->first();
        $this->assertNotNull($lead);
        $this->assertSame($this->partner1->id, $lead->partner_id);
        $this->assertSame(LeadStatus::Submitted, $lead->status);
        $this->assertStringStartsWith('LED-' . date('Y') . '-', $lead->lead_number);

        $response->assertRedirect(route('partner.leads.show', $lead));

        // Assert canonical timeline activity was created
        $activity = LeadActivity::where('lead_id', $lead->id)->first();
        $this->assertNotNull($activity);
        $this->assertSame(LeadActivityType::LeadSubmitted, $activity->activity_type);
        $this->assertFalse($activity->is_internal);
        $this->assertStringContainsString('Alpha Solutions', $activity->summary);
    }

    public function test_partner_cannot_view_another_partners_lead(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'John Doe',
            'customer_phone' => '9988776655',
            'service_required' => 'E-Commerce Platform',
            'project_description' => 'E-commerce marketplace platform.',
        ]);

        // Partner 2 attempts to view Partner 1's lead
        $response = $this->actingAs($this->partnerUser2)
            ->get(route('partner.leads.show', $lead));

        $response->assertStatus(403);
    }

    public function test_admin_can_view_all_leads_and_search(): void
    {
        $service = app(LeadService::class);
        $lead1 = $service->createLead($this->partner1, [
            'customer_name' => 'Customer One',
            'customer_phone' => '9911111111',
            'service_required' => 'ERP Application',
            'project_description' => 'ERP system needed.',
        ]);

        $lead2 = $service->createLead($this->partner2, [
            'customer_name' => 'Customer Two',
            'customer_phone' => '9922222222',
            'service_required' => 'Mobile App',
            'project_description' => 'Mobile app needed.',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.leads.index'));

        $response->assertStatus(200);
        $response->assertSee($lead1->lead_number);
        $response->assertSee($lead2->lead_number);

        // Search test
        $searchResponse = $this->actingAs($this->adminUser)
            ->get(route('admin.leads.index', ['search' => 'Customer One']));

        $searchResponse->assertStatus(200);
        $searchResponse->assertSee($lead1->lead_number);
        $searchResponse->assertDontSee($lead2->lead_number);
    }

    public function test_admin_can_assign_and_reassign_leads(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Ramesh Gupta',
            'customer_phone' => '9833333333',
            'service_required' => 'Cloud Migration',
            'project_description' => 'AWS cloud setup.',
        ]);

        // 1. Initial Assignment
        $assignResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.leads.assign', $lead), [
                'assigned_to' => $this->teamUser1->id,
                'note' => 'High priority enterprise client. Schedule call immediately.',
            ]);

        $assignResponse->assertRedirect(route('admin.leads.show', $lead));

        $lead->refresh();
        $this->assertSame($this->teamUser1->id, $lead->assigned_to);
        $this->assertSame(LeadStatus::Assigned, $lead->status);
        $this->assertNotNull($lead->assigned_at);

        // Check canonical activities
        $assignedActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::LeadAssigned)
            ->first();
        $this->assertNotNull($assignedActivity);
        $this->assertFalse($assignedActivity->is_internal);

        $internalNoteActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::AdminComment)
            ->first();
        $this->assertNotNull($internalNoteActivity);
        $this->assertTrue($internalNoteActivity->is_internal);
        $this->assertStringContainsString('High priority enterprise client', $internalNoteActivity->details);

        // 2. Reassignment to Team Member 2
        $reassignResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.leads.assign', $lead), [
                'assigned_to' => $this->teamUser2->id,
                'note' => 'Reassigning to specialized consultant.',
            ]);

        $lead->refresh();
        $this->assertSame($this->teamUser2->id, $lead->assigned_to);

        $reassignedActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::LeadReassigned)
            ->first();
        $this->assertNotNull($reassignedActivity);
    }

    public function test_team_member_can_access_assigned_leads_only(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Assigned Customer',
            'customer_phone' => '9844444444',
            'service_required' => 'UI UX Design',
            'project_description' => 'Figma design requirement.',
        ]);

        $service->assignLead($lead, $this->teamUser1, $this->adminUser);

        // Team User 1 can view assigned lead
        $response1 = $this->actingAs($this->teamUser1)
            ->get(route('team.leads.show', $lead));
        $response1->assertStatus(200);
        $response1->assertSee($lead->customer_name);

        // Team User 2 CANNOT access lead assigned to Team User 1
        $response2 = $this->actingAs($this->teamUser2)
            ->get(route('team.leads.show', $lead));
        $response2->assertStatus(403);
    }

    public function test_team_member_cannot_access_financial_information(): void
    {
        // Team member cannot access admin management
        $this->actingAs($this->teamUser1)
            ->get(route('admin.leads.index'))
            ->assertStatus(403);

        $this->actingAs($this->teamUser1)
            ->get(route('admin.partners.index'))
            ->assertStatus(403);

        // Team member cannot access partner workspace
        $this->actingAs($this->teamUser1)
            ->get(route('partner.workspace'))
            ->assertStatus(403);
    }

    public function test_status_transitions_are_recorded_in_canonical_timeline(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Pipeline Client',
            'customer_phone' => '9855555555',
            'service_required' => 'Custom ERP',
            'project_description' => 'ERP development.',
        ]);

        $service->assignLead($lead, $this->teamUser1, $this->adminUser);

        // Team Member updates status to Contacted
        $response = $this->actingAs($this->teamUser1)
            ->post(route('team.leads.status', $lead), [
                'status' => 'contacted',
                'reason' => 'First discovery call conducted.',
            ]);

        $response->assertRedirect(route('team.leads.show', $lead));

        $lead->refresh();
        $this->assertSame(LeadStatus::Contacted, $lead->status);

        $transitionActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::StatusChange)
            ->where('from_status', LeadStatus::Assigned->value)
            ->where('to_status', LeadStatus::Contacted->value)
            ->first();

        $this->assertNotNull($transitionActivity);
        $this->assertFalse($transitionActivity->is_internal);
        $this->assertSame('First discovery call conducted.', $transitionActivity->details);
    }

    public function test_internal_notes_are_hidden_from_partners(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Confidential Discussion Client',
            'customer_phone' => '9866666666',
            'service_required' => 'AI Automation',
            'project_description' => 'AI integration pipeline.',
        ]);

        $service->assignLead($lead, $this->teamUser1, $this->adminUser);

        // Log an internal private note
        $service->recordActivity($lead, $this->teamUser1, [
            'activity_type' => 'note',
            'summary' => 'Internal Team Discussion: Client requested 40% margin discount.',
            'details' => 'Do not disclose our internal margin to partner or client.',
            'is_internal' => true,
        ]);

        // Query partner-visible timeline: internal note must be absent
        $partnerVisibleActivities = $lead->activities()->partnerVisible()->get();
        foreach ($partnerVisibleActivities as $act) {
            $this->assertFalse($act->is_internal);
            $this->assertStringNotContainsString('40% margin discount', $act->summary);
        }

        // Partner visits show page: private note must NOT be rendered
        $partnerViewResponse = $this->actingAs($this->partnerUser1)
            ->get(route('partner.leads.show', $lead));

        $partnerViewResponse->assertStatus(200);
        $partnerViewResponse->assertDontSee('40% margin discount');
        $partnerViewResponse->assertDontSee('Do not disclose our internal margin');
    }

    public function test_duplicate_lead_detection_within_cooling_period(): void
    {
        $service = app(LeadService::class);

        // Lead 1: Submitted by Partner Alpha
        $lead1 = $service->createLead($this->partner1, [
            'customer_name' => 'Original Customer',
            'customer_phone' => '+91 98765 00000',
            'customer_email' => 'client@targetcorp.com',
            'service_required' => 'Web App',
            'project_description' => 'Original referral submission.',
        ]);

        $this->assertFalse($lead1->is_duplicate_flag);

        // Lead 2: Same Partner Alpha submits matching phone formatted differently
        $lead2 = $service->createLead($this->partner1, [
            'customer_name' => 'Duplicate By Same Partner',
            'customer_phone' => '9876500000', // normalized matches
            'customer_email' => 'other@targetcorp.com',
            'service_required' => 'Mobile App',
            'project_description' => 'Same customer, second request.',
        ]);

        $this->assertTrue($lead2->is_duplicate_flag);
        $this->assertSame($lead1->id, $lead2->duplicate_matched_lead_id);

        // Lead 3: Different Partner Beta submits matching email within cooling period
        $lead3 = $service->createLead($this->partner2, [
            'customer_name' => 'Conflicting Referral',
            'customer_phone' => '9999988888',
            'customer_email' => 'CLIENT@TARGETCORP.COM', // case insensitive match
            'service_required' => 'DevOps',
            'project_description' => 'Submitted by competitor partner.',
        ]);

        $this->assertTrue($lead3->is_duplicate_flag);
        $this->assertSame($lead1->id, $lead3->duplicate_matched_lead_id);

        // Admin resolves duplicate conflict on Lead 3
        $resolveResponse = $this->actingAs($this->adminUser)
            ->post(route('admin.leads.resolve-duplicate', $lead3), [
                'action' => 'dismiss',
                'notes' => 'Verified as separate division engagement.',
            ]);

        $resolveResponse->assertRedirect(route('admin.leads.show', $lead3));
        $lead3->refresh();
        $this->assertFalse($lead3->is_duplicate_flag);
    }

    public function test_follow_up_scheduling_and_interaction_logging(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Telecalling Client',
            'customer_phone' => '9877777777',
            'service_required' => 'ERP & CRM',
            'project_description' => 'Telecalling follow-up test.',
        ]);

        $service->assignLead($lead, $this->teamUser1, $this->adminUser);

        $nextFollowup = now()->addDays(3)->toDateString();

        $response = $this->actingAs($this->teamUser1)
            ->post(route('team.leads.activity', $lead), [
                'activity_type' => 'call',
                'summary' => 'Discovery call completed with purchase manager.',
                'details' => 'Client requested quotation for 50 user licenses.',
                'customer_interest' => 'hot',
                'call_duration_seconds' => 420,
                'next_followup_date' => $nextFollowup,
                'quotation_value' => 350000.00,
            ]);

        $response->assertRedirect(route('team.leads.show', $lead));

        $lead->refresh();
        $this->assertSame(LeadInterest::Hot, $lead->interest_level);
        $this->assertSame($nextFollowup, $lead->next_followup_date->toDateString());
        $this->assertEquals(350000.00, $lead->quotation_value);

        $activity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::Call)
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame(420, $activity->call_duration_seconds);
        $this->assertSame(LeadInterest::Hot, $activity->customer_interest);
    }

    public function test_invalid_status_transitions_are_rejected(): void
    {
        $service = app(LeadService::class);
        $lead = $service->createLead($this->partner1, [
            'customer_name' => 'Terminal Lead',
            'customer_phone' => '9888888888',
            'service_required' => 'Full Stack Dev',
            'project_description' => 'Testing terminal transition safety.',
        ]);

        $service->assignLead($lead, $this->teamUser1, $this->adminUser);
        $service->updateStatus($lead, LeadStatus::Won, $this->adminUser, 'Deal won and signed');

        $lead->refresh();
        $this->assertSame(LeadStatus::Won, $lead->status);

        // Attempting to revert Won lead back to Submitted or Assigned throws DomainException
        $this->expectException(DomainException::class);
        $service->updateStatus($lead, LeadStatus::Submitted, $this->adminUser);
    }
}
