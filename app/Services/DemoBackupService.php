<?php

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Models\DemoBackup;
use App\Models\User;
use DomainException;
use Illuminate\Http\File;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class DemoBackupService
{
    public const DISK = 'local';
    public const BACKUP_DIR = 'backups/demo';
    public const LOCK_KEY = 'demo_backup_cleanup_operation';
    public const LOCK_TIMEOUT_SECONDS = 180;

    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    /**
     * Create a complete demo data backup (Database + uploaded files + manifest).
     *
     * @throws DomainException
     */
    public function createBackup(
        User $admin,
        string $name = 'Demo Data Backup',
        string $type = 'manual',
        ?string $customPassword = null,
        bool $alreadyLocked = false
    ): DemoBackup {
        $lock = null;
        if (! $alreadyLocked) {
            $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TIMEOUT_SECONDS);
            if (! $lock->get()) {
                throw new DomainException('Another backup, restore, or cleanup operation is currently in progress. Please wait.');
            }
        }

        $backupCode = 'BK-' . date('Ymd-His') . '-' . strtoupper(Str::random(6));
        $filename = 'backup_' . date('Ymd_His') . '_' . strtolower(Str::random(6)) . '.zip';
        $tempDir = storage_path('app/private/temp_backups/' . $backupCode);

        // Record initial in-progress state
        /** @var DemoBackup $demoBackup */
        $demoBackup = DemoBackup::create([
            'backup_code' => $backupCode,
            'name' => $name,
            'filename' => $filename,
            'disk' => self::DISK,
            'filepath' => self::BACKUP_DIR . '/' . $filename,
            'file_size' => 0,
            'status' => 'in_progress',
            'type' => $type,
            'is_encrypted' => true,
            'encryption_method' => 'AES-256',
            'created_by' => $admin->id,
            'metadata' => [
                'initiated_at' => Carbon::now()->toIso8601String(),
                'initiator_name' => $admin->name,
                'initiator_email' => $admin->email,
            ],
        ]);

        try {
            if (! FileFacade::isDirectory($tempDir)) {
                FileFacade::makeDirectory($tempDir, 0755, true);
            }

            // 1. Dump database to database.sql
            $sqlPath = $tempDir . DIRECTORY_SEPARATOR . 'database.sql';
            $dumpResult = $this->dumpDatabase($sqlPath);
            $tablesCount = count($dumpResult['tables']);

            // 2. Collect uploaded documents & private files
            $storageFilesDir = $tempDir . DIRECTORY_SEPARATOR . 'storage';
            FileFacade::makeDirectory($storageFilesDir, 0755, true);
            $collectedFiles = $this->collectStorageFiles($storageFilesDir);
            $filesCount = count($collectedFiles);

            // 3. Generate Manifest
            $manifest = [
                'backup_code' => $backupCode,
                'name' => $name,
                'type' => $type,
                'created_at' => Carbon::now()->toIso8601String(),
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
                'app_version' => '1.0.0',
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'db_connection' => config('database.default'),
                'tables_count' => $tablesCount,
                'files_count' => $filesCount,
                'table_records' => $dumpResult['tables'],
                'files' => $collectedFiles,
            ];
            file_put_contents(
                $tempDir . DIRECTORY_SEPARATOR . 'manifest.json',
                json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // 4. Build Encrypted Zip Archive
            $zipPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
            $encryptionKey = $this->resolveEncryptionKey($customPassword);
            $this->buildZipArchive($tempDir, $zipPath, $encryptionKey);

            if (! file_exists($zipPath) || filesize($zipPath) === 0) {
                throw new DomainException('Backup archive creation failed or generated an empty file.');
            }

            $fileSize = (int) filesize($zipPath);
            $checksum = hash_file('sha256', $zipPath);

            // 5. Store on private disk
            $destinationDir = Storage::disk(self::DISK)->path(self::BACKUP_DIR);
            if (! FileFacade::isDirectory($destinationDir)) {
                FileFacade::makeDirectory($destinationDir, 0755, true);
            }

            Storage::disk(self::DISK)->putFileAs(
                self::BACKUP_DIR,
                new File($zipPath),
                $filename
            );

            // 6. Verify immediately
            $storedFullPath = Storage::disk(self::DISK)->path(self::BACKUP_DIR . '/' . $filename);
            $verified = $this->verifyArchiveFile($storedFullPath, $checksum, $encryptionKey);

            if (! $verified) {
                throw new DomainException('Newly generated backup archive failed immediate integrity verification.');
            }

            // 7. Update DemoBackup record
            $demoBackup->update([
                'file_size' => $fileSize,
                'sha256_checksum' => $checksum,
                'database_tables_count' => $tablesCount,
                'files_count' => $filesCount,
                'status' => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => $admin->id,
                'metadata' => array_merge($demoBackup->metadata ?? [], [
                    'manifest' => $manifest,
                    'verified_at' => Carbon::now()->toIso8601String(),
                ]),
            ]);

            // 8. Log audit trail
            $this->auditLogService->record(
                action: AuditLogAction::BackupCreated,
                module: AuditLogModule::SystemBackup,
                description: "Created and verified demo data backup [{$backupCode}] ({$name}, {$demoBackup->formattedSize()}).",
                subject: $demoBackup,
                metadata: [
                    'backup_code' => $backupCode,
                    'type' => $type,
                    'file_size' => $fileSize,
                    'tables_count' => $tablesCount,
                    'files_count' => $filesCount,
                    'sha256' => $checksum,
                ],
                actor: $admin,
                mandatory: true
            );

            return $demoBackup->fresh();
        } catch (\Throwable $e) {
            Log::error("Demo backup failed for [{$backupCode}]: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            $demoBackup->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $this->auditLogService->record(
                action: AuditLogAction::BackupCreated,
                module: AuditLogModule::SystemBackup,
                description: "Failed to create demo data backup [{$backupCode}]: " . $e->getMessage(),
                subject: $demoBackup,
                metadata: [
                    'backup_code' => $backupCode,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ],
                actor: $admin
            );

            throw new DomainException('Backup failed: ' . $e->getMessage(), 0, $e);
        } finally {
            if (FileFacade::isDirectory($tempDir)) {
                FileFacade::deleteDirectory($tempDir);
            }
            if ($lock !== null) {
                $lock->release();
            }
        }
    }

    /**
     * Verify an existing backup archive.
     */
    public function verifyBackup(DemoBackup $backup, ?User $admin = null): bool
    {
        $filePath = Storage::disk(self::DISK)->path($backup->filepath);
        if (! file_exists($filePath)) {
            $backup->update([
                'status' => 'failed',
                'error_message' => 'Archive file not found on disk at ' . $backup->filepath,
            ]);
            return false;
        }

        $checksum = (string) $backup->sha256_checksum;
        $encryptionKey = $this->resolveEncryptionKey();
        $isValid = $this->verifyArchiveFile($filePath, $checksum, $encryptionKey);

        if ($isValid) {
            $backup->update([
                'status' => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => $admin ? $admin->id : $backup->verified_by,
                'error_message' => null,
            ]);

            $this->auditLogService->record(
                action: AuditLogAction::BackupVerified,
                module: AuditLogModule::SystemBackup,
                description: "Verified backup integrity for [{$backup->backup_code}].",
                subject: $backup,
                metadata: [
                    'backup_code' => $backup->backup_code,
                    'sha256' => $checksum,
                ],
                actor: $admin
            );

            return true;
        }

        $backup->update([
            'status' => 'failed',
            'error_message' => 'Checksum mismatch or archive corruption detected during verification.',
        ]);

        return false;
    }

    /**
     * Securely delete a backup and its archive file.
     */
    public function deleteBackup(DemoBackup $backup, User $admin): bool
    {
        $lock = Cache::lock(self::LOCK_KEY, self::LOCK_TIMEOUT_SECONDS);
        if (! $lock->get()) {
            throw new DomainException('Another operation is in progress. Please wait.');
        }

        try {
            if (Storage::disk(self::DISK)->exists($backup->filepath)) {
                Storage::disk(self::DISK)->delete($backup->filepath);
            }

            $code = $backup->backup_code;
            $name = $backup->name;

            $backup->delete();

            $this->auditLogService->record(
                action: AuditLogAction::BackupDeleted,
                module: AuditLogModule::SystemBackup,
                description: "Deleted demo backup [{$code}] ({$name}).",
                metadata: [
                    'backup_code' => $code,
                    'name' => $name,
                ],
                actor: $admin,
                mandatory: true
            );

            return true;
        } finally {
            $lock->release();
        }
    }

    /**
     * Generate secure streaming download response for admin.
     */
    public function getDownloadResponse(DemoBackup $backup, User $admin): StreamedResponse
    {
        $filePath = Storage::disk(self::DISK)->path($backup->filepath);
        if (! file_exists($filePath)) {
            throw new DomainException('Backup file not found on disk.');
        }

        $this->auditLogService->record(
            action: AuditLogAction::BackupDownloaded,
            module: AuditLogModule::SystemBackup,
            description: "Admin downloaded backup archive [{$backup->backup_code}].",
            subject: $backup,
            metadata: [
                'backup_code' => $backup->backup_code,
                'filename' => $backup->filename,
                'file_size' => $backup->file_size,
            ],
            actor: $admin
        );

        return Storage::disk(self::DISK)->download(
            $backup->filepath,
            $backup->filename,
            [
                'Content-Type' => 'application/zip',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0',
                'Pragma' => 'no-cache',
            ]
        );
    }

    /**
     * Resolve encryption key.
     */
    public function resolveEncryptionKey(?string $customPassword = null): string
    {
        if (! empty($customPassword)) {
            return $customPassword;
        }

        $appKey = (string) config('app.key', 'tg_partner_program_default_secure_key');
        return hash('sha256', $appKey . '_demo_backup_salt_2026');
    }

    /**
     * Verify archive file consistency and checksum.
     */
    protected function verifyArchiveFile(string $filePath, string $expectedChecksum, string $encryptionKey): bool
    {
        if (! file_exists($filePath)) {
            return false;
        }

        $actualChecksum = hash_file('sha256', $filePath);
        if (! hash_equals($expectedChecksum, (string) $actualChecksum)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath, ZipArchive::CHECKCONS) !== true) {
            return false;
        }

        $zip->setPassword($encryptionKey);

        // Check essential contents
        $hasSql = ($zip->locateName('database.sql') !== false);
        $hasManifest = ($zip->locateName('manifest.json') !== false);

        $zip->close();

        return $hasSql && $hasManifest;
    }

    /**
     * Collect storage files (agreements, KYC docs, public uploads) into temp directory.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function collectStorageFiles(string $targetBaseDir): array
    {
        $collected = [];
        $sources = [
            'agreements' => storage_path('app/private/agreements'),
            'kyc_documents' => storage_path('app/private/private/kyc_documents'),
            'kyc_documents_alt' => storage_path('app/private/kyc_documents'),
            'public' => storage_path('app/public'),
        ];

        foreach ($sources as $sourceKey => $sourcePath) {
            if (! FileFacade::isDirectory($sourcePath)) {
                continue;
            }

            $allFiles = FileFacade::allFiles($sourcePath);
            foreach ($allFiles as $file) {
                $filename = $file->getFilename();
                // Skip gitkeep and gitignore
                if (in_array($filename, ['.gitignore', '.gitkeep'], true)) {
                    continue;
                }

                $relativePath = $sourceKey . '/' . str_replace('\\', '/', $file->getRelativePathname());
                $destFullPath = $targetBaseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

                $destDir = dirname($destFullPath);
                if (! FileFacade::isDirectory($destDir)) {
                    FileFacade::makeDirectory($destDir, 0755, true);
                }

                FileFacade::copy($file->getRealPath(), $destFullPath);

                $collected[] = [
                    'relative_path' => $relativePath,
                    'size' => $file->getSize(),
                    'last_modified' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return $collected;
    }

    /**
     * Build ZipArchive with AES-256 encryption.
     */
    protected function buildZipArchive(string $sourceDir, string $zipPath, string $password): void
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new DomainException('Unable to open zip archive at ' . $zipPath);
        }

        $zip->setPassword($password);

        $files = FileFacade::allFiles($sourceDir);
        foreach ($files as $file) {
            if ($file->getRealPath() === $zipPath) {
                continue; // Do not include the zip in itself
            }

            $relativePath = str_replace('\\', '/', $file->getRelativePathname());
            $zip->addFile($file->getRealPath(), $relativePath);
            $zip->setEncryptionName($relativePath, ZipArchive::EM_AES_256);
        }

        $zip->close();
    }

    /**
     * Dump database to SQL file (supports MySQL/MariaDB & SQLite).
     *
     * @return array{tables: array<string, int>}
     */
    public function dumpDatabase(string $outputPath): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $handle = fopen($outputPath, 'w');
        if (! $handle) {
            throw new DomainException('Cannot open output file for database dump.');
        }

        $tableCounts = [];

        fwrite($handle, "-- TG Partner Program Database Dump\n");
        fwrite($handle, "-- Generated: " . Carbon::now()->toIso8601String() . "\n");
        fwrite($handle, "-- Driver: " . $driver . "\n\n");

        if ($driver === 'sqlite') {
            fwrite($handle, "PRAGMA foreign_keys = OFF;\n\n");

            /** @var array<int, object{name: string, sql: string}> $tables */
            $tables = DB::select("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name");
            foreach ($tables as $t) {
                $tableName = $t->name;
                if ($tableName === 'demo_backups') {
                    continue;
                }
                $createSql = $t->sql;

                fwrite($handle, "-- Table: {$tableName}\n");
                fwrite($handle, "DROP TABLE IF EXISTS \"{$tableName}\";\n");
                fwrite($handle, $createSql . ";\n\n");

                $count = $this->dumpTableDataSqlite($handle, $tableName);
                $tableCounts[$tableName] = $count;
            }

            fwrite($handle, "PRAGMA foreign_keys = ON;\n");
        } else {
            // MySQL / MariaDB - strictly query tables in the current active database schema
            fwrite($handle, "SET FOREIGN_KEY_CHECKS = 0;\n");
            fwrite($handle, "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n\n");

            /** @var array<int, object{table_name: string}> $tableRows */
            $tableRows = DB::select("SELECT table_name AS table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY table_name");
            $tables = [];
            foreach ($tableRows as $row) {
                $rowArray = (array) $row;
                $name = $rowArray['table_name'] ?? (string) array_values($rowArray)[0];
                if (! empty($name) && ! in_array($name, $tables, true)) {
                    $tables[] = $name;
                }
            }

            foreach ($tables as $tableName) {
                if ($tableName === 'demo_backups') {
                    continue;
                }
                fwrite($handle, "-- Table structure for: `{$tableName}`\n");
                fwrite($handle, "DROP TABLE IF EXISTS `{$tableName}`;\n");

                try {
                    $createRow = DB::select("SHOW CREATE TABLE `{$tableName}`");
                    if (! empty($createRow)) {
                        $createObj = (array) $createRow[0];
                        $createSql = $createObj['Create Table'] ?? (string) array_values($createObj)[1];
                        fwrite($handle, $createSql . ";\n\n");
                    }
                } catch (\Throwable) {
                    // Fallback continue
                }

                $count = $this->dumpTableDataMysql($handle, $tableName);
                $tableCounts[$tableName] = $count;
            }

            fwrite($handle, "\nSET FOREIGN_KEY_CHECKS = 1;\n");
        }

        fclose($handle);

        return ['tables' => $tableCounts];
    }

    /**
     * Dump rows for SQLite table.
     *
     * @param resource $handle
     */
    protected function dumpTableDataSqlite($handle, string $tableName): int
    {
        $rows = DB::table($tableName)->get();
        $count = $rows->count();
        if ($count === 0) {
            return 0;
        }

        $pdo = DB::connection()->getPdo();

        foreach ($rows->chunk(100) as $chunk) {
            foreach ($chunk as $row) {
                $rowArray = (array) $row;
                $columns = array_keys($rowArray);
                $quotedColumns = array_map(fn($col) => "\"{$col}\"", $columns);

                $values = [];
                foreach ($rowArray as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } elseif (is_numeric($val) && ! is_string($val)) {
                        $values[] = (string) $val;
                    } else {
                        $values[] = $pdo->quote((string) $val);
                    }
                }

                $sql = "INSERT INTO \"{$tableName}\" (" . implode(', ', $quotedColumns) . ") VALUES (" . implode(', ', $values) . ");\n";
                fwrite($handle, $sql);
            }
        }

        fwrite($handle, "\n");
        return $count;
    }

    /**
     * Dump rows for MySQL table.
     *
     * @param resource $handle
     */
    protected function dumpTableDataMysql($handle, string $tableName): int
    {
        $count = DB::table($tableName)->count();
        if ($count === 0) {
            return 0;
        }

        $pdo = DB::connection()->getPdo();

        DB::table($tableName)->orderBy(DB::raw('1'))->chunk(250, function ($chunk) use ($handle, $tableName, $pdo) {
            $first = true;
            $insertHeader = '';

            foreach ($chunk as $row) {
                $rowArray = (array) $row;
                if ($first) {
                    $columns = array_keys($rowArray);
                    $quotedCols = array_map(fn($c) => "`{$c}`", $columns);
                    $insertHeader = "INSERT INTO `{$tableName}` (" . implode(', ', $quotedCols) . ") VALUES\n";
                    fwrite($handle, $insertHeader);
                    $first = false;
                } else {
                    fwrite($handle, ",\n");
                }

                $values = [];
                foreach ($rowArray as $val) {
                    if ($val === null) {
                        $values[] = 'NULL';
                    } elseif (is_int($val) || is_float($val)) {
                        $values[] = (string) $val;
                    } else {
                        $values[] = $pdo->quote((string) $val);
                    }
                }

                fwrite($handle, "  (" . implode(', ', $values) . ")");
            }

            fwrite($handle, ";\n\n");
        });

        return $count;
    }
}
