@extends('layouts.app')

@section('title', 'Partner KYC & Bank Verification — Admin Control')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Partner KYC &amp; Bank Verification</h1>
            <p class="text-xs text-slate-500 mt-0.5">Authoritative compliance registry for verifying partner legal identity, PAN documentation, and payout bank accounts.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.partners.index') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                &larr; All Partners
            </a>
            <a href="{{ route('admin.payouts.index') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                Payout Ledger &rarr;
            </a>
        </div>
    </div>

    <!-- 6 KPI Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <!-- 1. Total Applications -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Total Submissions</span>
            <div class="text-xl font-extrabold text-tg-dark mt-1">{{ number_format($kpis['total_applications']) }}</div>
            <span class="text-[10px] text-slate-400 mt-0.5 block">All applications</span>
        </div>

        <!-- 2. Pending Verification -->
        <div class="bg-white rounded-2xl border border-amber-200/80 bg-amber-50/20 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-amber-600 block">Pending Verification</span>
            <div class="text-xl font-extrabold text-amber-700 mt-1">{{ number_format($kpis['pending_verification']) }}</div>
            <span class="text-[10px] text-amber-600/80 mt-0.5 block">Awaiting review</span>
        </div>

        <!-- 3. Under Review -->
        <div class="bg-white rounded-2xl border border-sky-200/80 bg-sky-50/20 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-sky-600 block">Under Review</span>
            <div class="text-xl font-extrabold text-sky-700 mt-1">{{ number_format($kpis['under_review']) }}</div>
            <span class="text-[10px] text-sky-600/80 mt-0.5 block">Active audit</span>
        </div>

        <!-- 4. Approved -->
        <div class="bg-white rounded-2xl border border-emerald-200/80 bg-emerald-50/20 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-emerald-600 block">Approved</span>
            <div class="text-xl font-extrabold text-emerald-700 mt-1">{{ number_format($kpis['approved']) }}</div>
            <span class="text-[10px] text-emerald-600/80 mt-0.5 block">Verified compliant</span>
        </div>

        <!-- 5. Rejected -->
        <div class="bg-white rounded-2xl border border-rose-200/80 bg-rose-50/20 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-rose-600 block">Rejected</span>
            <div class="text-xl font-extrabold text-rose-700 mt-1">{{ number_format($kpis['rejected']) }}</div>
            <span class="text-[10px] text-rose-600/80 mt-0.5 block">Withdrawals blocked</span>
        </div>

        <!-- 6. Correction Requested -->
        <div class="bg-white rounded-2xl border border-purple-200/80 bg-purple-50/20 p-4 shadow-sm">
            <span class="text-[10px] uppercase font-bold tracking-wider text-purple-600 block">Corrections</span>
            <div class="text-xl font-extrabold text-purple-700 mt-1">{{ number_format($kpis['correction_requested']) }}</div>
            <span class="text-[10px] text-purple-600/80 mt-0.5 block">Partner action needed</span>
        </div>
    </div>

    <!-- Status Tabs & Filter -->
    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-2">
        <div class="flex space-x-1 sm:space-x-2 text-xs">
            <a href="{{ route('admin.kyc.index') }}"
               class="px-3.5 py-2 rounded-xl font-bold transition {{ ! $status ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                All Submissions
            </a>
            <a href="{{ route('admin.kyc.pending') }}"
               class="px-3.5 py-2 rounded-xl font-bold transition {{ $status === 'pending' ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Pending Review ({{ $kpis['pending_verification'] }})
            </a>
            <a href="{{ route('admin.kyc.approved') }}"
               class="px-3.5 py-2 rounded-xl font-bold transition {{ $status === 'approved' ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Approved ({{ $kpis['approved'] }})
            </a>
            <a href="{{ route('admin.kyc.rejected') }}"
               class="px-3.5 py-2 rounded-xl font-bold transition {{ $status === 'rejected' ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Rejected ({{ $kpis['rejected'] }})
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="{{ route('admin.kyc.index') }}" class="flex items-center gap-3">
        <div class="relative flex-1">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Search by Partner Code, Company, Legal Name, Masked PAN, or Phone..."
                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring focus:ring-tg-purple/20 pl-9 pr-4 py-2.5 shadow-sm">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
            Search
        </button>
        @if (request('search') || $status)
            <a href="{{ route('admin.kyc.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Reset
            </a>
        @endif
    </form>

    <!-- Applications Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="py-3.5 px-4">Partner Profile</th>
                        <th class="py-3.5 px-4">Legal Name &amp; PAN</th>
                        <th class="py-3.5 px-4">Receiving Bank Details</th>
                        <th class="py-3.5 px-4">KYC Status</th>
                        <th class="py-3.5 px-4">Bank Status</th>
                        <th class="py-3.5 px-4">Version &amp; Submitted</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    @forelse ($applications as $app)
                        <tr class="hover:bg-slate-50/60 transition">
                            <!-- Partner Profile -->
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-tg-dark">{{ $app->partner->company_name }}</div>
                                <div class="font-mono text-[11px] text-tg-purple font-semibold">{{ $app->partner->partner_code }}</div>
                                <div class="text-[10px] text-slate-400">{{ $app->partner->phone }}</div>
                            </td>

                            <!-- Legal Name & PAN -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-tg-dark">{{ $app->legal_name }}</div>
                                <div class="font-mono text-[11px] text-slate-500 font-bold mt-0.5">{{ $app->masked_pan }}</div>
                                <div class="text-[10px] text-slate-400 capitalize">{{ $app->partner_type }}</div>
                            </td>

                            <!-- Bank Details -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-slate-800">{{ $app->bank_name ?: 'N/A' }}</div>
                                <div class="font-mono text-[11px] text-slate-500 font-bold">{{ $app->masked_account }}</div>
                                <div class="text-[10px] text-slate-400">IFSC: {{ $app->bank_ifsc_code ?: 'N/A' }}</div>
                            </td>

                            <!-- KYC Status -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $app->kyc_status->badgeClasses() }}">
                                    {{ $app->kyc_status->label() }}
                                </span>
                            </td>

                            <!-- Bank Status -->
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $app->bank_verification_status->badgeClasses() }}">
                                    {{ $app->bank_verification_status->label() }}
                                </span>
                            </td>

                            <!-- Version & Date -->
                            <td class="py-3.5 px-4 text-[11px]">
                                <span class="font-bold text-tg-dark block">v{{ $app->current_version }}</span>
                                <span class="text-slate-400 block font-mono">{{ $app->submitted_at?->format('M d, Y h:i A') ?? '—' }}</span>
                            </td>

                            <!-- Action -->
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('admin.kyc.show', $app) }}"
                                   class="inline-flex items-center px-3.5 py-1.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 text-xs">
                                No KYC applications found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
