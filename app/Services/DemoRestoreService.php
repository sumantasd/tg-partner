<?php

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Models\DemoBackup;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class DemoRestoreService
{
    public const CONFIRMATION_PHRASE = 'RESTORE-CONFIRMED';
    public const LOCK_KEY = DemoBackupService::LOCK_KEY;
    public const LOCK_TIMEOUT_SECONDS = 300;

    public function __construct(
        protected DemoBackupService $backupService,
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Restore database and storage files from a verified backup archive.
     *
     * @return array{
     *     success: bool,
     *     backup_code: string,
     *     pre_backup_code: string,
     *     restored_at: string
     * }
     *
     * @throws ValidationException|DomainException
     */
    public function restore(
        DemoBackup $backup,
        User $admin,
        string $confirmationPhrase,
        string $password
    ): array {
        // 1. Re-authenticate password
        if (! Hash::check($password, $admin->password)) {
            throw ValidationException::withMessages([
                'password' => ['The provided administrator password does not match.'],
            ]);
        }

        // 2. Validate strict confirmation phrase
        if (trim($confirmationPhrase) !== self::CONFIRMATION_PHRASE) {
            throw ValidationException::withMessages([
                'confirmation_phrase' => ['You must enter exactly "RESTORE-CONFIRMED" to authorize restoration.'],
            ]);
        }

        // 3. Acquire atomic concurrency lock
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TIMEOUT_SECONDS);
        if (! $lock->get()) {
            throw new DomainException('Another backup, restore, or cleanup operation is currently in progress. Please wait.');
        }

        $tempExtractDir = storage_path('app/private/temp_restore_' . $backup->backup_code . '_' . time());
        $preBackup = null;
        $maintenanceActive = false;

        try {
            // 4. Validate source backup integrity
            $isValid = $this->backupService->verifyBackup($backup, $admin);
            if (! $isValid) {
                throw new DomainException("Backup archive [{$backup->backup_code}] failed integrity verification and cannot be restored.");
            }

            // 5. MANDATORY SAFETY PRE-RESTORE BACKUP
            // Automatically creates and verifies a backup of the current database and files before touching live data.
            $preBackup = $this->backupService->createBackup(
                admin: $admin,
                name: "Safety Pre-Restore Backup (Before {$backup->backup_code})",
                type: 'pre_restore',
                alreadyLocked: true
            );

            if (! $preBackup->isVerified()) {
                throw new DomainException('Safety pre-restore backup could not be verified. Restoration aborted to protect live data.');
            }

            $this->auditLogService->record(
                action: AuditLogAction::RestoreInitiated,
                module: AuditLogModule::SystemBackup,
                description: "Admin initiated restore from backup [{$backup->backup_code}]. Pre-restore safety backup: [{$preBackup->backup_code}].",
                subject: $backup,
                metadata: [
                    'target_backup_code' => $backup->backup_code,
                    'pre_backup_code' => $preBackup->backup_code,
                ],
                actor: $admin,
                mandatory: true
            );

            // 6. Enter Maintenance Mode
            try {
                Artisan::call('down', ['--render' => 'maintenance']);
                $maintenanceActive = true;
            } catch (\Throwable $e) {
                Log::warning('Could not put application in maintenance mode: ' . $e->getMessage());
            }

            // 7. Securely Extract Archive with Zip Slip Protection
            $filePath = Storage::disk(DemoBackupService::DISK)->path($backup->filepath);
            $encryptionKey = $this->backupService->resolveEncryptionKey();
            FileFacade::makeDirectory($tempExtractDir, 0755, true);

            $this->extractArchiveSafely($filePath, $tempExtractDir, $encryptionKey);

            $sqlFile = $tempExtractDir . DIRECTORY_SEPARATOR . 'database.sql';
            if (! file_exists($sqlFile) || filesize($sqlFile) === 0) {
                throw new DomainException('Restoration archive does not contain a valid database.sql file.');
            }

            // 8. Restore Database within controlled transactional execution
            $this->executeDatabaseRestore($sqlFile);

            // 9. Restore Storage Files
            $extractedStorage = $tempExtractDir . DIRECTORY_SEPARATOR . 'storage';
            if (FileFacade::isDirectory($extractedStorage)) {
                $this->restoreStorageFiles($extractedStorage);
            }

            $restoredAt = Carbon::now()->toIso8601String();

            // 10. Audit Log Success
            $this->auditLogService->record(
                action: AuditLogAction::RestoreCompleted,
                module: AuditLogModule::SystemBackup,
                description: "Successfully restored demo database and files from backup [{$backup->backup_code}]. Safety pre-backup: [{$preBackup->backup_code}].",
                subject: $backup,
                metadata: [
                    'target_backup_code' => $backup->backup_code,
                    'pre_backup_code' => $preBackup->backup_code,
                    'restored_at' => $restoredAt,
                ],
                actor: $admin,
                mandatory: true
            );

            return [
                'success' => true,
                'backup_code' => $backup->backup_code,
                'pre_backup_code' => $preBackup->backup_code,
                'restored_at' => $restoredAt,
            ];
        } catch (\Throwable $e) {
            Log::error("Restore failed for [{$backup->backup_code}]: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $this->auditLogService->record(
                action: AuditLogAction::RestoreFailed,
                module: AuditLogModule::SystemBackup,
                description: "Restoration failed for backup [{$backup->backup_code}]: " . $e->getMessage(),
                subject: $backup,
                metadata: [
                    'target_backup_code' => $backup->backup_code,
                    'pre_backup_code' => $preBackup?->backup_code,
                    'error' => $e->getMessage(),
                ],
                actor: $admin,
                mandatory: true
            );

            throw new DomainException('Restore failed: ' . $e->getMessage(), 0, $e);
        } finally {
            // Clean up temporary extracted folder
            if (FileFacade::isDirectory($tempExtractDir)) {
                FileFacade::deleteDirectory($tempExtractDir);
            }

            // Always exit maintenance mode
            if ($maintenanceActive) {
                try {
                    Artisan::call('up');
                } catch (\Throwable $e) {
                    Log::warning('Could not bring application up from maintenance mode: ' . $e->getMessage());
                }
            }

            $lock->release();
        }
    }

    /**
     * Safely extract zip archive with Zip Slip protection.
     */
    protected function extractArchiveSafely(string $zipPath, string $targetDir, string $password): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new DomainException('Cannot open backup archive for extraction.');
        }

        $zip->setPassword($password);

        $realTargetBase = realpath($targetDir);
        if (! $realTargetBase) {
            $realTargetBase = $targetDir;
        }

        // Validate all entries before extracting to prevent Zip Slip
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if ($filename === false) {
                continue;
            }

            // Prevent path traversal
            if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
                $zip->close();
                throw new DomainException("Potential malicious entry detected in archive: {$filename}");
            }
        }

        if (! $zip->extractTo($targetDir)) {
            $zip->close();
            throw new DomainException('Failed to extract files from backup archive.');
        }

        $zip->close();
    }

    /**
     * Restore database from SQL dump.
     */
    protected function executeDatabaseRestore(string $sqlFilePath): void
    {
        $driver = DB::connection()->getDriverName();
        $sql = file_get_contents($sqlFilePath);
        if ($sql === false || trim($sql) === '') {
            throw new DomainException('Database dump file is empty or unreadable.');
        }

        if ($driver === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            try {
                // Execute SQLite statements
                DB::connection()->getPdo()->exec($sql);
            } finally {
                DB::statement('PRAGMA foreign_keys = ON;');
            }
        } else {
            // MariaDB / MySQL
            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
            try {
                DB::connection()->getPdo()->exec($sql);
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
            }
        }
    }

    /**
     * Sync restored storage files into app directories.
     */
    protected function restoreStorageFiles(string $extractedStorageDir): void
    {
        $targets = [
            'agreements' => storage_path('app/private/agreements'),
            'kyc_documents' => storage_path('app/private/private/kyc_documents'),
            'kyc_documents_alt' => storage_path('app/private/kyc_documents'),
            'public' => storage_path('app/public'),
        ];

        foreach ($targets as $subKey => $targetPath) {
            $sourcePath = $extractedStorageDir . DIRECTORY_SEPARATOR . $subKey;
            if (! FileFacade::isDirectory($sourcePath)) {
                continue;
            }

            if (! FileFacade::isDirectory($targetPath)) {
                FileFacade::makeDirectory($targetPath, 0755, true);
            }

            $files = FileFacade::allFiles($sourcePath);
            foreach ($files as $file) {
                $destPath = $targetPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file->getRelativePathname());
                $destDir = dirname($destPath);
                if (! FileFacade::isDirectory($destDir)) {
                    FileFacade::makeDirectory($destDir, 0755, true);
                }
                FileFacade::copy($file->getRealPath(), $destPath);
            }
        }
    }
}
