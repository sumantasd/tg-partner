@extends('layouts.app')

@section('title', 'Commission Statements — Partner Portal')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Commission Statements</h1>
            <p class="text-xs text-slate-500 mt-0.5">Your official earnings ledger. Commissions are generated atomically when customer payments are verified.</p>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Earned to Date</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">₹{{ number_format($metrics['total_earned'], 2) }}</div>
        </div>
        <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-purple">Payable / Approved</div>
            <div class="text-2xl font-bold text-tg-purple mt-1">₹{{ number_format($metrics['payable'], 2) }}</div>
            <div class="text-[10px] text-tg-purple/80 font-semibold mt-1">Available for withdrawal</div>
        </div>
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Total Paid Out</div>
            <div class="text-2xl font-bold text-emerald-900 mt-1">₹{{ number_format($metrics['paid'], 2) }}</div>
            <div class="text-[10px] text-emerald-700 font-semibold mt-1">Disbursed to your bank</div>
        </div>
        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Pending Review</div>
            <div class="text-2xl font-bold text-amber-900 mt-1">₹{{ number_format($metrics['pending'], 2) }}</div>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('partner.commissions.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\CommissionStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ $status === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="billing_month" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Payment Periods / Months</option>
                    @foreach($availableBillingMonths ?? [] as $bm)
                        <option value="{{ $bm }}" {{ ($billingMonth ?? '') === $bm ? 'selected' : '' }}>{{ $bm }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Filter
                </button>
                @if($status || !empty($billingMonth))
                    <a href="{{ route('partner.commissions.index') }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Commission #</th>
                        <th class="px-5 py-3.5">Project & Customer</th>
                        <th class="px-5 py-3.5">Payment Period</th>
                        <th class="px-5 py-3.5">Eligible Payment</th>
                        <th class="px-5 py-3.5">Rate / Type</th>
                        <th class="px-5 py-3.5">Amount Earned</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($commissions as $comm)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('partner.commissions.show', $comm) }}" class="hover:underline">
                                    {{ $comm->commission_number }}
                                </a>
                                @if($comm->adjustment_type)
                                    <span class="block text-[9px] uppercase font-bold text-rose-600">
                                        [{{ $comm->adjustment_type }}]
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $comm->project?->project_title }}</div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $comm->project?->customer?->name ?? 'Referred Client' }}
                                    <span class="font-mono text-slate-400">({{ $comm->project?->project_code }})</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                @if($comm->billing_month || $comm->billing_period)
                                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-[11px]">
                                        📅 {{ $comm->billing_period ?? $comm->billing_month }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-mono text-slate-700">
                                ₹{{ number_format($comm->calculation_basis, 2) }}
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-purple">
                                @if($comm->commission_type === \App\Enums\CommissionType::Percentage)
                                    {{ $comm->rate_applied }}%
                                @else
                                    ₹{{ number_format($comm->rate_applied, 2) }} Fixed
                                @endif
                            </td>
                            <td class="px-5 py-4 font-bold text-sm {{ $comm->amount < 0 ? 'text-rose-600' : 'text-tg-green' }}">
                                ₹{{ number_format($comm->amount, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                                    {{ $comm->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('partner.commissions.show', $comm) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                    Statement &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                No commission earnings recorded yet. Once client payments are verified by Tech Googly, your commissions will appear here automatically.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($commissions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $commissions->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
