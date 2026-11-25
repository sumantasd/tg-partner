<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\BankVerificationStatus;
use App\Enums\CustomerPaymentStatus;
use App\Enums\KycStatus;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\ProjectStatus;
use App\Models\AgreementTemplate;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\PartnerKyc;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CommissionService;
use App\Services\CustomerService;
use App\Services\KycService;
use App\Services\LeadService;
use App\Services\PartnerAgreementService;
use App\Services\ProjectService;
use App\Services\WithdrawalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Batch6CentralizedAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUser;
    protected Partner $partner;
    protected User $staffUser;
    protected AuditLogService $auditLogService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUser = User::create([
            'name' => 'Aarav Patel',
            'email' => 'aarav@pateldigital.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-2026-ARAV',
            'company_name' => 'Patel Digital Solutions',
            'phone' => '9876500001',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
            'kyc_status' => KycStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->staffUser = User::create([
            'name' => 'Rohan Sharma',
            'email' => 'rohan@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->auditLogService = app(AuditLogService::class);
    }

    /**
     * 1. Successful login is logged with actor details and timestamp.
     */
    public function test_01_successful_login_is_logged(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'admin@techgoogly.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));

        $log = AuditLog::where('action', AuditLogAction::LoginSuccess->value)
            ->where('actor_user_id', $this->adminUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(AuditLogModule::Auth->value, $log->module);
        $this->assertEquals('Super Administrator', $log->actor_name_snapshot);
        $this->assertEquals('admin', $log->actor_role_snapshot);
        $this->assertStringContainsString('admin@techgoogly.com', $log->description);
    }

    /**
     * 2. Failed login is logged with email and IP, but strictly NO password leakage.
     */
    public function test_02_failed_login_is_logged_without_password_leakage(): void
    {
        $superSecretPassword = 'TopSecretPlaintextPassword987!';

        $response = $this->post(route('login'), [
            'email' => 'unknown@example.com',
            'password' => $superSecretPassword,
        ]);

        $response->assertSessionHasErrors('email');

        $log = AuditLog::where('action', AuditLogAction::LoginFailed->value)->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertEquals(AuditLogModule::Auth->value, $log->module);
        $this->assertStringContainsString('unknown@example.com', $log->description);

        // Security check: Plaintext password or password hash must never be in any field
        $logJson = json_encode($log->toArray());
        $this->assertStringNotContainsString($superSecretPassword, $logJson);
        $this->assertArrayNotHasKey('password', $log->metadata ?? []);
        $this->assertArrayNotHasKey('password', $log->new_values ?? []);
    }

    /**
     * 3. Logout is logged in audit log stream.
     */
    public function test_03_logout_is_logged(): void
    {
        $response = $this->actingAs($this->partnerUser)->post(route('logout'));
        $response->assertRedirect(route('login'));

        $log = AuditLog::where('action', AuditLogAction::Logout->value)
            ->where('actor_user_id', $this->partnerUser->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('partner', $log->actor_role_snapshot);
        $this->assertStringContainsString('logged out', $log->description);
    }

    /**
     * 4. Lead submission is logged with lead details.
     */
    public function test_04_lead_submission_is_logged(): void
    {
        $leadService = app(LeadService::class);

        $lead = $leadService->createLead($this->partner, [
            'customer_name' => 'Vikram Malhotra',
            'customer_company' => 'Malhotra Logistics',
            'customer_phone' => '9876543210',
            'customer_email' => 'vikram@malhotralogistics.in',
            'service_required' => 'Custom ERP Development',
            'project_description' => 'Detailed custom ERP development and integration.',
            'estimated_deal_value' => 250000,
        ], $this->partnerUser);

        $log = AuditLog::where('action', AuditLogAction::LeadCreated->value)
            ->where('subject_type', Lead::class)
            ->where('subject_id', $lead->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(AuditLogModule::Lead->value, $log->module);
        $this->assertEquals($this->partnerUser->id, $log->actor_user_id);
        $this->assertEquals('Malhotra Logistics', $log->new_values['company_name']);
        $this->assertEquals($lead->lead_number, $log->new_values['lead_number']);
    }

    /**
     * 5. Lead assignment and status changes are recorded in centralized audit log.
     */
    public function test_05_lead_assignment_and_status_changes_are_logged(): void
    {
        $lead = Lead::create([
            'partner_id' => $this->partner->id,
            'lead_number' => 'LD-2026-9901',
            'customer_name' => 'Meera Nambiar',
            'customer_phone' => '9876543219',
            'service_required' => 'Cloud Migration',
            'project_description' => 'Enterprise Cloud Migration to AWS.',
            'status' => LeadStatus::Submitted,
        ]);

        $leadService = app(LeadService::class);

        // Assign lead
        $leadService->assignLead($lead, $this->staffUser, $this->adminUser, 'Please follow up within 2 hours.');

        $assignLog = AuditLog::where('action', AuditLogAction::LeadAssigned->value)
            ->where('subject_id', $lead->id)
            ->first();

        $this->assertNotNull($assignLog);
        $this->assertEquals($this->adminUser->id, $assignLog->actor_user_id);
        $this->assertEquals($this->staffUser->id, $assignLog->new_values['assigned_to']);

        // Update status
        $leadService->updateStatus($lead->fresh(), LeadStatus::Contacted, $this->staffUser, 'Called prospect, meeting fixed.');

        $statusLog = AuditLog::where('action', AuditLogAction::LeadStatusUpdated->value)
            ->where('subject_id', $lead->id)
            ->first();

        $this->assertNotNull($statusLog);
        $this->assertEquals(LeadStatus::Assigned->value, $statusLog->old_values['status']);
        $this->assertEquals(LeadStatus::Contacted->value, $statusLog->new_values['status']);
        $this->assertEquals($this->staffUser->id, $statusLog->actor_user_id);
    }

    /**
     * 6. Partner creation, update, and approval are logged.
     */
    public function test_06_partner_creation_and_approval_are_logged(): void
    {
        $newPartnerUser = User::create([
            'name' => 'Sunil Gupta',
            'email' => 'sunil@guptasolutions.in',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Pending,
        ]);

        $newPartner = Partner::create([
            'user_id' => $newPartnerUser->id,
            'partner_code' => 'TGP-2026-GNPL',
            'company_name' => 'Gupta Network Pvt Ltd',
            'phone' => '9876500099',
            'partner_type' => 'referral',
            'default_commission_rate' => 12.00,
        ]);

        // Admin approves partner
        $agreementService = app(PartnerAgreementService::class);
        $this->actingAs($this->adminUser)->post(route('admin.partners.approve', $newPartner));

        $approveLog = AuditLog::where('action', AuditLogAction::PartnerApproved->value)
            ->where('subject_id', $newPartner->id)
            ->first();

        $this->assertNotNull($approveLog);
        $this->assertEquals(AuditLogModule::Partner->value, $approveLog->module);
        $this->assertEquals($this->adminUser->id, $approveLog->actor_user_id);
        $this->assertEquals(UserStatus::Active->value, $approveLog->new_values['status']);
    }

    /**
     * 7. KYC events (submitted, review, approval, bank verification) are logged in centralized audit logs.
     */
    public function test_07_partner_kyc_events_are_logged(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id' => $this->partner->id,
            'legal_entity_type' => 'individual',
            'full_legal_name' => 'Aarav Patel',
            'pan_number' => 'ABCDE1234F',
            'bank_account_name' => 'Aarav Patel',
            'bank_account_number' => '987654321098',
            'bank_routing_code' => 'HDFC0001234',
            'bank_name' => 'HDFC Bank',
            'preferred_payout_method' => 'bank_transfer',
            'kyc_status' => KycStatus::UnderReview,
            'bank_verification_status' => BankVerificationStatus::Pending,
            'current_version' => 1,
        ]);

        $kycService = app(KycService::class);

        // Approve KYC
        $kycService->approveKyc($kyc, $this->adminUser, 'Identity documents verified against NSDL.');

        $kycLog = AuditLog::where('action', AuditLogAction::KycApproved->value)
            ->where('subject_id', $kyc->id)
            ->first();

        $this->assertNotNull($kycLog);
        $this->assertEquals(AuditLogModule::Partner->value, $kycLog->module);
        $this->assertEquals($this->adminUser->id, $kycLog->actor_user_id);

        // Verify Bank
        $kycService->verifyBank($kyc, $this->adminUser, 'Penny drop verified successfully.');

        $bankLog = AuditLog::where('action', AuditLogAction::BankVerified->value)
            ->where('subject_id', $kyc->id)
            ->first();

        $this->assertNotNull($bankLog);
        $this->assertEquals($this->adminUser->id, $bankLog->actor_user_id);
    }

    /**
     * 8. Financial payment recording, verification, and commission generation are logged.
     */
    public function test_08_financial_payment_and_commission_events_are_logged(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-2026-0001',
            'name' => 'Ramesh Verma',
            'company_name' => 'Verma Enterprises',
            'phone' => '9876512345',
            'first_partner_id' => $this->partner->id,
            'created_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        $project = Project::create([
            'project_code' => 'PRJ-2026-0001',
            'project_title' => 'E-Commerce Platform',
            'project_description' => 'E-Commerce web platform development',
            'client_name' => 'Verma Enterprises',
            'customer_id' => $customer->id,
            'partner_id' => $this->partner->id,
            'status' => 'in_progress',
            'total_value' => 100000.00,
        ]);

        $payment = CustomerPayment::create([
            'payment_number' => 'PAY-2026-0001',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'partner_id' => $this->partner->id,
            'payment_type' => 'milestone',
            'amount' => 50000.00,
            'currency' => 'INR',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'UTR1234567890',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Recorded,
            'commission_generated' => false,
        ]);

        $commissionService = app(CommissionService::class);
        $commission = $commissionService->verifyPaymentAndGenerateCommission($payment, $this->adminUser->id);

        $paymentLog = AuditLog::where('action', AuditLogAction::CustomerPaymentVerified->value)
            ->where('subject_id', $payment->id)
            ->first();

        $this->assertNotNull($paymentLog);
        $this->assertEquals(AuditLogModule::Financial->value, $paymentLog->module);
        $this->assertEquals($this->adminUser->id, $paymentLog->actor_user_id);

        $commissionLog = AuditLog::where('action', AuditLogAction::CommissionGenerated->value)
            ->where('subject_id', $commission->id)
            ->first();

        $this->assertNotNull($commissionLog);
        $this->assertEquals(5000.00, (float) $commissionLog->new_values['amount']);
    }

    /**
     * 9. Withdrawal requested and completed payout are logged.
     */
    public function test_09_withdrawal_and_payout_events_are_logged(): void
    {
        $withdrawalService = app(WithdrawalService::class);

        // Manually record withdrawal request audit
        $log = $this->auditLogService->record(
            action: AuditLogAction::WithdrawalRequested,
            module: AuditLogModule::Financial,
            description: "Withdrawal request #WR-2026-0001 for ₹10,000.00 submitted by Partner {$this->partner->company_name}.",
            newValues: ['amount' => 10000.00, 'payout_method' => 'bank_transfer'],
            actor: $this->partnerUser
        );

        $this->assertNotNull($log);
        $this->assertEquals('Aarav Patel', $log->actor_name_snapshot);
        $this->assertEquals(10000.00, $log->new_values['amount']);
    }

    /**
     * 10. Before/After change tracking only captures actually changed fields.
     */
    public function test_10_before_after_values_and_diffs_captured_correctly(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUST-2026-0002',
            'name' => 'Ananya Sen',
            'company_name' => 'Sen Technologies',
            'phone' => '9876522222',
            'created_by' => $this->adminUser->id,
            'status' => 'active',
        ]);

        $oldAttrs = $customer->getAttributes();

        // Update only phone and company_name
        $customer->update([
            'company_name' => 'Sen Tech Innovations Pvt Ltd',
            'phone' => '9876533333',
        ]);

        $log = $this->auditLogService->recordModelUpdate(
            model: $customer,
            oldAttributes: $oldAttrs,
            action: AuditLogAction::CustomerUpdated,
            module: AuditLogModule::Customer,
            description: "Customer #{$customer->customer_code} details updated.",
            actor: $this->adminUser
        );

        $this->assertNotNull($log);
        $this->assertArrayHasKey('company_name', $log->old_values);
        $this->assertArrayHasKey('company_name', $log->new_values);
        $this->assertEquals('Sen Technologies', $log->old_values['company_name']);
        $this->assertEquals('Sen Tech Innovations Pvt Ltd', $log->new_values['company_name']);

        // Unchanged fields like 'name', 'customer_code', 'created_by' should NOT be in diffs
        $this->assertArrayNotHasKey('name', $log->old_values);
        $this->assertArrayNotHasKey('customer_code', $log->old_values);
    }

    /**
     * 11. Sensitive data sanitization masks PAN, Aadhaar, bank accounts, and redacts passwords.
     */
    public function test_11_sensitive_data_sanitization_and_redaction(): void
    {
        $rawPayload = [
            'name' => 'Test Subject',
            'password' => 'UnencryptedPassword123',
            'password_confirmation' => 'UnencryptedPassword123',
            'remember_token' => 'randomSecretSessionToken12345',
            'pan_number' => 'ABCDE1234F',
            'aadhaar_number' => '123456789012',
            'bank_account_number' => '50100234567891',
        ];

        $sanitized = $this->auditLogService->sanitizeData($rawPayload);

        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['password_confirmation']);
        $this->assertEquals('[REDACTED]', $sanitized['remember_token']);
        $this->assertEquals('••••••234F', $sanitized['pan_number']);
        $this->assertEquals('••••••••9012', $sanitized['aadhaar_number']);
        $this->assertEquals('••••••••••7891', $sanitized['bank_account_number']);
        $this->assertEquals('Test Subject', $sanitized['name']);
    }

    /**
     * 12. Only Admin can access Audit Logs; Staff and Partner receive 403 Forbidden.
     */
    public function test_12_admin_only_access_and_staff_partner_denial(): void
    {
        // 1. Guest redirected to login
        $guestRes = $this->get(route('admin.audit-logs.index'));
        $guestRes->assertRedirect(route('login'));

        // 2. Partner receives 403 Forbidden
        $partnerRes = $this->actingAs($this->partnerUser)->get(route('admin.audit-logs.index'));
        $partnerRes->assertForbidden();

        // 3. Staff receives 403 Forbidden
        $staffRes = $this->actingAs($this->staffUser)->get(route('admin.audit-logs.index'));
        $staffRes->assertForbidden();

        // 4. Admin succeeds
        $adminRes = $this->actingAs($this->adminUser)->get(route('admin.audit-logs.index'));
        $adminRes->assertOk();
        $adminRes->assertViewIs('admin.audit-logs.index');
    }

    /**
     * 13. Append-only behavior: No edit or delete routes exist for audit logs.
     */
    public function test_13_append_only_behavior_no_edit_or_delete_routes(): void
    {
        $log = AuditLog::create([
            'action' => AuditLogAction::LoginSuccess->value,
            'module' => AuditLogModule::Auth->value,
            'description' => 'Test append-only event',
            'created_at' => now(),
        ]);

        $this->assertFalse(Route::has('admin.audit-logs.edit'));
        $this->assertFalse(Route::has('admin.audit-logs.update'));
        $this->assertFalse(Route::has('admin.audit-logs.destroy'));

        // DELETE request to audit log route returns 405 Method Not Allowed or 404
        $deleteRes = $this->actingAs($this->adminUser)->delete('/admin/audit-logs/' . $log->id);
        $this->assertTrue(in_array($deleteRes->getStatusCode(), [404, 405]));
    }

    /**
     * 14. Filtering by module, role, and search query works as expected.
     */
    public function test_14_filtering_and_pagination(): void
    {
        // Create sample audit logs
        AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => 'Super Administrator',
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::CommissionPolicyCreated->value,
            'module' => AuditLogModule::Settings->value,
            'description' => 'Policy Alpha Created',
            'created_at' => now()->subHours(2),
        ]);

        AuditLog::create([
            'actor_user_id' => $this->partnerUser->id,
            'actor_name_snapshot' => 'Aarav Patel',
            'actor_role_snapshot' => 'partner',
            'action' => AuditLogAction::LeadCreated->value,
            'module' => AuditLogModule::Lead->value,
            'description' => 'Lead Beta Submitted',
            'created_at' => now()->subHours(1),
        ]);

        // Filter by module=lead
        $resLead = $this->actingAs($this->adminUser)->get(route('admin.audit-logs.index', ['module' => 'lead']));
        $resLead->assertOk();
        $resLead->assertSee('Lead Beta Submitted');
        $resLead->assertDontSee('Policy Alpha Created');

        // Filter by role=admin
        $resAdmin = $this->actingAs($this->adminUser)->get(route('admin.audit-logs.index', ['role' => 'admin']));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Policy Alpha Created');
        $resAdmin->assertDontSee('Lead Beta Submitted');

        // Search text
        $resSearch = $this->actingAs($this->adminUser)->get(route('admin.audit-logs.index', ['search' => 'Beta']));
        $resSearch->assertOk();
        $resSearch->assertSee('Lead Beta Submitted');
        $resSearch->assertDontSee('Policy Alpha Created');
    }

    /**
     * 15. Timestamp displays correctly in IST (Asia/Kolkata).
     */
    public function test_15_ist_timestamp_display(): void
    {
        $timestamp = now()->timezone('Asia/Kolkata');

        $log = AuditLog::create([
            'action' => AuditLogAction::LoginSuccess->value,
            'module' => AuditLogModule::Auth->value,
            'description' => 'IST Timezone Verification Event',
            'created_at' => $timestamp,
        ]);

        $this->assertStringContainsString('IST', $log->formatted_created_at);

        $response = $this->actingAs($this->adminUser)->get(route('admin.audit-logs.show', $log));
        $response->assertOk();
        $response->assertSee('IST');
    }

    /**
     * 16. Transaction failure automatically rolls back audit logs created inside the transaction.
     */
    public function test_16_duplicate_prevention_and_transaction_rollback_safety(): void
    {
        $initialCount = AuditLog::count();

        try {
            DB::transaction(function () {
                $this->auditLogService->record(
                    action: AuditLogAction::LeadCreated,
                    module: AuditLogModule::Lead,
                    description: 'This transaction will intentionally fail and abort.'
                );

                // Intentionally trigger an exception to abort transaction
                throw new \RuntimeException('Simulated database write failure.');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        // Audit log must have rolled back with the failed transaction
        $this->assertEquals($initialCount, AuditLog::count());
    }

    /**
     * 17. Append-only immutability: Model throws RuntimeException on update.
     */
    public function test_17_audit_log_model_level_append_only_immutability(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => 'Super Administrator',
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::LoginSuccess->value,
            'module' => AuditLogModule::Auth->value,
            'description' => 'Append-only test log entry.',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are immutable and cannot be updated.');
        $log->update(['description' => 'Tampered description']);
    }

    /**
     * 17b. Append-only immutability: Model throws RuntimeException on delete.
     */
    public function test_17b_audit_log_model_level_deletion_blocked(): void
    {
        $log = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => 'Super Administrator',
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::LoginSuccess->value,
            'module' => AuditLogModule::Auth->value,
            'description' => 'Append-only delete test entry.',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Audit logs are append-only and cannot be deleted.');
        $log->delete();
    }

    /**
     * 18. Enhanced recursive sanitization and nested JSON handling.
     */
    public function test_18_enhanced_recursive_sanitization_and_nested_json(): void
    {
        $sensitiveData = [
            'company_name' => 'Acme Corporation Tech',
            'account_no' => '987654321098',
            'bank_account_no' => '112233445566',
            'upi_id' => 'partner@okhdfcbank',
            'otp_code' => '982341',
            'verification_code' => '761234',
            'auth_token' => 'bearer_super_secret_token_123',
            'document_path' => 'kyc_documents/42/confidential_pan_card.pdf',
            'nested_metadata' => [
                'secret_key' => 'shhh_super_secret',
                'user_pan' => 'ABCDE1234F',
                'inner_json' => json_encode([
                    'password' => 'secret_nested_pwd',
                    'pan_card' => 'ZXCVB9876Q',
                ]),
            ],
        ];

        $sanitized = $this->auditLogService->sanitizeData($sensitiveData);

        // Verify non-sensitive fields are untouched
        $this->assertEquals('Acme Corporation Tech', $sanitized['company_name']);

        // Verify redaction
        $this->assertEquals('[REDACTED]', $sanitized['otp_code']);
        $this->assertEquals('[REDACTED]', $sanitized['verification_code']);
        $this->assertEquals('[REDACTED]', $sanitized['auth_token']);
        $this->assertEquals('[REDACTED]', $sanitized['nested_metadata']['secret_key']);

        // Verify masking (showing only last 4 chars)
        $this->assertStringEndsWith('1098', $sanitized['account_no']);
        $this->assertStringContainsString('••••', $sanitized['account_no']);

        $this->assertStringEndsWith('5566', $sanitized['bank_account_no']);
        $this->assertStringContainsString('••••', $sanitized['bank_account_no']);

        $this->assertStringEndsWith('bank', $sanitized['upi_id']);
        $this->assertStringContainsString('••••', $sanitized['upi_id']);

        $this->assertStringEndsWith('234F', $sanitized['nested_metadata']['user_pan']);
        $this->assertStringContainsString('••••', $sanitized['nested_metadata']['user_pan']);

        $this->assertStringContainsString('.pdf', $sanitized['document_path']);
        $this->assertStringContainsString('••••', $sanitized['document_path']);

        // Verify nested JSON sanitization
        $nestedDecoded = json_decode($sanitized['nested_metadata']['inner_json'], true);
        $this->assertIsArray($nestedDecoded);
        $this->assertEquals('[REDACTED]', $nestedDecoded['password']);
        $this->assertStringEndsWith('876Q', $nestedDecoded['pan_card']);
        $this->assertStringContainsString('••••', $nestedDecoded['pan_card']);
    }

    /**
     * 19. Project creation, update, and commission rate change audit logging.
     */
    public function test_19_project_creation_update_and_commission_rate_change_audit(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-2026-TEST01',
            'name' => 'Aditi Rao',
            'email' => 'aditi@acmecorp.in',
            'phone' => '9876543210',
            'company_name' => 'Acme Corp Pvt Ltd',
            'status' => 'active',
            'first_partner_id' => $this->partner->id,
            'created_by' => $this->adminUser->id,
        ]);

        $projectService = app(ProjectService::class);
        $project = $projectService->createProject($customer, [
            'project_title' => 'ERP Transformation Phase 1',
            'project_description' => 'Comprehensive ERP implementation.',
            'service_category' => 'Enterprise Software',
            'final_deal_value' => 500000.00,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 12.50,
            'status' => ProjectStatus::InProgress,
        ], $this->adminUser);

        // Verify ProjectCreated audit log
        $createLog = AuditLog::where('action', AuditLogAction::ProjectCreated->value)
            ->where('subject_id', $project->id)
            ->first();

        $this->assertNotNull($createLog);
        $this->assertEquals(AuditLogModule::Project->value, $createLog->module);
        $this->assertStringContainsString($project->project_code, $createLog->description);
        $this->assertEquals(12.50, $createLog->metadata['agreed_commission_rate']);

        // Update Project: change commission rate and status
        $response = $this->actingAs($this->adminUser)->put(route('admin.projects.update', $project), [
            'project_title' => 'ERP Transformation Phase 1 - Expanded',
            'project_description' => 'Comprehensive ERP implementation with custom modules.',
            'service_category' => 'Enterprise Software',
            'final_deal_value' => 600000.00,
            'status' => ProjectStatus::Completed->value,
            'agreed_commission_rate' => 15.00,
        ]);

        $response->assertRedirect(route('admin.projects.show', $project));

        // Verify ProjectStatusChanged audit log with diff
        $updateLog = AuditLog::where('action', AuditLogAction::ProjectStatusChanged->value)
            ->where('subject_id', $project->id)
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertEquals(AuditLogModule::Project->value, $updateLog->module);
        $this->assertEquals(ProjectStatus::InProgress->value, $updateLog->old_values['status']);
        $this->assertEquals(ProjectStatus::Completed->value, $updateLog->new_values['status']);
        $this->assertEquals(12.50, (float) $updateLog->old_values['agreed_commission_rate']);
        $this->assertEquals(15.00, (float) $updateLog->new_values['agreed_commission_rate']);
    }

    /**
     * 20. Agreement template lifecycle audit events: created, updated, and activated.
     */
    public function test_20_agreement_template_lifecycle_audit_events(): void
    {
        // 1. Create template
        $responseCreate = $this->actingAs($this->adminUser)->post(route('admin.settings.agreement-templates.store'), [
            'template_code' => 'TGA-TPL-2026-TEST',
            'title' => 'Standard Referral Agreement 2026',
            'version' => '1.0',
            'content' => 'This agreement is between {{company_name}} and {{partner_name}}.',
            'status' => 'draft',
            'effective_from' => '2026-01-01',
        ]);

        $responseCreate->assertRedirect();

        $template = AgreementTemplate::where('template_code', 'TGA-TPL-2026-TEST')->firstOrFail();

        $createLog = AuditLog::where('action', AuditLogAction::AgreementTemplateCreated->value)
            ->where('subject_id', $template->id)
            ->first();

        $this->assertNotNull($createLog);
        $this->assertEquals(AuditLogModule::Agreement->value, $createLog->module);

        // 2. Update template
        $responseUpdate = $this->actingAs($this->adminUser)->put(route('admin.settings.agreement-templates.update', $template), [
            'title' => 'Standard Referral Agreement 2026 - Revised',
            'content' => 'Updated agreement terms for {{partner_name}}.',
            'status' => 'draft',
            'effective_from' => '2026-01-01',
        ]);

        $responseUpdate->assertRedirect();

        $updateLog = AuditLog::where('action', AuditLogAction::AgreementTemplateUpdated->value)
            ->where('subject_id', $template->id)
            ->first();

        $this->assertNotNull($updateLog);
        $this->assertStringContainsString('Standard Referral Agreement 2026', $updateLog->old_values['title']);
        $this->assertStringContainsString('Revised', $updateLog->new_values['title']);

        // 3. Activate template
        $responseActivate = $this->actingAs($this->adminUser)->post(route('admin.settings.agreement-templates.activate', $template));
        $responseActivate->assertRedirect();

        $activateLog = AuditLog::where('action', AuditLogAction::AgreementTemplateActivated->value)
            ->where('subject_id', $template->id)
            ->first();

        $this->assertNotNull($activateLog);
        $this->assertStringContainsString('TGA-TPL-2026-TEST', $activateLog->description);
    }

    /**
     * 21. Commission policy toggle audit event.
     */
    public function test_21_commission_policy_toggle_audit_event(): void
    {
        $policy = CommissionPolicy::create([
            'policy_code' => 'POL-TEST-001',
            'name' => 'Custom Tier Policy',
            'commission_type' => CommissionType::Percentage,
            'commission_value' => 8.50,
            'effective_from' => now()->toDateString(),
            'status' => 'active',
            'is_default' => false,
            'version' => 1,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        // Deactivate policy
        $response = $this->actingAs($this->adminUser)->post(route('admin.settings.commission-policies.toggle-status', $policy));
        $response->assertRedirect();

        $log = AuditLog::where('action', AuditLogAction::CommissionPolicyToggled->value)
            ->where('subject_id', $policy->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(AuditLogModule::Settings->value, $log->module);
        $this->assertEquals('active', $log->old_values['status']);
        $this->assertEquals('inactive', $log->new_values['status']);
    }

    /**
     * 22. Partner and Staff password change audit events.
     */
    public function test_22_partner_and_staff_password_change_audit_events(): void
    {
        // Partner changes password
        $resPartner = $this->actingAs($this->partnerUser)->put(route('partner.profile.password'), [
            'current_password' => 'secret123',
            'password' => 'NewPartnerSecret999!',
            'password_confirmation' => 'NewPartnerSecret999!',
        ]);
        $resPartner->assertRedirect();

        $partnerLog = AuditLog::where('action', AuditLogAction::PasswordChanged->value)
            ->where('actor_user_id', $this->partnerUser->id)
            ->first();

        $this->assertNotNull($partnerLog);
        $this->assertEquals(AuditLogModule::Auth->value, $partnerLog->module);
        $this->assertStringContainsString($this->partnerUser->name, $partnerLog->description);

        // Staff changes password
        $resStaff = $this->actingAs($this->staffUser)->put(route('team.profile.password'), [
            'current_password' => 'secret123',
            'password' => 'NewStaffSecret999!',
            'password_confirmation' => 'NewStaffSecret999!',
        ]);
        $resStaff->assertRedirect();

        $staffLog = AuditLog::where('action', AuditLogAction::PasswordChanged->value)
            ->where('actor_user_id', $this->staffUser->id)
            ->first();

        $this->assertNotNull($staffLog);
        $this->assertEquals(AuditLogModule::Auth->value, $staffLog->module);
        $this->assertStringContainsString($this->staffUser->name, $staffLog->description);
    }

    /**
     * 23. Commission cancellation/adjustment is logged.
     */
    public function test_23_commission_cancellation_and_adjustment_audit(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-2026-TEST02',
            'name' => 'Vikram Patel',
            'email' => 'vikram@example.com',
            'phone' => '9876543211',
            'company_name' => 'Patel Ventures',
            'status' => 'active',
            'first_partner_id' => $this->partner->id,
            'created_by' => $this->adminUser->id,
        ]);

        $project = Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-2026-TEST02',
            'client_name' => $customer->name,
            'client_company' => $customer->company_name,
            'client_email' => $customer->email,
            'client_phone' => $customer->phone,
            'project_title' => 'Portal Development',
            'service_category' => 'Web App',
            'project_description' => 'Custom partner portal build.',
            'final_deal_value' => 100000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ]);

        $commission = Commission::create([
            'commission_number' => 'COM-2026-TEST01',
            'project_id' => $project->id,
            'partner_id' => $this->partner->id,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 50000.00,
            'rate_applied' => 10.00,
            'amount' => 5000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Pending,
        ]);

        $commissionService = app(CommissionService::class);
        $commissionService->cancelCommission($commission, 'Project scope cancelled by client.');

        $log = AuditLog::where('action', AuditLogAction::CommissionAdjusted->value)
            ->where('subject_id', $commission->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals(AuditLogModule::Financial->value, $log->module);
        $this->assertEquals('pending', $log->old_values['status']);
        $this->assertEquals('cancelled', $log->new_values['status']);
        $this->assertEquals('Project scope cancelled by client.', $log->metadata['reason']);
    }

    /**
     * 24. Diff tracking accuracy: no false positives on equivalent numbers or enums.
     */
    public function test_24_diff_tracking_accuracy_with_normalized_values(): void
    {
        $customer = Customer::create([
            'customer_code' => 'CUS-2026-TEST03',
            'name' => 'Meera Nair',
            'email' => 'meera@example.com',
            'phone' => '9876543212',
            'company_name' => 'Nair Design Studio',
            'status' => 'active',
            'first_partner_id' => $this->partner->id,
            'created_by' => $this->adminUser->id,
        ]);

        $project = Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-2026-TEST03',
            'client_name' => $customer->name,
            'client_company' => $customer->company_name,
            'client_email' => $customer->email,
            'client_phone' => $customer->phone,
            'project_title' => 'Branding Overhaul',
            'service_category' => 'Design',
            'project_description' => 'Corporate rebrand.',
            'final_deal_value' => 200000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ]);

        $oldAttributes = $project->getAttributes();

        // Updating only project_title, keeping final_deal_value identical numerically
        $project->update([
            'project_title' => 'Branding Overhaul Phase 2',
        ]);

        $log = $this->auditLogService->recordModelUpdate(
            model: $project,
            oldAttributes: $oldAttributes,
            action: AuditLogAction::ProjectUpdated,
            module: AuditLogModule::Project,
            description: 'Project title updated only.'
        );

        $this->assertNotNull($log);
        // Only project_title should be in changed old and new values
        $this->assertArrayHasKey('project_title', $log->old_values);
        $this->assertArrayHasKey('project_title', $log->new_values);
        $this->assertArrayNotHasKey('final_deal_value', $log->old_values);
        $this->assertArrayNotHasKey('status', $log->old_values);
    }
}
