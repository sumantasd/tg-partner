<?php

namespace Tests\Feature;

use App\Enums\LeadActivityType;
use App\Enums\LeadSlaEscalationType;
use App\Enums\LeadSlaStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSlaEscalation;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\LeadService;
use App\Services\LeadSlaService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Batch4LeadSlaAndEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $teamUser;
    protected User $teamUser2;
    protected User $partnerUser;
    protected Partner $partner;
    protected LeadSlaService $slaService;
    protected LeadService $leadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser = User::create([
            'name' => 'Rajesh Sharma',
            'email' => 'rajesh@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser2 = User::create([
            'name' => 'Priya Patel',
            'email' => 'priya@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser = User::create([
            'name' => 'Partner User',
            'email' => 'partner@alphacorp.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-2026-ALPHA',
            'company_name' => 'Alpha Corporation',
            'phone' => '9876543200',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->slaService = app(LeadSlaService::class);
        $this->leadService = app(LeadService::class);
    }

    /**
     * 1. SLA configuration defaults and retrieval from system settings.
     */
    public function test_sla_configuration_defaults_and_retrieval(): void
    {
        $config = $this->slaService->getConfig();

        $this->assertTrue($config['sla_enabled']);
        $this->assertEquals(24, $config['sla_first_contact_hours']);
        $this->assertEquals(48, $config['sla_followup_hours']);
        $this->assertEquals(12, $config['sla_reassigned_first_contact_hours']);
        $this->assertEquals('calendar_hours', $config['sla_calculation_mode']);
        $this->assertEquals('09:30', $config['sla_business_start_time']);
        $this->assertEquals('18:30', $config['sla_business_end_time']);
        $this->assertIsArray($config['sla_working_days']);
        $this->assertEquals(4, $config['sla_due_soon_threshold_hours']);
    }

    /**
     * 2. Admin can update SLA parameters via web controller.
     */
    public function test_admin_can_update_sla_parameters_via_web(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.lead-sla.settings.update'), [
                'sla_enabled' => '1',
                'sla_first_contact_hours' => 36,
                'sla_followup_hours' => 72,
                'sla_reassigned_first_contact_hours' => 18,
                'sla_calculation_mode' => 'business_hours',
                'sla_business_start_time' => '10:00',
                'sla_business_end_time' => '19:00',
                'sla_working_days' => [1, 2, 3, 4, 5],
                'sla_due_soon_threshold_hours' => 6,
            ]);

        $response->assertRedirect(route('admin.lead-sla.settings'));
        $response->assertSessionHas('success');

        $config = $this->slaService->getConfig();
        $this->assertEquals(36, $config['sla_first_contact_hours']);
        $this->assertEquals(72, $config['sla_followup_hours']);
        $this->assertEquals(18, $config['sla_reassigned_first_contact_hours']);
        $this->assertEquals('business_hours', $config['sla_calculation_mode']);
        $this->assertEquals('10:00', $config['sla_business_start_time']);
        $this->assertEquals('19:00', $config['sla_business_end_time']);
        $this->assertEquals([1, 2, 3, 4, 5], $config['sla_working_days']);
        $this->assertEquals(6, $config['sla_due_soon_threshold_hours']);
    }

    /**
     * 3. Deadline calculation in calendar hours mode (24/7 continuous).
     */
    public function test_deadline_calculation_calendar_hours(): void
    {
        SystemSetting::set('sla_calculation_mode', 'calendar_hours');
        $start = Carbon::parse('2026-11-23 10:00:00', 'Asia/Kolkata');

        $deadline = $this->slaService->calculateDeadline($start, 24);

        $this->assertEquals('2026-11-24 10:00:00', $deadline->format('Y-m-d H:i:s'));
    }

    /**
     * 4. Deadline calculation in business hours mode (respects business window & skips Sunday).
     */
    public function test_deadline_calculation_business_hours_skips_sunday_and_nights(): void
    {
        SystemSetting::set('sla_calculation_mode', 'business_hours');
        SystemSetting::set('sla_business_start_time', '09:30');
        SystemSetting::set('sla_business_end_time', '18:30'); // 9 working hours per day
        SystemSetting::set('sla_working_days', json_encode([1, 2, 3, 4, 5, 6])); // Mon-Sat, Sun off

        // Saturday Nov 28, 2026 at 15:30 IST.
        // 3 hours left on Saturday (15:30 to 18:30).
        // Target: 12 business hours.
        // Sunday is off.
        // Monday Nov 30: 9 hours (09:30 to 18:30). Total 3 + 9 = 12 hours.
        // Expected deadline: Monday Nov 30 at 18:30 IST.
        $satTime = Carbon::create(2026, 11, 28, 15, 30, 0, 'Asia/Kolkata');
        $this->assertEquals(6, $satTime->isoWeekday()); // Saturday

        $deadline = $this->slaService->calculateDeadline($satTime, 12, 'business_hours');

        $this->assertEquals('2026-11-30', $deadline->format('Y-m-d'));
        $this->assertEquals('18:30', $deadline->format('H:i'));
    }

    protected function createTestLead(string $name, string $phone, string $service = 'Custom Software'): Lead
    {
        return $this->leadService->createLead($this->partner, [
            'customer_name' => $name,
            'customer_phone' => $phone,
            'service_required' => $service,
            'project_description' => 'Test project requirement details',
        ], $this->partnerUser);
    }

    /**
     * 5. Assigning a lead automatically initiates First Contact SLA.
     */
    public function test_assigning_lead_initiates_first_contact_sla(): void
    {
        $lead = $this->createTestLead('Vikram Verma', '9876543210', 'Custom ERP Development');

        $this->assertNull($lead->assigned_to);
        $this->assertNull($lead->first_contact_due_at);

        // Admin assigns lead to staff
        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        $lead->refresh();
        $this->assertEquals($this->teamUser->id, $lead->assigned_to);
        $this->assertNotNull($lead->first_contact_due_at);
        $this->assertEquals(LeadSlaStatus::OnTrack, $lead->first_contact_sla_status);
        $this->assertEquals(LeadSlaStatus::OnTrack, $lead->overall_sla_status);
        $this->assertNull($lead->first_contacted_at);
    }

    /**
     * 6. Reassignment of lead uses reassigned first contact duration (12 hours default).
     */
    public function test_reassignment_uses_reassigned_sla_duration(): void
    {
        $lead = $this->createTestLead('Kavita Menon', '9876543211', 'Cloud Migration');

        // Initial assignment (24 hours)
        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);
        $initialDue = $lead->refresh()->first_contact_due_at;

        // Reassignment to Priya (12 hours)
        $this->leadService->assignLead($lead, $this->teamUser2, $this->adminUser, 'Reassigning to cloud specialist');

        $lead->refresh();
        $this->assertEquals($this->teamUser2->id, $lead->assigned_to);
        $this->assertNotNull($lead->first_contact_due_at);
        // Should be approximately now + 12 hours
        $this->assertTrue($lead->first_contact_due_at->diffInHours(now()) <= 12);
    }

    /**
     * 7. Only genuine direct customer contact (Call, WhatsApp, Email, Meeting) satisfies First Contact SLA.
     */
    public function test_only_genuine_customer_contact_satisfies_first_contact_sla(): void
    {
        $lead = $this->createTestLead('Amitabh Sen', '9876543212', 'Mobile App Development');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        // Logging an internal note MUST NOT satisfy first contact SLA
        $this->leadService->recordActivity($lead, $this->teamUser, [
            'activity_type' => LeadActivityType::Note->value,
            'summary' => 'Researched customer company website before calling',
            'details' => 'Found their current tech stack is legacy PHP.',
        ]);

        $lead->refresh();
        $this->assertNull($lead->first_contacted_at, 'Internal note must not satisfy first contact SLA.');
        $this->assertEquals(LeadSlaStatus::OnTrack, $lead->first_contact_sla_status);

        // Logging an actual Phone Call DOES satisfy first contact SLA
        $this->leadService->recordActivity($lead, $this->teamUser, [
            'activity_type' => LeadActivityType::Call->value,
            'summary' => 'Spoke with Amitabh regarding app requirements',
            'details' => 'Client requested quote for Android and iOS.',
            'call_duration_seconds' => 320,
            'customer_interest' => 'hot',
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->first_contacted_at);
        $this->assertEquals(LeadSlaStatus::Met, $lead->first_contact_sla_status);
        $this->assertEquals(LeadSlaStatus::OnTrack, $lead->overall_sla_status);
        $this->assertNotNull($lead->last_contacted_at);
        $this->assertNotNull($lead->followup_due_at);

        // Check that FirstContactMet timeline activity was generated
        $hasMetActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::FirstContactMet)
            ->exists();
        $this->assertTrue($hasMetActivity, 'FirstContactMet timeline event must be recorded.');
    }

    /**
     * 8. Status change alone does NOT satisfy First Contact SLA.
     */
    public function test_status_change_alone_does_not_satisfy_first_contact_sla(): void
    {
        $lead = $this->createTestLead('Deepak Roy', '9876543213', 'Website Redesign');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        // Transition status without genuine contact activity
        $this->leadService->updateStatus($lead, LeadStatus::Contacted, $this->teamUser);

        $lead->refresh();
        $this->assertNull($lead->first_contacted_at, 'Status transition without logged contact activity must not satisfy SLA.');
        $this->assertEquals(LeadStatus::Contacted, $lead->status);
    }

    /**
     * 9. Contacting customer after the deadline marks First Contact SLA as Overdue.
     */
    public function test_contacting_customer_after_deadline_marks_sla_overdue(): void
    {
        $lead = $this->createTestLead('Suresh Nair', '9876543214', 'Database Optimization');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        // Force deadline to past (yesterday)
        $lead->update(['first_contact_due_at' => now()->subHours(5)]);

        // Now staff logs WhatsApp contact
        $this->leadService->recordActivity($lead, $this->teamUser, [
            'activity_type' => LeadActivityType::WhatsApp->value,
            'summary' => 'Sent introductory WhatsApp message',
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->first_contacted_at);
        $this->assertEquals(LeadSlaStatus::Overdue, $lead->first_contact_sla_status);
        $this->assertEquals(LeadSlaStatus::Overdue, $lead->overall_sla_status);
    }

    /**
     * 10. Scheduling next follow-up date sets follow-up deadline to end of business on that date.
     */
    public function test_scheduled_followup_date_sets_deadline(): void
    {
        $lead = $this->createTestLead('Anita Desai', '9876543215', 'SEO Consultation');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        $targetDate = now()->addDays(3)->toDateString();

        $this->leadService->recordActivity($lead, $this->teamUser, [
            'activity_type' => LeadActivityType::Call->value,
            'summary' => 'Initial phone call, scheduled detailed demo',
            'next_followup_date' => $targetDate,
        ]);

        $lead->refresh();
        $this->assertNotNull($lead->followup_due_at);
        $this->assertEquals($targetDate, $lead->followup_due_at->format('Y-m-d'));
        $this->assertEquals('18:30', $lead->followup_due_at->format('H:i'));
    }

    /**
     * 11. Overdue first contact detected by breach checker and escalated to Admins.
     */
    public function test_breach_detection_and_escalation(): void
    {
        $lead = $this->createTestLead('Manish Gupta', '9876543216', 'Custom Software');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        // Simulate overdue deadline
        $lead->update([
            'first_contact_due_at' => now()->subHours(2),
            'first_contacted_at' => null,
        ]);

        $initialNotifications = Notification::where('user_id', $this->adminUser->id)->count();

        // Run Artisan escalation command
        $exitCode = \Illuminate\Support\Facades\Artisan::call('sla:check-escalations');
        $this->assertEquals(0, $exitCode);

        // Verify escalation record was stored
        $escalation = LeadSlaEscalation::where('lead_id', $lead->id)
            ->where('escalation_type', LeadSlaEscalationType::FirstContactBreach)
            ->first();

        $this->assertNotNull($escalation);
        $this->assertEquals('critical', $escalation->severity);
        $this->assertTrue($escalation->notification_sent);

        // Verify Admin received notification
        $newNotifications = Notification::where('user_id', $this->adminUser->id)->count();
        $this->assertTrue($newNotifications > $initialNotifications);

        // Verify lead timeline activity
        $timelineActivity = LeadActivity::where('lead_id', $lead->id)
            ->where('activity_type', LeadActivityType::SlaBreached)
            ->first();
        $this->assertNotNull($timelineActivity);

        $lead->refresh();
        $this->assertEquals(LeadSlaStatus::Overdue, $lead->first_contact_sla_status);
        $this->assertEquals(LeadSlaStatus::Overdue, $lead->overall_sla_status);
    }

    /**
     * 12. Deduplication: running escalation checker repeatedly does not create duplicate escalations or spam notifications.
     */
    public function test_escalation_deduplication(): void
    {
        $lead = $this->createTestLead('Ramesh Nair', '9876543217', 'Portal Maintenance');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);
        $lead->update([
            'first_contact_due_at' => now()->subHours(3),
            'first_contacted_at' => null,
        ]);

        // First run
        $this->slaService->checkAndEscalateBreaches();
        $escalationCount1 = LeadSlaEscalation::where('lead_id', $lead->id)->count();
        $notificationCount1 = Notification::where('user_id', $this->adminUser->id)->count();
        $this->assertEquals(1, $escalationCount1);

        // Second run immediately after
        $this->slaService->checkAndEscalateBreaches();
        $escalationCount2 = LeadSlaEscalation::where('lead_id', $lead->id)->count();
        $notificationCount2 = Notification::where('user_id', $this->adminUser->id)->count();

        // Must remain exactly identical - no duplicate escalations or notifications!
        $this->assertEquals(1, $escalationCount2);
        $this->assertEquals($notificationCount1, $notificationCount2);
    }

    /**
     * 13. Repeated missed follow-ups triggers critical RepeatedMissedFollowup escalation.
     */
    public function test_repeated_missed_followups_escalation(): void
    {
        $lead = $this->createTestLead('Alok Nath', '9876543218', 'E-Commerce Solution');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        // Set lead with 1 previous missed follow-up and past follow-up deadline
        $lead->update([
            'first_contacted_at' => now()->subDays(3),
            'first_contact_sla_status' => LeadSlaStatus::Met,
            'followup_due_at' => now()->subHours(2),
            'missed_followups_count' => 1,
        ]);

        $this->slaService->checkAndEscalateBreaches();

        $lead->refresh();
        $this->assertEquals(2, $lead->missed_followups_count);

        $repeatedEscalation = LeadSlaEscalation::where('lead_id', $lead->id)
            ->where('escalation_type', LeadSlaEscalationType::RepeatedMissedFollowup)
            ->first();

        $this->assertNotNull($repeatedEscalation);
        $this->assertEquals('critical', $repeatedEscalation->severity);
    }

    /**
     * 14. Won, Lost, and Rejected leads are exempt from active SLA tracking.
     */
    public function test_closed_leads_are_marked_exempt(): void
    {
        $lead = $this->createTestLead('Sunil Chhetri', '9876543219', 'Payment Gateway');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        $this->leadService->updateStatus($lead, LeadStatus::Contacted, $this->teamUser);
        $this->leadService->updateStatus($lead, LeadStatus::Lost, $this->teamUser, 'Budget mismatch with client');

        $lead->refresh();
        $this->assertEquals(LeadStatus::Lost, $lead->status);
        $this->assertEquals(LeadSlaStatus::Exempt, $lead->overall_sla_status);
        $this->assertFalse($lead->isUnderActiveSla());
    }

    /**
     * 15. Admin SLA Dashboard renders KPI cards and staff performance breakdown table.
     */
    public function test_admin_sla_dashboard_view_and_metrics(): void
    {
        $lead = $this->createTestLead('Meera Bai', '9876543220', 'AI Chatbot');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        $response = $this->actingAs($this->adminUser)->get(route('admin.lead-sla.index'));

        $response->assertOk();
        $response->assertViewIs('admin.sla.index');
        $response->assertSee('Lead SLA Management');
        $response->assertSee('Under Active SLA');
        $response->assertSee('SLA Compliance');
        $response->assertSee($this->teamUser->name);
    }

    /**
     * 16. Staff workspace displays SLA metrics and scoped follow-ups.
     */
    public function test_staff_workspace_scoped_sla_presentation(): void
    {
        $lead = $this->createTestLead('Harish Chandra', '9876543221', 'Mobile App');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);

        $response = $this->actingAs($this->teamUser)->get(route('team.workspace'));

        $response->assertOk();
        $response->assertSee($lead->lead_number);
        $response->assertSee('SLA:');
        $response->assertSee('1st Contact:');
    }

    /**
     * 17. Staff lead listing supports SLA filters.
     */
    public function test_staff_lead_listing_with_sla_filters(): void
    {
        $lead = $this->createTestLead('Bhavna Joshi', '9876543222', 'DevOps Support');

        $this->leadService->assignLead($lead, $this->teamUser, $this->adminUser);
        $lead->update([
            'overall_sla_status' => LeadSlaStatus::Overdue,
            'first_contact_sla_status' => LeadSlaStatus::Overdue,
        ]);

        $response = $this->actingAs($this->teamUser)->get(route('team.leads.index', ['sla' => 'overdue']));

        $response->assertOk();
        $response->assertSee($lead->lead_number);
    }
}
