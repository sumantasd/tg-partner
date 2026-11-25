@extends('layouts.app')

@section('title', 'Team Members & Staff — Admin Control')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header with Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-tg-dark tracking-tight">Internal Team Members</h1>
            <p class="text-xs text-slate-500 mt-1">Manage sales representatives, telecallers, and operations staff.</p>
        </div>
        <a href="{{ route('admin.team-members.create') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Add Team Member
        </a>
    </div>

    <!-- KPI / Status Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200/80 pb-3">
        <a href="{{ route('admin.team-members.index') }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'all' ? 'bg-tg-purple text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            All Staff ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.team-members.index', ['status' => 'active']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'active' ? 'bg-tg-green text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Active ({{ $counts['active'] }})
        </a>
        <a href="{{ route('admin.team-members.index', ['status' => 'suspended']) }}"
           class="px-3.5 py-1.5 rounded-xl text-xs font-bold transition {{ $status === 'suspended' ? 'bg-tg-pink text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' }}">
            Inactive / Suspended ({{ $counts['suspended'] }})
        </a>
    </div>

    <!-- Search & Filters Card -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <form method="GET" action="{{ route('admin.team-members.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            @if($status !== 'all')
                <input type="hidden" name="status" value="{{ $status }}">
            @endif
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, email, phone, designation..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>
            <div class="flex items-center space-x-2">
                <select name="department" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl text-xs transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Team Members Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        @if($teamMembers->isEmpty())
            <div class="p-12 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <h3 class="text-sm font-bold text-tg-dark">No team members found</h3>
                <p class="text-xs text-slate-500 mt-1">Try adjusting your search criteria or onboard a new team member.</p>
                <div class="mt-4">
                    <a href="{{ route('admin.team-members.create') }}" class="inline-flex items-center px-4 py-2 bg-tg-purple text-white text-xs font-bold rounded-xl shadow-sm">
                        Add First Team Member
                    </a>
                </div>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="p-4">Name / Contact</th>
                            <th class="p-4">Department & Role</th>
                            <th class="p-4">Assigned Leads</th>
                            <th class="p-4">Status</th>
                            <th class="p-4">Joined</th>
                            <th class="p-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($teamMembers as $member)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-tg-purple to-tg-blue text-white font-bold flex items-center justify-center text-xs shadow-sm flex-shrink-0">
                                            {{ strtoupper(substr($member->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('admin.team-members.show', $member) }}" class="font-bold text-tg-dark hover:text-tg-purple text-xs">
                                                {{ $member->name }}
                                            </a>
                                            <div class="text-[10px] text-tg-purple font-mono font-semibold">{{ $member->employee_id ?: $member->employee_id_display }}</div>
                                            <div class="text-[11px] text-slate-500">{{ $member->email }}</div>
                                            @if($member->phone)
                                                <div class="text-[10px] text-slate-400 font-mono">{{ $member->phone }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="font-semibold text-tg-dark">{{ $member->designation ?? 'Sales Executive' }}</div>
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 mt-0.5">
                                        {{ $member->department ?? 'Sales' }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-bold text-tg-dark">{{ $member->assigned_leads_count }}</span>
                                    <span class="text-[11px] text-slate-400">leads</span>
                                </td>
                                <td class="p-4">
                                    @if($member->isActive())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                            Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                                            Inactive
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-slate-500 text-[11px]">
                                    {{ $member->created_at->format('d M Y') }}
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <a href="{{ route('admin.team-members.show', $member) }}"
                                       class="inline-block px-2.5 py-1 text-tg-purple hover:bg-tg-purple-light rounded-lg font-bold">
                                        View
                                    </a>
                                    <a href="{{ route('admin.team-members.edit', $member) }}"
                                       class="inline-block px-2.5 py-1 text-slate-600 hover:bg-slate-100 rounded-lg font-bold">
                                        Edit
                                    </a>
                                    <form action="{{ route('admin.team-members.toggle-status', $member) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1 text-xs font-bold rounded-lg {{ $member->isActive() ? 'text-amber-700 hover:bg-amber-50' : 'text-tg-green hover:bg-tg-green-light' }}">
                                            {{ $member->isActive() ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $teamMembers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
