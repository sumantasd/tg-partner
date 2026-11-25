@extends('layouts.app')

@section('title', 'Team Member Profile — ' . $teamMember->name)

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Breadcrumb & Top Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.team-members.index') }}" class="inline-flex items-center text-tg-purple hover:underline font-bold">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Back to Team Members
            </a>
            <span>/</span>
            <span class="text-tg-dark font-bold truncate">{{ $teamMember->name }}</span>
        </div>
        <div class="flex items-center flex-wrap gap-2.5">
            <a href="{{ route('admin.team-members.index') }}"
               class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z"/>
                </svg>
                Back to Team Members
            </a>

            <a href="{{ route('admin.team-members.edit', $teamMember) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple-hover shadow-sm transition">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Team Member
            </a>

            <button type="button" onclick="document.getElementById('reset-password-modal').classList.remove('hidden')"
                    class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-sm">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Reset Password
            </button>

            <form action="{{ route('admin.team-members.toggle-status', $teamMember) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm {{ $teamMember->isActive() ? 'text-amber-800 bg-amber-50 border border-amber-200 hover:bg-amber-100' : 'text-white bg-tg-green hover:bg-tg-green-hover' }}">
                    {{ $teamMember->isActive() ? 'Suspend Account' : 'Activate Account' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Profile Header & Details Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 pb-6 border-b border-slate-100">
            <div class="flex items-start space-x-4">
                @if ($teamMember->profile_photo_url)
                    <img src="{{ $teamMember->profile_photo_url }}" alt="{{ $teamMember->name }}" class="w-16 h-16 rounded-2xl object-cover shadow-sm border border-slate-200 flex-shrink-0">
                @else
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-tg-purple to-tg-blue text-white font-extrabold text-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                        {{ strtoupper(substr($teamMember->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center space-x-3 flex-wrap gap-y-1">
                        <h1 class="text-2xl font-extrabold text-tg-dark tracking-tight">{{ $teamMember->name }}</h1>
                        @if($teamMember->isActive())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                Active Staff
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                                Suspended / Inactive
                            </span>
                        @endif
                    </div>
                    <div class="text-xs text-slate-500 mt-1 flex items-center gap-3 flex-wrap">
                        <span class="font-bold text-slate-700">{{ $teamMember->designation ?? 'Sales Executive' }}</span>
                        <span>&bull;</span>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-semibold">{{ $teamMember->department ?? 'Sales' }}</span>
                        <span>&bull;</span>
                        <span class="font-mono text-tg-purple font-semibold">{{ $teamMember->employee_id ?: $teamMember->employee_id_display }}</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:items-end text-xs text-slate-500 space-y-1">
                <div>Account Role: <strong class="text-tg-purple font-semibold">{{ $teamMember->role->label() }}</strong></div>
                <div>Created Date: <strong class="text-tg-dark">{{ $teamMember->created_at ? $teamMember->created_at->format('d M Y, h:i A') : 'N/A' }}</strong></div>
                <div>Last Login: <strong class="text-tg-dark">{{ $teamMember->last_login_at ? $teamMember->last_login_at->format('d M Y, h:i A') : 'Never logged in' }}</strong></div>
            </div>
        </div>

        <!-- 8 Detailed Information Tiles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 py-6 border-b border-slate-100">
            <!-- 1. Full Legal Name -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Full Name</div>
                <div class="text-xs font-bold text-tg-dark mt-1 truncate">{{ $teamMember->name }}</div>
            </div>

            <!-- 2. Email Address -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Official Email</div>
                <div class="text-xs font-bold text-tg-purple mt-1 truncate">
                    <a href="mailto:{{ $teamMember->email }}" class="hover:underline">{{ $teamMember->email }}</a>
                </div>
            </div>

            <!-- 3. Mobile Number -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Mobile Number</div>
                <div class="text-xs font-bold text-tg-dark mt-1 font-mono">
                    {{ $teamMember->phone ?: ($teamMember->mobile_number ?: 'Not provided') }}
                </div>
            </div>

            <!-- 4. Employee ID -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Employee ID</div>
                <div class="text-xs font-bold text-tg-dark mt-1 font-mono">
                    {{ $teamMember->employee_id ?: $teamMember->employee_id_display }}
                </div>
            </div>

            <!-- 5. Role -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Assigned Role</div>
                <div class="text-xs font-bold text-tg-dark mt-1">{{ $teamMember->role->label() }}</div>
            </div>

            <!-- 6. Account Status -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Account Status</div>
                <div class="text-xs font-bold mt-1 {{ $teamMember->isActive() ? 'text-tg-green' : 'text-tg-pink' }}">
                    {{ $teamMember->status->label() }}
                </div>
            </div>

            <!-- 7. Joining Date -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Joining Date</div>
                <div class="text-xs font-bold text-tg-dark mt-1">
                    {{ $teamMember->joining_date ? $teamMember->joining_date->format('d M Y') : ($teamMember->created_at ? $teamMember->created_at->format('d M Y') : 'Not specified') }}
                </div>
            </div>

            <!-- 8. Last Activity / Login -->
            <div class="p-3.5 bg-slate-50/70 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last Login Tracked</div>
                <div class="text-xs font-bold text-slate-600 mt-1">
                    {{ $teamMember->last_login_at ? $teamMember->last_login_at->format('d M Y, h:i A') : 'Never logged in' }}
                </div>
            </div>
        </div>

        <!-- 4 Performance Summary Cards -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6">
            <!-- 1. Total Leads Assigned -->
            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Assigned Leads Count</div>
                <div class="text-2xl font-extrabold text-tg-dark mt-1">{{ number_format($stats['total_assigned']) }}</div>
                <div class="text-[10px] text-slate-400 mt-0.5">All time assignments</div>
            </div>

            <!-- 2. Active Leads -->
            <div class="p-4 bg-tg-purple-light/40 rounded-2xl border border-tg-purple-border/60">
                <div class="text-[10px] font-bold uppercase tracking-wider text-tg-purple">Active Leads</div>
                <div class="text-2xl font-extrabold text-tg-purple mt-1">{{ number_format($stats['in_progress']) }}</div>
                <div class="text-[10px] text-tg-purple/80 mt-0.5 font-semibold">In active follow-up</div>
            </div>

            <!-- 3. Won Leads -->
            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-800">Won Leads</div>
                <div class="text-2xl font-extrabold text-tg-green mt-1">{{ number_format($stats['won_leads']) }}</div>
                <div class="text-[10px] text-emerald-600 mt-0.5 font-semibold">Converted deals</div>
            </div>

            <!-- 4. Lost Leads -->
            <div class="p-4 bg-rose-50 rounded-2xl border border-rose-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-rose-800">Lost Leads</div>
                <div class="text-2xl font-extrabold text-tg-pink mt-1">{{ number_format($stats['lost_leads']) }}</div>
                <div class="text-[10px] text-rose-600 mt-0.5 font-semibold">Unsuccessful pipeline</div>
            </div>
        </div>
    </div>

    <!-- Assigned Leads Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-tg-dark">Assigned Leads Pipeline</h2>
                <p class="text-xs text-slate-500">Leads assigned to {{ $teamMember->name }} for telecalling and follow-up.</p>
            </div>
            <span class="text-xs font-bold text-tg-purple bg-tg-purple-light px-2.5 py-1 rounded-lg">
                {{ $assignedLeads->total() }} Total Leads
            </span>
        </div>

        @if($assignedLeads->isEmpty())
            <div class="p-12 text-center text-xs text-slate-400">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                No leads are currently assigned to this team member.
            </div>
        @else
            <div class="overflow-x-auto w-full">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <th class="p-4 whitespace-nowrap">Lead #</th>
                            <th class="p-4 whitespace-nowrap">Customer</th>
                            <th class="p-4 whitespace-nowrap">Phone</th>
                            <th class="p-4 whitespace-nowrap">Partner Source</th>
                            <th class="p-4 whitespace-nowrap">Status</th>
                            <th class="p-4 whitespace-nowrap">Assigned On</th>
                            <th class="p-4 text-right whitespace-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($assignedLeads as $lead)
                            <tr class="hover:bg-slate-50/50 transition">
                                <td class="p-4 font-mono font-bold text-tg-purple whitespace-nowrap">{{ $lead->lead_number }}</td>
                                <td class="p-4 font-semibold text-tg-dark whitespace-nowrap">{{ $lead->customer_name }}</td>
                                <td class="p-4 whitespace-nowrap font-mono">{{ $lead->customer_phone }}</td>
                                <td class="p-4 whitespace-nowrap">
                                    @if($lead->partner)
                                        <span class="font-bold text-tg-dark">{{ $lead->partner->company_name }}</span>
                                        <span class="block text-[10px] text-slate-400 font-mono">{{ $lead->partner->partner_code }}</span>
                                    @else
                                        <span class="text-slate-400">Direct Tech Googly</span>
                                    @endif
                                </td>
                                <td class="p-4 whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                        {{ $lead->status->label() }}
                                    </span>
                                </td>
                                <td class="p-4 text-slate-500 whitespace-nowrap">{{ $lead->assigned_at ? $lead->assigned_at->format('d M Y') : $lead->created_at->format('d M Y') }}</td>
                                <td class="p-4 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.leads.show', $lead) }}" class="text-tg-purple hover:underline font-bold">Inspect Lead &rarr;</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-slate-100">
                {{ $assignedLeads->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Password Reset Modal -->
<div id="reset-password-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100">
        <h3 class="text-lg font-bold text-tg-dark">Reset Password for {{ $teamMember->name }}</h3>
        <p class="text-xs text-slate-500 mt-1">Set a new secure password for this team member.</p>

        <form action="{{ route('admin.team-members.reset-password', $teamMember) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">New Password <span class="text-tg-pink">*</span></label>
                <div class="relative">
                    <input type="password" id="modal_team_member_password" name="password" required placeholder="Minimum 8 characters"
                           class="w-full px-3.5 py-2.5 pr-11 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                    <button type="button"
                            data-password-toggle="modal_team_member_password"
                            onclick="togglePasswordVisibility('modal_team_member_password', this)"
                            aria-label="Show password"
                            aria-controls="modal_team_member_password"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                        <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password <span class="text-tg-pink">*</span></label>
                <div class="relative">
                    <input type="password" id="modal_team_member_password_confirmation" name="password_confirmation" required placeholder="Repeat new password"
                           class="w-full px-3.5 py-2.5 pr-11 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                    <button type="button"
                            data-password-toggle="modal_team_member_password_confirmation"
                            onclick="togglePasswordVisibility('modal_team_member_password_confirmation', this)"
                            aria-label="Show password"
                            aria-controls="modal_team_member_password_confirmation"
                            aria-pressed="false"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                        <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                        <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('reset-password-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple-hover transition shadow-sm">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
