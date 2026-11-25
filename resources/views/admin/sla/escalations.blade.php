@extends('layouts.app')

@section('title', 'Lead SLA Escalation History')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.lead-sla.index') }}" class="text-xs font-semibold text-tg-purple hover:underline">&larr; Back to SLA Dashboard</a>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">SLA Escalation Audit Log</h1>
            <p class="text-xs text-slate-500 mt-1">
                Permanent audit log of all automated first contact and follow-up deadline breaches with notification delivery status.
            </p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form action="{{ route('admin.lead-sla.escalations') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Escalation Type</label>
                <select name="type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Breach Types</option>
                    @foreach(\App\Enums\LeadSlaEscalationType::cases() as $type)
                        <option value="{{ $type->value }}" {{ request('type') === $type->value ? 'selected' : '' }}>{{ $type->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Assigned Staff</label>
                <select name="staff_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Staff Members</option>
                    @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                    Filter
                </button>
                @if(request()->hasAny(['type', 'staff_id']))
                    <a href="{{ route('admin.lead-sla.escalations') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Escalations Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Log ID</th>
                        <th class="py-3 px-4">Lead #</th>
                        <th class="py-3 px-4">Assigned Staff</th>
                        <th class="py-3 px-4">Severity &amp; Type</th>
                        <th class="py-3 px-4">Due Target</th>
                        <th class="py-3 px-4">Breach Recorded At</th>
                        <th class="py-3 px-4">Notification Sent</th>
                        <th class="py-3 px-4">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($escalations as $esc)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 font-mono font-bold text-slate-500">#{{ $esc->id }}</td>
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
                            <td class="py-3 px-4 font-semibold">
                                @if($esc->notification_sent)
                                    <span class="text-emerald-600 flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Dispatched
                                    </span>
                                @else
                                    <span class="text-slate-400">Pending</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-slate-600 max-w-xs truncate">
                                {{ $esc->details }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400">
                                No SLA escalation records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($escalations->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $escalations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
