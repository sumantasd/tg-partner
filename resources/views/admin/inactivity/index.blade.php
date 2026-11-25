@extends('layouts.app')

@section('title', 'Partner & Staff Inactivity Monitor')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto" x-data="{
    warningModalOpen: false,
    flagModalOpen: false,
    suspendModalOpen: false,
    reactivateModalOpen: false,
    selectedUser: null,
    selectedUserName: '',
    actionUrl: '',
    openWarningModal(userId, name) {
        this.selectedUser = userId;
        this.selectedUserName = name;
        this.actionUrl = '{{ url('admin/inactivity') }}/' + userId + '/warning';
        this.warningModalOpen = true;
    },
    openFlagModal(userId, name) {
        this.selectedUser = userId;
        this.selectedUserName = name;
        this.actionUrl = '{{ url('admin/inactivity') }}/' + userId + '/flag';
        this.flagModalOpen = true;
    },
    openSuspendModal(userId, name) {
        this.selectedUser = userId;
        this.selectedUserName = name;
        this.actionUrl = '{{ url('admin/inactivity') }}/' + userId + '/suspend';
        this.suspendModalOpen = true;
    },
    openReactivateModal(userId, name) {
        this.selectedUser = userId;
        this.selectedUserName = name;
        this.actionUrl = '{{ url('admin/inactivity') }}/' + userId + '/reactivate';
        this.reactivateModalOpen = true;
    }
}">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Inactivity Management System</h1>
            <p class="text-xs text-slate-500 mt-1">
                Monitor engagement levels, enforce 30/60/90-day inactivity policies, and manage administrative interventions.
            </p>
        </div>
        <div class="mt-4 sm:mt-0 flex items-center space-x-2.5">
            <a href="{{ route('admin.inactivity.settings') }}" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Inactivity Policies
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center">
            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tabs Navigation -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-px">
        <a href="{{ route('admin.inactivity.index', ['tab' => 'partners']) }}" class="px-5 py-2.5 text-xs font-bold rounded-t-xl transition {{ $tab === 'partners' ? 'bg-white text-tg-purple border-t-2 border-tg-purple shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100' }}">
            Referral Partners ({{ $tab === 'partners' ? $data['counts']['total'] : \App\Models\User::where('role', \App\Enums\UserRole::Partner)->count() }})
        </a>
        <a href="{{ route('admin.inactivity.index', ['tab' => 'staff']) }}" class="px-5 py-2.5 text-xs font-bold rounded-t-xl transition {{ $tab === 'staff' ? 'bg-white text-tg-purple border-t-2 border-tg-purple shadow-sm' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100' }}">
            Sales Staff Members ({{ $tab === 'staff' ? $data['counts']['total'] : \App\Models\User::where('role', \App\Enums\UserRole::TeamMember)->count() }})
        </a>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
        <!-- 1. Total Accounts -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Enrolled</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-tg-dark">{{ $data['counts']['total'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">{{ $tab === 'partners' ? 'Approved Partners' : 'Active Staff' }}</div>
        </div>

        <!-- 2. Active -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-600">Active (0-29d)</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-emerald-600">{{ $data['counts']['active'] }}</div>
            <div class="text-[11px] text-emerald-700 font-medium mt-1">Normal activity</div>
        </div>

        <!-- 3. At Risk -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-amber-600">At Risk (30d+)</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-amber-600">{{ $data['counts']['at_risk'] }}</div>
            <div class="text-[11px] text-amber-700 font-medium mt-1">Warning threshold</div>
        </div>

        <!-- 4. Inactive -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-orange-600">Inactive (60d+)</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-orange-600">{{ $data['counts']['inactive'] }}</div>
            <div class="text-[11px] text-orange-700 font-medium mt-1">Alert threshold</div>
        </div>

        <!-- 5. Critical -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-rose-600">Critical (90d+)</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-rose-600">{{ $data['counts']['critical'] }}</div>
            <div class="text-[11px] text-rose-700 font-medium mt-1">Requires review</div>
        </div>

        <!-- 6. Suspended -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-600">Suspended</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-slate-700">{{ $data['counts']['suspended'] }}</div>
            <div class="text-[11px] text-slate-500 mt-1">Access restricted</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form action="{{ route('admin.inactivity.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search by name, email, company, partner code, employee ID..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:border-tg-purple outline-none">
            </div>
            <div>
                <select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Inactivity Statuses</option>
                    @foreach(\App\Enums\InactivityStatus::cases() as $st)
                        <option value="{{ $st->value }}" {{ ($filters['status'] ?? '') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                    Filter
                </button>
                @if(!empty($filters['search']) || !empty($filters['status']) || !empty($filters['flagged_only']))
                    <a href="{{ route('admin.inactivity.index', ['tab' => $tab]) }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if($tab === 'partners')
        <!-- Partners Inactivity Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800">Partner Activity &amp; Engagement Roster</h2>
                <span class="text-xs text-slate-500">Tracking Referral Pipeline Vitality</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Partner Details</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Days Inactive</th>
                            <th class="py-3 px-4">Last Login</th>
                            <th class="py-3 px-4">Last Meaningful Activity</th>
                            <th class="py-3 px-4">Last Lead Submission</th>
                            <th class="py-3 px-4 text-center">Performance</th>
                            <th class="py-3 px-4 text-right">Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['partners'] as $pUser)
                            @php
                                $partner = $pUser->partner;
                                $inactivityDays = $pUser->getInactivityDays();
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition {{ $pUser->inactivity_flagged_for_review ? 'bg-amber-50/30' : '' }}">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ $pUser->name }}</div>
                                    <div class="text-[11px] text-tg-purple font-mono font-semibold">{{ $partner->company_name ?? 'Individual' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $partner->partner_code ?? 'N/A' }} &bull; {{ $pUser->email }}</div>
                                    @if($pUser->inactivity_flagged_for_review)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200 mt-1">
                                            Flagged for Review
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $pUser->inactivity_status->badgeClasses() }}">
                                        {{ $pUser->inactivity_status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-sm {{ $inactivityDays >= 90 ? 'text-rose-600' : ($inactivityDays >= 60 ? 'text-orange-600' : ($inactivityDays >= 30 ? 'text-amber-600' : 'text-slate-700')) }}">
                                    {{ $inactivityDays }}d
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $pUser->last_login_at?->format('d M Y, h:i A') ?? 'Never logged in' }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $pUser->last_meaningful_activity_at?->format('d M Y, h:i A') ?? 'No activity' }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $pUser->last_lead_submission_at?->format('d M Y, h:i A') ?? 'None submitted' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-mono font-bold text-slate-800">{{ $partner?->leads?->count() ?? 0 }}</span>
                                    <span class="text-[10px] text-slate-400 block">leads</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button type="button" @click="openWarningModal({{ $pUser->id }}, '{{ addslashes($pUser->name) }}')" title="Send Inactivity Warning" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 border border-slate-200 hover:border-amber-300 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </button>
                                        @if(!$pUser->inactivity_flagged_for_review)
                                            <button type="button" @click="openFlagModal({{ $pUser->id }}, '{{ addslashes($pUser->name) }}')" title="Flag for Review" class="p-1.5 rounded-lg text-tg-blue hover:bg-blue-50 border border-slate-200 hover:border-blue-300 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.inactivity.unflag', $pUser->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" title="Clear Review Flag" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if($pUser->status !== \App\Enums\UserStatus::Suspended)
                                            <button type="button" @click="openSuspendModal({{ $pUser->id }}, '{{ addslashes($pUser->name) }}')" title="Suspend Account" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 border border-slate-200 hover:border-rose-300 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        @else
                                            <button type="button" @click="openReactivateModal({{ $pUser->id }}, '{{ addslashes($pUser->name) }}')" title="Reactivate Account" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition font-bold text-[10px]">
                                                Reactivate
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.inactivity.history', $pUser->id) }}" title="View Inactivity History" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 border border-slate-200 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No partner records match the selected inactivity criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data['partners']->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $data['partners']->links() }}
                </div>
            @endif
        </div>
    @else
        <!-- Staff Inactivity Table -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-800">Staff CRM Engagement &amp; Activity Roster</h2>
                <span class="text-xs text-slate-500">Tracking Telecalling Team Adherence</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-3 px-4">Staff Member</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Days Inactive</th>
                            <th class="py-3 px-4">Last Login</th>
                            <th class="py-3 px-4">Last Meaningful Activity</th>
                            <th class="py-3 px-4">Last Customer Contact</th>
                            <th class="py-3 px-4 text-center">Assigned Leads</th>
                            <th class="py-3 px-4 text-right">Admin Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($data['staff'] as $sUser)
                            @php
                                $inactivityDays = $sUser->getInactivityDays();
                            @endphp
                            <tr class="hover:bg-slate-50/60 transition {{ $sUser->inactivity_flagged_for_review ? 'bg-amber-50/30' : '' }}">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-800">{{ $sUser->name }}</div>
                                    <div class="text-[11px] text-tg-blue font-mono font-semibold">{{ $sUser->employee_id_display }} &bull; {{ $sUser->designation ?? 'Sales CRM' }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $sUser->email }}</div>
                                    @if($sUser->inactivity_flagged_for_review)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 border border-amber-200 mt-1">
                                            Flagged for Review
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $sUser->inactivity_status->badgeClasses() }}">
                                        {{ $sUser->inactivity_status->label() }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold text-sm {{ $inactivityDays >= 90 ? 'text-rose-600' : ($inactivityDays >= 60 ? 'text-orange-600' : ($inactivityDays >= 30 ? 'text-amber-600' : 'text-slate-700')) }}">
                                    {{ $inactivityDays }}d
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $sUser->last_login_at?->format('d M Y, h:i A') ?? 'Never logged in' }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $sUser->last_meaningful_activity_at?->format('d M Y, h:i A') ?? 'No activity' }}
                                </td>
                                <td class="py-3 px-4 font-mono text-[11px] text-slate-600">
                                    {{ $sUser->last_customer_interaction_at?->format('d M Y, h:i A') ?? 'None logged' }}
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-mono font-bold text-slate-800">{{ $sUser->assignedLeads->count() }}</span>
                                    <span class="text-[10px] text-slate-400 block">leads</span>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <div class="flex items-center justify-end space-x-1.5">
                                        <button type="button" @click="openWarningModal({{ $sUser->id }}, '{{ addslashes($sUser->name) }}')" title="Send Inactivity Warning" class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 border border-slate-200 hover:border-amber-300 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        </button>
                                        @if(!$sUser->inactivity_flagged_for_review)
                                            <button type="button" @click="openFlagModal({{ $sUser->id }}, '{{ addslashes($sUser->name) }}')" title="Flag for Review" class="p-1.5 rounded-lg text-tg-blue hover:bg-blue-50 border border-slate-200 hover:border-blue-300 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/></svg>
                                            </button>
                                        @else
                                            <form action="{{ route('admin.inactivity.unflag', $sUser->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" title="Clear Review Flag" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </form>
                                        @endif
                                        @if($sUser->status !== \App\Enums\UserStatus::Suspended)
                                            <button type="button" @click="openSuspendModal({{ $sUser->id }}, '{{ addslashes($sUser->name) }}')" title="Suspend Account" class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 border border-slate-200 hover:border-rose-300 transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            </button>
                                        @else
                                            <button type="button" @click="openReactivateModal({{ $sUser->id }}, '{{ addslashes($sUser->name) }}')" title="Reactivate Account" class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 transition font-bold text-[10px]">
                                                Reactivate
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.inactivity.history', $sUser->id) }}" title="View Inactivity History" class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 border border-slate-200 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-400">
                                    No staff records match the selected inactivity criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($data['staff']->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $data['staff']->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Modal 1: Send Manual Warning -->
    <div x-show="warningModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="warningModalOpen" @click="warningModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="warningModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-slate-200">
                <form :action="actionUrl" method="POST">
                    @csrf
                    <h3 class="text-sm font-bold text-slate-800">Dispatch Inactivity Warning</h3>
                    <p class="text-xs text-slate-500 mt-1">Send an official in-app reminder to <strong x-text="selectedUserName"></strong>.</p>
                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Reason / Warning Message <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="State reason for warning and recommended action..." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none"></textarea>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="warningModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-amber-600 hover:bg-amber-700 transition shadow-sm">Send Warning</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 2: Flag for Review -->
    <div x-show="flagModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="flagModalOpen" @click="flagModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="flagModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-slate-200">
                <form :action="actionUrl" method="POST">
                    @csrf
                    <h3 class="text-sm font-bold text-slate-800">Flag Account for Review</h3>
                    <p class="text-xs text-slate-500 mt-1">Mark <strong x-text="selectedUserName"></strong> for internal evaluation.</p>
                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Review Notes / Reason <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="Specify why this account needs administrative review..." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none"></textarea>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="flagModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-blue hover:bg-blue-700 transition shadow-sm">Flag for Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 3: Suspend Account -->
    <div x-show="suspendModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="suspendModalOpen" @click="suspendModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="suspendModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-slate-200">
                <form :action="actionUrl" method="POST">
                    @csrf
                    <h3 class="text-sm font-bold text-rose-600 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Confirm Account Suspension
                    </h3>
                    <p class="text-xs text-slate-600 mt-1">You are suspending <strong x-text="selectedUserName"></strong>. The user will be immediately blocked from portal access.</p>
                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Suspension Reason <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="Enter mandatory reason for audit history..." class="w-full rounded-xl border border-rose-300 px-3 py-2 text-xs focus:border-rose-500 outline-none"></textarea>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="suspendModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm">Suspend Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal 4: Reactivate Account -->
    <div x-show="reactivateModalOpen" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="reactivateModalOpen" @click="reactivateModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div x-show="reactivateModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 border border-slate-200">
                <form :action="actionUrl" method="POST">
                    @csrf
                    <h3 class="text-sm font-bold text-emerald-600 flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Reactivate Account
                    </h3>
                    <p class="text-xs text-slate-600 mt-1">Restore portal login and active status for <strong x-text="selectedUserName"></strong>.</p>
                    <div class="mt-4">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Reactivation Reason <span class="text-rose-500">*</span></label>
                        <textarea name="reason" rows="3" required placeholder="Enter mandatory reason for reactivation..." class="w-full rounded-xl border border-emerald-300 px-3 py-2 text-xs focus:border-emerald-500 outline-none"></textarea>
                    </div>
                    <div class="mt-5 flex justify-end space-x-2">
                        <button type="button" @click="reactivateModalOpen = false" class="px-3.5 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">Reactivate Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
