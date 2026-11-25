@extends('layouts.app')

@section('title', 'Review Withdrawal #' . $withdrawal->request_number . ' — Admin Control')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.payouts.index') }}" class="text-xs font-bold text-tg-purple hover:underline">&larr; Back to Payouts Ledger</a>
                <span class="text-slate-300">&bull;</span>
                <span class="text-xs text-slate-500 font-mono">{{ $withdrawal->partner->partner_code }}</span>
            </div>
            <div class="flex items-center space-x-3 mt-1">
                <h1 class="text-2xl font-bold text-tg-dark tracking-tight">{{ $withdrawal->request_number }}</h1>
                <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border {{ $withdrawal->status->badgeClasses() }}">
                    {{ $withdrawal->status->label() }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">Submitted on {{ $withdrawal->created_at->format('M d, Y \a\t h:i A') }}</p>
        </div>

        <div class="flex items-center space-x-3 flex-shrink-0">
            @if ($withdrawal->payout && $withdrawal->payout->receipt_path)
                <a href="{{ route('admin.payouts.proof', $withdrawal) }}"
                   class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-green text-white text-xs font-bold hover:bg-tg-green-hover transition shadow-sm">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Payment Receipt
                </a>
            @endif
        </div>
    </div>

    <!-- Rejection Alert if Rejected -->
    @if ($withdrawal->isRejected())
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm text-xs text-rose-900">
            <h3 class="text-sm font-bold text-rose-800">Withdrawal Request Rejected</h3>
            <p class="mt-1 font-medium"><strong>Reason:</strong> {{ $withdrawal->rejection_reason }}</p>
            <p class="text-[11px] text-rose-700 mt-2">
                Reviewed by: {{ $withdrawal->reviewer->name ?? 'Admin' }} on {{ $withdrawal->reviewed_at?->format('M d, Y \a\t h:i A') }}.
                Reserved commissions have been returned to partner's available balance.
            </p>
        </div>
    @endif

    <!-- Completed Payout Voucher if Paid -->
    @if ($withdrawal->isPaid() && $withdrawal->payout)
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 shadow-sm text-xs text-emerald-900">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h3 class="text-sm font-bold text-emerald-950">Payout Successfully Disbursed</h3>
                    <p class="text-emerald-800 mt-0.5">
                        Bank UTR / Ref: <span class="font-mono font-bold text-emerald-900">{{ $withdrawal->payout->transaction_reference }}</span>
                        &bull; Date: {{ $withdrawal->payout->payment_date?->format('M d, Y') }}
                        &bull; Processed by: {{ $withdrawal->payout->processor->name ?? 'Admin' }}
                    </p>
                </div>
                @if ($withdrawal->payout->receipt_path)
                    <a href="{{ route('admin.payouts.proof', $withdrawal) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-emerald-600 text-white font-bold hover:bg-emerald-700 transition">
                        Receipt File &rarr;
                    </a>
                @endif
            </div>
        </div>
    @endif

    <!-- Partner & Destination Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Partner Information Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400">Partner Details</h2>
                <a href="{{ route('admin.partners.show', $withdrawal->partner) }}" class="text-xs font-bold text-tg-purple hover:underline">
                    Partner Profile &rarr;
                </a>
            </div>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">Company / Organization:</span>
                    <span class="font-bold text-tg-dark text-sm">{{ $withdrawal->partner->company_name }}</span>
                    <span class="font-mono text-tg-purple text-xs ml-1 font-bold">({{ $withdrawal->partner->partner_code }})</span>
                </div>
                <div>
                    <span class="text-slate-400 block text-[11px]">Contact Person:</span>
                    <span class="font-semibold text-tg-dark">{{ $withdrawal->partner->user->name }}</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Email:</span>
                        <span class="font-mono text-slate-700">{{ $withdrawal->partner->user->email }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Phone:</span>
                        <span class="font-mono text-slate-700">{{ $withdrawal->partner->phone }}</span>
                    </div>
                </div>
                @if ($withdrawal->partner_notes)
                    <div class="pt-2 border-t border-slate-100">
                        <span class="text-slate-400 block text-[11px]">Partner's Request Note:</span>
                        <span class="text-slate-700 italic">"{{ $withdrawal->partner_notes }}"</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Payout Destination Historical Snapshot Card -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
            <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-3">Payout Destination (Snapshot)</h2>

            @php $snap = $withdrawal->recipient_snapshot ?? []; @endphp

            <div class="space-y-3 text-xs">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Payout Method:</span>
                        <span class="font-bold text-tg-dark capitalize">{{ str_replace('_', ' ', $snap['payout_method'] ?? $withdrawal->payout_method) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-400 block text-[11px]">Requested Payout Amount:</span>
                        <span class="font-extrabold text-tg-green text-base">₹{{ number_format($withdrawal->amount, 2) }}</span>
                    </div>
                </div>

                @if (($snap['payout_method'] ?? $withdrawal->payout_method) === 'upi')
                    <div class="p-4 rounded-2xl bg-tg-purple-light/50 border border-tg-purple-border">
                        <span class="text-[10px] uppercase font-bold text-tg-purple block">UPI Virtual Payment Address</span>
                        <span class="font-mono font-bold text-tg-purple text-base mt-1 block select-all">{{ $snap['upi_id'] ?? 'N/A' }}</span>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <span class="text-slate-400 block text-[11px]">Account Holder:</span>
                            <span class="font-bold text-tg-dark">{{ $snap['account_holder'] ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 block text-[11px]">Bank Name:</span>
                            <span class="font-bold text-tg-dark">{{ $snap['bank_name'] ?? 'N/A' }}</span>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] uppercase font-bold text-slate-400">Account Number:</span>
                            <span class="font-mono text-tg-dark font-bold select-all text-sm">{{ $snap['account_number'] ?? $snap['masked_account'] ?? '••••' }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-2 pt-2 border-t border-slate-200">
                            <span class="text-[10px] uppercase font-bold text-slate-400">IFSC Code:</span>
                            <span class="font-mono text-tg-purple font-bold select-all text-sm">{{ $snap['ifsc_code'] ?? 'N/A' }}</span>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Admin Workflow & Action Panels -->
    @if (! $withdrawal->isPaid() && ! $withdrawal->isCancelled())
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h2 class="text-sm font-bold text-tg-dark">Administrative Actions</h2>
                <span class="text-xs text-slate-400">Current Stage: <strong class="text-tg-purple">{{ $withdrawal->status->label() }}</strong></span>
            </div>

            <!-- Stage 1 Actions: If Pending -->
            @if ($withdrawal->isPending())
                <div class="flex flex-wrap items-center gap-3">
                    <form action="{{ route('admin.payouts.approve', $withdrawal) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-tg-blue text-white text-xs font-bold hover:bg-tg-blue-hover transition shadow-sm">
                            ✓ Approve Withdrawal Request
                        </button>
                    </form>

                    <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                            class="px-5 py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 text-xs font-bold transition">
                        ✕ Reject Request...
                    </button>
                </div>
            @endif

            <!-- Stage 2 Actions: If Approved -->
            @if ($withdrawal->isApproved())
                <div class="flex flex-wrap items-center gap-3">
                    <form action="{{ route('admin.payouts.process', $withdrawal) }}" method="POST">
                        @csrf
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                            Mark as Processing (Sent to Bank)
                        </button>
                    </form>

                    <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                            class="px-5 py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 text-xs font-bold transition">
                        ✕ Reject Request...
                    </button>
                </div>
            @endif

            <!-- Stage 3 Actions: Complete Payout Form (if Approved or Processing) -->
            @if ($withdrawal->isApproved() || $withdrawal->isProcessing())
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-tg-dark mb-3">Mark Payout as Paid (Record Settlement)</h3>

                    <form method="POST" action="{{ route('admin.payouts.complete', $withdrawal) }}" enctype="multipart/form-data" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Bank Transaction Reference / UTR <span class="text-tg-pink">*</span>
                                </label>
                                <input type="text" name="transaction_reference" required
                                       placeholder="e.g. UTR123456789012, IMPS890123"
                                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs font-mono focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Payment Date <span class="text-tg-pink">*</span>
                                </label>
                                <input type="date" name="payment_date" value="{{ date('Y-m-d') }}" required
                                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Upload Payment Proof / Bank Slip (Optional)
                                </label>
                                <input type="file" name="receipt" accept=".pdf,.png,.jpg,.jpeg"
                                       class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-tg-purple-light file:text-tg-purple hover:file:bg-tg-purple hover:file:text-white transition">
                                <span class="text-[10px] text-slate-400">PDF, JPG, or PNG up to 5MB. Stored in private storage.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">
                                    Internal Admin Notes (Private)
                                </label>
                                <input type="text" name="admin_notes" placeholder="Private internal note..."
                                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                            </div>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-green text-white text-xs font-bold hover:bg-tg-green-hover transition shadow-sm">
                                Confirm &amp; Mark as Paid (Disbursed) &rarr;
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    @endif

    <!-- Linked Commissions Breakdown Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-tg-dark">Reserved Commissions Breakdown ({{ $withdrawal->commissions->count() }})</h2>
                <p class="text-xs text-slate-500 mt-0.5">Commissions locked for this withdrawal. Total sum: ₹{{ number_format($withdrawal->commissions->sum('amount'), 2) }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Commission #</th>
                        <th class="px-5 py-3">Project Title</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3">Basis / Rate</th>
                        <th class="px-5 py-3 text-right">Commission Amount</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($withdrawal->commissions as $comm)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-3 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.commissions.show', $comm) }}" class="hover:underline">
                                    {{ $comm->commission_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3 font-semibold text-tg-dark">{{ $comm->project->project_title ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $comm->project->customer->company_name ?? 'N/A' }}</td>
                            <td class="px-5 py-3 text-slate-600">
                                ₹{{ number_format($comm->calculation_basis, 2) }} &bull; {{ $comm->rate_applied }}%
                            </td>
                            <td class="px-5 py-3 text-right font-bold text-tg-green">₹{{ number_format($comm->amount, 2) }}</td>
                            <td class="px-5 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $comm->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-6 text-center text-slate-400">
                                No specific individual commissions mapped.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="reject-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
        <h3 class="text-base font-bold text-tg-dark">Reject Withdrawal Request</h3>
        <p class="text-xs text-slate-500 mt-1">
            Rejecting this request will immediately release all reserved commissions back to the partner's available balance.
        </p>

        <form method="POST" action="{{ route('admin.payouts.reject', $withdrawal) }}" class="mt-4 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Mandatory Rejection Reason <span class="text-tg-pink">*</span>
                </label>
                <textarea name="reason" rows="3" required placeholder="Provide clear reason to the partner (e.g. Invalid IFSC code, mismatched account holder name)..."
                          class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500 outline-none"></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Internal Admin Notes (Optional)
                </label>
                <input type="text" name="admin_notes" placeholder="Private internal note..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold transition shadow-sm">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
