@extends('layouts.app')

@section('title', 'Demo Data Backup & Cleanup')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    Admin Exclusive
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    AES-256 Encrypted
                </span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-2">Demo Data Backup &amp; Cleanup</h1>
            <p class="text-xs text-slate-500 mt-1">
                Create full encrypted archives of database and private storage files, verify integrity, restore backups with automatic safety snapshots, and purge demo data safely.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <button type="button" onclick="document.getElementById('create-backup-modal').classList.remove('hidden')"
                    class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition cursor-pointer">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Create Backup Now
            </button>

            <a href="{{ route('admin.demo-data.cleanup.preview') }}"
               class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Preview Demo Cleanup
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <!-- 1. Total Backups -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Backups</div>
            <div class="mt-2 text-2xl font-extrabold text-tg-dark">{{ number_format($totalBackups) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Historical archive count</div>
        </div>

        <!-- 2. Verified Backups -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Verified Archives</div>
            <div class="mt-2 text-2xl font-extrabold text-emerald-600">{{ number_format($verifiedCount) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Passed SHA-256 checksum</div>
        </div>

        <!-- 3. Total Storage -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Storage Used</div>
            <div class="mt-2 text-2xl font-extrabold text-indigo-600">{{ $totalSizeFormatted }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Protected private disk</div>
        </div>

        <!-- 4. Latest Verified Backup -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Latest Validated</div>
            <div class="mt-2 text-sm font-extrabold text-tg-dark truncate">
                {{ $latestVerified ? $latestVerified->backup_code : 'None Available' }}
            </div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">
                {{ $latestVerified ? $latestVerified->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') . ' IST' : 'Create a backup to protect data' }}
            </div>
        </div>
    </div>

    <!-- Backup Archives List -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-tg-dark">Backup History &amp; Archives</h2>
                <p class="text-xs text-slate-500 mt-0.5">Encrypted full system archives containing database snapshots, KYC documents, and agreement PDFs.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500">
                Showing {{ $backups->firstItem() ?? 0 }} - {{ $backups->lastItem() ?? 0 }} of {{ $backups->total() }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Backup Details</th>
                        <th class="py-3.5 px-4">Type</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Size &amp; Contents</th>
                        <th class="py-3.5 px-4">SHA-256 Checksum</th>
                        <th class="py-3.5 px-4">Created Date</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($backups as $b)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-3.5 px-4">
                                <a href="{{ route('admin.demo-data.backup.show', $b) }}" class="font-bold text-tg-purple hover:underline">
                                    {{ $b->backup_code }}
                                </a>
                                <div class="text-[11px] text-slate-500 font-medium truncate max-w-xs">{{ $b->name }}</div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $b->typeBadgeClass() }}">
                                    {{ $b->typeLabel() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-bold border {{ $b->statusBadgeClass() }}">
                                    @if($b->status === 'verified')
                                        <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                    {{ ucfirst($b->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[11px]">
                                <div class="font-bold text-slate-700">{{ $b->formattedSize() }}</div>
                                <div class="text-[10px] text-slate-400 font-sans">
                                    {{ $b->database_tables_count }} tables &bull; {{ $b->files_count }} files
                                </div>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-[10px] text-slate-500 max-w-xs truncate" title="{{ $b->sha256_checksum }}">
                                {{ $b->sha256_checksum ? Str::limit($b->sha256_checksum, 16) . '...' : 'Pending' }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="font-medium">{{ $b->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }}</div>
                                <div class="text-[10px] text-slate-400">By {{ $b->creator?->name ?? 'System' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <!-- Download -->
                                <a href="{{ route('admin.demo-data.backup.download', $b) }}"
                                   title="Download Encrypted Archive"
                                   class="inline-flex items-center p-1.5 rounded-lg text-slate-600 hover:text-tg-purple hover:bg-slate-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>

                                <!-- Verify -->
                                <form action="{{ route('admin.demo-data.backup.verify', $b) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" title="Verify Archive Checksum"
                                            class="inline-flex items-center p-1.5 rounded-lg text-slate-600 hover:text-emerald-600 hover:bg-emerald-50 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                        </svg>
                                    </button>
                                </form>

                                <!-- Restore -->
                                <a href="{{ route('admin.demo-data.backup.restore.preview', $b) }}"
                                   title="Restore This Backup"
                                   class="inline-flex items-center p-1.5 rounded-lg text-slate-600 hover:text-amber-600 hover:bg-amber-50 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </a>

                                <!-- Delete -->
                                <button type="button" onclick="openDeleteBackupModal({{ $b->id }}, '{{ $b->backup_code }}')"
                                        title="Delete Backup"
                                        class="inline-flex items-center p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                                </svg>
                                <div class="font-bold text-slate-600">No Backup Archives Found</div>
                                <div class="text-xs text-slate-400 mt-1">Create your first demo data backup before performing testing cleanups.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($backups->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $backups->links() }}
            </div>
        @endif
    </div>

    <!-- Recent Backup & Cleanup Audit Log -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
        <h3 class="text-sm font-bold text-tg-dark mb-3">Backup &amp; Cleanup Operation History</h3>
        <div class="space-y-2">
            @forelse($recentOperations as $op)
                <div class="p-3 rounded-xl bg-slate-50/75 border border-slate-100 flex items-center justify-between text-xs">
                    <div class="flex items-center space-x-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200">
                            @if($op->action instanceof \App\Enums\AuditLogAction)
                                {{ $op->action->label() }}
                            @elseif(is_string($op->action) && \App\Enums\AuditLogAction::tryFrom($op->action))
                                {{ \App\Enums\AuditLogAction::from($op->action)->label() }}
                            @elseif(is_string($op->action))
                                {{ ucwords(str_replace(['_', '-'], ' ', $op->action)) }}
                            @else
                                {{ $op->action_label ?? 'Unknown Action' }}
                            @endif
                        </span>
                        <span class="text-slate-700 font-medium">{{ $op->description }}</span>
                    </div>
                    <div class="text-slate-400 text-[11px] whitespace-nowrap">
                        {{ $op->created_at->setTimezone('Asia/Kolkata')->format('d M, h:i A') }} IST
                    </div>
                </div>
            @empty
                <div class="text-xs text-slate-400 py-3 text-center">No backup or cleanup events recorded yet.</div>
            @endforelse
        </div>
    </div>

    <!-- Create Backup Modal -->
    <div id="create-backup-modal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            <h3 class="text-base font-bold text-tg-dark">Create Demo Data Backup</h3>
            <p class="text-xs text-slate-500 mt-1">
                Generates a complete, AES-256 encrypted archive of all database records, customer payments, KYC documents, and agreement PDFs.
            </p>

            <form action="{{ route('admin.demo-data.backup.create') }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Backup Name / Label</label>
                    <input type="text" name="name" value="Demo Backup ({{ date('d M Y, H:i') }})"
                           class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring focus:ring-tg-purple/20">
                    <span class="text-[10px] text-slate-400 mt-1 block">Helpful description for identifying this archive.</span>
                </div>

                <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-amber-800 text-[11px] space-y-1">
                    <div class="font-bold">Automated Integrity Assurance:</div>
                    <div>&bull; Stored outside public web access on the private storage disk.</div>
                    <div>&bull; SHA-256 checksum calculated and verified immediately.</div>
                    <div>&bull; Database and uploaded documents included in single bundle.</div>
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeCreateBackupModal()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm cursor-pointer">
                        Create &amp; Verify Archive
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-backup-modal"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-100">
            <div class="flex items-center space-x-3 text-rose-600 mb-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-base font-bold text-tg-dark">Delete Backup Archive</h3>
            </div>
            <p class="text-xs text-slate-600 mt-1">
                Are you sure you want to permanently delete backup <span id="delete-backup-code" class="font-mono font-bold text-rose-600"></span>? The physical file will be removed from disk.
            </p>

            <form id="delete-backup-form" action="" method="POST" class="mt-4 space-y-4">
                @csrf
                @method('DELETE')

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Confirm Administrator Password
                    </label>
                    <input type="password" name="password" required placeholder="Enter your login password"
                           class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="closeDeleteBackupModal()"
                            class="px-3.5 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition cursor-pointer">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm cursor-pointer">
                        Permanently Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function closeCreateBackupModal() {
        const modal = document.getElementById('create-backup-modal');
        if (modal) modal.classList.add('hidden');
    }

    function openDeleteBackupModal(id, code) {
        const form = document.getElementById('delete-backup-form');
        const codeSpan = document.getElementById('delete-backup-code');
        const modal = document.getElementById('delete-backup-modal');
        if (form) form.action = '{{ url('admin/demo-data/backups') }}/' + id;
        if (codeSpan) codeSpan.textContent = code;
        if (modal) modal.classList.remove('hidden');
    }

    function closeDeleteBackupModal() {
        const modal = document.getElementById('delete-backup-modal');
        if (modal) modal.classList.add('hidden');
    }
</script>
@endsection
