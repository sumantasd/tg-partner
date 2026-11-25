@extends('layouts.app')

@section('title', 'Partner Business & Earnings App')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    
    @if (auth()->user()->inactivity_status && auth()->user()->inactivity_status->isAtRiskOrWorse() && !auth()->user()->isSuspended())
        <div class="bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-transparent border-l-4 border-amber-500 rounded-2xl p-4 sm:p-5 shadow-sm bg-white dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-xl flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Account Activity Notice ({{ auth()->user()->getInactivityDays() }} Days Inactive)
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Your account is currently <strong class="text-amber-700 dark:text-amber-400">{{ auth()->user()->inactivity_status->label() }}</strong>. Submit a new customer referral lead or interact with your pipeline to maintain active partner tier benefits.
                    </p>
                </div>
            </div>
            <a href="{{ route('partner.leads.create') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-tg-purple hover:bg-tg-purple-hover text-white font-bold text-xs shadow-sm transition whitespace-nowrap">
                Submit New Lead &rarr;
            </a>
        </div>
    @endif

    @if ($partner->hasPendingAgreement())
        <div class="bg-gradient-to-r from-amber-500/15 via-amber-500/10 to-transparent border-l-4 border-amber-500 rounded-2xl p-4 sm:p-5 shadow-sm bg-white dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-xl flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Action Required: Sign Your Partner Agreement
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Your official Business Partner Agreement has been issued and is awaiting digital acceptance. Submitting leads and requesting withdrawals are temporarily locked until accepted.
                    </p>
                </div>
            </div>
            <a href="{{ route('partner.agreement.show') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs shadow-sm transition whitespace-nowrap">
                Review &amp; Accept Agreement &rarr;
            </a>
        </div>
    @endif

    <!-- Partner Welcome Hero Card with Balance & Quick Action -->
    <div class="bg-gradient-to-br from-tg-purple via-[#58307d] to-[#3a1d56] rounded-3xl p-5 sm:p-7 text-white shadow-[0_10px_25px_rgba(107,61,149,0.15)] relative overflow-hidden">
        <!-- Subtle Background Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-tg-pink/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center space-x-2.5">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-sm border border-white/20">
                        Official Partner &bull; {{ $partner->partner_code }}
                    </span>
                    @if ($partner->isFullyKycVerified())
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-400 text-emerald-950 flex items-center">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            KYC Verified
                        </span>
                    @endif
                </div>

                <h1 class="text-2xl sm:text-3xl font-black mt-2 tracking-tight">
                    {{ $partner->company_name ?: auth()->user()->name }}
                </h1>
                <p class="text-xs text-white/80 mt-1 max-w-xl">
                    Welcome back! Track your client deal pipeline, monitor verified earnings, and withdraw commissions directly to your bank account.
                </p>

                <!-- Financial Balance Display -->
                <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap items-baseline gap-4">
                    <div>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-white/70 block">Available For Withdrawal</span>
                        <span class="text-2xl sm:text-3xl font-black text-white font-mono">
                            ₹{{ number_format($balanceSummary['available_balance'] ?? 0, 2) }}
                        </span>
                    </div>
                    @if (($balanceSummary['reserved_in_pending'] ?? 0) > 0)
                        <div class="px-2.5 py-1 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-[11px] text-white">
                            <span class="font-bold text-amber-300">₹{{ number_format($balanceSummary['reserved_in_pending'], 2) }}</span> in withdrawal processing
                        </div>
                    @endif
                    @if (($balanceSummary['pending_commission'] ?? 0) > 0)
                        <div class="text-[11px] text-white/70">
                            + ₹{{ number_format($balanceSummary['pending_commission'], 2) }} pending review
                        </div>
                    @endif
                </div>
            </div>

            <!-- Header Quick Actions -->
            <div class="flex flex-col sm:flex-row md:flex-col gap-2.5 sm:w-auto w-full">
                <a href="{{ route('partner.leads.create') }}"
                   class="px-5 py-3 rounded-2xl bg-tg-pink hover:bg-tg-pink-hover text-white text-xs font-bold text-center transition shadow-lg shadow-tg-pink/30 btn-tactile touch-target flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Submit New Client Lead
                </a>

                @if (($balanceSummary['available_balance'] ?? 0) > 0)
                    <a href="{{ route('partner.withdrawals.create') }}"
                       class="px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-tg-purple text-xs font-bold text-center transition shadow-md btn-tactile touch-target flex items-center justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        Request Withdrawal
                    </a>
                @else
                    <a href="{{ route('partner.withdrawals.index') }}"
                       class="px-5 py-3 rounded-2xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold text-center transition backdrop-blur-sm border border-white/20 btn-tactile touch-target flex items-center justify-center">
                        Withdrawal History &rarr;
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- KYC Compliance Alert Banner (If Not Verified) -->
    @if (!$partner->isFullyKycVerified())
        <div class="bg-amber-50 border border-amber-200 rounded-3xl p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
            <div class="flex items-start sm:items-center space-x-3">
                <div class="w-9 h-9 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0 mt-0.5 sm:mt-0">
                    !
                </div>
                <div>
                    <h2 class="text-xs font-bold text-amber-900">Mandatory KYC &amp; Bank Account Verification</h2>
                    <p class="text-[11px] text-amber-700 mt-0.5">
                        Financial compliance regulations require verified KYC and bank details before commission payouts can be disbursed.
                    </p>
                </div>
            </div>
            <a href="{{ route('partner.kyc.show') }}" class="px-4 py-2 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition shadow-sm btn-tactile touch-target text-center flex-shrink-0">
                Complete Verification &rarr;
            </a>
        </div>
    @endif

    <!-- Financial Metrics Grid (4 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Commission Earned -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Commission</div>
            <div class="text-lg sm:text-2xl font-black text-tg-dark mt-1 font-mono">
                ₹{{ number_format($balanceSummary['total_earned'] ?? 0, 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">All time earned</div>
        </div>

        <!-- 2. Available Balance -->
        <div class="rounded-3xl bg-gradient-to-br from-emerald-500/10 via-white to-white border border-emerald-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-emerald-800 uppercase tracking-wider">Available Balance</div>
            <div class="text-lg sm:text-2xl font-black text-emerald-700 mt-1 font-mono">
                ₹{{ number_format($balanceSummary['available_balance'] ?? 0, 2) }}
            </div>
            <a href="{{ route('partner.withdrawals.index') }}" class="text-[10px] text-emerald-800 font-bold hover:underline mt-1 block">
                Withdraw funds &rarr;
            </a>
        </div>

        <!-- 3. Total Paid / Withdrawn -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Disbursed</div>
            <div class="text-lg sm:text-2xl font-black text-tg-purple mt-1 font-mono">
                ₹{{ number_format($balanceSummary['total_paid'] ?? 0, 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Direct bank transfers</div>
        </div>

        <!-- 4. Commission Rate -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Default Rate</div>
            <div class="text-lg sm:text-2xl font-black text-tg-pink mt-1">
                {{ $partner->default_commission_rate }}%
            </div>
            <div class="text-[10px] text-slate-400 mt-1">Tier-based referral</div>
        </div>
    </div>

    <!-- Lead Pipeline Performance Grid (4 Cards) -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
        <!-- 1. Total Referrals -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Submitted Deals</div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-1">{{ $leadsCount }}</div>
            <div class="text-[10px] text-slate-400 mt-1">{{ $newLeadsCount }} newly registered &bull; {{ $customersCount }} converted</div>
        </div>

        <!-- 2. Active Pipeline -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-tg-blue uppercase tracking-wider">Active Pipeline</div>
            <div class="text-xl sm:text-2xl font-black text-tg-blue mt-1">{{ $inPipelineCount }}</div>
            <div class="text-[10px] text-slate-400 mt-1">In telecalling & negotiation</div>
        </div>

        <!-- 3. Won Deals -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-tg-green uppercase tracking-wider">Won Deals</div>
            <div class="text-xl sm:text-2xl font-black text-tg-green mt-1">{{ $wonCount }}</div>
            <div class="text-[10px] text-tg-green font-bold mt-1">{{ $conversionRate }}% conversion &bull; {{ $lostCount }} lost</div>
        </div>

        <!-- 4. Active Client Projects -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Active Projects</div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-1">{{ $activeProjectsCount }}</div>
            <div class="text-[10px] text-slate-400 mt-1">Generating milestone payouts</div>
        </div>
    </div>

    <!-- Recently Submitted Referrals Feed -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-xs sm:text-sm font-bold text-tg-dark uppercase tracking-wider">Recently Submitted Referrals</h2>
                <p class="text-[11px] text-slate-500">Live telecalling status and deal progress from Tech Googly sales desk.</p>
            </div>
            <a href="{{ route('partner.leads.index') }}" class="text-xs font-bold text-tg-purple hover:underline flex items-center">
                <span>All Deals</span>
                <span class="ml-1">&rarr;</span>
            </a>
        </div>

        <div class="divide-y divide-slate-100 text-sm">
            @forelse ($recentLeads as $lead)
                <div class="p-4 sm:px-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 hover:bg-slate-50 transition">
                    <div>
                        <div class="flex items-center space-x-2">
                            <span class="font-mono font-bold text-tg-purple text-xs">{{ $lead->lead_number }}</span>
                            <span class="font-bold text-tg-dark text-xs sm:text-sm">{{ $lead->customer_name }}</span>
                            @if($lead->customer_company)
                                <span class="text-xs text-slate-400 hidden sm:inline">({{ $lead->customer_company }})</span>
                            @endif
                        </div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            {{ $lead->service_required }} &bull; Submitted {{ $lead->created_at->format('M d, Y') }}
                        </div>
                    </div>
                    <div class="flex items-center space-x-3 justify-between sm:justify-end">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                            {{ $lead->status->label() }}
                        </span>
                        <a href="{{ route('partner.leads.show', $lead) }}" class="text-xs font-bold text-tg-purple hover:underline touch-target">
                            View Dossier &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-slate-400">
                    No leads submitted yet. Click <a href="{{ route('partner.leads.create') }}" class="text-tg-purple font-bold underline">Submit New Client Lead</a> to start earning referral commissions!
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
