@extends('layouts.app')

@section('title', 'Agreement #' . $agreement->agreement_number . ' — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0 flex-1">
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.agreements.index') }}" class="text-xs text-slate-400 hover:text-tg-purple">&larr; Back to Agreements</a>
                <span class="text-slate-300">/</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $agreement->status->badgeClass() }}">
                    {{ $agreement->status->label() }}
                </span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark mt-1 tracking-tight">
                Agreement #{{ $agreement->agreement_number }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Issued for <strong class="text-slate-700">{{ $agreement->partner->company_name ?: $agreement->partner->user->name }}</strong>
                &bull; Partner Code: <span class="font-mono text-tg-purple">{{ $agreement->partner->partner_code }}</span>
            </p>
        </div>

        <!-- Action Controls -->
        <div class="flex flex-wrap items-center gap-2.5">
            <a href="{{ route('admin.agreements.preview', $agreement) }}"
               target="_blank"
               class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                Preview PDF
            </a>
            <a href="{{ route('admin.agreements.download', $agreement) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Download PDF
            </a>

            @if($agreement->status->value !== 'revoked' && $agreement->status->value !== 'superseded')
                <!-- Revoke Button (Trigger Modal or Details) -->
                <button type="button"
                        onclick="document.getElementById('revoke-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-3.5 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200 text-xs font-bold transition">
                    <svg class="w-4 h-4 mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    Revoke Agreement
                </button>

                <!-- Reissue Button -->
                <button type="button"
                        onclick="document.getElementById('reissue-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-3.5 py-2 rounded-xl bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 text-xs font-bold transition">
                    <svg class="w-4 h-4 mr-1 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Reissue Agreement
                </button>
            @endif
        </div>
    </div>

    <!-- Metadata Dossier Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- Column 1: Core Agreement Details -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Agreement Specification</h2>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Agreement Number</span>
                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $agreement->agreement_number }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Template &amp; Revision</span>
                    <span class="text-slate-800 font-semibold">Template v{{ $agreement->template_version ?? '1.0' }} &bull; Rev {{ $agreement->version }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Issue &amp; Effective Dates (IST)</span>
                    <span class="text-slate-800 font-medium">Issue: {{ $agreement->issue_date->format('d M Y') }} &bull; Effective: {{ $agreement->effective_date->format('d M Y') }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Status</span>
                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $agreement->status->badgeClass() }}">
                        {{ $agreement->status->label() }}
                    </span>
                </div>
                @if($agreement->creator)
                    <div>
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Generated By Admin</span>
                        <span class="text-slate-700 font-medium">{{ $agreement->creator->name }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Column 2: Commercial Snapshot -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Commercial Snapshot</h2>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Commission Rate Snapshot</span>
                    <span class="text-2xl font-black text-slate-900 font-mono">{{ number_format($agreement->commission_rate_snapshot, 2) }}%</span>
                    <span class="text-slate-500 text-[11px] block capitalize">{{ $agreement->commission_type_snapshot }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Policy Origin</span>
                    <span class="text-slate-800 font-medium">{{ $agreement->commissionPolicy?->name ?? 'System Standard Policy' }} (v{{ $agreement->commission_policy_version ?? '1.0' }})</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Partner Account</span>
                    <a href="{{ route('admin.partners.show', $agreement->partner) }}" class="text-tg-purple hover:underline font-bold">
                        {{ $agreement->partner->company_name ?: $agreement->partner->user->name }}
                    </a>
                    <span class="block text-slate-500 font-mono text-[11px]">{{ $agreement->partner->partner_code }}</span>
                </div>
            </div>
        </div>

        <!-- Column 3: Execution & Audit Trail -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-400">Digital Execution &amp; Audit Trail</h2>
            <div class="space-y-3 text-xs">
                @if($agreement->status->value === 'accepted' && $agreement->accepted_at)
                    <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 space-y-1.5">
                        <div class="font-bold flex items-center">
                            <svg class="w-4 h-4 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Digitally Signed &amp; Accepted
                        </div>
                        <div class="text-[11px]">
                            <span class="font-medium">Timestamp (IST):</span> {{ $agreement->accepted_at->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}
                        </div>
                        <div class="text-[11px]">
                            <span class="font-medium">Recorded IP:</span> <code class="font-mono bg-emerald-100 px-1 rounded">{{ $agreement->accepted_ip }}</code>
                        </div>
                        <div class="text-[10px] text-emerald-700/80 truncate" title="{{ $agreement->accepted_user_agent }}">
                            UA: {{ $agreement->accepted_user_agent }}
                        </div>
                    </div>
                @elseif($agreement->status->value === 'revoked')
                    <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 space-y-1.5">
                        <div class="font-bold text-rose-900">Agreement Revoked</div>
                        <div class="text-[11px]"><span class="font-medium">Revoked At (IST):</span> {{ $agreement->revoked_at?->timezone('Asia/Kolkata')->format('d M Y, h:i:s A') }}</div>
                        <div class="text-[11px]"><span class="font-medium">Revoked By:</span> {{ $agreement->revoker?->name ?? 'Admin' }}</div>
                        <div class="text-[11px] font-medium text-rose-900 mt-1">Reason: {{ $agreement->revocation_reason }}</div>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 space-y-1">
                        <div class="font-bold">Pending Partner Acceptance</div>
                        <p class="text-[11px] text-amber-700">Awaiting digital signature from partner. Gating is active on their lead submissions and withdrawal requests.</p>
                    </div>
                @endif
                <div>
                    <span class="text-slate-400 block text-[10px] uppercase font-bold">Storage Path</span>
                    <span class="font-mono text-[11px] text-slate-600 break-all">{{ $agreement->pdf_path }}</span>
                </div>
            </div>
        </div>

    </div>

    <!-- Rendered Markdown Content Box -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-600">Agreement Content Snapshot</span>
            <span class="text-[11px] text-slate-400">Snapshot created {{ $agreement->created_at->format('d M Y, H:i') }}</span>
        </div>
        <div class="p-6 max-h-[600px] overflow-y-auto prose max-w-none text-xs sm:text-sm text-slate-700 space-y-3 font-sans">
            {!! \Illuminate\Support\Str::markdown($agreement->content_snapshot) !!}
        </div>
    </div>

</div>

<!-- Revoke Modal -->
<div id="revoke-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-rose-700 flex items-center">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                Revoke Partner Agreement
            </h3>
            <button type="button" onclick="document.getElementById('revoke-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
            Revoking Agreement <strong>#{{ $agreement->agreement_number }}</strong> will immediately invalidate this covenant and notify the partner. A mandatory justification reason is required for administrative audit logs.
        </p>
        <form action="{{ route('admin.agreements.revoke', $agreement) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="revoke_reason" class="block text-xs font-bold text-slate-700 mb-1">Revocation Reason <span class="text-rose-500">*</span></label>
                <textarea id="revoke_reason" name="reason" rows="3" required placeholder="Explain why this agreement is being revoked (e.g. breach of confidentiality, contract restructuring)..."
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('revoke-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-sm">Confirm Revocation</button>
            </div>
        </form>
    </div>
</div>

<!-- Reissue Modal -->
<div id="reissue-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm hidden">
    <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-base font-bold text-indigo-700 flex items-center">
                <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Reissue Agreement
            </h3>
            <button type="button" onclick="document.getElementById('reissue-modal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">&times;</button>
        </div>
        <p class="text-xs text-slate-600 leading-relaxed">
            Reissuing will mark Agreement <strong>#{{ $agreement->agreement_number }}</strong> as <em>superseded</em> and automatically issue a new agreement with incremented version (v{{ $agreement->version + 1 }}).
        </p>
        <form action="{{ route('admin.agreements.reissue', $agreement) }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="reissue_reason" class="block text-xs font-bold text-slate-700 mb-1">Reissue Reason / Notes (Optional)</label>
                <textarea id="reissue_reason" name="reason" rows="3" placeholder="Notes on template update, revised commission terms, etc..."
                          class="w-full px-3 py-2 border border-slate-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
            <div class="flex justify-end space-x-2 pt-2">
                <button type="button" onclick="document.getElementById('reissue-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold transition shadow-sm">Confirm &amp; Reissue</button>
            </div>
        </form>
    </div>
</div>
@endsection
