@extends('layouts.app')

@section('title', 'Centralized Audit Logs')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
    detailModalOpen: false,
    activeLog: null,
    openDetail(log) {
        this.activeLog = log;
        this.detailModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Centralized Audit Logs</h1>
            <p class="text-xs text-slate-500 mt-1">
                Immutable, append-only system audit trail tracking authentication, financial transactions, partner lifecycle, and administrative changes.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                Append-Only Logging Active
            </span>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <!-- 1. Total Events -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Audit Events</div>
            <div class="mt-2 text-2xl font-extrabold text-tg-dark">{{ number_format($kpis['total_events']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">All logged transactions</div>
        </div>

        <!-- 2. Today's Events -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Today's Events (IST)</div>
            <div class="mt-2 text-2xl font-extrabold text-tg-purple">{{ number_format($kpis['today_events']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Activity since midnight IST</div>
        </div>

        <!-- 3. Admin Activity -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Admin Actions</div>
            <div class="mt-2 text-2xl font-extrabold text-indigo-600">{{ number_format($kpis['admin_events']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Administrative events</div>
        </div>

        <!-- 4. Partner Activity -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Partner Actions</div>
            <div class="mt-2 text-2xl font-extrabold text-emerald-600">{{ number_format($kpis['partner_events']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Submissions &amp; withdrawals</div>
        </div>

        <!-- 5. Staff Activity -->
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm col-span-2 md:col-span-1">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Staff CRM Actions</div>
            <div class="mt-2 text-2xl font-extrabold text-cyan-600">{{ number_format($kpis['staff_events']) }}</div>
            <div class="mt-1 text-[11px] text-slate-500 font-medium">Lead updates &amp; notes</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
            <!-- Search -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Search Description / Subject ID</label>
                <input type="text"
                       name="search"
                       value="{{ request('search') }}"
                       placeholder="e.g. Lead, Partner, UTR, Email..."
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
            </div>

            <!-- Module Filter -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Module</label>
                <select name="module" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
                    <option value="">All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->value }}" {{ request('module') === $module->value ? 'selected' : '' }}>
                            {{ $module->label() }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Role Filter -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Actor Role</label>
                <select name="role" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
                    <option value="">All Roles</option>
                    <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="partner" {{ request('role') === 'partner' ? 'selected' : '' }}>Partner</option>
                    <option value="team_member" {{ request('role') === 'team_member' ? 'selected' : '' }}>Sales Staff</option>
                    <option value="system" {{ request('role') === 'system' ? 'selected' : '' }}>System / Cron</option>
                </select>
            </div>

            <!-- Actor Filter -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Specific Actor</label>
                <select name="actor_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
                    <option value="">All Actors</option>
                    @foreach($actors as $actor)
                        <option value="{{ $actor->id }}" {{ (string) request('actor_id') === (string) $actor->id ? 'selected' : '' }}>
                            {{ $actor->name }} ({{ $actor->email }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- From Date (IST) -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">From Date (IST)</label>
                <input type="date"
                       name="from_date"
                       value="{{ request('from_date') }}"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
            </div>

            <!-- To Date (IST) -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">To Date (IST)</label>
                <input type="date"
                       name="to_date"
                       value="{{ request('to_date') }}"
                       class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
            </div>

            <!-- Subject Type -->
            <div>
                <label class="block font-semibold text-slate-600 mb-1">Subject Entity</label>
                <select name="subject_type" class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:ring-2 focus:ring-tg-purple/20 focus:border-tg-purple text-xs">
                    <option value="">All Entities</option>
                    @foreach($subjectTypes as $st)
                        <option value="{{ $st['class'] }}" {{ request('subject_type') === $st['class'] ? 'selected' : '' }}>
                            {{ $st['name'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end space-x-2">
                <button type="submit" class="flex-1 py-2 px-4 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                    Apply Filters
                </button>
                <a href="{{ route('admin.audit-logs.index') }}" class="py-2 px-3 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Audit Events Stream Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-sm font-bold text-tg-dark">Audit Event Stream</h2>
            <span class="text-xs text-slate-400">Showing {{ $logs->firstItem() ?? 0 }} to {{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} events</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-400 uppercase text-[10px] font-bold tracking-wider">
                        <th class="py-3 px-4">Event #</th>
                        <th class="py-3 px-4">Timestamp (IST)</th>
                        <th class="py-3 px-4">Actor</th>
                        <th class="py-3 px-4">Module</th>
                        <th class="py-3 px-4">Action</th>
                        <th class="py-3 px-4">Subject</th>
                        <th class="py-3 px-4">Description</th>
                        <th class="py-3 px-4 text-center">Changes</th>
                        <th class="py-3 px-4 text-right">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                    @forelse($logs as $log)
                        @php
                            $moduleEnum = \App\Enums\AuditLogModule::tryFrom($log->module);
                            $badgeClass = $moduleEnum ? $moduleEnum->badgeClasses() : 'bg-slate-100 text-slate-700 border-slate-300';
                            $hasDiffs = !empty($log->old_values) || !empty($log->new_values);
                            $hasMeta = !empty($log->metadata);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition">
                            <!-- ID -->
                            <td class="py-3 px-4 font-mono font-bold text-slate-400 text-[11px]">
                                #{{ $log->id }}
                            </td>

                            <!-- Timestamp in IST -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-semibold text-tg-dark block">{{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('d M Y') : '-' }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('h:i:s A') . ' IST' : '-' }}</span>
                            </td>

                            <!-- Actor -->
                            <td class="py-3 px-4">
                                <div class="font-bold text-tg-dark">{{ $log->actor_name_snapshot ?? 'System' }}</div>
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                                    {{ $log->actor_role_snapshot === 'admin' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                                       ($log->actor_role_snapshot === 'partner' ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                                       ($log->actor_role_snapshot === 'team_member' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-slate-100 text-slate-600')) }}">
                                    {{ $log->actor_role_snapshot ?? 'system' }}
                                </span>
                            </td>

                            <!-- Module -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold border {{ $badgeClass }}">
                                    {{ $moduleEnum ? $moduleEnum->label() : ucfirst($log->module) }}
                                </span>
                            </td>

                            <!-- Action -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="font-mono text-[11px] font-semibold text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200">
                                    {{ $log->action_value ?? ($log->action instanceof \BackedEnum ? $log->action->value : (string) $log->action) }}
                                </span>
                            </td>

                            <!-- Subject -->
                            <td class="py-3 px-4 whitespace-nowrap">
                                @if($log->subject_type)
                                    <span class="font-semibold text-tg-dark">{{ class_basename($log->subject_type) }}</span>
                                    <span class="text-slate-400 font-mono text-[11px]">#{{ $log->subject_id }}</span>
                                @else
                                    <span class="text-slate-400 text-xs italic">—</span>
                                @endif
                            </td>

                            <!-- Description -->
                            <td class="py-3 px-4 max-w-xs">
                                <div class="truncate text-slate-700 font-normal" title="{{ $log->description }}">
                                    {{ $log->description }}
                                </div>
                                @if($log->ip_address)
                                    <div class="text-[10px] font-mono text-slate-400 mt-0.5">IP: {{ $log->ip_address }}</div>
                                @endif
                            </td>

                            <!-- Changes Indicator -->
                            <td class="py-3 px-4 text-center whitespace-nowrap">
                                @if($hasDiffs)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Diff Captured
                                    </span>
                                @elseif($hasMeta)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-50 text-slate-600 border border-slate-200">
                                        Metadata
                                    </span>
                                @else
                                    <span class="text-slate-300 text-xs">—</span>
                                @endif
                            </td>

                            <!-- Details Button -->
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <button type="button"
                                        @click="openDetail({{ json_encode($log) }})"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-tg-purple hover:bg-tg-purple-light transition border border-tg-purple-border">
                                    Inspect
                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div class="font-bold text-sm text-slate-600">No audit events found</div>
                                <p class="text-xs mt-1">Try widening your date range or adjusting applied filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Detail & Diff Inspection Modal -->
    <div x-show="detailModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;"
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="detailModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="detailModalOpen = false"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="detailModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-100">

                <template x-if="activeLog">
                    <div>
                        <!-- Modal Header -->
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-sm font-bold text-tg-dark flex items-center space-x-2">
                                    <span>Audit Event Dossier</span>
                                    <span class="font-mono text-xs text-tg-purple" x-text="'#' + activeLog.id"></span>
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5" x-text="activeLog.description"></p>
                            </div>
                            <button type="button" @click="detailModalOpen = false" class="text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Modal Body -->
                        <div class="p-6 space-y-5 text-xs max-h-[75vh] overflow-y-auto">
                            <!-- Event Metadata Grid -->
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-50 p-4 rounded-xl border border-slate-200/70">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Actor</span>
                                    <span class="font-bold text-tg-dark text-xs" x-text="activeLog.actor_name_snapshot || 'System'"></span>
                                    <span class="text-[10px] font-mono text-slate-500 block" x-text="'Role: ' + (activeLog.actor_role_snapshot || 'system')"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Module / Action</span>
                                    <span class="font-bold text-tg-dark text-xs uppercase" x-text="activeLog.module"></span>
                                    <span class="text-[10px] font-mono text-slate-500 block" x-text="activeLog.action"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Subject Entity</span>
                                    <span class="font-bold text-tg-dark text-xs" x-text="activeLog.subject_type ? activeLog.subject_type.split('\\').pop() : 'None'"></span>
                                    <span class="text-[10px] font-mono text-slate-500 block" x-text="activeLog.subject_id ? '#' + activeLog.subject_id : '—'"></span>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Client Network</span>
                                    <span class="font-mono text-xs text-tg-dark" x-text="activeLog.ip_address || '—'"></span>
                                </div>
                            </div>

                            <!-- Before / After Diffs -->
                            <template x-if="activeLog.old_values || activeLog.new_values">
                                <div class="space-y-3">
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Changed Attributes (Before / After)</div>

                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <!-- Previous State -->
                                        <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-100">
                                            <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider mb-2 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Before (Old State)
                                            </div>
                                            <pre class="bg-white p-3 rounded-lg border border-rose-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap"
                                                 x-text="JSON.stringify(activeLog.old_values, null, 2) || 'None'"></pre>
                                        </div>

                                        <!-- New State -->
                                        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                                            <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider mb-2 flex items-center">
                                                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                After (New State)
                                            </div>
                                            <pre class="bg-white p-3 rounded-lg border border-emerald-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap"
                                                 x-text="JSON.stringify(activeLog.new_values, null, 2) || 'None'"></pre>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Metadata -->
                            <template x-if="activeLog.metadata && Object.keys(activeLog.metadata).length > 0">
                                <div class="space-y-1.5">
                                    <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Contextual Metadata</div>
                                    <pre class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap"
                                         x-text="JSON.stringify(activeLog.metadata, null, 2)"></pre>
                                </div>
                            </template>

                            <!-- User Agent -->
                            <template x-if="activeLog.user_agent">
                                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 text-slate-500 font-mono text-[10px] break-all">
                                    <span class="font-bold text-slate-600 block mb-0.5">User Agent:</span>
                                    <span x-text="activeLog.user_agent"></span>
                                </div>
                            </template>
                        </div>

                        <!-- Modal Footer -->
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[10px] text-slate-400 font-mono">Immutable audit record &bull; Append-only</span>
                            <button type="button"
                                    @click="detailModalOpen = false"
                                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl font-bold text-xs transition">
                                Close
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection
