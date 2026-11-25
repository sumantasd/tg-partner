@extends('layouts.app')

@section('title', 'Commission Withdrawals & Payouts — Partner Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Withdrawals & Payouts</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage your commission withdrawals, review payment dispatch progress, and track bank disbursements.</p>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
            <a href="{{ route('partner.payout-settings.edit') }}"
               class="inline-flex items-center px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 text-xs font-semibold shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                Payout Settings
            </a>
            @if ($balanceSummary['available_balance'] >= 500 && ($balanceSummary['is_fully_verified'] ?? false))
                <a href="{{ route('partner.withdrawals.create') }}"
                   class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition whitespace-nowrap">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Request Withdrawal
                </a>
            @else
                <button type="button" disabled
                        title="{{ !($balanceSummary['is_fully_verified'] ?? false) ? 'KYC and Bank Verification must be approved first' : 'Minimum withdrawal is ₹500.00' }}"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl bg-slate-200 text-slate-400 text-xs font-bold cursor-not-allowed whitespace-nowrap">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Request Withdrawal
                </button>
            @endif
        </div>
    </div>

    <!-- Compliance Gate Warning if KYC / Bank is not fully verified -->
    @if (! ($balanceSummary['is_fully_verified'] ?? false))
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900 flex items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <span class="font-bold">Compliance Verification Required:</span>
                    <span>
                        @if (! ($balanceSummary['is_kyc_approved'] ?? false) && ! ($balanceSummary['is_bank_verified'] ?? false))
                            Both your KYC identity documents and bank account verification must be approved before you can submit commission withdrawal requests.
                        @elseif (! ($balanceSummary['is_kyc_approved'] ?? false))
                            Your KYC identity documents are pending verification and approval.
                        @else
                            Your bank account details are pending verification and approval.
                        @endif
                    </span>
                </div>
            </div>
            <a href="{{ route('partner.kyc.show') }}" class="px-3 py-1.5 rounded-lg bg-amber-600 text-white font-bold hover:bg-amber-700 transition flex-shrink-0">
                View Verification Status &rarr;
            </a>
        </div>
    @endif

    <!-- Payout Details Incomplete Notice -->
    @if (! $hasPayoutDetails)
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-4 text-xs text-amber-900 flex items-start sm:items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center space-x-2.5">
                <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <span><strong>Bank / UPI details required:</strong> Please configure your bank account or UPI details to enable withdrawal requests.</span>
            </div>
            <a href="{{ route('partner.payout-settings.edit') }}" class="px-3 py-1.5 rounded-lg bg-amber-600 text-white font-bold hover:bg-amber-700 transition flex-shrink-0">
                Configure Now &rarr;
            </a>
        </div>
    @endif

    <!-- 6 Financial Summary Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Total Commission Earned -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Earned</div>
            <div class="text-lg sm:text-xl font-bold text-tg-dark mt-1">₹{{ number_format($balanceSummary['total_earned'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">All time verified</div>
        </div>

        <!-- 2. Available Withdrawal Balance -->
        <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-purple">Available Balance</div>
            <div class="text-lg sm:text-xl font-extrabold text-tg-purple mt-1">₹{{ number_format($balanceSummary['available_balance'], 2) }}</div>
            <div class="text-[10px] text-tg-purple/80 font-semibold mt-0.5">Ready to withdraw</div>
        </div>

        <!-- 3. Reserved in Pending -->
        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Reserved in Queue</div>
            <div class="text-lg sm:text-xl font-bold text-amber-900 mt-1">₹{{ number_format($balanceSummary['reserved_in_pending'], 2) }}</div>
            <div class="text-[10px] text-amber-700 font-semibold mt-0.5">Pending disbursement</div>
        </div>

        <!-- 4. Total Commission Paid -->
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Commission Paid</div>
            <div class="text-lg sm:text-xl font-bold text-emerald-900 mt-1">₹{{ number_format($balanceSummary['total_paid'], 2) }}</div>
            <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">Settled to your bank</div>
        </div>

        <!-- 5. Pending Commission -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Pending Review</div>
            <div class="text-lg sm:text-xl font-bold text-slate-700 mt-1">₹{{ number_format($balanceSummary['pending_commission'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Client check stage</div>
        </div>

        <!-- 6. Total Withdrawn -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Withdrawn</div>
            <div class="text-lg sm:text-xl font-bold text-tg-dark mt-1">₹{{ number_format($balanceSummary['total_withdrawn'], 2) }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5">Gross disbursements</div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 text-xs font-bold overflow-x-auto w-full pb-1">
        <a href="{{ route('partner.withdrawals.index') }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ !request()->has('status') ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            All Requests
        </a>
        <a href="{{ route('partner.withdrawals.index', ['status' => 'pending']) }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ request('status') === 'pending' ? 'border-amber-500 text-amber-700' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Pending Review
        </a>
        <a href="{{ route('partner.withdrawals.index', ['status' => 'approved']) }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ request('status') === 'approved' ? 'border-tg-blue text-tg-blue' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Approved
        </a>
        <a href="{{ route('partner.withdrawals.index', ['status' => 'processing']) }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ request('status') === 'processing' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Processing
        </a>
        <a href="{{ route('partner.withdrawals.index', ['status' => 'paid']) }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ in_array(request('status'), ['paid', 'settled']) ? 'border-tg-green text-tg-green' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Paid / Settled
        </a>
        <a href="{{ route('partner.withdrawals.index', ['status' => 'rejected']) }}"
           class="px-4 py-2.5 border-b-2 whitespace-nowrap {{ request('status') === 'rejected' ? 'border-tg-pink text-tg-pink' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Rejected
        </a>
    </div>

    <!-- Withdrawals Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 whitespace-nowrap">Request #</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Requested Date</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Amount</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Method & Destination</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">UTR / Reference</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($withdrawals as $w)
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('partner.withdrawals.show', $w) }}" class="hover:underline">
                                    {{ $w->request_number }}
                                </a>
                            </td>
                            <td class="px-5 py-4 text-slate-600 whitespace-nowrap">
                                {{ $w->created_at->format('M d, Y') }}
                                <div class="text-[10px] text-slate-400">{{ $w->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-green text-sm whitespace-nowrap">
                                ₹{{ number_format($w->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
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
                                    <span class="text-tg-pink text-[11px]" title="{{ $w->rejection_reason }}">{{ Str::limit($w->rejection_reason, 25) }}</span>
                                @else
                                    <span class="text-slate-400">&mdash;</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right space-x-1.5 whitespace-nowrap">
                                <a href="{{ route('partner.withdrawals.show', $w) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-tg-purple hover:bg-slate-50 font-semibold transition text-xs">
                                    View Statement
                                </a>
                                @if($w->canBeCancelledByPartner())
                                    <form action="{{ route('partner.withdrawals.cancel', $w) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to cancel this pending withdrawal request? Your reserved commission balance will be released immediately.');">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-tg-pink-light text-tg-pink border border-tg-pink-border hover:bg-tg-pink hover:text-white font-bold text-xs transition">
                                            Cancel
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <div class="max-w-sm mx-auto">
                                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    <p class="font-bold text-slate-600 text-sm">No withdrawal requests found</p>
                                    <p class="text-xs text-slate-400 mt-1">Once you have an available commission balance of ₹500 or more, you can submit your withdrawal requests here.</p>
                                    @if ($balanceSummary['available_balance'] >= 500)
                                        <a href="{{ route('partner.withdrawals.create') }}" class="mt-4 inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold shadow-sm">
                                            Request First Withdrawal
                                        </a>
                                    @endif
                                </div>
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
