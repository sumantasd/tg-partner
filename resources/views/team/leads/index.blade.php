@extends('layouts.app')

@section('title', 'Sales & Telecalling CRM Workspace')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    
    <!-- Top CRM Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-[0_2px_12px_rgba(0,0,0,0.02)]">
        <div>
            <div class="flex items-center space-x-2.5">
                <h1 class="text-xl sm:text-2xl font-extrabold text-tg-dark tracking-tight">Sales &amp; Telecalling Workspace</h1>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                    CRM Agent
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Manage assigned customer leads, log telecalling interactions, schedule follow-ups, and update deal progress.
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <span class="text-xs text-slate-500 font-medium">Logged as: <strong class="text-tg-dark">{{ auth()->user()->name }}</strong></span>
        </div>
    </div>

    <!-- Staff CRM KPI Grid (Mobile 2-cols, Tablet 3-cols, Desktop 6-cols) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Assigned Leads -->
        <a href="{{ route('team.leads.index') }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ !request()->hasAny(['status', 'interest', 'followup_today', 'overdue']) ? 'border-tg-purple ring-2 ring-tg-purple/20' : 'border-slate-200/80 hover:border-slate-300' }}">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Assigned Leads</div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-1">{{ $counts['total_assigned'] }}</div>
        </a>

        <!-- 2. Today's Follow-ups -->
        <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ request('followup_today') === '1' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200/80 hover:border-amber-300' }}">
            <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Today's Calls</div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 mt-1">{{ $counts['today_followup'] }}</div>
        </a>

        <!-- 3. Overdue Follow-ups -->
        <a href="{{ route('team.leads.index', ['overdue' => '1']) }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ request('overdue') === '1' ? 'border-tg-pink ring-2 ring-tg-pink/20' : 'border-slate-200/80 hover:border-tg-pink-border' }}">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">Overdue Calls</div>
            <div class="text-xl sm:text-2xl font-black text-tg-pink mt-1">{{ $counts['overdue_followup'] }}</div>
        </a>

        <!-- 4. Hot Leads -->
        <a href="{{ route('team.leads.index', ['interest' => 'hot']) }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ request('interest') === 'hot' ? 'border-tg-pink ring-2 ring-tg-pink/20' : 'border-slate-200/80 hover:border-tg-pink-border' }}">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">🔥 Hot Leads</div>
            <div class="text-xl sm:text-2xl font-black text-tg-pink mt-1">{{ $counts['hot_leads'] }}</div>
        </a>

        <!-- 5. Active Pipeline -->
        <a href="{{ route('team.leads.index', ['status' => 'in_discussion']) }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ request('status') === 'in_discussion' ? 'border-tg-blue ring-2 ring-tg-blue/20' : 'border-slate-200/80 hover:border-tg-blue-border' }}">
            <div class="text-[10px] font-bold text-tg-blue uppercase tracking-wider">In Pipeline</div>
            <div class="text-xl sm:text-2xl font-black text-tg-blue mt-1">{{ $counts['in_discussion'] }}</div>
        </a>

        <!-- 6. Won Deals -->
        <a href="{{ route('team.leads.index', ['status' => 'won']) }}"
           class="p-4 rounded-3xl bg-white border transition shadow-sm btn-tactile touch-target flex flex-col justify-center {{ request('status') === 'won' ? 'border-tg-green ring-2 ring-tg-green/20' : 'border-slate-200/80 hover:border-tg-green-border' }}">
            <div class="text-[10px] font-bold text-tg-green uppercase tracking-wider">Won Deals</div>
            <div class="text-xl sm:text-2xl font-black text-tg-green mt-1">{{ $counts['won'] }}</div>
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('team.leads.index') }}" class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by Lead ID, Customer Name, Phone, Company..."
                       class="w-full rounded-2xl border border-slate-300 pl-10 pr-4 py-2.5 text-xs focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 outline-none transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <select name="status" class="rounded-2xl border border-slate-300 px-3 py-2.5 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Statuses</option>
                    @foreach (\App\Enums\LeadStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>
                            {{ $st->label() }}
                        </option>
                    @endforeach
                </select>
                <select name="sla" class="rounded-2xl border border-slate-300 px-3 py-2.5 text-xs bg-white focus:border-tg-purple outline-none font-semibold">
                    <option value="">All SLA Statuses</option>
                    <option value="overdue" {{ request('sla') === 'overdue' ? 'selected' : '' }}>⚠️ Overdue SLA</option>
                    <option value="due_soon" {{ request('sla') === 'due_soon' ? 'selected' : '' }}>⏳ Due Soon</option>
                    <option value="pending_contact" {{ request('sla') === 'pending_contact' ? 'selected' : '' }}>📞 1st Contact Pending</option>
                </select>
                <select name="interest" class="rounded-2xl border border-slate-300 px-3 py-2.5 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Interest Levels</option>
                    @foreach (\App\Enums\LeadInterest::cases() as $int)
                        <option value="{{ $int->value }}" {{ request('interest') === $int->value ? 'selected' : '' }}>
                            {{ $int->label() }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="px-5 py-2.5 rounded-2xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm btn-tactile touch-target">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'interest', 'followup_today', 'overdue']))
                    <a href="{{ route('team.leads.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-tg-dark touch-target">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Responsive Layout: Native Mobile Cards for Small Screens (< md) -->
    <div class="block md:hidden space-y-3.5">
        @forelse ($leads as $lead)
            @php
                $cleanPhone = preg_replace('/[^0-9]/', '', $lead->customer_phone);
                if (strlen($cleanPhone) === 10) { $cleanPhone = '91' . $cleanPhone; }

                $isDelayed = $lead->next_followup_date && $lead->next_followup_date->isPast() && !in_array($lead->status, [\App\Enums\LeadStatus::Won, \App\Enums\LeadStatus::Lost, \App\Enums\LeadStatus::Rejected]);
                $isToday = $lead->next_followup_date && $lead->next_followup_date->isToday();
            @endphp
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-3">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="font-mono font-bold text-xs text-tg-purple block">{{ $lead->lead_number }}</span>
                        <h2 class="text-sm font-bold text-tg-dark mt-0.5">{{ $lead->customer_name }}</h2>
                        @if($lead->customer_company)
                            <div class="text-[11px] text-slate-500">{{ $lead->customer_company }}</div>
                        @endif
                    </div>
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                            {{ $lead->status->label() }}
                        </span>
                    </div>
                </div>

                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-semibold">Service Requirement</div>
                        <div class="font-semibold text-tg-dark">{{ $lead->service_required }}</div>
                    </div>
                    @if($lead->estimated_value)
                        <div class="text-right">
                            <div class="text-[10px] text-slate-400 uppercase font-semibold">Deal Budget</div>
                            <div class="font-mono font-bold text-tg-dark">₹{{ number_format($lead->estimated_value, 2) }}</div>
                        </div>
                    @endif
                </div>

                @if($lead->next_followup_date)
                    <div class="flex items-center justify-between p-2.5 rounded-2xl {{ $isDelayed ? 'bg-rose-50 border border-rose-200 text-rose-800' : ($isToday ? 'bg-amber-50 border border-amber-200 text-amber-800' : 'bg-slate-50 text-slate-600') }} text-xs">
                        <span class="font-bold flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Follow-up: {{ $lead->next_followup_date->format('M d, Y') }}
                        </span>
                        @if($isDelayed)
                            <span class="text-[10px] font-black uppercase text-rose-600">Overdue</span>
                        @elseif($isToday)
                            <span class="text-[10px] font-black uppercase text-amber-600">Today</span>
                        @endif
                    </div>
                @endif

                <!-- One-Tap Action Buttons on Mobile -->
                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-slate-100">
                    <a href="tel:{{ $lead->customer_phone }}"
                       class="py-2 px-1 rounded-xl bg-emerald-50 text-emerald-800 font-bold text-xs flex items-center justify-center border border-emerald-200 hover:bg-emerald-100 transition btn-tactile touch-target">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        Call
                    </a>
                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank"
                       class="py-2 px-1 rounded-xl bg-emerald-600 text-white font-bold text-xs flex items-center justify-center hover:bg-emerald-700 transition btn-tactile touch-target">
                        WhatsApp
                    </a>
                    <a href="{{ route('team.leads.show', $lead) }}"
                       class="py-2 px-1 rounded-xl bg-tg-purple text-white font-bold text-xs flex items-center justify-center hover:bg-tg-purple-hover transition btn-tactile touch-target">
                        Dossier &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-3xl p-8 text-center text-xs text-slate-400 border border-slate-200/80">
                No customer leads assigned to your portfolio yet.
            </div>
        @endforelse
    </div>

    <!-- Desktop Data Table (>= md) -->
    <div class="hidden md:block bg-white rounded-3xl shadow-sm border border-slate-200/80 overflow-hidden">
        <div class="table-scroll-container">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                    <tr>
                        <th class="px-6 py-3.5">Lead Reference</th>
                        <th class="px-6 py-3.5">Customer &amp; Organization</th>
                        <th class="px-6 py-3.5">Contact Number</th>
                        <th class="px-6 py-3.5">Service Requested</th>
                        <th class="px-6 py-3.5">Interest Level</th>
                        <th class="px-6 py-3.5">Pipeline Status</th>
                        <th class="px-6 py-3.5">Next Follow-up</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700 text-xs">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('team.leads.show', $lead) }}" class="hover:underline">
                                    {{ $lead->lead_number }}
                                </a>
                                <div class="text-[11px] font-sans text-slate-400 mt-0.5">
                                    Assigned: {{ $lead->assigned_at?->format('M d, Y') ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-tg-dark">{{ $lead->customer_name }}</div>
                                @if($lead->customer_company)
                                    <div class="text-xs text-slate-500">{{ $lead->customer_company }}</div>
                                @endif
                                @if($lead->city)
                                    <div class="text-[11px] text-slate-400">{{ $lead->city }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono">
                                <a href="tel:{{ $lead->customer_phone }}" class="text-tg-purple font-bold hover:underline">
                                    {{ $lead->customer_phone }}
                                </a>
                                @if($lead->customer_email)
                                    <div class="text-[11px] text-slate-400 font-sans truncate max-w-[150px]">{{ $lead->customer_email }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-tg-dark">{{ $lead->service_required }}</div>
                                @if($lead->estimated_value)
                                    <div class="text-slate-500 font-semibold mt-0.5">Budget: ₹{{ number_format($lead->estimated_value, 2) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($lead->interest_level)
                                    @php
                                        $intClass = match($lead->interest_level) {
                                            \App\Enums\LeadInterest::Hot => 'bg-tg-pink-light text-tg-pink border-tg-pink-border',
                                            \App\Enums\LeadInterest::Warm => 'bg-amber-50 text-amber-700 border-amber-200',
                                            \App\Enums\LeadInterest::Cold => 'bg-tg-blue-light text-tg-blue border-tg-blue-border',
                                            \App\Enums\LeadInterest::NotInterested => 'bg-slate-100 text-slate-600 border-slate-200',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold border {{ $intClass }}">
                                        {{ $lead->interest_level->label() }}
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400 italic">Not Assessed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
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
                                @if($lead->overall_sla_status)
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $lead->overall_sla_status->badgeClasses() }}">
                                            SLA: {{ $lead->overall_sla_status->label() }}
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 font-mono">
                                @if($lead->next_followup_date)
                                    @php
                                        $isDelayed = $lead->next_followup_date->isPast() && !in_array($lead->status, [\App\Enums\LeadStatus::Won, \App\Enums\LeadStatus::Lost, \App\Enums\LeadStatus::Rejected]);
                                        $isToday = $lead->next_followup_date->isToday();
                                    @endphp
                                    <span class="{{ $isDelayed ? 'text-tg-pink font-bold' : ($isToday ? 'text-amber-600 font-bold' : 'text-tg-dark font-medium') }}">
                                        {{ $lead->next_followup_date->format('M d, Y') }}
                                        @if($isDelayed)
                                            <span class="block text-[10px] text-tg-pink font-sans font-bold">⚠️ OVERDUE</span>
                                        @elseif($isToday)
                                            <span class="block text-[10px] text-amber-600 font-sans font-bold">⏰ TODAY</span>
                                        @endif
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">None Scheduled</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('team.leads.show', $lead) }}"
                                   class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple-hover transition shadow-sm btn-tactile touch-target">
                                    Open CRM &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-sm text-slate-500">
                                No customer leads assigned to your portfolio yet.
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

    @if($leads->hasPages() && $leads->count() > 0)
        <!-- Mobile Pagination -->
        <div class="block md:hidden">
            {{ $leads->links() }}
        </div>
    @endif
</div>
@endsection
