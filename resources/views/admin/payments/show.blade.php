@extends('layouts.app')

@section('title', 'Payment Details — ' . $payment->payment_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.payments.index') }}" class="hover:text-tg-purple">Payments</a>
            <span>/</span>
            <span class="font-mono font-bold text-tg-purple">{{ $payment->payment_number }}</span>
        </div>

        <div class="flex items-center space-x-3">
            @if($payment->status === \App\Enums\CustomerPaymentStatus::Recorded)
                <a href="{{ route('admin.payments.edit', $payment) }}"
                   class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    Edit Payment Details
                </a>
            @endif
            @if($payment->proof_attachment_path)
                <a href="{{ route('admin.payments.proof', $payment) }}" target="_blank"
                   class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-tg-purple hover:bg-slate-50 transition shadow-sm flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    Download Proof Attachment
                </a>
            @endif
        </div>
    </div>

    <!-- Payment Main Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold font-mono text-tg-dark tracking-tight">{{ $payment->payment_number }}</h1>
                    @if($payment->status === \App\Enums\CustomerPaymentStatus::Verified)
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                            ✓ Verified
                        </span>
                    @elseif($payment->status === \App\Enums\CustomerPaymentStatus::Rejected)
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            ✕ Rejected
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                            ⏳ Pending Verification
                        </span>
                    @endif
                </div>

                <div class="text-3xl font-bold text-tg-dark mt-3">
                    ₹{{ number_format($payment->amount, 2) }}
                    <span class="text-xs font-medium text-slate-400">INR</span>
                </div>

                <p class="text-xs text-slate-500 mt-1">
                    Recorded on {{ $payment->created_at->format('M d, Y h:i A') }}
                    @if($payment->recordedBy) &bull; Logged by {{ $payment->recordedBy->name }} @endif
                </p>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 min-w-[240px] text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">Payment Date</span>
                    <span class="font-bold text-tg-dark">{{ $payment->payment_date->format('M d, Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Payment Stage</span>
                    <span class="font-bold text-tg-dark capitalize">{{ $payment->payment_type?->label() ?? 'Advance' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Payment Method</span>
                    <span class="font-bold text-tg-dark capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Transaction Ref</span>
                    <span class="font-mono font-bold text-tg-purple">{{ $payment->transaction_reference ?? 'None' }}</span>
                </div>
            </div>
        </div>

        <!-- Linked Entities Grid -->
        <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Customer</span>
                <div class="font-bold text-tg-dark mt-1">{{ $payment->customer->name ?? '—' }}</div>
                <a href="{{ route('admin.customers.show', $payment->customer_id) }}" class="text-tg-purple font-mono font-bold hover:underline text-[11px]">
                    {{ $payment->customer->customer_code ?? '' }}
                </a>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Project</span>
                <div class="font-bold text-tg-dark mt-1">{{ $payment->project->project_title ?? '—' }}</div>
                <a href="{{ route('admin.projects.show', $payment->project_id) }}" class="text-tg-purple font-mono font-bold hover:underline text-[11px]">
                    {{ $payment->project->project_code ?? '' }}
                </a>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Referred Partner</span>
                @if($payment->partner)
                    <div class="font-bold text-tg-dark mt-1">{{ $payment->partner->company_name }}</div>
                    <div class="text-[11px] font-mono text-tg-purple font-bold">{{ $payment->partner->partner_code }}</div>
                @else
                    <div class="text-slate-400 mt-1">Direct (Zero Commission)</div>
                @endif
            </div>
        </div>

        @if($payment->notes)
            <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                <span class="font-bold text-slate-700 block mb-1">Notes / Description:</span>
                <p class="text-slate-600 whitespace-pre-line">{{ $payment->notes }}</p>
            </div>
        @endif

        @if($payment->rejection_reason)
            <div class="mt-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-xs text-rose-800">
                <span class="font-bold block mb-1">Rejection / Reversal Audit Reason:</span>
                <p class="whitespace-pre-line">{{ $payment->rejection_reason }}</p>
            </div>
        @endif
    </div>

    <!-- Verification Actions / Linked Commission -->
    @if($payment->status === \App\Enums\CustomerPaymentStatus::Recorded)
        <div class="rounded-3xl bg-gradient-to-r from-tg-purple-light to-white border-2 border-tg-purple/30 p-6 sm:p-8 shadow-sm">
            <h2 class="text-lg font-bold text-tg-dark flex items-center">
                <svg class="w-5 h-5 mr-2 text-tg-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                Payment Verification Actions
            </h2>
            <p class="text-xs text-slate-600 mt-1">
                Verifying this payment confirms receipt in Tech Googly's bank account. This will atomically calculate and generate an authoritative commission ledger entry.
            </p>

            @php
                $rate = $payment->project->agreed_commission_rate > 0 ? $payment->project->agreed_commission_rate : 10.00;
                $estCommission = round(($payment->amount * $rate) / 100, 2);
            @endphp

            @if($payment->partner)
                <div class="mt-4 p-4 rounded-2xl bg-white border border-tg-purple-border/60 text-xs flex items-center justify-between">
                    <div>
                        <span class="text-slate-400 font-bold block text-[10px] uppercase">Automated Commission Preview</span>
                        <span class="text-tg-dark font-bold">Rate Applied: {{ $rate }}%</span>
                        <span class="text-slate-500"> on ₹{{ number_format($payment->amount, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 font-bold uppercase block">Commission Amount</span>
                        <span class="text-base font-bold text-tg-purple">₹{{ number_format($estCommission, 2) }}</span>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex flex-wrap items-center gap-4">
                <!-- Verify Action -->
                <form action="{{ route('admin.payments.verify', $payment) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-green text-white text-xs font-bold hover:bg-emerald-600 shadow-md transition flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Verify Payment & Generate Commission
                    </button>
                </form>

                <!-- Reject Action -->
                <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                        class="px-5 py-2.5 rounded-xl bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold hover:bg-rose-100 transition shadow-sm">
                    Reject Payment
                </button>
            </div>
        </div>

        <!-- Reject Modal -->
        <div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
                <h3 class="text-base font-bold text-rose-700">Reject Customer Payment</h3>
                <p class="text-xs text-slate-500 mt-1">Please provide a mandatory reason for rejecting this payment record.</p>

                <form action="{{ route('admin.payments.reject', $payment) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Reason *</label>
                        <textarea name="rejection_reason" required rows="3" placeholder="e.g. UTR reference not found in bank statement..."
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                                class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @elseif($payment->status === \App\Enums\CustomerPaymentStatus::Verified)
        <!-- Verified Information & Linked Commission -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-tg-dark">Linked Commission Record</h2>
                    <p class="text-xs text-slate-500">Verified by {{ $payment->verifiedBy->name ?? 'Admin' }} on {{ $payment->verified_at?->format('M d, Y h:i A') }}</p>
                </div>

                <!-- Reversal Action -->
                <button type="button" onclick="document.getElementById('reverseModal').classList.remove('hidden')"
                        class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-600 hover:text-rose-700 border border-slate-300 text-xs font-bold transition">
                    Reverse Payment
                </button>
            </div>

            @if($payment->commission)
                <div class="p-4 rounded-2xl bg-tg-purple-light border border-tg-purple-border/70 flex items-center justify-between text-xs">
                    <div>
                        <div class="text-[10px] uppercase font-bold text-tg-purple">Authoritative Ledger Entry</div>
                        <a href="{{ route('admin.commissions.show', $payment->commission) }}" class="font-mono font-bold text-sm text-tg-purple hover:underline">
                            {{ $payment->commission->commission_number }}
                        </a>
                        <div class="text-slate-600 mt-1">Snapshot Rate: <strong>{{ $payment->commission->rate_applied }}%</strong> &bull; Status: <span class="capitalize font-bold">{{ $payment->commission->status->label() }}</span></div>
                    </div>
                    <div class="text-right">
                        <div class="text-[10px] uppercase font-bold text-slate-400">Commission Amount</div>
                        <div class="text-xl font-bold text-tg-green">₹{{ number_format($payment->commission->amount, 2) }}</div>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-500">
                    This project has no referred partner; payment was verified with zero partner commission.
                </div>
            @endif
        </div>

        <!-- Reverse Modal -->
        <div id="reverseModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
                <h3 class="text-base font-bold text-rose-700">Reverse Verified Payment</h3>
                <p class="text-xs text-slate-500 mt-1">
                    Reversing will cancel the linked unpaid commission and mark the payment as reversed with an audit log. Paid commissions cannot be automatically reversed.
                </p>

                <form action="{{ route('admin.payments.reverse', $payment) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reversal Audit Reason *</label>
                        <textarea name="reversal_reason" required rows="3" placeholder="e.g. Client cheque bounced, bank reversal confirmed..."
                                  class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500"></textarea>
                    </div>

                    <div class="flex items-center justify-end space-x-2">
                        <button type="button" onclick="document.getElementById('reverseModal').classList.add('hidden')"
                                class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                            Cancel
                        </button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 transition">
                            Confirm Reversal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
