<?php

namespace Tests\Feature;

use App\Enums\InactivityStatus;
use App\Enums\KycStatus;
use App\Enums\LeadActivityType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserInactivityHistory;
use App\Services\InactivityService;
use App\Services\LeadService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Batch5InactivityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUser;
    protected Partner $partner;
    protected User $staffUser;
    protected InactivityService $inactivityService;
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

        $this->partnerUser = User::create([
            'name' => 'Partner Alpha Contact',
            'email' => 'partner@alphadigital.com',
            'password' => 'secret123',
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
            'last_meaningful_activity_at' => now(),
            'inactivity_status' => InactivityStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-2026-ALPHA',
            'company_name' => 'Alpha Digital Services',
            'phone' => '9876500001',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
            'kyc_status' => KycStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->staffUser = User::create([
            'name' => 'Staff Consultant Rajesh',
            'email' => 'rajesh@techgoogly.com',
            'password' => 'secret123',
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
            'employee_id' => 'TG-TM-101',
            'last_meaningful_activity_at' => now(),
            'inactivity_status' => InactivityStatus::Active,
        ]);

        $this->inactivityService = app(InactivityService::class);
        $this->leadService = app(LeadService::class);
    }

    /**
     * 1. Login timestamp is recorded and updates last_login_at & last_meaningful_activity_at.
     */
    public function test_01_login_timestamp_is_recorded(): void
    {
        $oldLogin = now()->subDays(5);
        $this->partnerUser->update([
            'last_login_at' => $oldLogin,
            'last_meaningful_activity_at' => $oldLogin,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'partner@alphadigital.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('partner.workspace'));

        $this->partnerUser->refresh();
        $this->assertNotNull($this->partnerUser->last_login_at);
        $this->assertTrue($this->partnerUser->last_login_at->greaterThan($oldLogin));
        $this->assertTrue($this->partnerUser->last_meaningful_activity_at->greaterThan($oldLogin));
    }

    /**
     * 2. Meaningful activity tracking: submitting a lead or recording CRM contact updates activity.
     */
    public function test_02_meaningful_activity_tracking(): void
    {
        $oldDate = now()->subDays(15);
        $this->partnerUser->update(['last_meaningful_activity_at' => $oldDate]);
        $this->staffUser->update(['last_meaningful_activity_at' => $oldDate]);

        // Partner submits a lead
        $lead = $this->leadService->createLead($this->partner, [
            'customer_name' => 'Deepak Verma',
            'customer_phone' => '9876543210',
            'service_required' => 'Cloud Architecture',
            'project_description' => 'Requirements for migration',
        ], $this->partnerUser);

        $this->partnerUser->refresh();
        $this->assertTrue($this->partnerUser->last_meaningful_activity_at->isToday());
        $this->assertNotNull($this->partnerUser->last_lead_submission_at);

        // Staff logs telecalling contact
        $this->leadService->assignLead($lead, $this->staffUser, $this->adminUser);
        $this->leadService->recordActivity($lead, $this->staffUser, [
            'activity_type' => LeadActivityType::Call->value,
            'summary' => 'Initial discovery call with client',
            'customer_interest' => 'hot',
        ]);

        $this->staffUser->refresh();
        $this->assertTrue($this->staffUser->last_meaningful_activity_at->isToday());
        $this->assertNotNull($this->staffUser->last_customer_interaction_at);
    }

    /**
     * 3. Background requests, viewing pages, or refreshes do NOT reset meaningful activity.
     */
    public function test_03_background_requests_do_not_reset_activity(): void
    {
        $staleDate = now()->subDays(40);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $staleDate,
            'inactivity_status' => InactivityStatus::AtRisk,
        ]);

        // Partner loads dashboard via GET request
        $response = $this->actingAs($this->partnerUser)->get(route('partner.workspace'));
        $response->assertOk();

        // Partner loads leads list via GET request
        $response2 = $this->actingAs($this->partnerUser)->get(route('partner.leads.index'));
        $response2->assertOk();

        $this->partnerUser->refresh();
        // Date must NOT have been updated by browsing!
        $this->assertEquals($staleDate->toDateTimeString(), $this->partnerUser->last_meaningful_activity_at->toDateTimeString());
        $this->assertEquals(InactivityStatus::AtRisk, $this->partnerUser->inactivity_status);
    }

    /**
     * 4. 30-day warning threshold evaluation and in-app notification.
     */
    public function test_04_30_day_warning_threshold(): void
    {
        $thirtyFiveDaysAgo = now()->subDays(35);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $thirtyFiveDaysAgo,
            'last_login_at' => $thirtyFiveDaysAgo,
            'inactivity_status' => InactivityStatus::Active,
            'inactivity_warning_sent_at' => null,
        ]);

        $status = $this->inactivityService->evaluateInactivityStatus($this->partnerUser);
        $this->assertEquals(InactivityStatus::AtRisk, $status);

        $results = $this->inactivityService->processInactivityEvaluations();

        $this->partnerUser->refresh();
        $this->assertEquals(InactivityStatus::AtRisk, $this->partnerUser->inactivity_status);
        $this->assertNotNull($this->partnerUser->inactivity_warning_sent_at);
        $this->assertTrue($results['warnings_sent'] >= 1);

        $notification = Notification::where('user_id', $this->partnerUser->id)
            ->where('title', 'like', '%Warning%')
            ->first();
        $this->assertNotNull($notification);
    }

    /**
     * 5. 60-day inactivity alert threshold evaluation and admin alert.
     */
    public function test_05_60_day_inactivity_alert_threshold(): void
    {
        $sixtyFiveDaysAgo = now()->subDays(65);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $sixtyFiveDaysAgo,
            'last_login_at' => $sixtyFiveDaysAgo,
            'inactivity_status' => InactivityStatus::AtRisk,
            'inactivity_warning_sent_at' => now()->subDays(30),
            'inactivity_alert_sent_at' => null,
        ]);

        $status = $this->inactivityService->evaluateInactivityStatus($this->partnerUser);
        $this->assertEquals(InactivityStatus::Inactive, $status);

        $this->inactivityService->processInactivityEvaluations();

        $this->partnerUser->refresh();
        $this->assertEquals(InactivityStatus::Inactive, $this->partnerUser->inactivity_status);
        $this->assertNotNull($this->partnerUser->inactivity_alert_sent_at);

        // Verify Admin received notification
        $adminNotif = Notification::where('user_id', $this->adminUser->id)
            ->where('title', 'like', '%Inactivity Alert%')
            ->first();
        $this->assertNotNull($adminNotif);
    }

    /**
     * 6. 90-day critical inactivity threshold evaluation and admin alert.
     */
    public function test_06_90_day_critical_threshold(): void
    {
        $ninetyFiveDaysAgo = now()->subDays(95);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $ninetyFiveDaysAgo,
            'last_login_at' => $ninetyFiveDaysAgo,
            'inactivity_status' => InactivityStatus::Inactive,
            'inactivity_warning_sent_at' => now()->subDays(60),
            'inactivity_alert_sent_at' => now()->subDays(30),
            'inactivity_critical_sent_at' => null,
        ]);

        $status = $this->inactivityService->evaluateInactivityStatus($this->partnerUser);
        $this->assertEquals(InactivityStatus::Critical, $status);

        $this->inactivityService->processInactivityEvaluations();

        $this->partnerUser->refresh();
        $this->assertEquals(InactivityStatus::Critical, $this->partnerUser->inactivity_status);
        $this->assertNotNull($this->partnerUser->inactivity_critical_sent_at);

        // Crucial safety rule: Never automatically suspend an account!
        $this->assertNotEquals(UserStatus::Suspended, $this->partnerUser->status);
        $this->assertEquals(UserStatus::Active, $this->partnerUser->status);
    }

    /**
     * 7. Duplicate notification prevention: repeated scans do not send duplicate warnings/alerts.
     */
    public function test_07_duplicate_notification_prevention(): void
    {
        $fortyDaysAgo = now()->subDays(40);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $fortyDaysAgo,
            'last_login_at' => $fortyDaysAgo,
            'inactivity_status' => InactivityStatus::Active,
            'inactivity_warning_sent_at' => null,
        ]);

        // First scan
        $this->inactivityService->processInactivityEvaluations();
        $initialUserNotifs = Notification::where('user_id', $this->partnerUser->id)->count();
        $this->assertEquals(1, $initialUserNotifs);

        // Second scan immediately after
        $this->inactivityService->processInactivityEvaluations();
        $secondUserNotifs = Notification::where('user_id', $this->partnerUser->id)->count();

        // Must remain exactly identical - no duplicate notification!
        $this->assertEquals(1, $secondUserNotifs);
    }

    /**
     * 8. Partner and Staff policies are separated and configurable.
     */
    public function test_08_partner_and_staff_separation(): void
    {
        // Set custom distinct policies
        SystemSetting::set('inactivity_partner_warning_days', '20');
        SystemSetting::set('inactivity_staff_warning_days', '45');

        $userDate = now()->subDays(25);
        $this->partnerUser->update(['last_meaningful_activity_at' => $userDate, 'last_login_at' => $userDate]);
        $this->staffUser->update(['last_meaningful_activity_at' => $userDate, 'last_login_at' => $userDate]);

        // At 25 days, Partner (threshold 20) is AtRisk, but Staff (threshold 45) is still Active!
        $partnerStatus = $this->inactivityService->evaluateInactivityStatus($this->partnerUser);
        $staffStatus = $this->inactivityService->evaluateInactivityStatus($this->staffUser);

        $this->assertEquals(InactivityStatus::AtRisk, $partnerStatus);
        $this->assertEquals(InactivityStatus::Active, $staffStatus);
    }

    /**
     * 9. Admin suspension requires mandatory reason and sets status to Suspended.
     */
    public function test_09_admin_suspension_with_reason(): void
    {
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.inactivity.suspend', $this->partnerUser->id),
            ['reason' => 'Extended 90+ day critical inactivity with unresponsiveness.']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->partnerUser->refresh();
        $this->assertEquals(UserStatus::Suspended, $this->partnerUser->status);
        $this->assertEquals(InactivityStatus::Suspended, $this->partnerUser->inactivity_status);
        $this->assertEquals(KycStatus::Approved, $this->partner->fresh()->kyc_status);

        // Verify history log entry
        $history = UserInactivityHistory::where('user_id', $this->partnerUser->id)
            ->where('action', 'suspended')
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals('Extended 90+ day critical inactivity with unresponsiveness.', $history->reason);
        $this->assertEquals($this->adminUser->id, $history->performed_by);
    }

    /**
     * 10. Admin reactivation requires mandatory reason and restores active status.
     */
    public function test_10_admin_reactivation_with_reason(): void
    {
        // First suspend
        $this->partnerUser->update([
            'status' => UserStatus::Suspended,
            'inactivity_status' => InactivityStatus::Suspended,
        ]);

        // Reactivate
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.inactivity.reactivate', $this->partnerUser->id),
            ['reason' => 'Partner submitted updated business registry documentation and resumed contact.']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->partnerUser->refresh();
        $this->assertEquals(UserStatus::Active, $this->partnerUser->status);
        $this->assertEquals(InactivityStatus::Active, $this->partnerUser->inactivity_status);
        $this->assertEquals(KycStatus::Approved, $this->partner->fresh()->kyc_status);

        $history = UserInactivityHistory::where('user_id', $this->partnerUser->id)
            ->where('action', 'reactivated')
            ->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->adminUser->id, $history->performed_by);
    }

    /**
     * 11. Mandatory reason check for suspension and reactivation.
     */
    public function test_11_mandatory_reason_validation(): void
    {
        // Attempt suspend without reason
        $response = $this->actingAs($this->adminUser)->post(
            route('admin.inactivity.suspend', $this->partnerUser->id),
            ['reason' => '']
        );
        $response->assertSessionHasErrors('reason');
        $this->assertEquals(UserStatus::Active, $this->partnerUser->fresh()->status);

        // Attempt reactivate without reason
        $this->partnerUser->update(['status' => UserStatus::Suspended]);
        $response2 = $this->actingAs($this->adminUser)->post(
            route('admin.inactivity.reactivate', $this->partnerUser->id),
            ['reason' => '   ']
        );
        $response2->assertSessionHasErrors('reason');
        $this->assertEquals(UserStatus::Suspended, $this->partnerUser->fresh()->status);
    }

    /**
     * 12. Suspended user access restriction according to existing security rules.
     */
    public function test_12_suspended_user_access_restriction(): void
    {
        $this->partnerUser->update([
            'status' => UserStatus::Suspended,
            'inactivity_status' => InactivityStatus::Suspended,
        ]);

        // Attempt login
        $response = $this->post(route('login'), [
            'email' => 'partner@alphadigital.com',
            'password' => 'secret123',
        ]);

        // Redirects to suspended account notice
        $response->assertRedirect(route('partner.suspended'));
    }

    /**
     * 13. Existing authentication regression: active users login seamlessly.
     */
    public function test_13_active_users_login_seamlessly(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@techgoogly.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);
    }

    /**
     * 14. Artisan command inactivity:check-thresholds runs successfully.
     */
    public function test_14_artisan_command_execution(): void
    {
        $exitCode = Artisan::call('inactivity:check-thresholds');
        $this->assertEquals(0, $exitCode);
    }

    /**
     * 15. Admin Inactivity Dashboard renders Partner and Staff tabs and settings.
     */
    public function test_15_admin_inactivity_dashboard_renders(): void
    {
        $resPartners = $this->actingAs($this->adminUser)->get(route('admin.inactivity.index', ['tab' => 'partners']));
        $resPartners->assertOk();
        $resPartners->assertSee('Inactivity Management System');
        $resPartners->assertSee('Referral Partners');
        $resPartners->assertSee($this->partnerUser->name);

        $resStaff = $this->actingAs($this->adminUser)->get(route('admin.inactivity.index', ['tab' => 'staff']));
        $resStaff->assertOk();
        $resStaff->assertSee('Sales Staff Members');
        $resStaff->assertSee($this->staffUser->name);

        $resSettings = $this->actingAs($this->adminUser)->get(route('admin.inactivity.settings'));
        $resSettings->assertOk();
        $resSettings->assertSee('Inactivity Policy Configuration');
    }

    /**
     * 16. Partner and Staff dashboards display warning banners when At Risk.
     */
    public function test_16_user_dashboards_display_inactivity_warning(): void
    {
        $fortyDaysAgo = now()->subDays(40);
        $this->partnerUser->update([
            'last_meaningful_activity_at' => $fortyDaysAgo,
            'last_login_at' => $fortyDaysAgo,
            'inactivity_status' => InactivityStatus::AtRisk,
        ]);

        $resPartner = $this->actingAs($this->partnerUser)->get(route('partner.workspace'));
        $resPartner->assertOk();
        $resPartner->assertSee('Account Activity Notice');

        $this->staffUser->update([
            'last_meaningful_activity_at' => $fortyDaysAgo,
            'last_login_at' => $fortyDaysAgo,
            'inactivity_status' => InactivityStatus::AtRisk,
        ]);

        $resStaff = $this->actingAs($this->staffUser)->get(route('team.workspace'));
        $resStaff->assertOk();
        $resStaff->assertSee('Telecalling Activity Notice');
    }
}
