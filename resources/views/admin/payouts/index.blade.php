@extends('layouts.app')

@section('title', 'Payouts & Withdrawals Management — Admin Control')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Payouts & Partner Withdrawals</h1>
            <p class="text-xs text-slate-500 mt-0.5">Authoritative settlement ledger for partner earnings, withdrawal approvals, and banking disbursements.</p>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
            <a href="{{ route('admin.commissions.index') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Commission Ledger
            </a>
        </div>
    </div>

    <!-- 6 Executive KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Total Settled -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Settled Disbursements</div>
            <div class="text-lg sm:text-xl font-bold text-tg-green mt-1">₹{{ number_format($metrics['total_settled_amount'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">{{ $metrics['completed_count'] }} paid vouchers</div>
        </div>

        <!-- 2. Pending Requests -->
        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Pending Review</div>
            <div class="text-lg sm:text-xl font-bold text-amber-900 mt-1">₹{{ number_format($metrics['pending_requests_val'], 2) }}</div>
            <div class="text-[10px] text-amber-700 font-semibold mt-0.5">{{ $metrics['pending_requests_cnt'] }} pending queue</div>
        </div>

        <!-- 3. Approved Amount -->
        <div class="rounded-2xl bg-tg-blue-light/60 border border-tg-blue-border p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-blue">Approved Queue</div>
            <div class="text-lg sm:text-xl font-bold text-tg-blue mt-1">₹{{ number_format($metrics['approved_val'], 2) }}</div>
            <div class="text-[10px] text-tg-blue/80 font-semibold mt-0.5">Ready for processing</div>
        </div>

        <!-- 4. Processing Amount -->
        <div class="rounded-2xl bg-tg-purple-light/60 border border-tg-purple-border p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-purple">Processing</div>
            <div class="text-lg sm:text-xl font-bold text-tg-purple mt-1">₹{{ number_format($metrics['processing_val'], 2) }}</div>
            <div class="text-[10px] text-tg-purple/80 font-semibold mt-0.5">Bank dispatch stage</div>
        </div>

        <!-- 5. Paid Amount -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Paid Amount</div>
            <div class="text-lg sm:text-xl font-bold text-tg-dark mt-1">₹{{ number_format($metrics['paid_amount'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Disbursed to partners</div>
        </div>

        <!-- 6. Rejected Requests -->
        <div class="rounded-2xl bg-rose-50/70 border border-rose-200 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-rose-800">Rejected Requests</div>
            <div class="text-lg sm:text-xl font-bold text-rose-900 mt-1">{{ $metrics['rejected_count'] }}</div>
            <div class="text-[10px] text-rose-700 font-semibold mt-0.5">Balance unreserved</div>
        </div>
    </div>

    <!-- Filter Tabs & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
        <div class="flex items-center space-x-2 border-b border-slate-100 text-xs font-bold overflow-x-auto w-full pb-2">
            <a href="{{ route('admin.payouts.index') }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'all' ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                All Requests
            </a>
            <a href="{{ route('admin.payouts.index', ['status' => 'pending']) }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Pending Review ({{ $metrics['pending_requests_cnt'] }})
            </a>
            <a href="{{ route('admin.payouts.index', ['status' => 'approved']) }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'approved' ? 'bg-tg-blue text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Approved
            </a>
            <a href="{{ route('admin.payouts.index', ['status' => 'processing']) }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'processing' ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Processing
            </a>
            <a href="{{ route('admin.payouts.index', ['status' => 'paid']) }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'paid' ? 'bg-tg-green text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Paid / Settled
            </a>
            <a href="{{ route('admin.payouts.index', ['status' => 'rejected']) }}"
               class="px-3.5 py-1.5 rounded-xl whitespace-nowrap transition {{ $status === 'rejected' ? 'bg-tg-pink text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Rejected ({{ $metrics['rejected_count'] }})
            </a>
        </div>

        <form method="GET" action="{{ route('admin.payouts.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex-1 w-full min-w-0">
                <input type="text" name="search" value="{{ $search }}"
                       placeholder="Search by request #, partner company, code, or bank UTR..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
            </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto flex-shrink-0">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm whitespace-nowrap">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.payouts.index', ['status' => $status]) }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 whitespace-nowrap">Request #</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Partner</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Amount (₹)</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Destination Snapshot</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">UTR / Reference</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Requested Date</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($withdrawals as $w)
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.payouts.show', $w) }}" class="hover:underline">
                                    {{ $w->request_number }}
                                </a>
                            </td>
                            <td class="px-5 py-4 min-w-[160px]">
                                <div class="font-bold text-tg-dark">{{ $w->partner->company_name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $w->partner->partner_code }}</div>
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-dark text-sm whitespace-nowrap">
                                ₹{{ number_format($w->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 min-w-[180px]">
                                <div class="font-semibold text-tg-dark capitalize">{{ str_replace('_', ' ', $w->payout_method) }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $w->getDestinationSummary() }}</div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $w->status->badgeClasses() }}">
                                    {{ $w->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 font-mono text-xs whitespace-nowrap">
                                @if($w->payout && $w->payout->transaction_reference)
                                    <span class="text-tg-purple font-semibold">{{ $w->payout->transaction_reference }}</span>
                                    <div class="text-[10px] text-slate-400">{{ $w->payout->payment_date?->format('M d, Y') }}</div>
                                @elseif($w->rejection_reason)
                                    <span class="text-tg-pink text-[11px]" title="{{ $w->rejection_reason }}">{{ Str::limit($w->rejection_reason, 20) }}</span>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap">
                                {{ $w->created_at->format('M d, Y') }}
                                <div class="text-[10px] text-slate-400">{{ $w->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.payouts.show', $w) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg bg-tg-purple text-white hover:bg-tg-purple-hover font-bold text-xs transition shadow-sm">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                No partner withdrawal requests found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($withdrawals->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $withdrawals->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
