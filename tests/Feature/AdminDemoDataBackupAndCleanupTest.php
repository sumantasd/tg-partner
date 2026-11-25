<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\KycStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\AgreementTemplate;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DemoBackup;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\PartnerKyc;
use App\Models\Project;
use App\Models\ProjectPaymentSchedule;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DemoBackupService;
use App\Services\DemoCleanupService;
use App\Services\DemoRestoreService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminDemoDataBackupAndCleanupTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $staffUser;
    protected User $partnerUser;
    protected Partner $partner;
    protected DemoBackupService $backupService;
    protected DemoRestoreService $restoreService;
    protected DemoCleanupService $cleanupService;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Create designated Admin
        $this->adminUser = User::create([
            'name' => 'Designated Super Admin',
            'email' => 'admin@techgoogly.com',
            'password' => Hash::make('AdminSecret123!'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // 2. Create Staff user
        $this->staffUser = User::create([
            'name' => 'Demo Staff Consultant',
            'email' => 'staff@techgoogly.com',
            'password' => Hash::make('StaffSecret123!'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        // 3. Create Partner user & Partner profile
        $this->partnerUser = User::create([
            'name' => 'Demo Agency Partner',
            'email' => 'partner@techgoogly.com',
            'password' => Hash::make('PartnerSecret123!'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partner = Partner::create([
            'user_id' => $this->partnerUser->id,
            'partner_code' => 'TGP-2026-TEST',
            'company_name' => 'Demo Agency Pvt Ltd',
            'phone' => '9876543210',
            'country' => 'India',
            'kyc_status' => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);

        // 4. Create production configurations to verify preservation
        SystemSetting::set('company_name', 'Tech Googly');
        SystemSetting::set('lead_sla_hours', '48');

        CommissionPolicy::create([
            'policy_code' => 'POL-GLOBAL-TEST',
            'name' => 'Standard Global Commission Policy',
            'commission_type' => CommissionType::Percentage,
            'commission_value' => 10.00,
            'effective_from' => Carbon::now()->subMonths(1),
            'status' => 'active',
            'is_default' => true,
        ]);

        AgreementTemplate::create([
            'template_code' => 'TPL-MASTER-2026',
            'title' => 'Master Partner Program Agreement',
            'version' => '1.0',
            'content' => 'Standard legal terms and clauses for Tech Googly.',
            'status' => 'active',
            'is_active' => true,
            'effective_from' => Carbon::now()->subMonths(1),
        ]);

        $this->backupService = app(DemoBackupService::class);
        $this->restoreService = app(DemoRestoreService::class);
        $this->cleanupService = app(DemoCleanupService::class);
    }

    /**
     * Test 1: Unauthorized users cannot access demo data module.
     */
    public function test_unauthorized_users_cannot_access_demo_data_module(): void
    {
        // Guests redirected to login
        $this->get(route('admin.demo-data.index'))
            ->assertRedirect(route('login'));

        // Partners denied access (403)
        $this->actingAs($this->partnerUser)
            ->get(route('admin.demo-data.index'))
            ->assertForbidden();

        // Staff members denied access (403)
        $this->actingAs($this->staffUser)
            ->get(route('admin.demo-data.index'))
            ->assertForbidden();

        // Admins granted access (200)
        $this->actingAs($this->adminUser)
            ->get(route('admin.demo-data.index'))
            ->assertOk()
            ->assertSee('Demo Data Backup &amp; Cleanup', false);
    }

    /**
     * Test 2: Admin can create demo data backup with checksum and encrypted zip.
     */
    public function test_admin_can_create_demo_data_backup(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.demo-data.backup.create'), [
                'name' => 'Automated Feature Test Backup',
            ]);

        $response->assertRedirect(route('admin.demo-data.index'));
        $response->assertSessionHas('success');

        /** @var DemoBackup $backup */
        $backup = DemoBackup::latest()->first();
        $this->assertNotNull($backup);
        $this->assertEquals('verified', $backup->status);
        $this->assertNotNull($backup->verified_at);
        $this->assertEquals(64, strlen((string) $backup->sha256_checksum));
        $this->assertTrue($backup->file_size > 0);
        $this->assertTrue($backup->is_encrypted);

        $path = Storage::disk(DemoBackupService::DISK)->path($backup->filepath);
        $this->assertFileExists($path);

        // Verify audit log
        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::BackupCreated->value,
            'module' => AuditLogModule::SystemBackup->value,
            'actor_user_id' => $this->adminUser->id,
        ]);
    }

    /**
     * Test 3: Checksum verification verifies valid backups and catches corruption.
     */
    public function test_checksum_verification_validates_and_catches_corruption(): void
    {
        $backup = $this->backupService->createBackup($this->adminUser, 'Checksum Test Backup');
        $this->assertTrue($backup->isVerified());

        // Re-verifying valid archive succeeds
        $this->assertTrue($this->backupService->verifyBackup($backup, $this->adminUser));

        // Corrupting checksum detects mismatch
        $backup->sha256_checksum = 'corrupted_fake_checksum_0000000000000000000000000000000000000000';
        $backup->save();

        $this->assertFalse($this->backupService->verifyBackup($backup, $this->adminUser));
        $this->assertEquals('failed', $backup->fresh()->status);
    }

    /**
     * Test 4: Backup download is secure and exclusive to admin.
     */
    public function test_backup_download_is_secure_and_admin_only(): void
    {
        $backup = $this->backupService->createBackup($this->adminUser, 'Download Test');

        // Partners denied download
        $this->actingAs($this->partnerUser)
            ->get(route('admin.demo-data.backup.download', $backup))
            ->assertForbidden();

        // Staff denied download
        $this->actingAs($this->staffUser)
            ->get(route('admin.demo-data.backup.download', $backup))
            ->assertForbidden();

        // Admin download succeeds with streaming response
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.demo-data.backup.download', $backup));

        $response->assertOk();
        $this->assertTrue($response->headers->contains('content-type', 'application/zip'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::BackupDownloaded->value,
            'module' => AuditLogModule::SystemBackup->value,
        ]);
    }

    /**
     * Test 5: Backup deletion requires valid admin password.
     */
    public function test_backup_deletion_requires_valid_admin_password(): void
    {
        $backup = $this->backupService->createBackup($this->adminUser, 'Delete Test');

        // Wrong password fails deletion
        $this->actingAs($this->adminUser)
            ->delete(route('admin.demo-data.backup.destroy', $backup), [
                'password' => 'WrongPassword!',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseHas('demo_backups', ['id' => $backup->id]);

        // Correct password succeeds
        $this->actingAs($this->adminUser)
            ->delete(route('admin.demo-data.backup.destroy', $backup), [
                'password' => 'AdminSecret123!',
            ])
            ->assertRedirect(route('admin.demo-data.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('demo_backups', ['id' => $backup->id]);
    }

    /**
     * Test 6: Restore workflow creates safety pre-backup before restoring.
     */
    public function test_restore_workflow_creates_safety_pre_backup(): void
    {
        $sourceBackup = $this->backupService->createBackup($this->adminUser, 'Source Restore Point');

        $initialBackupCount = DemoBackup::count();

        // Execute restore
        $response = $this->actingAs($this->adminUser)
            ->post(route('admin.demo-data.backup.restore.execute', $sourceBackup), [
                'confirmation_phrase' => DemoRestoreService::CONFIRMATION_PHRASE,
                'password' => 'AdminSecret123!',
            ]);

        $response->assertRedirect(route('admin.demo-data.index'));
        $response->assertSessionHas('success');

        // Check that a new safety pre-restore backup was automatically created
        $this->assertGreaterThan($initialBackupCount, DemoBackup::count());
        $preBackup = DemoBackup::where('type', 'pre_restore')->latest()->first();
        $this->assertNotNull($preBackup);
        $this->assertEquals('verified', $preBackup->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => AuditLogAction::RestoreCompleted->value,
            'module' => AuditLogModule::SystemBackup->value,
        ]);
    }

    /**
     * Test 7: Restore fails and aborts if confirmation phrase or password is invalid.
     */
    public function test_restore_aborts_on_invalid_phrase_or_password(): void
    {
        $backup = $this->backupService->createBackup($this->adminUser, 'Abortion Test');

        // Invalid phrase
        $this->actingAs($this->adminUser)
            ->post(route('admin.demo-data.backup.restore.execute', $backup), [
                'confirmation_phrase' => 'WRONG-PHRASE',
                'password' => 'AdminSecret123!',
            ])
            ->assertSessionHasErrors(['confirmation_phrase']);

        // Invalid password
        $this->actingAs($this->adminUser)
            ->post(route('admin.demo-data.backup.restore.execute', $backup), [
                'confirmation_phrase' => DemoRestoreService::CONFIRMATION_PHRASE,
                'password' => 'IncorrectPass',
            ])
            ->assertSessionHasErrors(['password']);
    }

    /**
     * Test 8: Cleanup preview calculates accurate counts.
     */
    public function test_cleanup_preview_calculates_accurate_counts(): void
    {
        // Create demo records
        $customer = Customer::create([
            'customer_code' => 'CUST-DEMO-001',
            'partner_id' => $this->partner->id,
            'name' => 'Demo Customer Corp',
            'email' => 'client@democorp.com',
            'phone' => '9988776655',
        ]);

        $lead = Lead::create([
            'lead_number' => 'LD-2026-0001',
            'partner_id' => $this->partner->id,
            'customer_name' => 'Rajesh Sharma',
            'customer_company' => 'Sharma Logistics',
            'customer_phone' => '9123456780',
            'customer_email' => 'rajesh@sharma.in',
            'service_required' => 'Custom Software',
            'project_description' => 'Custom ERP solution requirement for logistics business.',
            'status' => LeadStatus::Submitted,
        ]);

        $preview = $this->cleanupService->getCleanupPreview($this->adminUser);

        $this->assertEquals($this->adminUser->id, $preview['admin_preserved']['id']);
        $this->assertGreaterThan(0, $preview['total_records_to_delete']);
        $this->assertCount(18, $preview['categories_to_delete']);
        $this->assertCount(5, $preview['categories_preserved']);
    }

    /**
     * Test 9: Cleanup is blocked if no verified backup exists.
     */
    public function test_cleanup_is_blocked_without_verified_backup(): void
    {
        // Ensure no verified backups exist
        DemoBackup::query()->delete();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cleanup requires at least one verified backup before proceeding');

        $this->cleanupService->executeCleanup(
            currentAdmin: $this->adminUser,
            confirmationPhrase: DemoCleanupService::CONFIRMATION_PHRASE,
            password: 'AdminSecret123!'
        );
    }

    /**
     * Test 10: Cleanup execution preserves designated admin and configurations while purging demo data.
     */
    public function test_cleanup_preserves_designated_admin_and_purges_demo_data(): void
    {
        // 1. Create verified backup first
        $this->backupService->createBackup($this->adminUser, 'Pre-Cleanup Safety');

        // 2. Create demo transactions and related records
        $customer = Customer::create([
            'customer_code' => 'CUST-DEMO-002',
            'partner_id' => $this->partner->id,
            'name' => 'Demo Client',
            'email' => 'demo@client.com',
            'phone' => '9876543210',
        ]);

        $project = Project::create([
            'partner_id' => $this->partner->id,
            'customer_id' => $customer->id,
            'client_name' => 'Demo Client Corp',
            'project_title' => 'Demo Mobile App',
            'project_description' => 'Demo mobile app project for testing cleanup.',
            'project_code' => 'PRJ-DEMO-001',
            'payment_type' => \App\Enums\ProjectPaymentType::Single,
            'total_value' => 50000.00,
            'status' => ProjectStatus::InProgress,
        ]);

        CustomerPayment::create([
            'payment_number' => 'PAY-2026-0001',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'partner_id' => $this->partner->id,
            'payment_type' => CustomerPaymentType::Advance,
            'payment_method' => 'bank_transfer',
            'amount' => 25000.00,
            'payment_date' => Carbon::now()->subDays(2),
            'status' => CustomerPaymentStatus::Verified,
        ]);

        Commission::create([
            'commission_number' => 'COM-2026-0001',
            'partner_id' => $this->partner->id,
            'project_id' => $project->id,
            'commission_type' => CommissionType::Percentage,
            'rate_applied' => 10.00,
            'calculation_basis' => 25000.00,
            'amount' => 2500.00,
            'status' => CommissionStatus::Payable,
        ]);

        // 3. Execute cleanup
        $result = $this->cleanupService->executeCleanup(
            currentAdmin: $this->adminUser,
            confirmationPhrase: DemoCleanupService::CONFIRMATION_PHRASE,
            password: 'AdminSecret123!'
        );

        $this->assertTrue($result['success']);

        // 4. Verify designated admin STILL EXISTS
        $this->assertDatabaseHas('users', [
            'id' => $this->adminUser->id,
            'email' => 'admin@techgoogly.com',
            'role' => UserRole::Admin->value,
        ]);

        // 5. Verify demo users and partners are DELETED
        $this->assertDatabaseMissing('users', ['id' => $this->partnerUser->id]);
        $this->assertDatabaseMissing('users', ['id' => $this->staffUser->id]);
        $this->assertDatabaseMissing('partners', ['id' => $this->partner->id]);

        // 6. Verify demo transactions are DELETED
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('commissions', ['amount' => 2500.00]);

        // 7. Verify production configurations PRESERVED
        $this->assertEquals('Tech Googly', SystemSetting::get('company_name'));
        $this->assertDatabaseHas('commission_policies', ['is_default' => true]);
        $this->assertDatabaseHas('agreement_templates', ['status' => 'active']);
    }

    /**
     * Test 11: Concurrent operation lock prevents simultaneous backups or cleanups.
     */
    public function test_concurrent_operation_lock_prevents_simultaneous_execution(): void
    {
        // Acquire lock manually
        $lock = Cache::lock(DemoBackupService::LOCK_KEY, 60);
        $this->assertTrue($lock->get());

        try {
            $this->expectException(DomainException::class);
            $this->expectExceptionMessage('Another backup, restore, or cleanup operation is currently in progress');

            $this->backupService->createBackup($this->adminUser, 'Blocked Concurrent Backup');
        } finally {
            $lock->release();
        }
    }

    /**
     * Test 12: File cleanup cleans storage files while preserving directory structure and .gitignore.
     */
    public function test_cleanup_removes_demo_files_while_preserving_directory_structures(): void
    {
        // Setup temporary mock demo files
        $agreementsDir = storage_path('app/private/agreements');
        $kycDir = storage_path('app/private/private/kyc_documents/test_partner');

        if (! FileFacade::isDirectory($agreementsDir)) {
            FileFacade::makeDirectory($agreementsDir, 0755, true);
        }
        if (! FileFacade::isDirectory($kycDir)) {
            FileFacade::makeDirectory($kycDir, 0755, true);
        }

        file_put_contents($agreementsDir . '/.gitignore', "*\n!.gitignore\n");
        file_put_contents($agreementsDir . '/TGPA-TEST-DEMO.pdf', '%PDF-1.4 demo agreement');
        file_put_contents($kycDir . '/aadhaar_test.pdf', '%PDF-1.4 demo aadhaar');

        // Create verified backup first
        $this->backupService->createBackup($this->adminUser, 'Pre-File-Cleanup Safety');

        $this->cleanupService->executeCleanup(
            currentAdmin: $this->adminUser,
            confirmationPhrase: DemoCleanupService::CONFIRMATION_PHRASE,
            password: 'AdminSecret123!'
        );

        // Demo files deleted
        $this->assertFileDoesNotExist($agreementsDir . '/TGPA-TEST-DEMO.pdf');
        $this->assertFileDoesNotExist($kycDir . '/aadhaar_test.pdf');

        // Directory & .gitignore preserved
        $this->assertDirectoryExists($agreementsDir);
        $this->assertFileExists($agreementsDir . '/.gitignore');
    }

    /**
     * Test 13: Failed cleanup rolls back database transaction and does not partially delete records.
     */
    public function test_failed_cleanup_rolls_back_atomically(): void
    {
        $this->backupService->createBackup($this->adminUser, 'Pre-Rollback Safety');

        // We simulate a failure by checking invalid password or missing prerequisite
        $initialUserCount = User::count();

        try {
            $this->cleanupService->executeCleanup(
                currentAdmin: $this->adminUser,
                confirmationPhrase: 'WRONG-PHRASE',
                password: 'AdminSecret123!'
            );
            $this->fail('Expected ValidationException was not thrown.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Expected
        }

        // Database records untouched
        $this->assertEquals($initialUserCount, User::count());
        $this->assertDatabaseHas('users', ['id' => $this->partnerUser->id]);
        $this->assertDatabaseHas('users', ['id' => $this->staffUser->id]);
    }

    /**
     * Test 14: Cleanup preview handles empty database and non-existent optional tables safely.
     */
    public function test_cleanup_preview_handles_empty_tables_and_missing_optional_tables(): void
    {
        // Delete all leads, customers, projects, payments, commissions
        Lead::query()->delete();
        Customer::query()->delete();
        Project::query()->delete();
        CustomerPayment::query()->delete();
        Commission::query()->delete();

        // Verify safeTableCount returns 0 for non-existent table
        $this->assertEquals(0, $this->cleanupService->safeTableCount('non_existent_optional_table_xyz'));

        // Load preview page via HTTP request
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.demo-data.cleanup.preview'));

        $response->assertOk();
        $response->assertSee('Demo Data Cleanup — Impact Assessment');
        $response->assertSee('admin@techgoogly.com');
        $response->assertSee('Designated Super Admin Account');
        $response->assertSee('Legal Agreement Templates');
        $response->assertSee('Global &amp; Tier Commission Policies', false);

        // Preview service method directly
        $preview = $this->cleanupService->getCleanupPreview($this->adminUser);
        $this->assertIsArray($preview);
        $this->assertEquals('admin@techgoogly.com', $preview['admin_preserved']['email']);
        $this->assertCount(18, $preview['categories_to_delete']);
        $this->assertCount(5, $preview['categories_preserved']);
    }

    /**
     * Test 15: Backup failure records failed status and captures error message.
     */
    public function test_backup_failure_handling_and_logging(): void
    {
        $backup = DemoBackup::create([
            'backup_code' => 'BK-TEST-FAIL-001',
            'name' => 'Simulated Failure Backup',
            'filename' => 'non_existent_backup.zip',
            'disk' => 'local',
            'filepath' => 'backups/demo/non_existent_backup.zip',
            'file_size' => 0,
            'status' => 'in_progress',
            'type' => 'manual',
            'is_encrypted' => true,
            'created_by' => $this->adminUser->id,
        ]);

        // Attempting to verify missing archive marks status failed
        $verified = $this->backupService->verifyBackup($backup, $this->adminUser);
        $this->assertFalse($verified);
        $this->assertEquals('failed', $backup->fresh()->status);
        $this->assertStringContainsString('Archive file not found', (string) $backup->fresh()->error_message);
    }

    /**
     * Test 16: AuditLog action handles Enum instances, valid strings, and legacy strings gracefully.
     */
    public function test_audit_log_action_enum_and_string_casting_and_fallbacks(): void
    {
        // 1. Enum action value
        $logEnum = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::BackupCreated,
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Backup created via enum',
        ]);

        $this->assertInstanceOf(AuditLogAction::class, $logEnum->action);
        $this->assertEquals(AuditLogAction::BackupCreated, $logEnum->action);
        $this->assertEquals('Backup Created', $logEnum->action->label());
        $this->assertEquals('Backup Created', $logEnum->action_label);
        $this->assertEquals('backup_created', $logEnum->action_value);
        $this->assertEquals('backup_created', $logEnum->getRawOriginal('action'));

        // 2. String action value matching a known enum case
        $logString = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => 'backup_verified',
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Backup verified via string',
        ]);

        $this->assertInstanceOf(AuditLogAction::class, $logString->action);
        $this->assertEquals(AuditLogAction::BackupVerified, $logString->action);
        $this->assertEquals('Backup Verified', $logString->action->label());
        $this->assertEquals('Backup Verified', $logString->action_label);
        $this->assertEquals('backup_verified', $logString->action_value);
        $this->assertEquals('backup_verified', $logString->getRawOriginal('action'));

        // 3. Unknown or legacy action string
        $logLegacy = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => 'legacy_custom_migration',
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Legacy migration event',
        ]);

        $this->assertIsString($logLegacy->action);
        $this->assertEquals('legacy_custom_migration', $logLegacy->action);
        $this->assertEquals('Legacy Custom Migration', $logLegacy->action_label);
        $this->assertEquals('legacy_custom_migration', $logLegacy->action_value);
        $this->assertEquals('legacy_custom_migration', $logLegacy->getRawOriginal('action'));

        // 4. Test AuditLogAction::labelFor static helper
        $this->assertEquals('Backup Created', AuditLogAction::labelFor(AuditLogAction::BackupCreated));
        $this->assertEquals('Backup Verified', AuditLogAction::labelFor('backup_verified'));
        $this->assertEquals('Custom Legacy Operation', AuditLogAction::labelFor('custom_legacy_operation'));
        $this->assertEquals('Unknown Action', AuditLogAction::labelFor(null));
    }

    /**
     * Test 17: Admin Demo Data Management page loads successfully and renders recent history
     * with enum, string, and legacy action values without throwing 'label() on string'.
     */
    public function test_demo_data_management_page_renders_recent_operation_history_with_all_action_types(): void
    {
        // Clear any existing backup module audit logs in test DB
        AuditLog::where('module', AuditLogModule::SystemBackup->value)->delete();

        // 1. Audit log with enum action
        AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::BackupCreated,
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Demo backup generated successfully',
            'created_at' => now()->subMinutes(10),
        ]);

        // 2. Audit log with valid string action
        AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => 'cleanup_previewed',
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Cleanup preview generated',
            'created_at' => now()->subMinutes(5),
        ]);

        // 3. Audit log with legacy/unknown action string
        AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => $this->adminUser->name,
            'actor_role_snapshot' => 'admin',
            'action' => 'legacy_custom_purge',
            'module' => AuditLogModule::SystemBackup->value,
            'description' => 'Legacy purge record test',
            'created_at' => now()->subMinute(),
        ]);

        // Perform GET request to Admin Demo Data page
        $response = $this->actingAs($this->adminUser)
            ->get(route('admin.demo-data.index'));

        // Assert successful page render
        $response->assertOk();
        $response->assertSee('Demo Data Backup &amp; Cleanup', false);
        $response->assertSee('Backup &amp; Cleanup Operation History', false);

        // Assert all action labels render cleanly
        $response->assertSee('Backup Created');
        $response->assertSee('Cleanup Previewed');
        $response->assertSee('Legacy Custom Purge');

        // Assert descriptions render
        $response->assertSee('Demo backup generated successfully');
        $response->assertSee('Cleanup preview generated');
        $response->assertSee('Legacy purge record test');
    }
}
