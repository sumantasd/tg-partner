@extends('layouts.app')

@section('title', 'Commission Details — ' . $commission->commission_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.commissions.index') }}" class="hover:text-tg-purple">Commissions</a>
        <span>/</span>
        <span class="font-mono font-bold text-tg-purple">{{ $commission->commission_number }}</span>
    </div>

    <!-- Ledger Entry Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold font-mono text-tg-dark tracking-tight">{{ $commission->commission_number }}</h1>
                    <span class="px-3 py-1 rounded-md text-xs font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                        {{ $commission->status->label() }}
                    </span>
                </div>
                <div class="text-3xl font-bold text-tg-green mt-3">
                    ₹{{ number_format($commission->amount, 2) }}
                    <span class="text-xs font-medium text-slate-400">INR</span>
                </div>
                <p class="text-xs text-slate-400 mt-1">Generated {{ $commission->created_at->format('M d, Y h:i A') }}</p>
            </div>

            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 min-w-[220px] text-xs space-y-2">
                <div class="flex justify-between">
                    <span class="text-slate-400">Milestone</span>
                    <span class="font-bold text-tg-dark">#{{ $commission->milestone_sequence }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Policy Source</span>
                    <span class="font-bold {{ $commission->policy_source === 'project_override' ? 'text-amber-700' : 'text-tg-purple' }}">
                        {{ $commission->policy_source === 'project_override' ? 'Project Override' : 'Global Policy' }}
                    </span>
                </div>
                @if($commission->billing_month || $commission->billing_period)
                    <div class="flex justify-between">
                        <span class="text-slate-400">Billing Period</span>
                        <span class="font-bold font-mono text-tg-dark">{{ $commission->billing_period ?? $commission->billing_month }}</span>
                    </div>
                @endif
                <div class="flex justify-between">
                    <span class="text-slate-400">Rate Snapshot</span>
                    <span class="font-bold text-tg-purple">
                        @if($commission->commission_type === \App\Enums\CommissionType::Percentage)
                            {{ $commission->rate_applied }}%
                        @else
                            ₹{{ number_format($commission->rate_applied, 2) }} Fixed
                        @endif
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Calculation Basis</span>
                    <span class="font-bold text-tg-dark">₹{{ number_format($commission->calculation_basis, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Calculation Formula Breakdown Box -->
        <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
            <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Authoritative Formula</span>
            <div class="font-mono text-tg-dark">
                ₹{{ number_format($commission->calculation_basis, 2) }} (Verified Payment) &times; {{ $commission->rate_applied }}% = <strong>₹{{ number_format($commission->amount, 2) }}</strong>
            </div>
            <p class="text-[11px] text-slate-500 mt-1">
                The rate snapshot is locked and will never be altered even if system default commission settings are changed in the future.
            </p>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Partner Beneficiary</span>
                <div class="font-bold text-tg-dark mt-1">{{ $commission->partner->company_name }}</div>
                <div class="font-mono text-[10px] text-tg-purple font-bold">{{ $commission->partner->partner_code }}</div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Linked Project</span>
                <div class="font-bold text-tg-dark mt-1">{{ $commission->project->project_title }}</div>
                <a href="{{ route('admin.projects.show', $commission->project_id) }}" class="font-mono text-tg-purple font-bold hover:underline text-[11px]">
                    {{ $commission->project->project_code }}
                </a>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400">Triggering Payment</span>
                @if($commission->customerPayment)
                    <div class="font-bold text-tg-dark mt-1">₹{{ number_format($commission->customerPayment->amount, 2) }}</div>
                    <a href="{{ route('admin.payments.show', $commission->customerPayment) }}" class="font-mono text-tg-purple font-bold hover:underline text-[11px]">
                        {{ $commission->customerPayment->payment_number }}
                    </a>
                @else
                    <div class="text-slate-400 mt-1">Milestone Direct</div>
                @endif
            </div>
        </div>

        @if($commission->notes)
            <div class="mt-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                <span class="font-bold block mb-1">Ledger Notes:</span>
                <p class="whitespace-pre-line">{{ $commission->notes }}</p>
            </div>
        @endif
    </div>
</div>
@endsection
