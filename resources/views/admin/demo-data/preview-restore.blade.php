@extends('layouts.app')

@section('title', 'Restore Backup Confirmation')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Back -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.demo-data.index') }}" class="hover:text-tg-purple font-medium">Demo Data &amp; Backups</a>
        <span>&rsaquo;</span>
        <span class="text-slate-700 font-bold font-mono">Restore {{ $backup->backup_code }}</span>
    </div>

    <!-- Warning Card -->
    <div class="bg-amber-50 rounded-2xl border-2 border-amber-300 p-6 shadow-sm">
        <div class="flex items-start space-x-4">
            <div class="p-3 bg-amber-100 rounded-xl text-amber-700 flex-shrink-0">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-amber-900 tracking-tight">Confirm Database &amp; Files Restoration</h1>
                <p class="text-xs text-amber-800 mt-1 leading-relaxed">
                    Restoring this backup will replace the live database and uploaded documents with the state captured in this archive. All modifications made since this backup was taken will be overwritten.
                </p>

                <!-- Pre-Restore Safety Disclosure -->
                <div class="mt-4 p-4 bg-white/80 rounded-xl border border-amber-200 text-xs text-amber-900 space-y-1.5">
                    <div class="font-bold flex items-center text-emerald-800">
                        <svg class="w-4 h-4 mr-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Automatic Pre-Restore Safety Guarantee:
                    </div>
                    <div>&bull; The system will automatically create and verify a full backup of the live database and files <strong>immediately before</strong> starting restoration.</div>
                    <div>&bull; If the safety pre-restore backup fails, the restore is automatically aborted and the live database remains untouched.</div>
                    <div>&bull; The application will enter maintenance mode during the restore procedure.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Target Backup Summary -->
    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm space-y-4">
        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Target Backup Details</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block">Backup Code:</span>
                <span class="font-mono font-bold text-tg-dark text-sm">{{ $backup->backup_code }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Backup Name:</span>
                <span class="font-semibold text-slate-800">{{ $backup->name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block">Created Timestamp:</span>
                <span class="font-semibold text-slate-800">{{ $backup->created_at->setTimezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST</span>
            </div>
            <div>
                <span class="text-slate-400 block">Archive Size:</span>
                <span class="font-mono font-bold text-slate-800">{{ $backup->formattedSize() }}</span>
            </div>
        </div>

        <div class="pt-2 text-xs text-slate-500 font-mono">
            SHA-256 Checksum: <span class="text-slate-700">{{ $backup->sha256_checksum }}</span>
        </div>
    </div>

    <!-- Execution Form -->
    <div class="bg-white rounded-2xl border border-slate-100 p-6 shadow-sm">
        <h2 class="text-sm font-bold text-tg-dark mb-4">Required Authorization &amp; Confirmation</h2>

        <form action="{{ route('admin.demo-data.backup.restore.execute', $backup) }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Step 1: Type Confirmation Phrase
                </label>
                <p class="text-xs text-slate-500 mb-2">
                    Type exactly <code class="px-2 py-0.5 bg-slate-100 font-bold text-rose-600 rounded">{{ $confirmationPhrase }}</code> to unlock restoration.
                </p>
                <input type="text" name="confirmation_phrase" required autocomplete="off"
                       placeholder="{{ $confirmationPhrase }}"
                       class="w-full text-xs font-mono rounded-xl border-slate-200 focus:border-amber-500 focus:ring focus:ring-amber-200">
                @error('confirmation_phrase')
                    <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                    Step 2: Confirm Administrator Password
                </label>
                <p class="text-xs text-slate-500 mb-2">Re-enter your account password to verify your administrative privileges.</p>
                <input type="password" name="password" required
                       placeholder="Enter your current password"
                       class="w-full text-xs rounded-xl border-slate-200 focus:border-amber-500 focus:ring focus:ring-amber-200">
                @error('password')
                    <span class="text-xs text-rose-600 mt-1 block font-medium">{{ $message }}</span>
                @enderror
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-slate-100">
                <a href="{{ route('admin.demo-data.index') }}"
                   class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100 transition">
                    Cancel &amp; Return
                </a>

                <button type="submit"
                        class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 shadow-sm transition">
                    Authorize &amp; Execute Restore
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
