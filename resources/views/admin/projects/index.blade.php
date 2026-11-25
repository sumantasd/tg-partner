@extends('layouts.app')

@section('title', 'Projects — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Project Management</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage customer projects, assign consultants, monitor contracts, and track commission milestones.</p>
        </div>
        <a href="{{ route('admin.projects.create') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create New Project
        </a>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Projects</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">{{ number_format($metrics['total_projects']) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">In Progress</div>
            <div class="text-2xl font-bold text-tg-blue mt-1">{{ number_format($metrics['in_progress']) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Completed</div>
            <div class="text-2xl font-bold text-tg-green mt-1">{{ number_format($metrics['completed']) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Contract Value</div>
            <div class="text-2xl font-bold text-tg-purple mt-1">₹{{ number_format($metrics['total_value'], 2) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.projects.index') }}" class="grid grid-cols-1 sm:grid-cols-5 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by project code, title, customer..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>

            <div>
                <select name="status" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Statuses</option>
                    @foreach(\App\Enums\ProjectStatus::cases() as $case)
                        <option value="{{ $case->value }}" {{ $status === $case->value ? 'selected' : '' }}>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <select name="partner_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Referral Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ $partnerId == $partner->id ? 'selected' : '' }}>
                            {{ $partner->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="w-full px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Filter
                </button>
                @if($search || $status || $partnerId || $assignedTo)
                    <a href="{{ route('admin.projects.index') }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Project Code</th>
                        <th class="px-5 py-3.5">Title & Customer</th>
                        <th class="px-5 py-3.5">Partner</th>
                        <th class="px-5 py-3.5">Assigned Consultant</th>
                        <th class="px-5 py-3.5">Contract Value</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.projects.show', $project) }}" class="hover:underline">
                                    {{ $project->project_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $project->project_title }}</div>
                                <div class="text-[11px] text-slate-500">
                                    Customer: <strong class="text-slate-700">{{ $project->customer->name ?? $project->client_name }}</strong>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                @if($project->partner)
                                    <div class="font-bold text-tg-dark">{{ $project->partner->company_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">{{ $project->partner->partner_code }}</div>
                                @else
                                    <span class="text-slate-400">Direct</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-700">
                                {{ $project->assignedTo?->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-dark whitespace-nowrap">
                                ₹{{ number_format($project->final_deal_value, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold border {{ $project->status->badgeClasses() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.projects.show', $project) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No projects found matching criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
