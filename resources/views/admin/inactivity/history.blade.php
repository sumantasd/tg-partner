@extends('layouts.app')

@section('title', 'Inactivity Audit History — ' . $user->name)

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.inactivity.index', ['tab' => $user->role === \App\Enums\UserRole::TeamMember ? 'staff' : 'partners']) }}" class="text-xs font-semibold text-tg-purple hover:underline">&larr; Back to Inactivity Monitor</a>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">Inactivity Audit History</h1>
            <p class="text-xs text-slate-500 mt-1">
                Account: <strong class="text-slate-800">{{ $user->name }}</strong> ({{ $user->email }}) &bull; Role: {{ $user->role->label() }}
            </p>
        </div>
    </div>

    <!-- Account Snapshot Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
        <div>
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Current Inactivity Status</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border mt-1 {{ $user->inactivity_status->badgeClasses() }}">
                {{ $user->inactivity_status->label() }}
            </span>
        </div>
        <div>
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Days Inactive</span>
            <span class="font-mono font-bold text-sm text-slate-800 mt-0.5 block">{{ $user->getInactivityDays() }} days</span>
        </div>
        <div>
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Last Login</span>
            <span class="font-mono text-slate-700 mt-0.5 block">{{ $user->last_login_at?->format('d M Y, h:i A') ?? 'Never' }}</span>
        </div>
        <div>
            <span class="text-[10px] font-bold uppercase text-slate-400 block">Last Meaningful Activity</span>
            <span class="font-mono text-slate-700 mt-0.5 block">{{ $user->last_meaningful_activity_at?->format('d M Y, h:i A') ?? 'None' }}</span>
        </div>
    </div>

    <!-- History Timeline / Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-800">Permanent Inactivity &amp; Administrative Event Log</h2>
            <span class="text-xs text-slate-500">{{ $histories->total() }} recorded events</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Event Date</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4 text-center">Inactivity Days</th>
                        <th class="py-3 px-4">Reason / Details</th>
                        <th class="py-3 px-4">Performed By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($histories as $log)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-3 px-4 font-mono text-slate-600 whitespace-nowrap">
                                {{ $log->created_at->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-3 px-4">
                                @php
                                    $actionStr = $log->action instanceof \BackedEnum ? $log->action->value : (string) $log->action;
                                    $actionBadge = match($actionStr) {
                                        'warning_sent', 'manual_warning_sent' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'alert_sent' => 'bg-orange-50 text-orange-700 border-orange-200',
                                        'critical_alert_sent', 'suspended' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'reactivated' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'marked_for_review' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $actionBadge }}">
                                    {{ ucwords(str_replace(['_', '-'], ' ', $actionStr)) }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center font-mono font-bold text-slate-700">
                                {{ $log->inactivity_days !== null ? $log->inactivity_days . 'd' : '—' }}
                            </td>
                            <td class="py-3 px-4 text-slate-700 max-w-sm">
                                {{ $log->reason ?? '—' }}
                            </td>
                            <td class="py-3 px-4 text-slate-600">
                                {{ $log->performer->name ?? 'System Automated Cron' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                No inactivity history events recorded for this account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($histories->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $histories->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
