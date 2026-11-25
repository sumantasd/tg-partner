@extends('layouts.app')

@section('title', 'Commission Policies — Admin Portal')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.commissions.index') }}" class="hover:text-tg-purple">Commissions</a>
                <span>/</span>
                <span class="text-tg-purple font-semibold">Policies</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark">Versioned Commission Policies</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage global default and promotional commission policies with full version history.</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.settings.commission.index') }}"
               class="inline-flex items-center px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Rate Settings
            </a>
            <a href="{{ route('admin.settings.commission-policies.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + New Policy
            </a>
        </div>
    </div>

    <!-- Active Global Default Policy Spotlight Card -->
    @if($activeDefault)
    <div class="rounded-3xl p-6 bg-gradient-to-r from-tg-purple to-indigo-700 text-white shadow-md relative overflow-hidden">
        <div class="absolute -right-8 -bottom-8 w-44 h-44 rounded-full bg-white/10 blur-xl pointer-events-none"></div>
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 relative z-10">
            <div class="space-y-1">
                <div class="flex items-center space-x-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-white/20 text-white border border-white/30">
                        Active Global Default
                    </span>
                    <span class="font-mono text-xs text-white/80 font-bold">#{{ $activeDefault->policy_code }} (v{{ $activeDefault->version }})</span>
                </div>
                <h2 class="text-xl font-extrabold tracking-tight text-white">{{ $activeDefault->name }}</h2>
                <p class="text-xs text-white/80 max-w-xl">{{ $activeDefault->description ?: 'Default system remuneration rate applied to partner referrals.' }}</p>
            </div>

            <div class="flex items-center space-x-6">
                <div class="text-right">
                    <span class="text-[10px] uppercase font-bold text-white/70 block tracking-wider">Default Rate</span>
                    <span class="text-3xl font-extrabold text-white">
                        {{ $activeDefault->commission_type->value === 'percentage' ? number_format($activeDefault->commission_value, 2) . '%' : '₹' . number_format($activeDefault->commission_value, 2) }}
                    </span>
                    <span class="text-[11px] text-white/70 block mt-0.5">
                        Effective from @istDate($activeDefault->effective_from)
                    </span>
                </div>

                <div class="flex flex-col space-y-2">
                    <a href="{{ route('admin.settings.commission-policies.show', $activeDefault) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-white text-tg-purple hover:bg-slate-100 text-xs font-bold transition text-center shadow-sm">
                        View Dossier
                    </a>
                    <a href="{{ route('admin.settings.commission-policies.revision.create', $activeDefault) }}"
                       class="px-3.5 py-1.5 rounded-xl bg-white/15 hover:bg-white/25 text-white text-xs font-bold transition text-center border border-white/20">
                        New Revision
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Metric Stat Badges -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Total Policies</span>
            <div class="text-2xl font-bold text-tg-dark mt-1">{{ $metrics['total_policies'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-emerald-600 tracking-wider">Active Policies</span>
            <div class="text-2xl font-bold text-emerald-600 mt-1">{{ $metrics['active_policies'] }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm">
            <span class="text-[10px] uppercase font-bold text-tg-purple tracking-wider">Default Rate</span>
            <div class="text-2xl font-bold text-tg-purple mt-1">{{ number_format($metrics['default_rate'], 2) }}%</div>
        </div>
    </div>

    <!-- Filter Bar & Search -->
    <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-3">
        <div class="flex items-center space-x-1.5 overflow-x-auto text-xs font-semibold">
            <a href="{{ route('admin.settings.commission-policies.index') }}"
               class="px-3 py-1.5 rounded-xl transition {{ empty($status) ? 'bg-tg-purple text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                All Policies
            </a>
            <a href="{{ route('admin.settings.commission-policies.index', ['status' => 'active']) }}"
               class="px-3 py-1.5 rounded-xl transition {{ $status === 'active' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Active
            </a>
            <a href="{{ route('admin.settings.commission-policies.index', ['status' => 'superseded']) }}"
               class="px-3 py-1.5 rounded-xl transition {{ $status === 'superseded' ? 'bg-slate-700 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Superseded
            </a>
            <a href="{{ route('admin.settings.commission-policies.index', ['status' => 'inactive']) }}"
               class="px-3 py-1.5 rounded-xl transition {{ $status === 'inactive' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' }}">
                Inactive
            </a>
        </div>

        <form method="GET" action="{{ route('admin.settings.commission-policies.index') }}" class="relative max-w-xs w-full">
            @if($status) <input type="hidden" name="status" value="{{ $status }}"> @endif
            <input type="text" name="search" value="{{ $search }}" placeholder="Search policy code or name..."
                   class="w-full pl-9 pr-4 py-2 text-xs rounded-xl border border-slate-300 focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </form>
    </div>

    <!-- Policy Ledger Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto table-scroll-container">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Policy Code</th>
                        <th class="py-3.5 px-4">Policy Name</th>
                        <th class="py-3.5 px-4">Type &amp; Remuneration</th>
                        <th class="py-3.5 px-4">Fixed Frequency</th>
                        <th class="py-3.5 px-4">Effective Range (IST)</th>
                        <th class="py-3.5 px-4">Version</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($policies as $p)
                        <tr class="hover:bg-slate-50/60 transition {{ $p->is_default ? 'bg-tg-purple/5' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.settings.commission-policies.show', $p) }}" class="hover:underline">
                                    {{ $p->policy_code }}
                                </a>
                                @if($p->is_default)
                                    <span class="ml-1.5 px-1.5 py-0.5 rounded text-[9px] font-extrabold uppercase bg-tg-purple text-white">Default</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-tg-dark">
                                {{ $p->name }}
                                @if($p->parentPolicy)
                                    <span class="block text-[10px] text-slate-400 font-normal">Revises: {{ $p->parentPolicy->policy_code }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($p->commission_type->value === 'percentage')
                                    <span class="font-bold text-tg-purple text-sm">{{ number_format($p->commission_value, 2) }}%</span>
                                    <span class="text-[10px] text-slate-400 block font-normal">Percentage</span>
                                @else
                                    <span class="font-bold text-emerald-600 text-sm">₹{{ number_format($p->commission_value, 2) }}</span>
                                    <span class="text-[10px] text-slate-400 block font-normal">Fixed Amount</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                @if($p->fixed_frequency)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $p->fixed_frequency->label() }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[11px]">—</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                <div class="font-medium">From: @istDate($p->effective_from)</div>
                                <div class="text-[10px] text-slate-400">To: {{ $p->effective_until ? \App\Support\DateTimeHelper::formatDate($p->effective_until) : 'Indefinite' }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                                v{{ $p->version }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($p->status === 'active')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Active
                                    </span>
                                @elseif($p->status === 'superseded')
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Superseded
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Inactive
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.settings.commission-policies.show', $p) }}"
                                   class="text-tg-purple hover:text-tg-purple/80 font-bold hover:underline">
                                    View
                                </a>
                                <span class="text-slate-300">|</span>
                                <a href="{{ route('admin.settings.commission-policies.revision.create', $p) }}"
                                   class="text-slate-600 hover:text-tg-purple font-medium hover:underline">
                                    Revise
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-400">
                                No commission policies found matching current filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($policies->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $policies->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
