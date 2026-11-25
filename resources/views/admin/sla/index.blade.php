@extends('layouts.app')

@section('title', 'Lead SLA & First Contact Tracking')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Lead SLA Management</h1>
            <p class="text-xs text-slate-500 mt-1">
                Monitor team response times, first contact deadlines, scheduled follow-up adherence, and automated escalations.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-2.5">
            <a href="{{ route('admin.lead-sla.escalations') }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-200 hover:border-tg-purple hover:text-tg-purple transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                Escalation Logs
            </a>
            <a href="{{ route('admin.lead-sla.settings') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                SLA Settings
            </a>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <!-- 1. Total Active Under SLA -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Under Active SLA</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-tg-dark">{{ $metrics['total_under_sla'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Assigned open leads</div>
        </div>

        <!-- 2. On Track -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-600">On Track</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-emerald-600">{{ $metrics['on_track_count'] }}</div>
            <div class="text-[11px] text-emerald-700 font-medium mt-1">Within SLA window</div>
        </div>

        <!-- 3. Due Soon -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-600">Due Soon</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-amber-600">{{ $metrics['due_soon_count'] }}</div>
            <div class="text-[11px] text-amber-700 font-medium mt-1">Approaching target</div>
        </div>

        <!-- 4. Overdue First Contact -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-rose-600">Overdue First Contact</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-rose-600">{{ $metrics['overdue_first_contact_count'] }}</div>
            <div class="text-[11px] text-rose-700 font-medium mt-1">Awaiting 1st contact</div>
        </div>

        <!-- 5. Overdue Follow-up -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-purple-600">Overdue Follow-up</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-purple-600">{{ $metrics['overdue_followup_count'] }}</div>
            <div class="text-[11px] text-purple-700 font-medium mt-1">Scheduled call missed</div>
        </div>

        <!-- 6. SLA Compliance % -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-tg-blue">SLA Compliance</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-tg-blue">{{ $metrics['compliance_rate'] }}%</div>
            <div class="w-full bg-slate-100 rounded-full h-1.5 mt-2 overflow-hidden">
                <div class="bg-tg-blue h-1.5 rounded-full" style="width: {{ min(100, $metrics['compliance_rate']) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form action="{{ route('admin.lead-sla.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Staff Member</label>
                <select name="staff_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Team Members</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ ($filters['staff_id'] ?? '') == $staff->id ? 'selected' : '' }}>
                            {{ $staff->name }} ({{ $staff->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">SLA Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Active Statuses</option>
                    @foreach(\App\Enums\LeadSlaStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') == $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Created From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                    Apply Filters
                </button>
                @if(!empty($filters['staff_id']) || !empty($filters['status']) || !empty($filters['date_from']) || !empty($filters['date_to']))
                    <a href="{{ route('admin.lead-sla.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Staff Performance Breakdown -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Team Member SLA Performance &amp; Adherence</h2>
            <span class="text-xs text-slate-500">Live IST Tracking</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Staff Member</th>
                        <th class="py-3 px-4 text-center">Active Leads</th>
                        <th class="py-3 px-4 text-center">First Contact Met</th>
                        <th class="py-3 px-4 text-center">Overdue 1st Contact</th>
                        <th class="py-3 px-4 text-center">Overdue Follow-up</th>
                        <th class="py-3 px-4 text-center">Due Soon</th>
                        <th class="py-3 px-4 text-center">Escalations</th>
                        <th class="py-3 px-4 text-right">Compliance Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($metrics['staff_performance'] as $row)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4">
                                <div class="font-bold text-slate-800">{{ $row['staff']->name }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $row['staff']->email }}</div>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold">{{ $row['active_leads'] }}</td>
                            <td class="py-3 px-4 text-center font-mono text-emerald-600 font-semibold">{{ $row['first_contact_met'] }}</td>
                            <td class="py-3 px-4 text-center font-mono font-semibold {{ $row['first_contact_overdue'] > 0 ? 'text-rose-600 font-bold' : 'text-slate-400' }}">
                                {{ $row['first_contact_overdue'] }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-semibold {{ $row['followup_overdue'] > 0 ? 'text-purple-600 font-bold' : 'text-slate-400' }}">
                                {{ $row['followup_overdue'] }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-semibold {{ $row['due_soon'] > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                                {{ $row['due_soon'] }}
                            </td>
                            <td class="py-3 px-4 text-center font-mono">
                                @if($row['total_escalations'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        {{ $row['total_escalations'] }} Breaches
                                    </span>
                                @else
                                    <span class="text-slate-400 font-mono">0</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold font-mono {{ $row['compliance_rate'] >= 85 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($row['compliance_rate'] >= 65 ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}">
                                    {{ $row['compliance_rate'] }}%
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-center text-slate-400">
                                No staff performance records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Escalations Section -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Recent SLA Escalation Alerts</h2>
            <a href="{{ route('admin.lead-sla.escalations') }}" class="text-xs font-bold text-tg-purple hover:underline">
                View All &rarr;
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Lead #</th>
                        <th class="py-3 px-4">Assigned Staff</th>
                        <th class="py-3 px-4">Escalation Type</th>
                        <th class="py-3 px-4">Target Due At</th>
                        <th class="py-3 px-4">Breached At</th>
                        <th class="py-3 px-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($recentEscalations as $esc)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 font-mono font-bold">
                                <a href="{{ route('admin.leads.show', $esc->lead_id) }}" class="text-tg-purple hover:underline">
                                    #{{ $esc->lead->lead_number ?? $esc->lead_id }}
                                </a>
                            </td>
                            <td class="py-3 px-4 font-semibold text-slate-700">
                                {{ $esc->assignedTo->name ?? 'Unassigned' }}
                            </td>
                            <td class="py-3 px-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $esc->severity === 'critical' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                    {{ $esc->escalation_type->label() }}
                                </span>
                            </td>
                            <td class="py-3 px-4 font-mono text-slate-600">
                                {{ $esc->due_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4 font-mono text-rose-600 font-semibold">
                                {{ $esc->breached_at?->format('d M Y, h:i A') ?? 'N/A' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $esc->details }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No SLA escalations logged yet. Excellent compliance!
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
