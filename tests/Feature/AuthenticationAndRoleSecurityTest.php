<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AuthenticationAndRoleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/admin/partners')->assertRedirect(route('login'));
        $this->get('/team/workspace')->assertRedirect(route('login'));
        $this->get('/partner/workspace')->assertRedirect(route('login'));
        $this->get('/')->assertOk();
    }

    public function test_admin_cannot_be_created_through_public_registration(): void
    {
        // Attacker attempts to forge role=admin and status=active in registration payload
        $response = $this->post('/register', [
            'name'                  => 'Malicious Actor',
            'email'                 => 'hacker@partner.com',
            'password'              => 'StrongPassword123!',
            'password_confirmation' => 'StrongPassword123!',
            'company_name'          => 'Evil Corp',
            'phone'                 => '9999999999',
            'role'                  => 'admin',   // FORGED
            'status'                => 'active',  // FORGED
        ]);

        $user = User::where('email', 'hacker@partner.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(UserRole::Partner, $user->role, 'Public registration must strictly enforce Partner role.');
        $this->assertSame(UserStatus::Pending, $user->status, 'Public registration must strictly enforce Pending status.');
        $this->assertNotNull($user->partner, 'Partner profile must be created automatically.');
        $response->assertRedirect(route('partner.pending'));
    }

    public function test_partner_cannot_access_admin_routes(): void
    {
        $partnerUser = User::create([
            'name'     => 'Legit Partner',
            'email'    => 'active_partner@tg.com',
            'password' => 'secret123',
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->actingAs($partnerUser)
            ->get('/admin/partners')
            ->assertForbidden();
    }

    public function test_team_member_cannot_access_admin_or_partner_routes(): void
    {
        $teamUser = User::create([
            'name'     => 'Sales Rep',
            'email'    => 'sales@tg.com',
            'password' => 'secret123',
            'role'     => UserRole::TeamMember,
            'status'   => UserStatus::Active,
        ]);

        // Cannot access Admin routes
        $this->actingAs($teamUser)
            ->get('/admin/partners')
            ->assertForbidden();

        // Cannot access Partner routes
        $this->actingAs($teamUser)
            ->get('/partner/workspace')
            ->assertForbidden();

        // CAN access Team workspace
        $this->actingAs($teamUser)
            ->get('/team/workspace')
            ->assertOk();
    }

    public function test_pending_partner_cannot_access_protected_partner_workspace(): void
    {
        $pendingUser = User::create([
            'name'     => 'Waiting Partner',
            'email'    => 'waiting@tg.com',
            'password' => 'secret123',
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Pending,
        ]);

        $this->actingAs($pendingUser)
            ->get('/partner/workspace')
            ->assertRedirect(route('partner.pending'));

        // Can access pending notice
        $this->actingAs($pendingUser)
            ->get('/partner/pending-approval')
            ->assertOk();
    }

    public function test_suspended_partner_cannot_access_protected_partner_workspace(): void
    {
        $suspendedUser = User::create([
            'name'     => 'Blocked Partner',
            'email'    => 'blocked@tg.com',
            'password' => 'secret123',
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Suspended,
        ]);

        $this->actingAs($suspendedUser)
            ->get('/partner/workspace')
            ->assertRedirect(route('partner.suspended'));

        // Can access suspended notice
        $this->actingAs($suspendedUser)
            ->get('/partner/suspended')
            ->assertOk();
    }

    public function test_session_is_regenerated_after_successful_login(): void
    {
        $user = User::create([
            'name'     => 'Admin Auth',
            'email'    => 'admin_login@tg.com',
            'password' => bcrypt('AdminPassword123!'),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Active,
        ]);

        $this->get('/login');
        $initialSessionId = session()->getId();

        $response = $this->post('/login', [
            'email'    => 'admin_login@tg.com',
            'password' => 'AdminPassword123!',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($initialSessionId, session()->getId(), 'Session ID must be regenerated on login.');
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_invalidates_the_session(): void
    {
        $user = User::create([
            'name'     => 'Active User',
            'email'    => 'logout_user@tg.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Active,
        ]);

        $this->actingAs($user);
        $this->assertTrue(Auth::check());

        $response = $this->post('/logout');

        $this->assertFalse(Auth::check(), 'User should be unauthenticated after logout.');
        $response->assertRedirect(route('login'));
    }

    public function test_admin_partner_approval_actions(): void
    {
        $admin = User::create([
            'name'     => 'Super Admin',
            'email'    => 'super@tg.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Active,
        ]);

        $partnerUser = User::create([
            'name'     => 'New Partner',
            'email'    => 'apply@tg.com',
            'password' => bcrypt('Password123!'),
            'role'     => UserRole::Partner,
            'status'   => UserStatus::Pending,
        ]);

        $partner = Partner::create([
            'user_id'      => $partnerUser->id,
            'partner_code' => 'TGP-2026-APP',
            'company_name' => 'Applicant Co',
            'phone'        => '9876543210',
        ]);

        // 1. Admin approves partner
        $this->actingAs($admin)
            ->post("/admin/partners/{$partner->id}/approve")
            ->assertRedirect();

        $partnerUser->refresh();
        $partner->refresh();

        $this->assertSame(UserStatus::Active, $partnerUser->status);
        $this->assertNotNull($partner->approved_at);
        $this->assertSame($admin->id, $partner->approved_by);

        // 2. Admin suspends partner
        $this->actingAs($admin)
            ->post("/admin/partners/{$partner->id}/suspend", ['reason' => 'Compliance review'])
            ->assertRedirect();

        $partnerUser->refresh();
        $this->assertSame(UserStatus::Suspended, $partnerUser->status);

        // 3. Admin reactivates partner
        $this->actingAs($admin)
            ->post("/admin/partners/{$partner->id}/activate")
            ->assertRedirect();

        $partnerUser->refresh();
        $this->assertSame(UserStatus::Active, $partnerUser->status);
    }
}
