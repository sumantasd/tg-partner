@extends('layouts.app')

@section('title', 'Admin Lead Management & Pipeline')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Leads & Deal Pipeline</h1>
            <p class="text-xs text-slate-500 mt-1">
                Central control for partner referrals, consultant assignments, duplicate resolution, and follow-up monitoring.
            </p>
        </div>
        <a href="{{ route('admin.leads.create') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            + Add New Lead
        </a>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <a href="{{ route('admin.leads.index') }}"
           class="p-3.5 rounded-2xl bg-white border {{ !request()->hasAny(['status', 'duplicates_only', 'delayed', 'assigned_to']) ? 'border-tg-purple ring-2 ring-tg-purple/20' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Total Leads</div>
            <div class="text-xl font-bold text-tg-dark mt-1">{{ $counts['total'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['status' => 'submitted']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('status') === 'submitted' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Submitted</div>
            <div class="text-xl font-bold text-amber-600 mt-1">{{ $counts['submitted'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['assigned_to' => 'unassigned']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('assigned_to') === 'unassigned' ? 'border-tg-pink ring-2 ring-tg-pink/20' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">Unassigned</div>
            <div class="text-xl font-bold text-tg-pink mt-1">{{ $counts['submitted'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['status' => 'in_discussion']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('status') === 'in_discussion' ? 'border-tg-blue ring-2 ring-tg-blue/20' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-tg-blue uppercase tracking-wider">In Pipeline</div>
            <div class="text-xl font-bold text-tg-blue mt-1">{{ $counts['in_pipeline'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['status' => 'won']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('status') === 'won' ? 'border-tg-green ring-2 ring-tg-green/20' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-tg-green uppercase tracking-wider">Won / Converted</div>
            <div class="text-xl font-bold text-tg-green mt-1">{{ $counts['won'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['duplicates_only' => '1']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('duplicates_only') === '1' ? 'border-tg-pink ring-2 ring-tg-pink/20' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">Duplicate Flags</div>
            <div class="text-xl font-bold text-tg-pink mt-1">{{ $counts['duplicates'] }}</div>
        </a>
        <a href="{{ route('admin.leads.index', ['delayed' => '1']) }}"
           class="p-3.5 rounded-2xl bg-white border {{ request('delayed') === '1' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200' }} shadow-sm">
            <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Delayed Followups</div>
            <div class="text-xl font-bold text-amber-600 mt-1">{{ $counts['delayed'] }}</div>
        </a>
    </div>

    <!-- Search & Advanced Filter Toolbar -->
    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.leads.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Search Keywords</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Lead ID, Customer, Phone, Email, Company..."
                           class="w-full rounded-xl border border-slate-300 pl-9 pr-3 py-2 text-xs focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Deal Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none transition">
                    <option value="">All Statuses</option>
                    @foreach (\App\Enums\LeadStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Partner Referral</label>
                <select name="partner_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none transition">
                    <option value="">All Partners</option>
                    @foreach ($partners as $p)
                        <option value="{{ $p->id }}" {{ request('partner_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->company_name }} ({{ $p->partner_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Assigned Consultant</label>
                <select name="assigned_to" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none transition">
                    <option value="">All Consultants</option>
                    <option value="unassigned" {{ request('assigned_to') === 'unassigned' ? 'selected' : '' }}>Unassigned Only</option>
                    @foreach ($teamMembers as $tm)
                        <option value="{{ $tm->id }}" {{ request('assigned_to') == $tm->id ? 'selected' : '' }}>
                            {{ $tm->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover transition shadow-sm">
                    Filter Leads
                </button>
                <a href="{{ route('admin.leads.index') }}" class="px-3 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold text-xs hover:bg-slate-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                    <tr>
                        <th class="px-5 py-3.5">Lead Reference</th>
                        <th class="px-5 py-3.5">Partner Referral</th>
                        <th class="px-5 py-3.5">Customer & Organization</th>
                        <th class="px-5 py-3.5">Service & Budget</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Consultant</th>
                        <th class="px-5 py-3.5">Next Follow-up</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.leads.show', $lead) }}" class="hover:underline">
                                    {{ $lead->lead_number }}
                                </a>
                                @if($lead->is_duplicate_flag)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                                            ⚠️ Duplicate Flag
                                        </span>
                                    </div>
                                @endif
                                <div class="text-[11px] font-sans text-slate-400 mt-0.5">
                                    {{ $lead->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $lead->partner->company_name }}</div>
                                <div class="text-xs font-mono text-tg-purple font-semibold">{{ $lead->partner->partner_code }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $lead->customer_name }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $lead->customer_phone }}</div>
                                @if($lead->customer_email)
                                    <div class="text-[11px] text-slate-400">{{ $lead->customer_email }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-medium text-xs text-tg-dark">{{ $lead->service_required }}</div>
                                <div class="text-xs text-slate-500 font-semibold mt-0.5">
                                    {{ $lead->estimated_value ? '₹ ' . number_format($lead->estimated_value, 2) : 'Undisclosed' }}
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @php
                                    $statusBadge = match($lead->status) {
                                        \App\Enums\LeadStatus::Submitted => 'bg-amber-50 text-amber-700 border-amber-200',
                                        \App\Enums\LeadStatus::Assigned => 'bg-tg-blue-light text-tg-blue border-tg-blue-border',
                                        \App\Enums\LeadStatus::Contacted,
                                        \App\Enums\LeadStatus::InDiscussion => 'bg-tg-purple-light text-tg-purple border-tg-purple-border',
                                        \App\Enums\LeadStatus::QuotationSent,
                                        \App\Enums\LeadStatus::Negotiating => 'bg-tg-pink-light text-tg-pink border-tg-pink-border',
                                        \App\Enums\LeadStatus::Won => 'bg-tg-green-light text-tg-green border-tg-green-border',
                                        \App\Enums\LeadStatus::Lost,
                                        \App\Enums\LeadStatus::Rejected => 'bg-slate-100 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border {{ $statusBadge }}">
                                    {{ $lead->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-xs">
                                @if($lead->assignedTo)
                                    <span class="font-bold text-tg-dark">{{ $lead->assignedTo->name }}</span>
                                    <span class="block text-[10px] text-slate-400">{{ $lead->assigned_at?->format('M d') }}</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Needs Assignment
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-xs font-mono">
                                @if($lead->next_followup_date)
                                    @php
                                        $isDelayed = $lead->next_followup_date->isPast() && !in_array($lead->status, [\App\Enums\LeadStatus::Won, \App\Enums\LeadStatus::Lost, \App\Enums\LeadStatus::Rejected]);
                                    @endphp
                                    <span class="{{ $isDelayed ? 'text-tg-pink font-bold' : 'text-tg-dark font-medium' }}">
                                        {{ $lead->next_followup_date->format('M d, Y') }}
                                        @if($isDelayed)
                                            <span class="block text-[10px] text-tg-pink font-sans font-bold">⚠️ OVERDUE</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">None set</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.leads.show', $lead) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold text-tg-purple bg-tg-purple-light hover:bg-tg-purple hover:text-white border border-tg-purple-border transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">
                                No leads match the selected filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
