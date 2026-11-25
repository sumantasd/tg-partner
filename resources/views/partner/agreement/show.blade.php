@extends('layouts.app')

@section('title', 'Business Partner Agreement')

@section('content')
<div class="space-y-6 w-full max-w-5xl mx-auto">

    <!-- Header Section -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 border border-slate-200/80 dark:border-slate-800 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tg-purple/10 text-tg-purple dark:bg-tg-purple/30 dark:text-purple-300">
                        Official Legal Instrument
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border {{ $agreement->status->badgeClass() }}">
                        {{ $agreement->status->label() }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black text-slate-900 dark:text-white mt-1.5 tracking-tight">
                    Partner Commercial Agreement
                </h1>
                <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Agreement No: <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $agreement->agreement_number }}</span>
                    &bull; Version {{ $agreement->template_version ?? '1.0' }} (Rev {{ $agreement->version }})
                </p>
            </div>

            <!-- PDF Action Buttons -->
            <div class="flex items-center gap-2.5">
                <a href="{{ route('partner.agreement.preview') }}"
                   target="_blank"
                   class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-xs font-bold hover:bg-slate-50 dark:hover:bg-slate-750 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    Preview PDF
                </a>
                <a href="{{ route('partner.agreement.download') }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple hover:bg-tg-purple-hover text-white text-xs font-bold transition shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    Download Official PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Status Alert Banner -->
    @if ($agreement->status->value === 'pending_acceptance')
        <div class="bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent border-l-4 border-amber-500 rounded-2xl p-5 shadow-sm">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-amber-500/20 text-amber-600 dark:text-amber-400 rounded-xl flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">
                        Action Required: Digital Acceptance Pending
                    </h3>
                    <p class="text-xs text-amber-700 dark:text-amber-300/90 mt-1 leading-relaxed">
                        Please review the terms of your business partner covenant below. Full partner operations (including <strong>submitting leads</strong> and <strong>requesting commission withdrawals</strong>) require your digital acceptance of this agreement.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($agreement->status->value === 'accepted')
        <div class="bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border-l-4 border-emerald-500 rounded-2xl p-5 shadow-sm">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 rounded-xl flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                        <h3 class="text-sm font-bold text-emerald-900 dark:text-emerald-200">
                            Digitally Signed &amp; Legally Binding
                        </h3>
                        <span class="text-[11px] font-mono font-medium text-emerald-700 dark:text-emerald-400">
                            Accepted: {{ $agreement->accepted_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST
                        </span>
                    </div>
                    <p class="text-xs text-emerald-700 dark:text-emerald-300/90 mt-1 leading-relaxed">
                        This agreement was electronically verified and executed by <strong>{{ $partner->user->name }}</strong>.
                        Signed IP: <code class="px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-950 font-mono text-[11px]">{{ $agreement->accepted_ip }}</code>.
                        Electronic verification hash is permanently affixed to the official PDF.
                    </p>
                </div>
            </div>
        </div>
    @elseif ($agreement->status->value === 'revoked')
        <div class="bg-gradient-to-r from-rose-500/10 via-rose-500/5 to-transparent border-l-4 border-rose-500 rounded-2xl p-5 shadow-sm">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-rose-500/20 text-rose-600 dark:text-rose-400 rounded-xl flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-rose-900 dark:text-rose-200">
                        Agreement Revoked
                    </h3>
                    <p class="text-xs text-rose-700 dark:text-rose-300/90 mt-1">
                        Reason: {{ $agreement->revocation_reason ?? 'Administrative Action' }}
                    </p>
                    <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-1">
                        Revoked on: {{ $agreement->revoked_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} IST.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Agreement Overview Metadata Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Commission Baseline</span>
            <span class="text-xl font-black text-slate-900 dark:text-white mt-1 block">
                {{ number_format($agreement->commission_rate_snapshot, 2) }}%
            </span>
            <span class="text-[11px] text-slate-500 capitalize">{{ $agreement->commission_type_snapshot }}</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Partner Entity</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white mt-1 block truncate" title="{{ $partner->company_name }}">
                {{ $partner->company_name ?: $partner->user->name }}
            </span>
            <span class="text-[11px] font-mono text-tg-purple">{{ $partner->partner_code }}</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Issue Date (IST)</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white mt-1 block">
                {{ $agreement->issue_date->format('d M Y') }}
            </span>
            <span class="text-[11px] text-slate-500">Effective: {{ $agreement->effective_date->format('d M Y') }}</span>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-800 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider block">Policy Schedule</span>
            <span class="text-sm font-bold text-slate-900 dark:text-white mt-1 block truncate">
                {{ $agreement->commissionPolicy?->name ?? 'Standard Platform Policy' }}
            </span>
            <span class="text-[11px] text-slate-500">Version {{ $agreement->commission_policy_version ?? '1.0' }}</span>
        </div>
    </div>

    <!-- Scrollable Agreement Document Reader -->
    <div class="bg-white dark:bg-slate-900 rounded-3xl border border-slate-200/80 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-200/80 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-tg-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider">
                    Full Document Reader &bull; {{ $agreement->agreement_number }}
                </span>
            </div>
            <span class="text-[11px] text-slate-400">Parchment View</span>
        </div>

        <div class="p-6 sm:p-10 max-h-[500px] overflow-y-auto prose dark:prose-invert max-w-none text-xs sm:text-sm text-slate-700 dark:text-slate-300 leading-relaxed font-sans space-y-4">
            {!! \Illuminate\Support\Str::markdown($agreement->content_snapshot) !!}
        </div>
    </div>

    <!-- Digital Acceptance Card (Only shown if pending) -->
    @if ($agreement->status->value === 'pending_acceptance')
        <div class="bg-white dark:bg-slate-900 rounded-3xl p-6 sm:p-8 border-2 border-amber-400/80 dark:border-amber-500/50 shadow-md">
            <h2 class="text-lg font-black text-slate-900 dark:text-white flex items-center">
                <span class="w-7 h-7 rounded-full bg-amber-500 text-white flex items-center justify-center text-xs mr-2.5 font-bold">✓</span>
                Execute Electronic Signature &amp; Acceptance
            </h2>
            <p class="text-xs text-slate-600 dark:text-slate-400 mt-1.5 leading-relaxed">
                By completing this step, you execute a legally binding agreement under the Information Technology Act, 2000.
            </p>

            <form action="{{ route('partner.agreement.accept') }}" method="POST" class="mt-5 space-y-5">
                @csrf

                <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-slate-700 dark:text-slate-300 space-y-2">
                    <p class="font-semibold text-amber-900 dark:text-amber-200">
                        Electronic Declaration of Acceptance:
                    </p>
                    <p class="italic">
                        "I, <strong>{{ $partner->user->name }}</strong>, as the authorized representative of <strong>{{ $partner->company_name ?: $partner->user->name }}</strong>, hereby declare that I have carefully read, reviewed, and unconditionally agree to abide by all the clauses, lead registration rules, commercial commissions (<strong>{{ number_format($agreement->commission_rate_snapshot, 2) }}%</strong>), confidentiality covenanted, and codes of conduct set forth in Business Partner Agreement <strong>#{{ $agreement->agreement_number }}</strong>."
                    </p>
                </div>

                <div class="flex items-start space-x-3 pt-2">
                    <input type="checkbox"
                           id="accepted"
                           name="accepted"
                           value="1"
                           class="mt-1 w-5 h-5 rounded text-tg-purple focus:ring-tg-purple border-slate-300 dark:border-slate-700 dark:bg-slate-800 cursor-pointer"
                           required>
                    <label for="accepted" class="text-xs sm:text-sm text-slate-700 dark:text-slate-300 cursor-pointer select-none">
                        <span class="font-bold text-slate-900 dark:text-white">I accept and agree to the Business Partner Agreement</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                            Audit trail stamping: Your IP address (<code class="font-mono text-tg-purple">{{ request()->ip() }}</code>) and system time (Asia/Kolkata) will be permanently affixed.
                        </span>
                    </label>
                </div>
                @error('accepted')
                    <p class="text-xs text-rose-500 font-semibold">{{ $message }}</p>
                @enderror

                <div class="pt-3 flex flex-col sm:flex-row items-center gap-3">
                    <button type="submit"
                            class="w-full sm:w-auto px-7 py-3 rounded-xl bg-gradient-to-r from-tg-purple to-[#4d276b] hover:opacity-95 text-white font-bold text-xs sm:text-sm shadow-md transition transform active:scale-95 flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Digitally Sign &amp; Accept Agreement
                    </button>
                    <a href="{{ route('partner.workspace') }}"
                       class="text-xs text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 font-medium">
                        Return to Dashboard
                    </a>
                </div>
            </form>
        </div>
    @endif

</div>
@endsection
