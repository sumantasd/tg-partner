<?php

namespace Tests\Feature;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PartnerBankSettingsAndFooterCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teamUser;
    protected User $partnerUser;
    protected Partner $partner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email'  => 'admin_test@techgoogly.com',
            'role'   => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->teamUser = User::factory()->create([
            'email'  => 'team_test@techgoogly.com',
            'role'   => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser = User::factory()->create([
            'email'  => 'partner_test@techgoogly.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id'                  => $this->partnerUser->id,
            'partner_code'             => 'TGP-TEST01',
            'company_name'             => 'Apex Referral Solutions',
            'phone'                    => '9876543210',
            'bank_name'                => 'HDFC Bank',
            'bank_account_name'        => 'Apex Referral Solutions',
            'bank_account_number'      => '50100456789012',
            'bank_routing_code'        => 'HDFC0001234',
            'payout_method'            => 'bank_transfer',
            'payout_details'           => 'apex@okhdfcbank',
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);
    }

    /**
     * 1. Bank details form renders with professional security notice and no internal technical wording.
     */
    public function test_bank_details_form_renders_with_updated_security_notice_and_no_technical_wording(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('partner.payout-settings.edit'));

        $response->assertOk();

        // Must contain customer-facing wording
        $response->assertSee('Your bank account details are securely encrypted and protected.');
        $response->assertSee('Account numbers are masked across the platform, showing only the last 4 digits.');
        $response->assertSee('Team members and unauthorized parties cannot access your confidential financial information.');
        $response->assertSee('Zero-Exposure Security Guarantee');

        // Must NOT contain internal framework or technical wording
        $response->assertDontSee('Laravel encryption');
        $response->assertDontSee('using Laravel');
        $response->assertDontSee('AES-256');
    }

    /**
     * 2. Account number and confirmation inputs use visible text input type and high-contrast styling.
     */
    public function test_account_number_inputs_have_visible_text_input_type_and_proper_styling(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('partner.payout-settings.edit'));

        $response->assertOk();
        $content = $response->getContent();

        // bank_account_number must be type="text", NOT type="password"
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="text"[^>]*name="bank_account_number"/i',
            $content,
            'Bank account number input must use type="text" to display entered digits clearly.'
        );

        // bank_account_number_confirmation must be type="text", NOT type="password"
        $this->assertMatchesRegularExpression(
            '/<input[^>]*type="text"[^>]*name="bank_account_number_confirmation"/i',
            $content,
            'Confirm account number input must use type="text" to display entered digits clearly.'
        );

        // Ensure no password inputs for account numbers
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*type="password"[^>]*name="bank_account_number"/i',
            $content
        );
        $this->assertDoesNotMatchRegularExpression(
            '/<input[^>]*type="password"[^>]*name="bank_account_number_confirmation"/i',
            $content
        );

        // Ensure inputmode="numeric" is present for mobile keypad
        $this->assertMatchesRegularExpression(
            '/<input[^>]*inputmode="numeric"[^>]*name="bank_account_number"/i',
            $content
        );
        $this->assertMatchesRegularExpression(
            '/<input[^>]*inputmode="numeric"[^>]*name="bank_account_number_confirmation"/i',
            $content
        );

        // Ensure high-contrast styling is present
        $this->assertStringContainsString('bg-white', $content);
        $this->assertStringContainsString('text-slate-900', $content);
        $this->assertStringContainsString('placeholder:text-slate-400', $content);
    }

    /**
     * 3. Bank Name, Holder Name, IFSC, and UPI inputs render with readable contrast styling.
     */
    public function test_bank_name_ifsc_and_upi_inputs_render_with_readable_styling(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('partner.payout-settings.edit'));

        $response->assertOk();
        $content = $response->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]*name="bank_account_name"/i', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*name="bank_name"/i', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*name="bank_routing_code"/i', $content);
        $this->assertMatchesRegularExpression('/<input[^>]*name="upi_id"/i', $content);
    }

    /**
     * 4. Account number encryption on save remains functional and secure.
     */
    public function test_account_number_encryption_on_save_remains_functional(): void
    {
        $newAccountNumber = '98765432109876';

        $response = $this->actingAs($this->partnerUser)->put(route('partner.payout-settings.update'), [
            'payout_method'                    => 'bank_transfer',
            'bank_name'                        => 'ICICI Bank',
            'bank_account_name'                => 'Apex Referral Solutions',
            'bank_account_number'              => $newAccountNumber,
            'bank_account_number_confirmation' => $newAccountNumber,
            'bank_routing_code'                => 'ICIC0001234',
        ]);

        $response->assertRedirect(route('partner.payout-settings.edit'));
        $response->assertSessionHas('success');

        // Verify in database: column must NOT equal raw plaintext (it must be encrypted)
        $rawPartner = DB::table('partners')->where('id', $this->partner->id)->first();
        $this->assertNotEquals($newAccountNumber, $rawPartner->bank_account_number);
        $this->assertEquals('9876', $rawPartner->bank_account_last4);

        // Verify model decryption
        $this->partner->refresh();
        $this->assertEquals($newAccountNumber, $this->partner->bank_account_number);
        $this->assertEquals('9876', $this->partner->bank_account_last4);
    }

    /**
     * 5. Saved account number remains masked in dashboard summary and does not expose full number.
     */
    public function test_saved_account_number_remains_masked_in_dashboard_summary(): void
    {
        $response = $this->actingAs($this->partnerUser)->get(route('partner.payout-settings.edit'));

        $response->assertOk();

        // Must display masked account number
        $response->assertSee('•••• •••• •••• 9012');

        // Must NEVER expose the complete unmasked account number in the rendered HTML
        $response->assertDontSee('50100456789012');
    }

    /**
     * 6. Account number confirmation validation functions properly.
     */
    public function test_account_number_confirmation_validation(): void
    {
        // Mismatched confirmation should fail
        $response = $this->actingAs($this->partnerUser)->put(route('partner.payout-settings.update'), [
            'payout_method'                    => 'bank_transfer',
            'bank_name'                        => 'HDFC Bank',
            'bank_account_name'                => 'Apex Referral Solutions',
            'bank_account_number'              => '112233445566',
            'bank_account_number_confirmation' => '998877665544',
            'bank_routing_code'                => 'HDFC0001234',
        ]);

        $response->assertSessionHasErrors(['bank_account_number']);
        $this->assertEquals('50100456789012', $this->partner->fresh()->bank_account_number);
    }

    /**
     * 7. Team members and unauthenticated guests cannot access Partner payout settings.
     */
    public function test_team_member_and_guest_cannot_access_partner_payout_settings(): void
    {
        // Team member blocked
        $this->actingAs($this->teamUser)
            ->get(route('partner.payout-settings.edit'))
            ->assertForbidden();

        // Guest redirected to login
        auth()->logout();
        $this->get(route('partner.payout-settings.edit'))
            ->assertRedirect(route('login'));
    }

    /**
     * 8. Global footers do not render the unwanted Target: partner.techgoogly.com label.
     */
    public function test_global_footers_do_not_render_unwanted_target_label(): void
    {
        // 1. Authenticated Partner App Layout
        $partnerResponse = $this->actingAs($this->partnerUser)->get(route('partner.workspace'));
        $partnerResponse->assertOk();
        $partnerResponse->assertDontSee('Target: partner.techgoogly.com');
        $partnerResponse->assertDontSee('Target:');
        $partnerResponse->assertSee('Tech Googly. All rights reserved.');

        // 2. Authenticated Admin App Layout
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.dashboard'));
        $adminResponse->assertOk();
        $adminResponse->assertDontSee('Target: partner.techgoogly.com');
        $adminResponse->assertDontSee('Target:');
        $adminResponse->assertSee('Tech Googly. All rights reserved.');

        // 3. Guest Auth Layout
        auth()->logout();
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertOk();
        $loginResponse->assertDontSee('Target: partner.techgoogly.com');
        $loginResponse->assertDontSee('Target:');
        $loginResponse->assertSee('techgoogly.com');
        $loginResponse->assertSee('Tech Googly. All rights reserved.');
    }
}
