@extends('layouts.app')

@section('title', 'Project Details — ' . $project->project_code)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.projects.index') }}" class="hover:text-tg-purple">Projects</a>
            <span>/</span>
            <span class="font-mono font-bold text-tg-purple">{{ $project->project_code }}</span>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.projects.edit', $project) }}"
               class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                Edit Project
            </a>
            <a href="{{ route('admin.payments.create', ['project_id' => $project->id]) }}"
               class="px-4 py-2 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition">
                Record Customer Payment
            </a>
        </div>
    </div>

    <!-- Project Overview Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-tg-dark tracking-tight">{{ $project->project_title }}</h1>
                    <span class="font-mono px-3 py-1 rounded-md text-xs font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                        {{ $project->project_code }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded text-xs font-bold border {{ $project->status->badgeClasses() }}">
                        {{ $project->status->label() }}
                    </span>
                    @if($project->payment_type)
                        <span class="px-2.5 py-0.5 rounded text-xs font-bold {{ $project->payment_type->badgeClass() }}">
                            {{ $project->payment_type->label() }}
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-xs text-slate-500 flex flex-wrap items-center gap-3">
                    <span>Customer: <a href="{{ route('admin.customers.show', $project->customer_id) }}" class="font-bold text-tg-dark hover:underline">{{ $project->customer->name ?? $project->client_name }}</a></span>
                    &bull;
                    <span>Category: <strong class="text-slate-700">{{ $project->service_category ?? 'General' }}</strong></span>
                    &bull;
                    <span>Assigned: <strong class="text-slate-700">{{ $project->assignedTo?->name ?? 'Unassigned' }}</strong></span>
                </div>
            </div>

            <!-- Financial Summary Box -->
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-5 min-w-[280px]">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-500">Payment Type</span>
                    <span class="font-bold text-tg-dark">{{ $project->payment_type?->label() ?? 'Single Payment' }}</span>
                </div>
                <div class="flex justify-between items-center text-xs mt-2">
                    <span class="text-slate-500">Total Project Amount</span>
                    <span class="font-bold text-tg-dark">₹{{ number_format($project->final_deal_value, 2) }}</span>
                </div>
                @php
                    $totalReceived = $project->payments->sum('amount');
                    $verifiedPayments = $totalVerifiedPayments ?? $project->payments->where('status', \App\Enums\CustomerPaymentStatus::Verified)->sum('amount');
                    $outstanding = $totalOutstandingProject ?? max(0, $project->final_deal_value - $verifiedPayments);
                    $totalCommission = $project->commissions->where('status', '!=', \App\Enums\CommissionStatus::Cancelled)->sum('amount');
                @endphp
                <div class="flex justify-between items-center text-xs mt-2">
                    <span class="text-slate-500">Total Received (Gross)</span>
                    <span class="font-semibold text-slate-700">₹{{ number_format($totalReceived, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs mt-2">
                    <span class="text-slate-500">Total Verified</span>
                    <span class="font-bold text-tg-green">₹{{ number_format($verifiedPayments, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs mt-2">
                    <span class="text-slate-500">Outstanding Balance</span>
                    <span class="font-bold {{ $outstanding > 0 ? 'text-amber-600' : 'text-slate-500' }}">₹{{ number_format($outstanding, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs mt-2 pt-2 border-t border-slate-200">
                    <span class="text-slate-500">Total Commission Generated</span>
                    <span class="font-bold text-tg-purple">₹{{ number_format($totalCommission, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-100 text-xs">
            <h3 class="font-bold text-slate-700 mb-1">Project Description & Deliverables</h3>
            <p class="text-slate-600 leading-relaxed">{{ $project->project_description }}</p>
        </div>
    </div>

    <!-- Dynamic Commission Policy Terms Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h2 class="text-base font-bold text-tg-dark">Dynamic Commission Terms & Policy Hierarchy</h2>
                <p class="text-xs text-slate-500">Active commission resolution rules applicable to customer payments for this project.</p>
            </div>
            <a href="{{ route('admin.projects.edit', $project) }}" class="text-xs font-bold text-tg-purple hover:underline">
                Configure Terms &rarr;
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">
            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Policy Source</span>
                @if(($resolvedPolicy['source'] ?? '') === 'project_override')
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-amber-50 text-amber-800 border border-amber-300">
                        ★ Project Override
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold bg-purple-50 text-purple-800 border border-purple-200">
                        ⚡ Global Policy Default
                    </span>
                @endif
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Commission Type</span>
                <span class="text-xs font-bold text-tg-dark capitalize block">
                    {{ $resolvedPolicy['commission_type']->label() ?? $resolvedPolicy['commission_type'] }}
                </span>
                @if(!empty($resolvedPolicy['fixed_frequency']))
                    <span class="text-[10px] text-slate-500 font-medium">Frequency: {{ $resolvedPolicy['fixed_frequency']->label() }}</span>
                @endif
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Applicable Rate / Value</span>
                <span class="text-sm font-extrabold text-tg-purple block">
                    @if(($resolvedPolicy['commission_type']->value ?? $resolvedPolicy['commission_type']) === 'percentage')
                        {{ number_format($resolvedPolicy['rate_applied'], 2) }}%
                    @else
                        ₹{{ number_format($resolvedPolicy['rate_applied'], 2) }} Fixed
                    @endif
                </span>
                <span class="text-[10px] text-slate-400">Effective: {{ $resolvedPolicy['effective_policy_date'] ?? 'N/A' }}</span>
            </div>

            <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/70">
                <span class="text-[10px] uppercase font-bold text-slate-400 block mb-1">Policy Reference</span>
                <span class="text-xs font-mono font-bold text-slate-700 block">
                    {{ $resolvedPolicy['policy_code'] ?? 'PROJECT-CUSTOM' }}
                </span>
                @if(!empty($resolvedPolicy['policy_version']))
                    <span class="text-[10px] text-slate-400">Version v{{ $resolvedPolicy['policy_version'] }}</span>
                @endif
            </div>
        </div>

        @if(isset($policyHistory) && $policyHistory->count() > 0)
            <div class="mt-5 pt-4 border-t border-slate-100">
                <span class="text-xs font-bold text-slate-700 block mb-2">Commission Terms Change History:</span>
                <div class="space-y-1.5 text-xs text-slate-600">
                    @foreach($policyHistory as $log)
                        <div class="flex items-center space-x-2 text-[11px]">
                            <span class="font-mono text-slate-400">{{ $log->created_at->format('Y-m-d H:i') }}</span>
                            <span class="font-semibold text-slate-700">{{ $log->actor_name_snapshot ?? 'Admin' }}:</span>
                            <span>{{ $log->description }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if($project->isMonthlyPayment() || (isset($schedules) && $schedules->count() > 0))
    <!-- Monthly Payment Schedule Management Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden" id="schedules-section">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <h2 class="text-base font-bold text-tg-dark">Monthly Payment Schedule</h2>
                    <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-700">
                        {{ $schedules->count() }} Billing Months
                    </span>
                    @if($project->schedules_paused)
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            ⏸ All Paused
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500">Recurring client billing schedule, verification status, and monthly commission eligibility.</p>
            </div>

            <div class="flex items-center space-x-3 text-xs">
                @if($project->schedules_paused)
                    <form method="POST" action="{{ route('admin.projects.schedules.resume', $project) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold hover:bg-emerald-100 transition shadow-sm">
                            ▶ Resume All Schedules
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.projects.schedules.pause', $project) }}">
                        @csrf
                        <button type="submit" class="px-3 py-1.5 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 font-bold hover:bg-amber-100 transition shadow-sm">
                            ⏸ Pause All Schedules
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Schedule Summary Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-4 bg-slate-50/60 border-b border-slate-100 text-xs">
            <div class="bg-white p-3 rounded-xl border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Expected Schedule</span>
                <span class="text-sm font-bold text-tg-dark">₹{{ number_format($totalExpectedSchedule ?? 0, 2) }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Total Received & Verified</span>
                <span class="text-sm font-bold text-emerald-600">₹{{ number_format($totalReceivedSchedule ?? 0, 2) }}</span>
            </div>
            <div class="bg-white p-3 rounded-xl border border-slate-200">
                <span class="text-[10px] uppercase font-bold text-slate-400 block">Outstanding Balance</span>
                <span class="text-sm font-bold {{ ($totalOutstandingSchedule ?? 0) > 0 ? 'text-amber-600' : 'text-slate-500' }}">₹{{ number_format($totalOutstandingSchedule ?? 0, 2) }}</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">Billing Period</th>
                        <th class="px-5 py-3">Due Date</th>
                        <th class="px-5 py-3">Expected</th>
                        <th class="px-5 py-3">Received</th>
                        <th class="px-5 py-3">Outstanding</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($schedules as $sch)
                        <tr class="hover:bg-slate-50/60 transition {{ $sch->is_paused ? 'opacity-60 bg-slate-50/30' : '' }}">
                            <td class="px-5 py-3.5 font-mono text-slate-400">#{{ $sch->sequence_number }}</td>
                            <td class="px-5 py-3.5 font-bold text-tg-dark">
                                {{ $sch->billing_period }}
                                <span class="font-mono text-[10px] text-slate-400 font-normal block">{{ $sch->billing_month }}</span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600 font-mono">
                                {{ $sch->due_date->format('M d, Y') }}
                                @if($sch->isOverdue())
                                    <span class="text-[9px] font-bold text-rose-600 block">OVERDUE</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-700">₹{{ number_format($sch->expected_amount, 2) }}</td>
                            <td class="px-5 py-3.5 font-bold text-emerald-600">₹{{ number_format($sch->received_amount, 2) }}</td>
                            <td class="px-5 py-3.5 font-bold {{ (float)$sch->outstanding_amount > 0 ? 'text-amber-600' : 'text-slate-400' }}">
                                ₹{{ number_format($sch->outstanding_amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $sch->status->badgeClass() }}">
                                    {{ $sch->status->label() }}
                                </span>
                                @if($sch->is_paused)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 ml-1">
                                        Paused
                                    </span>
                                @endif
                                @if($sch->cancellation_reason)
                                    <span class="text-[10px] text-slate-400 block mt-0.5 truncate max-w-xs" title="{{ $sch->cancellation_reason }}">
                                        Reason: {{ $sch->cancellation_reason }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($sch->status !== \App\Enums\PaymentScheduleStatus::Verified && $sch->status !== \App\Enums\PaymentScheduleStatus::Cancelled && !$sch->is_paused)
                                        <button type="button" onclick="openRecordPaymentModal({{ $sch->id }}, '{{ $sch->billing_period }}', {{ $sch->outstanding_amount > 0 ? $sch->outstanding_amount : $sch->expected_amount }})"
                                                class="px-2.5 py-1 rounded-lg bg-tg-purple text-white font-bold text-[11px] hover:bg-tg-purple-hover transition shadow-sm">
                                            Record Payment
                                        </button>
                                    @endif

                                    @if($sch->status !== \App\Enums\PaymentScheduleStatus::Cancelled && $sch->status !== \App\Enums\PaymentScheduleStatus::Verified)
                                        @if($sch->is_paused)
                                            <form method="POST" action="{{ route('admin.schedules.resume', $sch) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 rounded text-[11px] font-bold text-emerald-600 hover:bg-emerald-50 transition">
                                                    Resume
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.schedules.pause', $sch) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="px-2 py-1 rounded text-[11px] font-bold text-amber-600 hover:bg-amber-50 transition">
                                                    Pause
                                                </button>
                                            </form>
                                        @endif
                                    @endif

                                    @if((float)$sch->received_amount <= 0 && $sch->status !== \App\Enums\PaymentScheduleStatus::Cancelled)
                                        <button type="button" onclick="openCancelScheduleModal({{ $sch->id }}, '{{ $sch->billing_period }}')"
                                                class="px-2 py-1 rounded text-[11px] font-bold text-rose-600 hover:bg-rose-50 transition">
                                            Cancel
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-6 text-center text-slate-400">
                                No monthly schedules generated for this project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Payments Section -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-tg-dark">Customer Payments</h2>
                <p class="text-xs text-slate-500">Payments recorded and verification state for this project.</p>
            </div>
            <a href="{{ route('admin.payments.create', ['project_id' => $project->id]) }}"
               class="text-xs font-bold text-tg-purple hover:underline">
                + Record New Payment
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Receipt / Payment #</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Method & Reference</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($project->payments as $payment)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="hover:underline">
                                    {{ $payment->payment_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">
                                {{ $payment->payment_date->format('M d, Y') }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-tg-dark capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</div>
                                @if($payment->transaction_reference)
                                    <div class="font-mono text-[10px] text-slate-400">Ref: {{ $payment->transaction_reference }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-bold text-tg-dark">
                                ₹{{ number_format($payment->amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if($payment->status === \App\Enums\CustomerPaymentStatus::Verified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                        ✓ Verified
                                    </span>
                                @elseif($payment->status === \App\Enums\CustomerPaymentStatus::Rejected)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        ✕ Rejected
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        ⏳ Pending Verification
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.payments.show', $payment) }}" class="font-bold text-tg-purple hover:underline">
                                    View / Verify &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No payments recorded yet for this project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Commissions Generated Section -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-tg-dark">Generated Partner Commissions</h2>
            <p class="text-xs text-slate-500">Commissions created automatically upon admin verification of customer payments.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Commission #</th>
                        <th class="px-5 py-3">Milestone / Billing Period</th>
                        <th class="px-5 py-3">Policy Source</th>
                        <th class="px-5 py-3">Basis Amount</th>
                        <th class="px-5 py-3">Rate Snapshot</th>
                        <th class="px-5 py-3">Commission Earned</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($project->commissions as $comm)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.commissions.show', $comm) }}" class="hover:underline">
                                    {{ $comm->commission_number }}
                                </a>
                            </td>
                            <td class="px-5 py-3.5 text-slate-600">
                                <div class="font-semibold text-tg-dark">{{ $comm->milestone_name }}</div>
                                @if($comm->billing_month || $comm->billing_period)
                                    <span class="inline-flex items-center px-2 py-0.5 mt-0.5 rounded text-[10px] bg-slate-100 text-slate-600 font-mono">
                                        📅 {{ $comm->billing_period ?? $comm->billing_month }}
                                    </span>
                                @endif
                                @if($comm->adjustment_type)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-rose-50 text-rose-700 font-bold uppercase ml-1">
                                        {{ $comm->adjustment_type }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                @if($comm->policy_source === 'project_override')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Project Override
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                        Global Policy @if($comm->policy_version)v{{ $comm->policy_version }}@endif
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                ₹{{ number_format($comm->calculation_basis, 2) }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-tg-purple">
                                @if($comm->commission_type === \App\Enums\CommissionType::Percentage)
                                    {{ $comm->rate_applied }}%
                                @else
                                    ₹{{ number_format($comm->rate_applied, 2) }}
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-bold {{ $comm->amount < 0 ? 'text-rose-600' : 'text-tg-green' }}">
                                ₹{{ number_format($comm->amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                                    {{ $comm->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No commissions generated yet. Commissions are created automatically when payments are verified.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Record Payment for Schedule -->
<div id="recordPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-tg-dark">Record Schedule Payment</h3>
                <p id="recordPaymentPeriod" class="text-xs text-slate-500 font-semibold"></p>
            </div>
            <button type="button" onclick="closeRecordPaymentModal()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>
        <form id="recordPaymentForm" method="POST" action="" class="space-y-4 mt-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Payment Amount (INR) *</label>
                <input type="number" step="0.01" name="amount" id="modalPaymentAmount" required min="0.01"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Payment Date *</label>
                <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-tg-purple">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Payment Method *</label>
                <select name="payment_method" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-tg-purple">
                    @foreach(\App\Enums\PaymentMethod::cases() as $method)
                        <option value="{{ $method->value }}" {{ $method->value === 'bank_transfer' ? 'selected' : '' }}>
                            {{ $method->label() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Transaction Reference / UTR</label>
                <input type="text" name="transaction_reference" placeholder="e.g. UTR-98218731"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-tg-purple font-mono">
            </div>
            <div>
                <label class="block font-bold text-slate-700 mb-1">Internal Notes</label>
                <input type="text" name="notes" placeholder="Optional payment remarks"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-tg-purple">
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeRecordPaymentModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-tg-purple text-white font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Record Payment (Pending) &rarr;
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Cancel Schedule Entry -->
<div id="cancelScheduleModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl max-w-md w-full mx-4 p-6 shadow-2xl border border-slate-100">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h3 class="text-base font-bold text-rose-700">Cancel Schedule Entry</h3>
                <p id="cancelSchedulePeriod" class="text-xs text-slate-500 font-semibold"></p>
            </div>
            <button type="button" onclick="closeCancelScheduleModal()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
        </div>
        <form id="cancelScheduleForm" method="POST" action="" class="space-y-4 mt-4 text-xs">
            @csrf
            <div>
                <label class="block font-bold text-slate-700 mb-1">Mandatory Cancellation Reason *</label>
                <textarea name="cancellation_reason" rows="3" required placeholder="State why this schedule is cancelled (e.g. Contract terminated early by mutual agreement)..."
                          class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-rose-500 text-xs"></textarea>
            </div>
            <div class="pt-3 border-t border-slate-100 flex items-center justify-end space-x-3">
                <button type="button" onclick="closeCancelScheduleModal()" class="px-4 py-2 rounded-xl border border-slate-300 text-slate-600 font-bold hover:bg-slate-50 transition">
                    Close
                </button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white font-bold hover:bg-rose-700 shadow-md transition">
                    Confirm Cancellation
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRecordPaymentModal(scheduleId, period, amount) {
    document.getElementById('recordPaymentForm').action = '/schedules/' + scheduleId + '/payment';
    document.getElementById('recordPaymentPeriod').textContent = 'For ' + period;
    document.getElementById('modalPaymentAmount').value = parseFloat(amount).toFixed(2);
    document.getElementById('recordPaymentModal').classList.remove('hidden');
}

function closeRecordPaymentModal() {
    document.getElementById('recordPaymentModal').classList.add('hidden');
}

function openCancelScheduleModal(scheduleId, period) {
    document.getElementById('cancelScheduleForm').action = '/schedules/' + scheduleId + '/cancel';
    document.getElementById('cancelSchedulePeriod').textContent = 'For ' + period;
    document.getElementById('cancelScheduleModal').classList.remove('hidden');
}

function closeCancelScheduleModal() {
    document.getElementById('cancelScheduleModal').classList.add('hidden');
}
</script>
@endsection
