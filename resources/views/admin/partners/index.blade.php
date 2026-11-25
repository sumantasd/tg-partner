@extends('layouts.app')

@section('title', 'Partner Directory & Management — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 w-full">
        <div class="min-w-0 flex-1">
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Partner Directory & Management</h1>
            <p class="text-xs text-slate-500 mt-1">Manage referral partners, process onboarding approvals, configure commission rates, and track performance.</p>
        </div>
        <div class="flex items-center space-x-3 flex-shrink-0">
            <a href="{{ route('admin.partners.create') }}"
               class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition whitespace-nowrap">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add New Partner
            </a>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 text-xs font-bold overflow-x-auto w-full pb-1">
        <a href="{{ route('admin.partners.index', ['status' => 'all']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'all' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            All Partners ({{ $counts['all'] }})
        </a>
        <a href="{{ route('admin.partners.index', ['status' => 'pending']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'pending' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Pending Review ({{ $counts['pending'] }})
        </a>
        <a href="{{ route('admin.partners.index', ['status' => 'active']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'active' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Active Partners ({{ $counts['active'] }})
        </a>
        <a href="{{ route('admin.partners.index', ['status' => 'suspended']) }}"
           class="px-4 py-3 border-b-2 whitespace-nowrap {{ $status === 'suspended' ? 'border-tg-purple text-tg-purple' : 'border-transparent text-slate-500 hover:text-tg-dark' }}">
            Suspended ({{ $counts['suspended'] }})
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm w-full max-w-full">
        <form method="GET" action="{{ route('admin.partners.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 w-full">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="flex-1 w-full min-w-0">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by partner code, company name, contact person, email, or city..."
                       class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>
            <div class="flex items-center space-x-2 w-full sm:w-auto flex-shrink-0">
                <button type="submit" class="w-full sm:w-auto px-5 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm whitespace-nowrap">
                    Search
                </button>
                @if($search)
                    <a href="{{ route('admin.partners.index', ['status' => $status]) }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold whitespace-nowrap text-center">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 whitespace-nowrap">Partner Code</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Company & Contact</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Email & Mobile</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Location</th>
                        <th class="px-5 py-3.5 text-center whitespace-nowrap">Portfolio</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($partners as $p)
                        <tr class="hover:bg-slate-50/75 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.partners.show', $p) }}" class="hover:underline">
                                    {{ $p->partner_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $p->company_name }}</div>
                                <div class="text-[11px] text-slate-500">{{ $p->user->name }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-mono text-tg-dark">{{ $p->user->email }}</div>
                                <div class="text-slate-500 font-mono text-[11px] mt-0.5">{{ $p->phone }}</div>
                            </td>
                            <td class="px-5 py-4 text-slate-500">
                                {{ $p->city ? $p->city . ', ' : '' }}{{ $p->state ?? 'India' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <div class="inline-flex items-center space-x-2 text-[11px]">
                                    <span title="Leads" class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold">{{ $p->leads_count ?? 0 }}L</span>
                                    <span title="Projects" class="px-2 py-0.5 rounded bg-tg-purple-light text-tg-purple font-bold">{{ $p->projects_count ?? 0 }}P</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-md px-2.5 py-0.5 text-[11px] font-bold border
                                    {{ $p->user->isActive() ? 'bg-tg-green-light text-tg-green border-tg-green-border' : '' }}
                                    {{ $p->user->isPending() ? 'bg-amber-50 text-amber-700 border-amber-200' : '' }}
                                    {{ $p->user->isSuspended() ? 'bg-tg-pink-light text-tg-pink border-tg-pink-border' : '' }}">
                                    {{ $p->user->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('admin.partners.show', $p) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-tg-purple hover:bg-slate-50 font-semibold transition text-xs">
                                    View
                                </a>
                                <a href="{{ route('admin.partners.edit', $p) }}"
                                   class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-tg-purple hover:bg-slate-50 font-semibold transition text-xs">
                                    Edit
                                </a>
                                @if ($p->user->isPending())
                                    <form action="{{ route('admin.partners.approve', $p) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-tg-green text-white font-bold text-xs hover:bg-tg-green-hover transition">
                                            Approve
                                        </button>
                                    </form>
                                @elseif ($p->user->isActive())
                                    <form action="{{ route('admin.partners.suspend', $p) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-tg-pink-light text-tg-pink border border-tg-pink-border font-bold text-xs hover:bg-tg-pink-border transition">
                                            Suspend
                                        </button>
                                    </form>
                                @elseif ($p->user->isSuspended())
                                    <form action="{{ route('admin.partners.activate', $p) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover transition">
                                            Activate
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-xs text-slate-500">
                                No partner records found matching your filter criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($partners->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $partners->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
