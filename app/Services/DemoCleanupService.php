<?php

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\UserRole;
use App\Models\AgreementTemplate;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DemoBackup;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadDispute;
use App\Models\LeadOwnershipHistory;
use App\Models\LeadSlaEscalation;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\PartnerKyc;
use App\Models\PartnerKycHistory;
use App\Models\Payout;
use App\Models\Project;
use App\Models\ProjectPaymentSchedule;
use App\Models\ProjectStatusHistory;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserInactivityHistory;
use App\Models\WithdrawalRequest;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DemoCleanupService
{
    public const CONFIRMATION_PHRASE = 'CONFIRM-CLEANUP-DEMO-DATA';
    public const LOCK_KEY = DemoBackupService::LOCK_KEY;
    public const LOCK_TIMEOUT_SECONDS = 300;

    public function __construct(
        protected DemoBackupService $backupService,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Preview record counts for all 18 demo data categories to delete vs 5 categories preserved.
     *
     * @return array<string, mixed>
     */
    public function getCleanupPreview(User $currentAdmin): array
    {
        // 1. Count storage files safely
        $agreementsFileCount = $this->countDirectoryFiles(storage_path('app/private/agreements'));
        $kycFileCount = $this->countDirectoryFiles(storage_path('app/private/private/kyc_documents'))
            + $this->countDirectoryFiles(storage_path('app/private/kyc_documents'));

        $kycDbCount = $this->safeTableCount('partner_kycs');
        $agreementDbCount = $this->safeTableCount('partner_agreements');

        // 2. Categories to be deleted
        $categoriesToDelete = [
            [
                'id' => 'partners',
                'name' => 'Partner Accounts',
                'count' => $this->safeTableCount('partners'),
                'description' => 'Registered demo partners, profiles, and banking credentials',
            ],
            [
                'id' => 'staff_accounts',
                'name' => 'Internal Staff Accounts',
                'count' => $this->safeTableCount('users', fn($q) => $q->where('role', UserRole::TeamMember->value)),
                'description' => 'Internal team members, sales consultants, and assignees',
            ],
            [
                'id' => 'leads',
                'name' => 'Leads',
                'count' => $this->safeTableCount('leads'),
                'description' => 'All partner-submitted and admin-created demo leads',
            ],
            [
                'id' => 'lead_activities',
                'name' => 'Lead Activities & Timelines',
                'count' => $this->safeTableCount('lead_activities'),
                'description' => 'Call logs, notes, timeline entries, and status touchpoints',
            ],
            [
                'id' => 'lead_ownership_histories',
                'name' => 'Lead Ownership Histories',
                'count' => $this->safeTableCount('lead_ownership_histories'),
                'description' => 'Historical 90-day protection and reassignment logs',
            ],
            [
                'id' => 'lead_disputes',
                'name' => 'Lead Disputes',
                'count' => $this->safeTableCount('lead_disputes'),
                'description' => 'Disputes filed between partners over duplicate submissions',
            ],
            [
                'id' => 'customers',
                'name' => 'Customers',
                'count' => $this->safeTableCount('customers'),
                'description' => 'Converted customers and client profiles',
            ],
            [
                'id' => 'projects',
                'name' => 'Projects',
                'count' => $this->safeTableCount('projects'),
                'description' => 'Single and monthly service projects',
            ],
            [
                'id' => 'customer_payments',
                'name' => 'Customer Payments',
                'count' => $this->safeTableCount('customer_payments'),
                'description' => 'Recorded, verified, and reversed customer receipts',
            ],
            [
                'id' => 'project_payment_schedules',
                'name' => 'Monthly Billing Schedules',
                'count' => $this->safeTableCount('project_payment_schedules'),
                'description' => 'Monthly recurring invoices and billing period schedules',
            ],
            [
                'id' => 'commissions',
                'name' => 'Commissions',
                'count' => $this->safeTableCount('commissions'),
                'description' => 'Pending, payable, paid, and recovery commission records',
            ],
            [
                'id' => 'withdrawal_requests',
                'name' => 'Withdrawal Requests',
                'count' => $this->safeTableCount('withdrawal_requests'),
                'description' => 'Partner payout requests and reservations',
            ],
            [
                'id' => 'payouts',
                'name' => 'Payouts',
                'count' => $this->safeTableCount('payouts'),
                'description' => 'Completed and processed banking/UPI disbursement records',
            ],
            [
                'id' => 'kyc_records',
                'name' => 'KYC Records & Document Files',
                'count' => $kycDbCount . ' records (' . $kycFileCount . ' uploaded files)',
                'numeric_count' => $kycDbCount + $kycFileCount,
                'description' => 'Partner Aadhaar, PAN, and passbook submissions and files',
            ],
            [
                'id' => 'partner_agreements',
                'name' => 'Partner Agreements & PDFs',
                'count' => $agreementDbCount . ' records (' . $agreementsFileCount . ' generated PDFs)',
                'numeric_count' => $agreementDbCount + $agreementsFileCount,
                'description' => 'Signed and pending TGPA legal agreement records and PDF files',
            ],
            [
                'id' => 'notifications',
                'name' => 'In-App Notifications',
                'count' => $this->safeTableCount('notifications'),
                'description' => 'System alerts, task reminders, and status notifications',
            ],
            [
                'id' => 'test_audit_logs',
                'name' => 'Demo Audit Logs',
                'count' => $this->safeTableCount('audit_logs', fn($q) => $q->where(function ($sub) use ($currentAdmin) {
                    $sub->where('actor_user_id', '!=', $currentAdmin->id)
                        ->orWhereNull('actor_user_id');
                })),
                'description' => 'Audit logs generated by demo users, staff, and automated routines',
            ],
            [
                'id' => 'dependent_records',
                'name' => 'Other Dependent Testing Records',
                'count' => $this->getDependentRecordsCount($currentAdmin),
                'description' => 'Support tickets, SLA escalations, inactivity logs, status histories, demo users',
            ],
        ];

        // 3. Categories preserved
        $categoriesPreserved = [
            [
                'name' => 'Designated Super Admin Account',
                'details' => "Preserved exactly one account: {$currentAdmin->name} ({$currentAdmin->email})",
                'count' => 1,
            ],
            [
                'name' => 'Database Schema & Migrations',
                'details' => 'All database tables, column structures, indexes and migrations table',
                'count' => $this->safeTableCount('migrations'),
            ],
            [
                'name' => 'Production System Settings',
                'details' => 'Branding, company details, lead SLA rules, and platform configuration',
                'count' => $this->safeTableCount('system_settings'),
            ],
            [
                'name' => 'Global & Tier Commission Policies',
                'details' => 'Standard commission policy configurations and rate definitions',
                'count' => $this->safeTableCount('commission_policies'),
            ],
            [
                'name' => 'Legal Agreement Templates',
                'details' => 'Official Tech Googly legal templates, clauses, and terms',
                'count' => $this->safeTableCount('agreement_templates'),
            ],
        ];

        // 4. Check for verified backup existence
        /** @var DemoBackup|null $latestVerifiedBackup */
        $latestVerifiedBackup = DemoBackup::where('status', 'verified')->latest()->first();
        $hasVerifiedBackup = ($latestVerifiedBackup !== null);

        $totalRecordsToDelete = 0;
        foreach ($categoriesToDelete as $cat) {
            $totalRecordsToDelete += isset($cat['numeric_count']) ? (int) $cat['numeric_count'] : (int) $cat['count'];
        }

        return [
            'admin_preserved' => [
                'id' => $currentAdmin->id,
                'name' => $currentAdmin->name,
                'email' => $currentAdmin->email,
            ],
            'categories_to_delete' => $categoriesToDelete,
            'categories_preserved' => $categoriesPreserved,
            'total_records_to_delete' => $totalRecordsToDelete,
            'has_verified_backup' => $hasVerifiedBackup,
            'latest_verified_backup' => $latestVerifiedBackup,
            'can_cleanup' => $hasVerifiedBackup,
            'confirmation_phrase' => self::CONFIRMATION_PHRASE,
        ];
    }

    /**
     * Execute full cleanup of demo data while preserving designated admin and configuration.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException|DomainException
     */
    public function executeCleanup(
        User $currentAdmin,
        string $confirmationPhrase,
        string $password
    ): array {
        // 1. Re-authenticate password
        if (! Hash::check($password, $currentAdmin->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided administrator password does not match.'],
            ]);
        }

        // 2. Validate strict confirmation phrase
        if (trim($confirmationPhrase) !== self::CONFIRMATION_PHRASE) {
            throw ValidationException::withMessages([
                'confirmation_phrase' => ['You must enter exactly "' . self::CONFIRMATION_PHRASE . '" to authorize cleanup.'],
            ]);
        }

        // 3. Mandatory Verified Backup Check
        /** @var DemoBackup|null $latestBackup */
        $latestBackup = DemoBackup::where('status', 'verified')->latest()->first();
        if (! $latestBackup) {
            throw new DomainException('Cleanup requires at least one verified backup before proceeding. Please create and verify a backup first.');
        }

        // 4. Acquire atomic concurrency lock
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TIMEOUT_SECONDS);
        if (! $lock->get()) {
            throw new DomainException('Another operation is in progress. Please wait.');
        }

        $driver = DB::connection()->getDriverName();
        $deletedCounts = [];

        try {
            DB::transaction(function () use ($driver, $currentAdmin, &$deletedCounts) {
                // Disable foreign key checks for clean dependency deletion
                if ($driver === 'sqlite') {
                    DB::statement('PRAGMA foreign_keys = OFF;');
                } else {
                    DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
                }

                try {
                    // Dependency-aware order: child/linking tables first
                    $tablesToDeleteInOrder = [
                        'support_ticket_replies',
                        'support_tickets',
                        'lead_disputes',
                        'lead_activities',
                        'lead_ownership_histories',
                        'lead_sla_escalations',
                        'commissions',
                        'payouts',
                        'withdrawal_requests',
                        'customer_payments',
                        'project_payment_schedules',
                        'project_status_histories',
                        'projects',
                        'customers',
                        'leads',
                        'partner_kyc_histories',
                        'partner_kycs',
                        'partner_agreements',
                        'user_inactivity_histories',
                        'notifications',
                    ];

                    foreach ($tablesToDeleteInOrder as $table) {
                        if (! Schema::hasTable($table)) {
                            $deletedCounts[$table] = 0;
                            continue;
                        }
                        $count = DB::table($table)->count();
                        DB::table($table)->delete();
                        $deletedCounts[$table] = $count;
                    }

                    // Delete demo audit logs, preserving logs generated by this admin and system backup operations
                    $auditLogsCount = 0;
                    if (Schema::hasTable('audit_logs')) {
                        $auditLogsCount = DB::table('audit_logs')
                            ->where('module', '!=', AuditLogModule::SystemBackup->value)
                            ->where(function ($q) use ($currentAdmin) {
                                $q->where('actor_user_id', '!=', $currentAdmin->id)
                                    ->orWhereNull('actor_user_id');
                            })
                            ->delete();
                    }
                    $deletedCounts['audit_logs'] = $auditLogsCount;

                    // Delete partners
                    $partnersCount = 0;
                    if (Schema::hasTable('partners')) {
                        $partnersCount = DB::table('partners')->count();
                        DB::table('partners')->delete();
                    }
                    $deletedCounts['partners'] = $partnersCount;

                    // Delete all users EXCEPT the designated current admin
                    $usersCount = 0;
                    if (Schema::hasTable('users')) {
                        $usersCount = DB::table('users')->where('id', '!=', $currentAdmin->id)->count();
                        DB::table('users')->where('id', '!=', $currentAdmin->id)->delete();
                    }
                    $deletedCounts['users'] = $usersCount;
                } finally {
                    // Re-enable foreign key checks
                    if ($driver === 'sqlite') {
                        DB::statement('PRAGMA foreign_keys = ON;');
                    } else {
                        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
                    }
                }
            });

            // 5. Clean up uploaded storage files (preserving directory structures and .gitignore files)
            $cleanedFiles = $this->cleanStorageFiles();

            // 6. Record Audit Log for Cleanup
            $this->auditLogService->record(
                action: AuditLogAction::CleanupExecuted,
                module: AuditLogModule::SystemBackup,
                description: "Cleaned all demo data while preserving designated Admin account [ID: {$currentAdmin->id}, {$currentAdmin->email}].",
                metadata: [
                    'admin_id' => $currentAdmin->id,
                    'admin_email' => $currentAdmin->email,
                    'deleted_records' => $deletedCounts,
                    'cleaned_files_count' => $cleanedFiles,
                    'backup_code' => $latestBackup->backup_code,
                ],
                actor: $currentAdmin,
                mandatory: true
            );

            return [
                'success' => true,
                'preserved_admin' => [
                    'id' => $currentAdmin->id,
                    'name' => $currentAdmin->name,
                    'email' => $currentAdmin->email,
                ],
                'deleted_counts' => $deletedCounts,
                'cleaned_files' => $cleanedFiles,
                'cleaned_at' => Carbon::now()->toIso8601String(),
            ];
        } catch (\Throwable $e) {
            Log::error('Demo cleanup failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->auditLogService->record(
                action: AuditLogAction::CleanupFailed,
                module: AuditLogModule::SystemBackup,
                description: 'Demo cleanup failed and rolled back: ' . $e->getMessage(),
                metadata: [
                    'admin_id' => $currentAdmin->id,
                    'error' => $e->getMessage(),
                ],
                actor: $currentAdmin,
                mandatory: true
            );

            throw new DomainException('Demo cleanup failed: ' . $e->getMessage(), 0, $e);
        } finally {
            $lock->release();
        }
    }

    /**
     * Clean up uploaded storage files without removing directories.
     */
    protected function cleanStorageFiles(): int
    {
        $directoriesToClean = [
            storage_path('app/private/agreements'),
            storage_path('app/private/private/kyc_documents'),
            storage_path('app/private/kyc_documents'),
        ];

        $cleanedCount = 0;

        foreach ($directoriesToClean as $dir) {
            if (! FileFacade::isDirectory($dir)) {
                continue;
            }

            $files = FileFacade::allFiles($dir);
            foreach ($files as $file) {
                $name = $file->getFilename();
                if (in_array($name, ['.gitignore', '.gitkeep'], true)) {
                    continue;
                }

                FileFacade::delete($file->getRealPath());
                $cleanedCount++;
            }

            // Remove empty subdirectories under KYC
            $subdirs = FileFacade::directories($dir);
            foreach ($subdirs as $subdir) {
                if (count(FileFacade::allFiles($subdir)) === 0) {
                    FileFacade::deleteDirectory($subdir);
                }
            }
        }

        return $cleanedCount;
    }

    /**
     * Count non-git files in directory.
     */
    protected function countDirectoryFiles(string $dir): int
    {
        if (! FileFacade::isDirectory($dir)) {
            return 0;
        }

        $count = 0;
        $files = FileFacade::allFiles($dir);
        foreach ($files as $file) {
            if (! in_array($file->getFilename(), ['.gitignore', '.gitkeep'], true)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Safely count rows in table, returning 0 if table does not exist.
     */
    public function safeTableCount(string $tableName, ?\Closure $callback = null): int
    {
        if (! Schema::hasTable($tableName)) {
            return 0;
        }

        $query = DB::table($tableName);
        if ($callback) {
            $callback($query);
        }

        return (int) $query->count();
    }

    /**
     * Count other dependent records.
     */
    protected function getDependentRecordsCount(User $currentAdmin): int
    {
        return $this->safeTableCount('support_tickets')
            + $this->safeTableCount('support_ticket_replies')
            + $this->safeTableCount('lead_sla_escalations')
            + $this->safeTableCount('project_status_histories')
            + $this->safeTableCount('user_inactivity_histories')
            + $this->safeTableCount('partner_kyc_histories')
            + $this->safeTableCount('users', fn($q) => $q->where('role', UserRole::Partner->value))
            + $this->safeTableCount('users', fn($q) => $q->where('role', UserRole::Admin->value)->where('id', '!=', $currentAdmin->id));
    }
}
