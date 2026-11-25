@extends('layouts.app')

@section('title', 'Backup Details - ' . $backup->backup_code)

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.demo-data.index') }}" class="hover:text-tg-purple font-medium">Demo Data &amp; Backups</a>
        <span>&rsaquo;</span>
        <span class="text-slate-700 font-bold font-mono">{{ $backup->backup_code }}</span>
    </div>

    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $backup->typeBadgeClass() }}">
                    {{ $backup->typeLabel() }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $backup->statusBadgeClass() }}">
                    @if($backup->status === 'verified')
                        <svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    @endif
                    Status: {{ ucfirst($backup->status) }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    AES-256 Encrypted
                </span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-2 font-mono">{{ $backup->backup_code }}</h1>
            <p class="text-xs text-slate-500 mt-1 font-sans">{{ $backup->name }}</p>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
            <!-- Download -->
            <a href="{{ route('admin.demo-data.backup.download', $backup) }}"
               class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download Archive
            </a>

            <!-- Verify -->
            <form action="{{ route('admin.demo-data.backup.verify', $backup) }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    Re-Verify Integrity
                </button>
            </form>

            <!-- Restore Button -->
            <a href="{{ route('admin.demo-data.backup.restore.preview', $backup) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Restore Backup
            </a>
        </div>
    </div>

    <!-- Metadata Grid -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Archive Size</div>
            <div class="mt-2 text-xl font-mono font-extrabold text-tg-dark">{{ $backup->formattedSize() }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-sans">Compressed zip file</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Database Tables</div>
            <div class="mt-2 text-xl font-mono font-extrabold text-indigo-600">{{ $backup->database_tables_count }} tables</div>
            <div class="mt-1 text-[11px] text-slate-500 font-sans">Schema + full records dump</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Uploaded Documents</div>
            <div class="mt-2 text-xl font-mono font-extrabold text-emerald-600">{{ $backup->files_count }} files</div>
            <div class="mt-1 text-[11px] text-slate-500 font-sans">KYC &amp; Agreement PDFs</div>
        </div>

        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Checksum Status</div>
            <div class="mt-2 text-xs font-bold text-slate-700 flex items-center">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                SHA-256 Validated
            </div>
            <div class="mt-1 text-[10px] text-slate-400 font-mono truncate" title="{{ $backup->sha256_checksum }}">
                {{ $backup->sha256_checksum }}
            </div>
        </div>
    </div>

    <!-- Manifest Content -->
    @if($manifest)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Database Tables Breakdown -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-tg-dark">Database Tables Captured</h3>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                        {{ count($manifest['table_records'] ?? []) }} Tables
                    </span>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 text-xs">
                    @forelse($manifest['table_records'] ?? [] as $table => $rows)
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="font-mono text-slate-700 font-medium">{{ $table }}</span>
                            <span class="font-mono font-bold text-slate-900 bg-slate-50 px-2 py-0.5 rounded">
                                {{ number_format($rows) }} rows
                            </span>
                        </div>
                    @empty
                        <div class="text-slate-400 py-4 text-center">No table manifest records found.</div>
                    @endforelse
                </div>
            </div>

            <!-- Uploaded Files Manifest -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-tg-dark">Uploaded Files in Archive</h3>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">
                        {{ count($manifest['files'] ?? []) }} Files
                    </span>
                </div>
                <div class="max-h-96 overflow-y-auto divide-y divide-slate-100 text-xs">
                    @forelse($manifest['files'] ?? [] as $f)
                        <div class="py-2.5 flex items-center justify-between">
                            <span class="font-mono text-slate-700 truncate max-w-xs" title="{{ $f['relative_path'] }}">
                                {{ $f['relative_path'] }}
                            </span>
                            <span class="font-mono text-slate-500 text-[11px]">
                                {{ number_format($f['size'] / 1024, 1) }} KB
                            </span>
                        </div>
                    @empty
                        <div class="text-slate-400 py-4 text-center">No uploaded files in this archive.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
