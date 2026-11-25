@extends('layouts.app')

@section('title', 'Staff Sales & Telecalling Dashboard')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">

    @if ($teamUser->inactivity_status && $teamUser->inactivity_status->isAtRiskOrWorse() && !$teamUser->isSuspended())
        <div class="bg-gradient-to-r from-amber-500/15 via-orange-500/10 to-transparent border-l-4 border-amber-500 rounded-2xl p-4 sm:p-5 shadow-sm bg-white dark:bg-slate-900 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-start space-x-3.5">
                <div class="p-2 bg-amber-500/20 text-amber-700 dark:text-amber-400 rounded-xl flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">
                        Telecalling Activity Notice ({{ $teamUser->getInactivityDays() }} Days Inactive)
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Your account is currently <strong class="text-amber-700 dark:text-amber-400">{{ $teamUser->inactivity_status->label() }}</strong>. Log client calls, update lead statuses, or schedule follow-ups to maintain active staff status.
                    </p>
                </div>
            </div>
            <a href="{{ route('team.leads.index') }}"
               class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl bg-tg-purple hover:bg-tg-purple-hover text-white font-bold text-xs shadow-sm transition whitespace-nowrap">
                Open CRM Leads &rarr;
            </a>
        </div>
    @endif

    <!-- 1. Staff Hero Banner with Identity & Conversion Rate -->
    <div class="bg-gradient-to-br from-tg-purple via-[#58307d] to-[#3a1d56] rounded-3xl p-5 sm:p-7 text-white shadow-[0_10px_25px_rgba(107,61,149,0.15)] relative overflow-hidden">
        <!-- Background decorative glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-tg-pink/20 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6 relative z-10">
            <div>
                <div class="flex items-center space-x-2.5 flex-wrap gap-y-1">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/20 text-white backdrop-blur-sm border border-white/20 font-mono">
                        {{ $teamUser->employee_id_display }}
                    </span>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-white/10 text-white/90 border border-white/15">
                        {{ $teamUser->designation ?? 'Sales CRM Specialist' }} &bull; {{ $teamUser->department ?? 'Telecalling Desk' }}
                    </span>
                </div>

                <h1 class="text-2xl sm:text-3xl font-black mt-2 tracking-tight">
                    Welcome back, {{ $teamUser->name }}!
                </h1>
                <p class="text-xs text-white/80 mt-1 max-w-xl">
                    Track your assigned customer pipeline, log telecalling interactions, and execute scheduled client follow-ups.
                </p>

                <!-- Performance Quick Stats Row -->
                <div class="mt-4 pt-4 border-t border-white/10 flex flex-wrap items-baseline gap-4">
                    <div>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-white/70 block">Conversion Rate</span>
                        <span class="text-2xl sm:text-3xl font-black text-white font-mono">
                            {{ $stats['conversion_rate'] }}%
                        </span>
                    </div>
                    <div class="px-2.5 py-1 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-[11px] text-white">
                        <span class="font-bold text-emerald-300">{{ $stats['won'] }}</span> Deals Won / Converted
                    </div>
                    <div class="px-2.5 py-1 rounded-xl bg-white/15 backdrop-blur-sm border border-white/20 text-[11px] text-white">
                        <span class="font-bold text-amber-300">{{ $stats['in_progress'] }}</span> In Active Negotiation
                    </div>
                </div>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-col sm:flex-row md:flex-col gap-2.5 sm:w-auto w-full">
                <a href="{{ route('team.leads.index') }}"
                   class="px-5 py-3 rounded-2xl bg-tg-pink hover:bg-tg-pink-hover text-white text-xs font-bold text-center transition shadow-lg shadow-tg-pink/30 btn-tactile touch-target flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    View All Assigned Leads
                </a>
                <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}"
                   class="px-5 py-3 rounded-2xl bg-white hover:bg-slate-50 text-tg-purple text-xs font-bold text-center transition shadow-md btn-tactile touch-target flex items-center justify-center">
                    <svg class="w-4 h-4 mr-2 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    Today's Call Schedule ({{ $stats['today_followup'] }})
                </a>
            </div>
        </div>
    </div>

    <!-- 2. Staff CRM KPI Metrics Grid (Mobile 2-col, Tablet 3-col, Desktop 6-col) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <!-- 1. Total Assigned -->
        <a href="{{ route('team.leads.index') }}"
           class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:border-tg-purple transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Assigned Leads</div>
            <div class="text-xl sm:text-2xl font-black text-tg-dark mt-1">{{ $stats['total_assigned'] }}</div>
            <div class="text-[10px] text-slate-400 mt-1">Total portfolio</div>
        </a>

        <!-- 2. Today's Calls -->
        <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}"
           class="p-4 rounded-3xl bg-white border {{ $stats['today_followup'] > 0 ? 'border-amber-400 ring-2 ring-amber-100 bg-amber-50/20' : 'border-slate-200/80' }} shadow-sm hover:border-amber-500 transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-amber-600 uppercase tracking-wider">Today's Calls</div>
            <div class="text-xl sm:text-2xl font-black text-amber-600 mt-1">{{ $stats['today_followup'] }}</div>
            <div class="text-[10px] text-amber-600/80 mt-1">Requires follow-up</div>
        </a>

        <!-- 3. Overdue Calls -->
        <a href="{{ route('team.leads.index', ['overdue' => '1']) }}"
           class="p-4 rounded-3xl bg-white border {{ $stats['overdue_followup'] > 0 ? 'border-rose-300 ring-2 ring-rose-100 bg-rose-50/20' : 'border-slate-200/80' }} shadow-sm hover:border-tg-pink transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">Overdue Calls</div>
            <div class="text-xl sm:text-2xl font-black text-tg-pink mt-1">{{ $stats['overdue_followup'] }}</div>
            <div class="text-[10px] text-tg-pink/80 mt-1">Needs attention</div>
        </a>

        <!-- 4. Active Pipeline -->
        <a href="{{ route('team.leads.index', ['status' => 'in_discussion']) }}"
           class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:border-tg-blue transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-tg-blue uppercase tracking-wider">In Pipeline</div>
            <div class="text-xl sm:text-2xl font-black text-tg-blue mt-1">{{ $stats['in_progress'] }}</div>
            <div class="text-[10px] text-slate-400 mt-1">Active discussions</div>
        </a>

        <!-- 5. Hot Deals -->
        <a href="{{ route('team.leads.index', ['interest' => 'hot']) }}"
           class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:border-tg-pink transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-tg-pink uppercase tracking-wider">🔥 Hot Leads</div>
            <div class="text-xl sm:text-2xl font-black text-tg-pink mt-1">{{ $stats['hot_leads'] }}</div>
            <div class="text-[10px] text-slate-400 mt-1">High purchase intent</div>
        </a>

        <!-- 6. Won Conversions -->
        <a href="{{ route('team.leads.index', ['status' => 'won']) }}"
           class="p-4 rounded-3xl bg-white border border-slate-200/80 shadow-sm hover:border-tg-green transition btn-tactile touch-target flex flex-col justify-center">
            <div class="text-[10px] font-bold text-tg-green uppercase tracking-wider">Won Deals</div>
            <div class="text-xl sm:text-2xl font-black text-tg-green mt-1">{{ $stats['won'] }}</div>
            <div class="text-[10px] text-tg-green/80 mt-1">{{ $stats['conversion_rate'] }}% conversion</div>
        </a>
    </div>

    <!-- 3. Follow-up Calendar / Schedule Horizon Strip -->
    <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider flex items-center">
                <svg class="w-4 h-4 text-tg-purple mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                Follow-up Calendar &amp; Schedule Agenda
            </h2>
            <a href="{{ route('team.leads.index') }}" class="text-xs text-tg-purple font-bold hover:underline">
                View Calendar Pipeline &rarr;
            </a>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200">
                <div class="text-[10px] font-bold uppercase text-rose-700">Overdue Follow-ups</div>
                <div class="text-xl font-black text-rose-800 mt-1 font-mono">{{ $calendarSchedule['overdue'] }}</div>
                <div class="text-[10px] text-rose-600 mt-0.5">Calls past target date</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200">
                <div class="text-[10px] font-bold uppercase text-amber-700">Calls Scheduled Today</div>
                <div class="text-xl font-black text-amber-800 mt-1 font-mono">{{ $calendarSchedule['today'] }}</div>
                <div class="text-[10px] text-amber-600 mt-0.5">Priority calls for today</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-blue-50 border border-blue-200">
                <div class="text-[10px] font-bold uppercase text-blue-700">Tomorrow's Schedule</div>
                <div class="text-xl font-black text-blue-800 mt-1 font-mono">{{ $calendarSchedule['tomorrow'] }}</div>
                <div class="text-[10px] text-blue-600 mt-0.5">Scheduled for next day</div>
            </div>

            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200">
                <div class="text-[10px] font-bold uppercase text-slate-600">Upcoming This Week</div>
                <div class="text-xl font-black text-slate-800 mt-1 font-mono">{{ $calendarSchedule['this_week'] }}</div>
                <div class="text-[10px] text-slate-500 mt-0.5">Remaining week horizon</div>
            </div>
        </div>
    </div>

    <!-- 4. Two-Column Split: Today's Priority Follow-ups & Recent Activity Stream -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Today's Scheduled Telecalling Calls -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <div>
                        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Today's Priority Follow-ups</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Customer leads requiring telephone or WhatsApp contact today.</p>
                    </div>
                    <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}" class="text-xs font-bold text-tg-purple hover:underline">
                        View All ({{ $stats['today_followup'] }}) &rarr;
                    </a>
                </div>

                <div class="space-y-3">
                    @forelse ($todayLeads as $lead)
                        @php
                            $cleanPhone = preg_replace('/[^0-9]/', '', $lead->customer_phone);
                            if (strlen($cleanPhone) === 10) { $cleanPhone = '91' . $cleanPhone; }
                            $isOverdue = $lead->next_followup_date && $lead->next_followup_date->isPast() && !$lead->next_followup_date->isToday();
                        @endphp
                        <div class="p-4 rounded-2xl border transition {{ $isOverdue ? 'bg-rose-50/40 border-rose-200' : 'bg-slate-50/60 border-slate-200' }}">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                                        <span class="font-mono text-xs font-bold text-tg-purple">{{ $lead->lead_number }}</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200/80 text-slate-700">
                                            {{ $lead->status->label() }}
                                        </span>
                                        @if($lead->overall_sla_status)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[9px] font-black uppercase border {{ $lead->overall_sla_status->badgeClasses() }}">
                                                SLA: {{ $lead->overall_sla_status->label() }}
                                            </span>
                                        @endif
                                        @if($isOverdue)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-rose-100 text-rose-800">
                                                Overdue
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-amber-100 text-amber-800">
                                                Today
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="text-sm font-bold text-tg-dark mt-1 truncate">{{ $lead->customer_name }}</h3>
                                    <div class="text-xs text-slate-500 flex items-center space-x-2 mt-0.5 flex-wrap">
                                        @if(!$lead->first_contacted_at && $lead->first_contact_due_at)
                                            <span class="text-[11px] font-bold font-mono text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md border border-rose-200">
                                                ⏱ 1st Contact: {{ $lead->getFirstContactRemainingHuman() }}
                                            </span>
                                        @elseif($lead->followup_due_at)
                                            <span class="text-[11px] font-bold font-mono text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">
                                                ⏱ Follow-up: {{ $lead->getFollowupRemainingHuman() }}
                                            </span>
                                        @endif
                                        @if($lead->customer_company)
                                            <span>{{ $lead->customer_company }}</span>
                                            <span>&bull;</span>
                                        @endif
                                        <span>{{ $lead->service_required }}</span>
                                        @if($lead->estimated_value)
                                            <span>&bull;</span>
                                            <span class="font-mono font-bold text-tg-dark">₹{{ number_format($lead->estimated_value, 2) }}</span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2 flex-shrink-0">
                                    <a href="tel:{{ $lead->customer_phone }}"
                                       class="px-3 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs flex items-center transition shadow-sm btn-tactile touch-target">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                        Call
                                    </a>
                                    <a href="https://wa.me/{{ $cleanPhone }}" target="_blank"
                                       class="px-3 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-xs flex items-center transition shadow-sm btn-tactile touch-target">
                                        WhatsApp
                                    </a>
                                    <a href="{{ route('team.leads.show', $lead) }}"
                                       class="px-3 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple-hover text-white font-bold text-xs transition shadow-sm btn-tactile touch-target">
                                        Dossier &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center bg-slate-50 rounded-2xl border border-slate-200">
                            <svg class="w-8 h-8 text-emerald-500 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <h4 class="text-xs font-bold text-tg-dark">No Follow-ups Pending for Today</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">All scheduled telecalling calls for today are complete.</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Pipeline Stage Funnel Distribution -->
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100 mb-4">
                    Pipeline Stages Breakdown
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5">
                    <a href="{{ route('team.leads.index', ['status' => 'assigned']) }}" class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 transition border border-slate-200 text-center">
                        <span class="text-[10px] font-bold text-slate-500 uppercase block">Assigned</span>
                        <span class="text-base font-black text-tg-dark font-mono">{{ $stages['assigned'] }}</span>
                    </a>
                    <a href="{{ route('team.leads.index', ['status' => 'contacted']) }}" class="p-3 rounded-2xl bg-purple-50 hover:bg-purple-100 transition border border-purple-200 text-center">
                        <span class="text-[10px] font-bold text-purple-700 uppercase block">Contacted</span>
                        <span class="text-base font-black text-purple-800 font-mono">{{ $stages['contacted'] }}</span>
                    </a>
                    <a href="{{ route('team.leads.index', ['status' => 'in_discussion']) }}" class="p-3 rounded-2xl bg-blue-50 hover:bg-blue-100 transition border border-blue-200 text-center">
                        <span class="text-[10px] font-bold text-blue-700 uppercase block">In Discussion</span>
                        <span class="text-base font-black text-blue-800 font-mono">{{ $stages['in_discussion'] }}</span>
                    </a>
                    <a href="{{ route('team.leads.index', ['status' => 'quotation_sent']) }}" class="p-3 rounded-2xl bg-pink-50 hover:bg-pink-100 transition border border-pink-200 text-center">
                        <span class="text-[10px] font-bold text-pink-700 uppercase block">Quote Sent</span>
                        <span class="text-base font-black text-pink-800 font-mono">{{ $stages['quotation_sent'] }}</span>
                    </a>
                    <a href="{{ route('team.leads.index', ['status' => 'negotiating']) }}" class="p-3 rounded-2xl bg-amber-50 hover:bg-amber-100 transition border border-amber-200 text-center">
                        <span class="text-[10px] font-bold text-amber-700 uppercase block">Negotiating</span>
                        <span class="text-base font-black text-amber-800 font-mono">{{ $stages['negotiating'] }}</span>
                    </a>
                    <a href="{{ route('team.leads.index', ['status' => 'won']) }}" class="p-3 rounded-2xl bg-emerald-50 hover:bg-emerald-100 transition border border-emerald-200 text-center">
                        <span class="text-[10px] font-bold text-emerald-700 uppercase block">Won Deals</span>
                        <span class="text-base font-black text-emerald-800 font-mono">{{ $stages['won'] }}</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Column 3: Recent Telecalling Activities Timeline -->
        <div class="space-y-6">
            <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                    <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider flex items-center">
                        <svg class="w-4 h-4 text-tg-purple mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Recent Activity Stream
                    </h2>
                    <span class="text-[10px] font-bold text-slate-400">Latest Logs</span>
                </div>

                <div class="space-y-4">
                    @forelse ($recentActivities as $act)
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-mono text-[11px] font-bold text-tg-purple">
                                    {{ $act->lead->lead_number ?? 'Lead' }}
                                </span>
                                <time class="text-[10px] text-slate-400">{{ $act->created_at->diffForHumans() }}</time>
                            </div>
                            <div class="font-bold text-tg-dark text-[11px]">
                                {{ $act->summary }}
                            </div>
                            @if ($act->details)
                                <div class="text-[10px] text-slate-600 line-clamp-2">
                                    {{ $act->details }}
                                </div>
                            @endif
                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 text-[10px] text-slate-500">
                                <span>{{ $act->activity_type->label() }}</span>
                                @if($act->call_duration_seconds)
                                    <span>{{ gmdate("i:s", $act->call_duration_seconds) }} mins</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6 text-slate-400 text-xs">
                            No recent CRM activities logged yet.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Quick Profile & Settings Shortcut Card -->
            <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm space-y-3">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Staff Account</div>
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-2xl bg-tg-purple text-white font-extrabold text-sm flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden">
                        @if($teamUser->profile_photo_path)
                            <img src="{{ Storage::url($teamUser->profile_photo_path) }}" alt="{{ $teamUser->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($teamUser->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="truncate">
                        <div class="font-bold text-xs text-tg-dark truncate">{{ $teamUser->name }}</div>
                        <div class="text-[10px] text-slate-500 font-mono">{{ $teamUser->employee_id_display }}</div>
                    </div>
                </div>
                <a href="{{ route('team.profile.edit') }}"
                   class="w-full flex items-center justify-center px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-tg-dark text-xs font-bold transition btn-tactile touch-target">
                    Manage Profile &amp; Password &rarr;
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
