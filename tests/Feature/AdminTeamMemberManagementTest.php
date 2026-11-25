<?php

namespace Tests\Feature;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTeamMemberManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teamMember1;
    protected User $teamMember2;
    protected User $partnerUser;
    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Admin
        $this->admin = User::factory()->create([
            'name'   => 'Sumanta Das (Admin)',
            'email'  => 'admin@techgoogly.com',
            'role'   => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // 2. Team Member 1
        $this->teamMember1 = User::create([
            'name'              => 'Rohan Sen',
            'email'             => 'rohan.sen@techgoogly.com',
            'phone'             => '9830011223',
            'employee_id'       => 'TG-TM-101',
            'designation'       => 'Senior Sales Consultant',
            'department'        => 'Sales',
            'joining_date'      => '2025-03-01',
            'password'          => Hash::make('InitialPassword123!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        // 3. Team Member 2
        $this->teamMember2 = User::create([
            'name'              => 'Priya Roy',
            'email'             => 'priya.roy@techgoogly.com',
            'phone'             => '9830099887',
            'employee_id'       => 'TG-TM-102',
            'designation'       => 'Operations Lead',
            'department'        => 'Operations',
            'joining_date'      => '2025-06-15',
            'password'          => Hash::make('InitialPassword456!'),
            'role'              => UserRole::TeamMember,
            'status'            => UserStatus::Active,
            'email_verified_at' => now(),
        ]);

        // 4. Partner
        $this->partnerUser = User::factory()->create([
            'name'   => 'Partner Contact',
            'email'  => 'partner@agency.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner = Partner::create([
            'user_id'                 => $this->partnerUser->id,
            'partner_code'            => 'TGP-TM-01',
            'company_name'            => 'Apex Agency',
            'phone'                   => '9876543210',
            'default_commission_rate' => 10.00,
        ]);
    }

    /**
     * 1. Admin can open Team Member details.
     */
    public function test_admin_can_open_team_member_details(): void
    {
        // Seed some assigned leads to test stats and pipeline rendering
        Lead::create([
            'partner_id'          => $this->partner->id,
            'assigned_to'         => $this->teamMember1->id,
            'lead_number'         => 'LD-TEST-001',
            'customer_name'       => 'Acme Client Corp',
            'customer_phone'      => '9830000001',
            'service_required'    => 'Cloud Migration & Infrastructure',
            'project_description' => 'Test project description 1',
            'status'              => LeadStatus::InDiscussion,
        ]);

        Lead::create([
            'partner_id'          => $this->partner->id,
            'assigned_to'         => $this->teamMember1->id,
            'lead_number'         => 'LD-TEST-002',
            'customer_name'       => 'Won Enterprise',
            'customer_phone'      => '9830000002',
            'service_required'    => 'Custom ERP Software',
            'project_description' => 'Test project description 2',
            'status'              => LeadStatus::Won,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.team-members.show', $this->teamMember1));

        $response->assertStatus(200);
        $response->assertSee('Rohan Sen');
        $response->assertSee('rohan.sen@techgoogly.com');
        $response->assertSee('9830011223');
        $response->assertSee('TG-TM-101');
        $response->assertSee('Senior Sales Consultant');
        $response->assertSee('Back to Team Members');
        $response->assertSee('Edit Team Member');
        $response->assertSee('Suspend Account');
        $response->assertSee('Acme Client Corp');
        $response->assertSee('Won Enterprise');
    }

    /**
     * 2. Admin can open Edit page.
     */
    public function test_admin_can_open_edit_page(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.team-members.edit', $this->teamMember1));

        $response->assertStatus(200);
        $response->assertSee('Edit Team Member Profile');
        $response->assertSee('Rohan Sen');
        $response->assertSee('rohan.sen@techgoogly.com');
        $response->assertSee('9830011223');
        $response->assertSee('TG-TM-101');
        $response->assertSee('Save Changes');
    }

    /**
     * 3. Admin can update Team Member information.
     */
    public function test_admin_can_update_team_member_information(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.team-members.update', $this->teamMember1), [
            'name'         => 'Rohan Sen (Lead Consultant)',
            'email'        => 'rohan.lead@techgoogly.com',
            'phone'        => '9830099999',
            'employee_id'  => 'TG-TM-888',
            'designation'  => 'Principal Sales Consultant',
            'department'   => 'Operations',
            'joining_date' => '2024-01-10',
            'status'       => 'suspended',
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember1));
        $response->assertSessionHas('success');

        $this->teamMember1->refresh();
        $this->assertEquals('Rohan Sen (Lead Consultant)', $this->teamMember1->name);
        $this->assertEquals('rohan.lead@techgoogly.com', $this->teamMember1->email);
        $this->assertEquals('9830099999', $this->teamMember1->phone);
        $this->assertEquals('TG-TM-888', $this->teamMember1->employee_id);
        $this->assertEquals('Principal Sales Consultant', $this->teamMember1->designation);
        $this->assertEquals('Operations', $this->teamMember1->department);
        $this->assertEquals(UserStatus::Suspended, $this->teamMember1->status);
    }

    /**
     * 4. Admin can update password securely.
     */
    public function test_admin_can_update_password_securely(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.team-members.update', $this->teamMember1), [
            'name'                  => $this->teamMember1->name,
            'email'                 => $this->teamMember1->email,
            'status'                => 'active',
            'password'              => 'BrandNewSecretPass2026!',
            'password_confirmation' => 'BrandNewSecretPass2026!',
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember1));
        $response->assertSessionHas('success');

        $this->teamMember1->refresh();
        $this->assertTrue(Hash::check('BrandNewSecretPass2026!', $this->teamMember1->password));
    }

    /**
     * 5. Blank password preserves existing password.
     */
    public function test_blank_password_preserves_existing_password(): void
    {
        $originalHash = $this->teamMember1->password;

        $response = $this->actingAs($this->admin)->put(route('admin.team-members.update', $this->teamMember1), [
            'name'                  => 'Rohan Sen Updated',
            'email'                 => $this->teamMember1->email,
            'status'                => 'active',
            'password'              => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember1));
        $this->teamMember1->refresh();
        $this->assertEquals($originalHash, $this->teamMember1->password);
        $this->assertTrue(Hash::check('InitialPassword123!', $this->teamMember1->password));
    }

    /**
     * 6. Duplicate email is rejected.
     */
    public function test_duplicate_email_is_rejected(): void
    {
        $response = $this->actingAs($this->admin)->from(route('admin.team-members.edit', $this->teamMember1))->put(
            route('admin.team-members.update', $this->teamMember1),
            [
                'name'   => 'Rohan Sen',
                'email'  => 'priya.roy@techgoogly.com', // Duplicate of teamMember2
                'status' => 'active',
            ]
        );

        $response->assertRedirect(route('admin.team-members.edit', $this->teamMember1));
        $response->assertSessionHasErrors('email');

        $this->teamMember1->refresh();
        $this->assertEquals('rohan.sen@techgoogly.com', $this->teamMember1->email);
    }

    /**
     * 7. Existing email is accepted for the same user.
     */
    public function test_existing_email_is_accepted_for_the_same_user(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.team-members.update', $this->teamMember1), [
            'name'   => 'Rohan Sen Renamed',
            'email'  => 'rohan.sen@techgoogly.com', // Same email
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember1));
        $response->assertSessionHas('success');

        $this->teamMember1->refresh();
        $this->assertEquals('Rohan Sen Renamed', $this->teamMember1->name);
    }

    /**
     * 8. Invalid user ID returns 404.
     */
    public function test_invalid_user_id_returns_404(): void
    {
        // Non-existent ID
        $this->actingAs($this->admin)->get('/admin/team-members/999999')->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/team-members/999999/edit')->assertStatus(404);

        // Partner ID accessed via team-members routes returns 404 (strict role isolation)
        $this->actingAs($this->admin)->get('/admin/team-members/' . $this->partnerUser->id)->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/team-members/' . $this->partnerUser->id . '/edit')->assertStatus(404);
    }

    /**
     * 9. Partner cannot access View or Edit.
     */
    public function test_partner_cannot_access_view_or_edit(): void
    {
        $this->actingAs($this->partnerUser)->get(route('admin.team-members.show', $this->teamMember1))->assertStatus(403);
        $this->actingAs($this->partnerUser)->get(route('admin.team-members.edit', $this->teamMember1))->assertStatus(403);
        $this->actingAs($this->partnerUser)->put(route('admin.team-members.update', $this->teamMember1), [
            'name' => 'Hacked Name',
        ])->assertStatus(403);
    }

    /**
     * 10. Team Member cannot access Admin View or Edit.
     */
    public function test_team_member_cannot_access_admin_view_or_edit(): void
    {
        $this->actingAs($this->teamMember1)->get(route('admin.team-members.show', $this->teamMember2))->assertStatus(403);
        $this->actingAs($this->teamMember1)->get(route('admin.team-members.edit', $this->teamMember2))->assertStatus(403);
        $this->actingAs($this->teamMember1)->put(route('admin.team-members.update', $this->teamMember2), [
            'name' => 'Hacked Name',
        ])->assertStatus(403);
    }

    /**
     * 11. Role cannot be manipulated through form submission.
     */
    public function test_role_cannot_be_manipulated_through_form_submission(): void
    {
        $response = $this->actingAs($this->admin)->put(route('admin.team-members.update', $this->teamMember1), [
            'name'   => 'Rohan Sen',
            'email'  => 'rohan.sen@techgoogly.com',
            'status' => 'active',
            'role'   => 'admin', // Forged privilege escalation
        ]);

        $response->assertRedirect(route('admin.team-members.show', $this->teamMember1));

        $this->teamMember1->refresh();
        $this->assertEquals(UserRole::TeamMember, $this->teamMember1->role, 'Role must strictly remain team_member');
    }
}
