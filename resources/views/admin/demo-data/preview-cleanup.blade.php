@extends('layouts.app')

@section('title', 'Demo Data Cleanup Preview')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.demo-data.index') }}" class="hover:text-tg-purple font-medium">Demo Data &amp; Backups</a>
        <span>&rsaquo;</span>
        <span class="text-slate-700 font-bold">Cleanup Impact Assessment &amp; Preview</span>
    </div>

    <!-- Header Banner -->
    <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                    Irreversible Cleanup Preview
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                    Admin Preserved
                </span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-2">Demo Data Cleanup — Impact Assessment</h1>
            <p class="text-xs text-slate-500 mt-1">
                Inspect every record category targeted for purge versus production assets strictly preserved. Cleanup resets test activity while keeping your system configuration intact.
            </p>
        </div>

        <div class="mt-4 sm:mt-0 flex items-center space-x-3">
            <a href="{{ route('admin.demo-data.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                Back to Backups
            </a>
        </div>
    </div>

    <!-- Verified Backup Status Check -->
    @if($has_verified_backup)
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-emerald-100 rounded-xl text-emerald-700 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-emerald-900">Safety Prerequisite Met: Verified Backup Available</div>
                    <div class="text-emerald-700 mt-0.5">
                        Latest verified archive: <span class="font-mono font-bold">{{ $latest_verified_backup->backup_code }}</span>
                        ({{ $latest_verified_backup->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST).
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.demo-data.backup.show', $latest_verified_backup) }}"
               class="px-3 py-1.5 rounded-lg text-xs font-bold text-emerald-800 bg-emerald-100 hover:bg-emerald-200 transition">
                View Archive
            </a>
        </div>
    @else
        <div class="p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-900 text-xs flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="p-2 bg-rose-100 rounded-xl text-rose-700 flex-shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <div class="font-bold text-sm text-rose-900">Safety Requirement Missing: No Verified Backup Found!</div>
                    <div class="text-rose-700 mt-0.5 leading-relaxed">
                        To protect your data, demo cleanup is blocked until at least one complete backup is created and successfully verified.
                    </div>
                </div>
            </div>
            <a href="{{ route('admin.demo-data.index') }}"
               class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition">
                Create Backup Now
            </a>
        </div>
    @endif

    <!-- Preserved vs Deleted Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Preserved Production Assets (Left Column) -->
        <div class="lg:col-span-1 space-y-4">
            <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
                <div class="flex items-center space-x-2 text-emerald-700 mb-3">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <h2 class="text-sm font-bold uppercase tracking-wider">Production Assets Preserved</h2>
                </div>
                <p class="text-xs text-slate-500 mb-4 leading-relaxed">
                    The following critical core assets will remain untouched and fully operational:
                </p>

                <div class="space-y-3">
                    @foreach($categories_preserved as $p)
                        <div class="p-3 bg-emerald-50/60 rounded-xl border border-emerald-100 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-emerald-900">{{ $p['name'] }}</span>
                                <span class="font-mono font-bold text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded text-[11px]">
                                    {{ $p['count'] }}
                                </span>
                            </div>
                            <div class="text-[11px] text-emerald-700 mt-1">{{ $p['details'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 text-xs text-slate-600 space-y-1.5">
                <div class="font-bold text-slate-800">Preservation Rules:</div>
                <div>&bull; Never executes <code>migrate:fresh</code> or <code>db:wipe</code>.</div>
                <div>&bull; Preserves file directory structures and <code>.gitignore</code> files.</div>
                <div>&bull; Never deletes application source code or branding assets.</div>
            </div>
        </div>

        <!-- Targeted for Deletion (Right Column) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Demo / Testing Records Targeted for Deletion</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Records and storage files that will be permanently purged during cleanup.</p>
                    </div>
                    <span class="text-xs font-mono font-bold px-3 py-1 rounded-xl bg-rose-100 text-rose-800">
                        Total Items: {{ number_format($total_records_to_delete) }}
                    </span>
                </div>

                <div class="divide-y divide-slate-100 text-xs">
                    @foreach($categories_to_delete as $cat)
                        <div class="p-4 hover:bg-slate-50/50 transition flex items-center justify-between">
                            <div class="space-y-0.5 max-w-md">
                                <div class="font-bold text-slate-800 flex items-center">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span>
                                    {{ $cat['name'] }}
                                </div>
                                <div class="text-[11px] text-slate-500">{{ $cat['description'] }}</div>
                            </div>
                            <div class="text-right">
                                <span class="font-mono font-bold text-rose-700 bg-rose-50 px-2.5 py-1 rounded-lg border border-rose-200 text-xs">
                                    {{ $cat['count'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Execution Card -->
            <div class="bg-white rounded-2xl border border-rose-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center space-x-3 text-rose-700">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h2 class="text-base font-bold text-rose-900">Authorize Demo Cleanup Execution</h2>
                </div>

                @if(! $can_cleanup)
                    <div class="p-4 rounded-xl bg-slate-100 text-slate-600 text-xs font-medium">
                        Cleanup execution is currently disabled because no verified backup exists. Please create and verify a backup first.
                    </div>
                @else
                    <form action="{{ route('admin.demo-data.cleanup.execute') }}" method="POST" class="space-y-4">
                        @csrf

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Step 1: Type Confirmation Phrase
                            </label>
                            <p class="text-xs text-slate-500 mb-2">
                                Type exactly <code class="px-2 py-0.5 bg-slate-100 font-bold text-rose-600 rounded">{{ $confirmation_phrase }}</code> to confirm deletion.
                            </p>
                            <input type="text" name="confirmation_phrase" required autocomplete="off"
                                   placeholder="{{ $confirmation_phrase }}"
                                   class="w-full text-xs font-mono rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200">
                            @error('confirmation_phrase')
                                <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Step 2: Confirm Administrator Password
                            </label>
                            <p class="text-xs text-slate-500 mb-2">
                                Authenticate as <strong class="text-slate-800">{{ $admin_preserved['email'] }}</strong> by entering your login password.
                            </p>
                            <input type="password" name="password" required
                                   placeholder="Enter your current password"
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200">
                            @error('password')
                                <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                            <a href="{{ route('admin.demo-data.index') }}"
                               class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                                Cancel &amp; Keep Demo Data
                            </a>

                            <button type="submit"
                                    class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 shadow-sm transition">
                                Permanently Clean All Demo Data
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
