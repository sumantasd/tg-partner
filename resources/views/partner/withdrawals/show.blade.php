@extends('layouts.app')

@section('title', 'Withdrawal Statement #' . $withdrawal->request_number . ' — Partner Portal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('partner.withdrawals.index') }}" class="text-xs font-bold text-tg-purple hover:underline">&larr; All Withdrawals</a>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs text-slate-500">Statement</span>
            </div>
            <div class="flex items-center space-x-3 mt-1">
                <h1 class="text-2xl font-bold text-tg-dark tracking-tight">{{ $withdrawal->request_number }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border {{ $withdrawal->status->badgeClasses() }}">
                    {{ $withdrawal->status->label() }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Submitted on {{ $withdrawal->created_at->format('F d, Y \a\t h:i A') }}</p>
        </div>

        <div class="flex items-center space-x-3 flex-shrink-0">
            @if ($withdrawal->canBeCancelledByPartner())
                <form action="{{ route('partner.withdrawals.cancel', $withdrawal) }}" method="POST" onsubmit="return confirm('Cancel this pending withdrawal request? Your reserved balance will be restored immediately.');">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-tg-pink-light text-tg-pink border border-tg-pink-border hover:bg-tg-pink hover:text-white font-bold text-xs transition">
                        Cancel Request
                    </button>
                </form>
            @endif

            @if ($withdrawal->payout && $withdrawal->payout->receipt_path)
                <a href="{{ route('partner.withdrawals.proof', $withdrawal) }}"
                   class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-green text-white text-xs font-bold hover:bg-tg-green-hover transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Payment Receipt
                </a>
            @endif
        </div>
    </div>

    <!-- Rejection Alert -->
    @if ($withdrawal->isRejected())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm text-xs text-rose-900">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <h3 class="text-sm font-bold text-rose-800">Withdrawal Request Rejected</h3>
                    <p class="mt-1 font-medium"><strong>Reason:</strong> {{ $withdrawal->rejection_reason }}</p>
                    <p class="text-[11px] text-rose-700 mt-2">All commissions linked to this request have been unreserved and restored to your available withdrawal balance.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Completed Settlement Banner -->
    @if ($withdrawal->isPaid() && $withdrawal->payout)
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 shadow-sm text-xs text-emerald-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-emerald-950">Payment Disbursed Successfully</h3>
                        <p class="text-emerald-800 mt-0.5">
                            Bank Transaction UTR: <span class="font-mono font-bold text-emerald-900">{{ $withdrawal->payout->transaction_reference }}</span>
                            &bull; Dispatched on {{ $withdrawal->payout->payment_date?->format('M d, Y') ?? 'N/A' }}
                        </p>
                    </div>
                </div>
                @if ($withdrawal->payout->receipt_path)
                    <a href="{{ route('partner.withdrawals.proof', $withdrawal) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition">
                        View Voucher &rarr;
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Visual Status Timeline -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-5">Lifecycle Progress</h2>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 relative">
            <!-- Step 1: Request Submitted -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-full bg-tg-green text-white font-bold flex items-center justify-center text-xs flex-shrink-0">
                    ✓
                </div>
                <div>
                    <h3 class="text-xs font-bold text-tg-dark">Request Submitted</h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">{{ $withdrawal->created_at->format('M d, h:i A') }}</p>
                </div>
            </div>

            <!-- Step 2: Under Review / Approved -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-full font-bold flex items-center justify-center text-xs flex-shrink-0 {{ in_array($withdrawal->status->value, ['approved', 'processing', 'paid', 'settled']) ? 'bg-tg-green text-white' : ($withdrawal->isPending() ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-400') }}">
                    {{ in_array($withdrawal->status->value, ['approved', 'processing', 'paid', 'settled']) ? '✓' : '2' }}
                </div>
                <div>
                    <h3 class="text-xs font-bold {{ in_array($withdrawal->status->value, ['approved', 'processing', 'paid', 'settled']) ? 'text-tg-dark' : 'text-slate-400' }}">
                        Admin Approval
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $withdrawal->reviewed_at ? $withdrawal->reviewed_at->format('M d, h:i A') : ($withdrawal->isPending() ? 'In Review Queue' : '&mdash;') }}
                    </p>
                </div>
            </div>

            <!-- Step 3: Payment Processing -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-full font-bold flex items-center justify-center text-xs flex-shrink-0 {{ in_array($withdrawal->status->value, ['processing', 'paid', 'settled']) ? 'bg-tg-green text-white' : ($withdrawal->isApproved() ? 'bg-tg-blue text-white' : 'bg-slate-100 text-slate-400') }}">
                    {{ in_array($withdrawal->status->value, ['processing', 'paid', 'settled']) ? '✓' : '3' }}
                </div>
                <div>
                    <h3 class="text-xs font-bold {{ in_array($withdrawal->status->value, ['processing', 'paid', 'settled']) ? 'text-tg-dark' : 'text-slate-400' }}">
                        Processing &amp; Dispatch
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $withdrawal->isProcessing() ? 'Banking gateway dispatch' : ($withdrawal->isPaid() ? 'Processed' : '&mdash;') }}
                    </p>
                </div>
            </div>

            <!-- Step 4: Disbursed / Settled -->
            <div class="flex items-start space-x-3">
                <div class="w-8 h-8 rounded-full font-bold flex items-center justify-center text-xs flex-shrink-0 {{ $withdrawal->isPaid() ? 'bg-tg-green text-white' : ($withdrawal->isRejected() ? 'bg-rose-500 text-white' : ($withdrawal->isCancelled() ? 'bg-slate-300 text-slate-600' : 'bg-slate-100 text-slate-400')) }}">
                    {{ $withdrawal->isPaid() ? '✓' : ($withdrawal->isRejected() ? '✕' : ($withdrawal->isCancelled() ? '—' : '4')) }}
                </div>
                <div>
                    <h3 class="text-xs font-bold {{ $withdrawal->isPaid() ? 'text-tg-dark' : 'text-slate-400' }}">
                        {{ $withdrawal->isRejected() ? 'Rejected' : ($withdrawal->isCancelled() ? 'Cancelled' : 'Disbursed (Paid)') }}
                    </h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        {{ $withdrawal->settled_at ? $withdrawal->settled_at->format('M d, Y') : '&mdash;' }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Snapshot & Summary Details Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- 1. Payout Destination Historical Snapshot -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-3">Payout Destination (Snapshot)</h2>

            @php $snap = $withdrawal->recipient_snapshot ?? []; @endphp

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Payout Method:</span>
                    <span class="font-bold text-tg-dark capitalize">{{ str_replace('_', ' ', $snap['payout_method'] ?? $withdrawal->payout_method) }}</span>
                </div>

                @if (($snap['payout_method'] ?? $withdrawal->payout_method) === 'upi')
                    <div>
                        <span class="text-slate-400 block text-[11px]">UPI ID:</span>
                        <span class="font-mono font-bold text-tg-purple text-sm">{{ $snap['upi_id'] ?? 'N/A' }}</span>
                    </div>
                @else
                    <div>
                        <span class="text-slate-400 block text-[11px]">Account Holder Name:</span>
                        <span class="font-bold text-tg-dark">{{ $snap['account_holder'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Bank Name:</span>
                        <span class="font-bold text-tg-dark">{{ $snap['bank_name'] ?? 'N/A' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Account Number:</span>
                        <span class="font-mono font-bold text-tg-dark text-sm">{{ $snap['masked_account'] ?? '••••' }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">IFSC Code:</span>
                        <span class="font-mono font-bold text-tg-purple">{{ $snap['ifsc_code'] ?? 'N/A' }}</span>
                    </div>
                @endif

                @if ($withdrawal->partner_notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 block text-[11px]">Your Note to Accounts:</span>
                        <span class="text-slate-600 italic">"{{ $withdrawal->partner_notes }}"</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- 2. Financial Summary -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-3">Financial Settlement Breakdown</h2>

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">Requested Amount:</span>
                    <span class="font-bold text-tg-dark text-base">₹{{ number_format($withdrawal->amount, 2) }}</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">Processing Fees:</span>
                    <span class="font-semibold text-tg-green">₹0.00 (Zero Fee)</span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-slate-500">TDS / Withholding:</span>
                    <span class="font-semibold text-slate-600">₹0.00</span>
                </div>
                <div class="flex items-center justify-between pt-1">
                    <span class="font-bold text-tg-dark">Net Disbursed Amount:</span>
                    <span class="font-extrabold text-tg-green text-lg">₹{{ number_format($withdrawal->amount, 2) }}</span>
                </div>
                <div class="text-[11px] text-slate-400 mt-2">
                    Currency: <span class="font-mono font-bold text-slate-600">INR (₹)</span> &bull; 100% Authoritative database ledger verification.
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Linked Commissions Ledger -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-tg-dark">Reserved Commission Records ({{ $withdrawal->commissions->count() }})</h2>
                <p class="text-xs text-slate-500 mt-0.5">Commissions allocated to and settled through this withdrawal voucher.</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Commission #</th>
                        <th class="px-5 py-3">Project / Deal</th>
                        <th class="px-5 py-3">Milestone</th>
                        <th class="px-5 py-3">Rate</th>
                        <th class="px-5 py-3 text-right">Amount</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($withdrawal->commissions as $comm)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 font-mono font-bold text-tg-purple">
                                <a href="{{ route('partner.commissions.show', $comm) }}" class="hover:underline">
                                    {{ $comm->commission_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <div class="font-bold text-tg-dark">{{ $comm->project->project_title ?? 'Project' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $comm->project->customer->company_name ?? '' }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $comm->milestone_name }}</td>
                            <td class="px-5 py-3 font-bold text-tg-purple">{{ $comm->rate_applied }}%</td>
                            <td class="px-5 py-3 text-right font-bold text-tg-green">₹{{ number_format($comm->amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $comm->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-slate-400">
                                No specific individual commissions linked directly.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

