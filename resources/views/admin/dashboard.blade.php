@extends('layouts.app')

@section('title', 'Admin Executive Dashboard')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    
    <!-- Top Executive Header & Primary Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-tg-dark tracking-tight">Executive Financial Dashboard</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Live System
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Real-time database aggregation of partner referrals, client deals, verified revenue, and commission liabilities.</p>
        </div>
        <div class="flex items-center space-x-2.5 flex-wrap gap-y-2">
            <a href="{{ route('admin.leads.create') }}" class="px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition btn-tactile touch-target">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Lead
            </a>
            <a href="{{ route('admin.payments.create') }}" class="px-4 py-2.5 rounded-xl bg-tg-pink text-white text-xs font-bold hover:bg-tg-pink-hover shadow-sm transition btn-tactile touch-target">
                <svg class="w-4 h-4 mr-1.5 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Record Payment
            </a>
            <a href="{{ route('admin.payouts.index') }}" class="px-4 py-2.5 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm btn-tactile touch-target">
                Payouts Hub
            </a>
        </div>
    </div>

    <!-- KPI Summary Grid (Mobile 2-cols, Tablet 3-cols, Desktop 4-cols) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Verified Revenue -->
        <div class="col-span-2 sm:col-span-1 rounded-3xl bg-gradient-to-br from-emerald-500/10 via-white to-white border border-emerald-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Verified Revenue</span>
                <span class="p-1.5 rounded-xl bg-emerald-100 text-emerald-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-emerald-900 mt-2">₹{{ number_format($verifiedPaymentsAmount, 2) }}</div>
            <div class="text-[11px] text-emerald-700 font-semibold mt-1">From {{ number_format($totalPayments) }} client payments</div>
        </div>

        <!-- 2. Pending Client Payments -->
        <div class="rounded-3xl bg-gradient-to-br from-amber-500/10 via-white to-white border border-amber-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Pending Payments</span>
                <span class="p-1.5 rounded-xl bg-amber-100 text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-amber-900 mt-2">₹{{ number_format($pendingPaymentsAmount, 2) }}</div>
            <a href="{{ route('admin.payments.index', ['status' => 'submitted']) }}" class="text-[11px] text-amber-800 font-bold hover:underline mt-1 block">
                {{ $pendingPaymentsCount }} awaiting audit &rarr;
            </a>
        </div>

        <!-- 3. Commission Liability (Payable) -->
        <div class="rounded-3xl bg-gradient-to-br from-tg-purple/10 via-white to-white border border-tg-purple-border p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-tg-purple">Payable Commissions</span>
                <span class="p-1.5 rounded-xl bg-tg-purple-light text-tg-purple">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-purple mt-2">₹{{ number_format($approvedCommission, 2) }}</div>
            <div class="text-[11px] text-tg-purple font-semibold mt-1">Ready for withdrawal</div>
        </div>

        <!-- 4. Total Disbursed (Paid) -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Payouts Paid</span>
                <span class="p-1.5 rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-green mt-2">₹{{ number_format($paidCommission, 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Settled to partners</div>
        </div>

        <!-- 5. Total Partners & Active -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Partners Network</span>
                <span class="p-1.5 rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-2">{{ $totalPartners }}</div>
            <a href="{{ route('admin.partners.index') }}" class="text-[11px] text-tg-purple font-semibold hover:underline mt-1 block">
                {{ $activePartners }} active partners &rarr;
            </a>
        </div>

        <!-- 6. Total Leads & Won -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Leads Pipeline</span>
                <span class="p-1.5 rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-2">{{ $totalLeads }}</div>
            <div class="text-[11px] text-tg-green font-bold mt-1">{{ $wonLeads }} Won Deals ({{ $conversionRate }}% conv.)</div>
        </div>

        <!-- 7. Customers & Projects -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Deals &amp; Projects</span>
                <span class="p-1.5 rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-2">{{ $totalCustomers }} Clients</div>
            <div class="text-[11px] text-slate-500 font-semibold mt-1">{{ $activeProjects }} active projects (₹{{ number_format($totalProjectValue, 2) }})</div>
        </div>

        <!-- 8. Staff / Team Members -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Internal Staff</span>
                <span class="p-1.5 rounded-xl bg-slate-100 text-slate-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-2">{{ $totalTeamMembers }} Members</div>
            <a href="{{ route('admin.team-members.index') }}" class="text-[11px] text-tg-purple font-semibold hover:underline mt-1 block">
                Manage telecalling &rarr;
            </a>
        </div>
    </div>

    <!-- Compliance & Operational Attention Alerts (Pending KYC / Pending Withdrawals) -->
    @if ($pendingKycCount > 0 || $pendingWithdrawalsCount > 0)
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @if ($pendingKycCount > 0)
                <div class="bg-amber-50 border border-amber-200 rounded-3xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                            !
                        </div>
                        <div>
                            <div class="text-xs font-bold text-amber-900">{{ $pendingKycCount }} Partner KYC Reviews Pending</div>
                            <div class="text-[11px] text-amber-700">Bank accounts awaiting admin compliance approval</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.kyc.pending') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition shadow-sm btn-tactile">
                        Review KYC &rarr;
                    </a>
                </div>
            @endif

            @if ($pendingWithdrawalsCount > 0)
                <div class="bg-tg-purple-light border border-tg-purple-border rounded-3xl p-4 sm:p-5 flex items-center justify-between shadow-sm">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-2xl bg-tg-purple text-white flex items-center justify-center font-bold text-sm shadow-sm flex-shrink-0">
                            ₹
                        </div>
                        <div>
                            <div class="text-xs font-bold text-tg-purple">{{ $pendingWithdrawalsCount }} Withdrawal Requests Pending</div>
                            <div class="text-[11px] text-tg-purple/80">Total ₹{{ number_format($pendingWithdrawalsAmount, 2) }} ready for disbursement</div>
                        </div>
                    </div>
                    <a href="{{ route('admin.payouts.index') }}" class="px-3.5 py-1.5 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover transition shadow-sm btn-tactile">
                        Process Payouts &rarr;
                    </a>
                </div>
            @endif
        </div>
    @endif

    <!-- Analytical Visualizations & Performance Bars -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Pipeline & Service Distribution (Col 1 & 2) -->
        <div class="lg:col-span-2 bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-tg-dark">Demand by Service Vertical</h2>
                    <p class="text-xs text-slate-500">Distribution of referred customer project requirements.</p>
                </div>
                <span class="text-xs font-bold text-tg-purple">{{ $totalLeads }} Total Leads</span>
            </div>

            <div class="mt-4 space-y-3.5">
                @forelse ($serviceDistribution as $dist)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="font-bold text-slate-700">{{ $dist['service'] }}</span>
                            <span class="font-mono text-slate-500">{{ $dist['count'] }} leads ({{ $dist['percent'] }}%)</span>
                        </div>
                        <div class="w-full h-2.5 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-tg-purple to-tg-pink rounded-full transition-all duration-500" style="width: {{ max(4, $dist['percent']) }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">
                        No service distribution data available yet.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Top Referring Partners (Col 3) -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-tg-dark">Top Performing Partners</h2>
                    <p class="text-xs text-slate-500">Highest deal conversion volume.</p>
                </div>
                <a href="{{ route('admin.partners.index') }}" class="text-xs font-bold text-tg-purple hover:underline">All &rarr;</a>
            </div>

            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($topPartners as $tp)
                    <div class="py-2.5 flex items-center justify-between">
                        <div class="flex items-center space-x-2.5 overflow-hidden">
                            <div class="w-7 h-7 rounded-lg bg-tg-purple-light text-tg-purple font-bold text-xs flex items-center justify-center flex-shrink-0">
                                {{ strtoupper(substr($tp->user->name ?? 'P', 0, 1)) }}
                            </div>
                            <div class="truncate">
                                <div class="font-bold text-xs text-tg-dark truncate">{{ $tp->company_name ?: ($tp->user->name ?? 'Partner') }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $tp->partner_code }}</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                {{ $tp->won_leads_count }} Won
                            </span>
                            <div class="text-[10px] text-slate-400 mt-0.5">{{ $tp->leads_count }} total</div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No partner activity recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bottom Feeds (Recent Leads & Recent Client Payments) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Recent Referrals -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-tg-dark">Recently Submitted Leads</h2>
                    <p class="text-xs text-slate-500">New referrals awaiting sales telecalling.</p>
                </div>
                <a href="{{ route('admin.leads.index') }}" class="text-xs font-bold text-tg-purple hover:underline">View All &rarr;</a>
            </div>

            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($recentLeads as $rLead)
                    <div class="py-3 flex items-center justify-between hover:bg-slate-50 transition px-2 rounded-xl">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-tg-purple text-xs">{{ $rLead->lead_number }}</span>
                                <span class="font-bold text-xs text-tg-dark">{{ $rLead->customer_name }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $rLead->service_required }} &bull; Partner: <strong class="text-slate-700">{{ $rLead->partner->company_name ?? 'Direct' }}</strong>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                {{ $rLead->status->label() }}
                            </span>
                            <a href="{{ route('admin.leads.show', $rLead) }}" class="text-xs font-bold text-tg-purple hover:underline touch-target">
                                &rarr;
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No leads submitted yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Recent Payments Recorded -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 sm:p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-tg-dark">Recent Client Payments</h2>
                    <p class="text-xs text-slate-500">Latest financial transactions recorded in system.</p>
                </div>
                <a href="{{ route('admin.payments.index') }}" class="text-xs font-bold text-tg-purple hover:underline">View All &rarr;</a>
            </div>

            <div class="mt-3 divide-y divide-slate-100">
                @forelse ($recentPayments as $rPay)
                    <div class="py-3 flex items-center justify-between hover:bg-slate-50 transition px-2 rounded-xl">
                        <div>
                            <div class="flex items-center space-x-2">
                                <span class="font-mono font-bold text-xs text-tg-dark">₹{{ number_format($rPay->amount, 2) }}</span>
                                <span class="text-xs text-slate-600 font-semibold truncate max-w-[140px]">{{ $rPay->customer->name ?? 'Customer' }}</span>
                            </div>
                            <div class="text-[11px] text-slate-400 font-mono mt-0.5">
                                {{ $rPay->payment_number }} &bull; {{ $rPay->payment_date?->format('M d, Y') }}
                            </div>
                        </div>
                        <div>
                            @if ($rPay->status->value === 'verified')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    Verified
                                </span>
                            @elseif ($rPay->status->value === 'rejected')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-800 border border-rose-200">
                                    Rejected
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                    Pending
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-xs text-slate-400">No customer payments recorded yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
