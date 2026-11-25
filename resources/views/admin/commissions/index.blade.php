@extends('layouts.app')

@section('title', 'Authoritative Commission Ledger — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Commission Ledger</h1>
            <p class="text-xs text-slate-500 mt-0.5">Authoritative financial records for partner commissions. Generated automatically upon payment verification with locked rate snapshots.</p>
        </div>
        <a href="{{ route('admin.settings.commission.index') }}"
           class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-tg-purple hover:bg-slate-50 shadow-sm transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Commission Rate Settings
        </a>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Commissions Generated</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">₹{{ number_format($metrics['total_generated'], 2) }}</div>
        </div>
        <div class="rounded-2xl bg-amber-50/70 border border-amber-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-800">Pending Review</div>
            <div class="text-2xl font-bold text-amber-900 mt-1">₹{{ number_format($metrics['pending_amount'], 2) }}</div>
        </div>
        <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-purple">Payable / Approved</div>
            <div class="text-2xl font-bold text-tg-purple mt-1">₹{{ number_format($metrics['payable_amount'], 2) }}</div>
        </div>
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Disbursed (Paid)</div>
            <div class="text-2xl font-bold text-emerald-900 mt-1">₹{{ number_format($metrics['paid_amount'], 2) }}</div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200">
        <a href="{{ route('admin.commissions.index', array_merge(request()->query(), ['tab' => 'ledger'])) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition {{ ($tab ?? 'ledger') === 'ledger' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Authoritative Ledger ({{ $commissions->total() }})
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->query(), ['tab' => 'project_summary'])) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition {{ ($tab ?? '') === 'project_summary' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Project-wise Summary
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->query(), ['tab' => 'partner_summary'])) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition {{ ($tab ?? '') === 'partner_summary' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Partner-wise Summary
        </a>
        <a href="{{ route('admin.commissions.index', array_merge(request()->query(), ['tab' => 'adjustments'])) }}"
           class="px-4 py-2.5 text-xs font-bold border-b-2 transition {{ ($tab ?? '') === 'adjustments' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
            Adjustments & Recoveries ({{ $metrics['adjustments_count'] ?? 0 }})
        </a>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.commissions.index') }}" class="grid grid-cols-1 sm:grid-cols-4 md:grid-cols-6 gap-3">
            <input type="hidden" name="tab" value="{{ $tab ?? 'ledger' }}">
            
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by commission #, milestone, billing period..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>

            <div>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\CommissionStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ $status === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="partner_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Partners</option>
                    @foreach($partners as $p)
                        <option value="{{ $p->id }}" {{ $partnerId == $p->id ? 'selected' : '' }}>
                            {{ $p->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="project_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Projects</option>
                    @foreach($projects as $prj)
                        <option value="{{ $prj->id }}" {{ $projectId == $prj->id ? 'selected' : '' }}>
                            {{ $prj->project_title }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="billing_month" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Billing Months</option>
                    @foreach($availableBillingMonths ?? [] as $bm)
                        <option value="{{ $bm }}" {{ $billingMonth === $bm ? 'selected' : '' }}>{{ $bm }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="commission_type" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Types</option>
                    <option value="percentage" {{ $commissionType === 'percentage' ? 'selected' : '' }}>Percentage</option>
                    <option value="fixed" {{ $commissionType === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                </select>
            </div>

            <div>
                <select name="policy_source" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Policy Sources</option>
                    <option value="global_policy" {{ $policySource === 'global_policy' ? 'selected' : '' }}>Global Policy</option>
                    <option value="project_override" {{ $policySource === 'project_override' ? 'selected' : '' }}>Project Override</option>
                </select>
            </div>

            <div class="flex items-center space-x-2 sm:col-span-2 md:col-span-2">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                <span class="text-xs text-slate-400">to</span>
                <input type="date" name="date_to" value="{{ $dateTo }}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>

            <div class="flex items-center space-x-2 sm:col-span-2 md:col-span-2">
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Filter
                </button>
                @if($search || $status || $partnerId || $projectId || $billingMonth || $commissionType || $policySource || $dateFrom || $dateTo)
                    <a href="{{ route('admin.commissions.index', ['tab' => $tab ?? 'ledger']) }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold whitespace-nowrap">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        @if(($tab ?? '') === 'project_summary')
            <!-- Project-wise Summary Table -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider">Project-wise Commission Aggregate</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Project Code & Title</th>
                            <th class="px-5 py-3">Client / Customer</th>
                            <th class="px-5 py-3">Total Commissions Generated</th>
                            <th class="px-5 py-3">Total Commission Value</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($projectSummaries as $ps)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4 font-bold text-tg-dark">
                                    <div class="text-tg-purple font-mono">{{ $ps->project?->project_code }}</div>
                                    <div>{{ $ps->project?->project_title }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $ps->project?->customer?->name ?? $ps->project?->client_name }}
                                </td>
                                <td class="px-5 py-4 font-mono font-bold text-slate-700">
                                    {{ $ps->count }} payments/milestones
                                </td>
                                <td class="px-5 py-4 font-bold text-tg-green">
                                    ₹{{ number_format($ps->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.commissions.index', ['project_id' => $ps->project_id]) }}"
                                       class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                        View Records &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">No project commission summaries available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif(($tab ?? '') === 'partner_summary')
            <!-- Partner-wise Summary Table -->
            <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider">Partner-wise Commission Aggregate</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3">Partner Code & Company</th>
                            <th class="px-5 py-3">Contact</th>
                            <th class="px-5 py-3">Total Commissions Generated</th>
                            <th class="px-5 py-3">Total Commission Earned</th>
                            <th class="px-5 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($partnerSummaries as $pts)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4 font-bold text-tg-dark">
                                    <div class="text-tg-purple font-mono">{{ $pts->partner?->partner_code }}</div>
                                    <div>{{ $pts->partner?->company_name }}</div>
                                </td>
                                <td class="px-5 py-4 text-slate-600">
                                    {{ $pts->partner?->user?->email }}
                                </td>
                                <td class="px-5 py-4 font-mono font-bold text-slate-700">
                                    {{ $pts->count }} commissions
                                </td>
                                <td class="px-5 py-4 font-bold text-tg-green">
                                    ₹{{ number_format($pts->total_amount, 2) }}
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <a href="{{ route('admin.commissions.index', ['partner_id' => $pts->partner_id]) }}"
                                       class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                        View Records &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-10 text-center text-slate-400">No partner commission summaries available.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @else
            <!-- Standard Ledger & Adjustments Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Commission #</th>
                            <th class="px-5 py-3.5">Partner</th>
                            <th class="px-5 py-3.5">Project & Period</th>
                            <th class="px-5 py-3.5">Policy Snapshot</th>
                            <th class="px-5 py-3.5">Payment Basis</th>
                            <th class="px-5 py-3.5">Rate Applied</th>
                            <th class="px-5 py-3.5">Commission</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($commissions as $comm)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="hover:underline">
                                        {{ $comm->commission_number }}
                                    </a>
                                    @if($comm->adjustment_type)
                                        <span class="block text-[9px] uppercase font-bold text-rose-600">
                                            [{{ $comm->adjustment_type }}]
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-tg-dark">{{ $comm->partner?->company_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">{{ $comm->partner?->partner_code }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold text-tg-dark">{{ $comm->project?->project_title }}</div>
                                    <div class="text-[11px] text-slate-500 flex items-center space-x-1.5 mt-0.5">
                                        @if($comm->billing_month || $comm->billing_period)
                                            <span class="font-mono bg-slate-100 px-1.5 py-0.5 rounded text-[10px]">
                                                📅 {{ $comm->billing_period ?? $comm->billing_month }}
                                            </span>
                                        @endif
                                        @if($comm->customerPayment)
                                            <span class="font-mono text-[10px]">
                                                Pay: <a href="{{ route('admin.payments.show', $comm->customerPayment) }}" class="underline hover:text-tg-purple">{{ $comm->customerPayment->payment_number }}</a>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    @if($comm->policy_source === 'project_override')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            Project Override
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                                            Global Policy @if($comm->policy_version)v{{ $comm->policy_version }}@endif
                                        </span>
                                    @endif
                                    @if($comm->commission_frequency)
                                        <div class="text-[9px] text-slate-400 mt-0.5 font-medium">Freq: {{ $comm->commission_frequency->label() }}</div>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-slate-700 whitespace-nowrap">
                                    ₹{{ number_format($comm->calculation_basis, 2) }}
                                </td>
                                <td class="px-5 py-4 font-bold text-tg-purple whitespace-nowrap">
                                    @if($comm->commission_type === \App\Enums\CommissionType::Percentage)
                                        {{ $comm->rate_applied }}%
                                    @else
                                        ₹{{ number_format($comm->rate_applied, 2) }}
                                    @endif
                                </td>
                                <td class="px-5 py-4 font-bold whitespace-nowrap {{ $comm->amount < 0 ? 'text-rose-600' : 'text-tg-green' }}">
                                    ₹{{ number_format($comm->amount, 2) }}
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                                        {{ $comm->status->label() }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.commissions.show', $comm) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                        Details &rarr;
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-slate-400">
                                    No commissions recorded yet matching criteria.
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
        @endif
    </div>
</div>
@endsection
