@extends('layouts.app')

@section('title', 'Customer Payments & Verification — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Payments & Commission Verification</h1>
            <p class="text-xs text-slate-500 mt-0.5">Authoritative customer payments ledger. Verifying a payment atomically calculates and generates the partner commission.</p>
        </div>
        <a href="{{ route('admin.payments.create') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Record Customer Payment
        </a>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Payments Recorded</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">{{ number_format($metrics['total_payments']) }}</div>
            <div class="text-[11px] text-slate-500 mt-1 font-semibold">₹{{ number_format($metrics['total_amount'], 2) }} total gross</div>
        </div>
        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Pending Verification</div>
            <div class="text-2xl font-bold text-amber-900 mt-1">{{ number_format($metrics['pending_count']) }}</div>
            <div class="text-[11px] text-amber-700 mt-1 font-semibold">₹{{ number_format($metrics['pending_amount'], 2) }} awaiting check</div>
        </div>
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Verified Revenue</div>
            <div class="text-2xl font-bold text-emerald-900 mt-1">₹{{ number_format($metrics['verified_amount'], 2) }}</div>
            <div class="text-[11px] text-emerald-700 mt-1 font-semibold">{{ number_format($metrics['verified_count']) }} verified payments</div>
        </div>
        <div class="rounded-2xl bg-rose-50/70 border border-rose-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-rose-800">Rejected Payments</div>
            <div class="text-2xl font-bold text-rose-900 mt-1">{{ number_format($metrics['rejected_count']) }}</div>
            <div class="text-[11px] text-rose-700 mt-1 font-semibold">Zero commission generated</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by payment #, UTR, customer, project..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>

            <div>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Verification Statuses</option>
                    @foreach(\App\Enums\CustomerPaymentStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ $status === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="method" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Payment Methods</option>
                    @foreach(\App\Enums\PaymentMethod::cases() as $case)
                        <option value="{{ $case->value }}" {{ $method === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Filter
                </button>
                @if($search || $status || $method || $customerId || $projectId || $dateFrom || $dateTo)
                    <a href="{{ route('admin.payments.index') }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Payment #</th>
                        <th class="px-5 py-3.5">Customer & Project</th>
                        <th class="px-5 py-3.5">Referral Partner</th>
                        <th class="px-5 py-3.5">Method & UTR</th>
                        <th class="px-5 py-3.5">Payment Date</th>
                        <th class="px-5 py-3.5">Amount</th>
                        <th class="px-5 py-3.5">Verification</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="hover:underline">
                                    {{ $payment->payment_number }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $payment->customer->name ?? 'Customer' }}</div>
                                <div class="text-[11px] text-slate-500">{{ $payment->project->project_title ?? 'Project' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                @if($payment->partner)
                                    <div class="font-bold text-tg-dark">{{ $payment->partner->company_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">{{ $payment->partner->partner_code }}</div>
                                @else
                                    <span class="text-slate-400">Direct (No Partner)</span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</div>
                                @if($payment->transaction_reference)
                                    <div class="font-mono text-[10px] text-slate-400">Ref: {{ $payment->transaction_reference }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600 whitespace-nowrap">
                                {{ $payment->payment_date->format('M d, Y') }}
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-dark whitespace-nowrap">
                                ₹{{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                @if($payment->status === \App\Enums\CustomerPaymentStatus::Verified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                        ✓ Verified
                                    </span>
                                    @if($payment->commission)
                                        <div class="text-[10px] font-mono font-bold text-tg-purple mt-0.5">
                                            COM: ₹{{ number_format($payment->commission->amount, 2) }}
                                        </div>
                                    @endif
                                @elseif($payment->status === \App\Enums\CustomerPaymentStatus::Rejected)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        ✕ Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 animate-pulse">
                                        ⏳ Needs Review
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                    Review &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                No customer payments found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($payments->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
