@extends('layouts.app')

@section('title', 'Partner Agreements — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Partner Agreements &amp; Legal Compliance</h1>
            <p class="text-xs text-slate-500 mt-1">Manage versioned partner covenants, track digital acceptances, download legal PDF archives, and revoke or reissue agreements.</p>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
            <a href="{{ route('admin.settings.agreement-templates.index') }}"
               class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-700 text-xs font-bold hover:bg-slate-50 shadow-sm transition whitespace-nowrap">
                <svg class="w-4 h-4 mr-1.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Agreement Templates
            </a>
        </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 text-xs font-bold overflow-x-auto w-full pb-1">
        <a href="{{ route('admin.agreements.index', ['status' => 'all']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'all' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            All Agreements ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.agreements.index', ['status' => 'pending_acceptance']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'pending_acceptance' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Pending Acceptance ({{ $counts['pending_acceptance'] }})
        </a>
        <a href="{{ route('admin.agreements.index', ['status' => 'accepted']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'accepted' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Accepted &amp; Active ({{ $counts['accepted'] }})
        </a>
        <a href="{{ route('admin.agreements.index', ['status' => 'revoked']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'revoked' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Revoked ({{ $counts['revoked'] }})
        </a>
        <a href="{{ route('admin.agreements.index', ['status' => 'superseded']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'superseded' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Superseded ({{ $counts['superseded'] }})
        </a>
    </div>

    <!-- Search Bar -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm w-full max-w-full">
        <form method="GET" action="{{ route('admin.agreements.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex-1 w-full min-w-0">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by agreement number (TGPA-...), partner code, company name, or representative email..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto flex-shrink-0">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm whitespace-nowrap">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.agreements.index', ['status' => $status]) }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold whitespace-nowrap text-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Agreements Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-4 py-3">Agreement Number</th>
                        <th class="px-4 py-3">Partner Details</th>
                        <th class="px-4 py-3">Commission Rate</th>
                        <th class="px-4 py-3">Issue Date (IST)</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Digital Acceptance</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($agreements as $agr)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-900">
                                <a href="{{ route('admin.agreements.show', $agr) }}" class="text-tg-purple hover:underline">
                                    {{ $agr->agreement_number }}
                                </a>
                                <span class="block text-[10px] font-sans text-slate-400 font-normal">
                                    v{{ $agr->template_version ?? '1.0' }} (Rev {{ $agr->version }})
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-800">
                                    {{ $agr->partner->company_name ?: $agr->partner->user->name }}
                                </div>
                                <div class="text-[11px] text-slate-500">
                                    {{ $agr->partner->user->name }} &bull; <span class="font-mono text-tg-purple">{{ $agr->partner->partner_code }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-bold text-slate-900">{{ number_format($agr->commission_rate_snapshot, 2) }}%</span>
                                <span class="block text-[10px] text-slate-400 capitalize">{{ $agr->commission_type_snapshot }}</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                {{ $agr->issue_date->format('d M Y') }}
                                <span class="block text-[10px] text-slate-400">Eff: {{ $agr->effective_date->format('d M Y') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $agr->status->badgeClass() }}">
                                    {{ $agr->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($agr->status->value === 'accepted' && $agr->accepted_at)
                                    <div class="text-emerald-700 font-semibold text-[11px] flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $agr->accepted_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                    </div>
                                    <span class="block text-[10px] font-mono text-slate-400">IP: {{ $agr->accepted_ip }}</span>
                                @elseif($agr->status->value === 'revoked')
                                    <span class="text-rose-600 text-[11px]">Revoked: {{ $agr->revoked_at?->format('d M Y') }}</span>
                                @else
                                    <span class="text-amber-600 text-[11px] font-medium">Pending Partner Signature</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('admin.agreements.preview', $agr) }}"
                                       target="_blank"
                                       class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-tg-purple hover:bg-slate-50 transition"
                                       title="Preview PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('admin.agreements.download', $agr) }}"
                                       class="p-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-tg-purple hover:bg-slate-50 transition"
                                       title="Download Official PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    </a>
                                    <a href="{{ route('admin.agreements.show', $agr) }}"
                                       class="px-2.5 py-1.5 rounded-lg bg-tg-purple text-white text-[11px] font-bold hover:bg-tg-purple-hover transition">
                                        View Dossier
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No partner agreements found matching the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agreements->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $agreements->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
