<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditLogModule;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DemoBackup;
use App\Models\User;
use App\Services\DemoBackupService;
use App\Services\DemoCleanupService;
use App\Services\DemoRestoreService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DemoDataController extends Controller
{
    public function __construct(
        protected DemoBackupService $backupService,
        protected DemoRestoreService $restoreService,
        protected DemoCleanupService $cleanupService
    ) {}

    /**
     * Display the demo data backup dashboard and history.
     */
    public function index(Request $request): View
    {
        $backups = DemoBackup::with(['creator', 'verifier'])
            ->latest()
            ->paginate(15);

        $totalSize = (int) DemoBackup::whereIn('status', ['completed', 'verified'])->sum('file_size');
        $verifiedCount = DemoBackup::where('status', 'verified')->count();
        $latestVerified = DemoBackup::where('status', 'verified')->latest()->first();

        // Recent audit operations for the backup/cleanup module
        $recentOperations = AuditLog::where('module', AuditLogModule::SystemBackup->value)
            ->latest()
            ->take(8)
            ->get();

        return view('admin.demo-data.index', [
            'backups' => $backups,
            'totalBackups' => DemoBackup::count(),
            'verifiedCount' => $verifiedCount,
            'totalSizeFormatted' => $this->formatBytes($totalSize),
            'latestVerified' => $latestVerified,
            'recentOperations' => $recentOperations,
        ]);
    }

    /**
     * Create a new backup.
     */
    public function createBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:100',
        ]);

        /** @var User $admin */
        $admin = $request->user();
        $name = $request->input('name') ?: 'Manual Demo Backup (' . date('M j, Y H:i') . ')';

        try {
            $backup = $this->backupService->createBackup($admin, $name, 'manual');

            return redirect()->route('admin.demo-data.index')->with(
                'success',
                "Demo backup [{$backup->backup_code}] was created and verified successfully ({$backup->formattedSize()})."
            );
        } catch (\Throwable $e) {
            return redirect()->route('admin.demo-data.index')->with(
                'error',
                'Failed to create demo backup: ' . $e->getMessage()
            );
        }
    }

    /**
     * View details of a specific backup.
     */
    public function showBackup(DemoBackup $backup): View
    {
        $backup->load(['creator', 'verifier']);

        return view('admin.demo-data.show', [
            'backup' => $backup,
            'manifest' => $backup->metadata['manifest'] ?? null,
        ]);
    }

    /**
     * Manually trigger integrity verification of a backup archive.
     */
    public function verifyBackup(Request $request, DemoBackup $backup): RedirectResponse
    {
        /** @var User $admin */
        $admin = $request->user();
        $isValid = $this->backupService->verifyBackup($backup, $admin);

        if ($isValid) {
            return back()->with('success', "Backup [{$backup->backup_code}] passed all SHA-256 and archive consistency checks.");
        }

        return back()->with('error', "Backup [{$backup->backup_code}] failed verification: " . ($backup->error_message ?? 'Corrupted or modified archive.'));
    }

    /**
     * Download backup archive securely.
     */
    public function downloadBackup(Request $request, DemoBackup $backup): StreamedResponse
    {
        /** @var User $admin */
        $admin = $request->user();

        return $this->backupService->getDownloadResponse($backup, $admin);
    }

    /**
     * Delete an existing backup after password confirmation.
     */
    public function destroyBackup(Request $request, DemoBackup $backup): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        /** @var User $admin */
        $admin = $request->user();

        if (! Hash::check($request->password, $admin->password)) {
            return back()->with('error', 'Administrator password confirmation failed. Backup was not deleted.');
        }

        try {
            $this->backupService->deleteBackup($backup, $admin);

            return redirect()->route('admin.demo-data.index')->with(
                'success',
                "Backup archive [{$backup->backup_code}] was permanently deleted."
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Failed to delete backup: ' . $e->getMessage());
        }
    }

    /**
     * Show restore preview and warning confirmation.
     */
    public function previewRestore(DemoBackup $backup): View
    {
        return view('admin.demo-data.preview-restore', [
            'backup' => $backup,
            'manifest' => $backup->metadata['manifest'] ?? null,
            'confirmationPhrase' => DemoRestoreService::CONFIRMATION_PHRASE,
        ]);
    }

    /**
     * Execute restore from backup with safety pre-backup and maintenance mode.
     */
    public function executeRestore(Request $request, DemoBackup $backup): RedirectResponse
    {
        $request->validate([
            'confirmation_phrase' => 'required|string',
            'password' => 'required|string',
        ]);

        /** @var User $admin */
        $admin = $request->user();

        try {
            $result = $this->restoreService->restore(
                backup: $backup,
                admin: $admin,
                confirmationPhrase: $request->confirmation_phrase,
                password: $request->password
            );

            return redirect()->route('admin.demo-data.index')->with(
                'success',
                "Database and files successfully restored from backup [{$backup->backup_code}]. An automatic safety pre-backup [{$result['pre_backup_code']}] was verified and saved prior to restoration."
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Restoration failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Preview cleanup with exact counts of records to delete vs preserved.
     */
    public function previewCleanup(Request $request): View
    {
        /** @var User $admin */
        $admin = $request->user();
        $preview = $this->cleanupService->getCleanupPreview($admin);

        return view('admin.demo-data.preview-cleanup', $preview);
    }

    /**
     * Execute demo data cleanup.
     */
    public function executeCleanup(Request $request): RedirectResponse
    {
        $request->validate([
            'confirmation_phrase' => 'required|string',
            'password' => 'required|string',
        ]);

        /** @var User $admin */
        $admin = $request->user();

        try {
            $result = $this->cleanupService->executeCleanup(
                currentAdmin: $admin,
                confirmationPhrase: $request->confirmation_phrase,
                password: $request->password
            );

            return redirect()->route('admin.demo-data.index')->with(
                'success',
                "All demo/testing data has been successfully cleaned up. Designated Administrator [{$result['preserved_admin']['email']}], database migrations, and system configurations were preserved."
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Throwable $e) {
            return back()->with('error', 'Cleanup failed: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Helper to format bytes.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
