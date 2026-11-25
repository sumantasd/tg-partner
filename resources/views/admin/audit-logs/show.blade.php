@extends('layouts.app')

@section('title', "Audit Event #{$log->id}")

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.audit-logs.index') }}" class="hover:text-tg-purple transition font-semibold">Central Audit Logs</a>
                <span>&bull;</span>
                <span class="font-mono text-tg-purple font-bold">Event #{{ $log->id }}</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Audit Event Dossier</h1>
            <p class="text-xs text-slate-500 mt-1">{{ $log->description }}</p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-2">
            <a href="{{ route('admin.audit-logs.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs transition">
                &larr; Back to Stream
            </a>
        </div>
    </div>

    @php
        $moduleEnum = \App\Enums\AuditLogModule::tryFrom($log->module);
        $badgeClass = $moduleEnum ? $moduleEnum->badgeClasses() : 'bg-slate-100 text-slate-700 border-slate-300';
    @endphp

    <!-- Event Summary Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/70">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Actor</span>
                <span class="font-bold text-tg-dark text-xs block">{{ $log->actor_name_snapshot ?? 'System' }}</span>
                <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider
                    {{ $log->actor_role_snapshot === 'admin' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' :
                       ($log->actor_role_snapshot === 'partner' ? 'bg-purple-50 text-purple-700 border border-purple-200' :
                       ($log->actor_role_snapshot === 'team_member' ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-slate-100 text-slate-600')) }}">
                    {{ $log->actor_role_snapshot ?? 'system' }}
                </span>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Module / Action</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $badgeClass }} mt-0.5">
                    {{ $moduleEnum ? $moduleEnum->label() : ucfirst($log->module) }}
                </span>
                <span class="text-[11px] font-mono text-slate-600 block mt-1">{{ $log->action_value ?? ($log->action instanceof \BackedEnum ? $log->action->value : (string) $log->action) }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Subject Entity</span>
                <span class="font-bold text-tg-dark text-xs block">{{ $log->subject_type ? class_basename($log->subject_type) : 'None' }}</span>
                <span class="text-[11px] font-mono text-slate-500 block">{{ $log->subject_id ? '#' . $log->subject_id : '—' }}</span>
            </div>
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Timestamp (IST)</span>
                <span class="font-bold text-tg-dark text-xs block">{{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('d M Y') : '-' }}</span>
                <span class="text-[11px] font-mono text-slate-500 block">{{ $log->created_at ? $log->created_at->timezone('Asia/Kolkata')->format('h:i:s A') . ' IST' : '-' }}</span>
            </div>
        </div>

        <!-- Before / After Diffs -->
        @if(!empty($log->old_values) || !empty($log->new_values))
            <div class="space-y-3">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Captured Attribute Changes</div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Before -->
                    <div class="bg-rose-50/50 p-4 rounded-xl border border-rose-100">
                        <div class="text-[11px] font-bold text-rose-800 uppercase tracking-wider mb-2 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Before (Old State)
                        </div>
                        <pre class="bg-white p-3 rounded-lg border border-rose-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>

                    <!-- After -->
                    <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100">
                        <div class="text-[11px] font-bold text-emerald-800 uppercase tracking-wider mb-2 flex items-center">
                            <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            After (New State)
                        </div>
                        <pre class="bg-white p-3 rounded-lg border border-emerald-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contextual Metadata -->
        @if(!empty($log->metadata))
            <div class="space-y-1.5">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Contextual Metadata</div>
                <pre class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-[11px] font-mono text-slate-700 overflow-x-auto whitespace-pre-wrap">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        @endif

        <!-- Network & Device Info -->
        <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80 space-y-1 font-mono text-xs">
            <div class="text-slate-600"><span class="font-bold text-tg-dark">IP Address:</span> {{ $log->ip_address ?? 'Not Recorded' }}</div>
            <div class="text-slate-500 text-[11px] break-all"><span class="font-bold text-tg-dark">User Agent:</span> {{ $log->user_agent ?? 'Not Recorded' }}</div>
        </div>
    </div>
</div>
@endsection
