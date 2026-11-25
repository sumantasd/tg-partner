@extends('layouts.app')

@section('title', 'Lead Disputes Management')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Lead Ownership Disputes</h1>
            <p class="text-xs text-slate-500 mt-1">
                Authoritative conflict management and dispute arbitration for partner referral claims and duplicate contentions.
            </p>
        </div>
    </div>

    <!-- Status KPI Metric Tabs -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <a href="{{ route('admin.lead-disputes.index') }}" class="p-4 rounded-2xl border transition {{ !request('status') ? 'bg-tg-purple text-white border-tg-purple shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-tg-purple' }}">
            <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">All Disputes</div>
            <div class="text-2xl font-extrabold mt-1 font-mono">{{ $counts['total'] }}</div>
        </a>
        <a href="{{ route('admin.lead-disputes.index', ['status' => 'pending']) }}" class="p-4 rounded-2xl border transition {{ request('status') === 'pending' ? 'bg-amber-600 text-white border-amber-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-amber-400' }}">
            <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">Pending Review</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-amber-600 {{ request('status') === 'pending' ? 'text-white' : '' }}">{{ $counts['pending'] }}</div>
        </a>
        <a href="{{ route('admin.lead-disputes.index', ['status' => 'under_review']) }}" class="p-4 rounded-2xl border transition {{ request('status') === 'under_review' ? 'bg-sky-600 text-white border-sky-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-sky-400' }}">
            <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">Under Review</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-sky-600 {{ request('status') === 'under_review' ? 'text-white' : '' }}">{{ $counts['under_review'] }}</div>
        </a>
        <a href="{{ route('admin.lead-disputes.index', ['status' => 'resolved']) }}" class="p-4 rounded-2xl border transition {{ request('status') === 'resolved' ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-emerald-400' }}">
            <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">Resolved</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-emerald-600 {{ request('status') === 'resolved' ? 'text-white' : '' }}">{{ $counts['resolved'] }}</div>
        </a>
        <a href="{{ route('admin.lead-disputes.index', ['status' => 'rejected']) }}" class="p-4 rounded-2xl border transition {{ request('status') === 'rejected' ? 'bg-rose-600 text-white border-rose-600 shadow-sm' : 'bg-white text-slate-700 border-slate-200 hover:border-rose-400' }}">
            <div class="text-[10px] uppercase font-bold tracking-wider opacity-80">Rejected</div>
            <div class="text-2xl font-extrabold mt-1 font-mono text-rose-600 {{ request('status') === 'rejected' ? 'text-white' : '' }}">{{ $counts['rejected'] }}</div>
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm">
        <form action="{{ route('admin.lead-disputes.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by dispute #, claimant partner, claimed phone/email..." class="w-full rounded-xl border border-slate-300 px-3.5 py-2 text-xs focus:border-tg-purple outline-none">
            </div>
            <div>
                <select name="dispute_category" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                    <option value="">All Categories</option>
                    @foreach(\App\Enums\LeadDisputeCategory::cases() as $cat)
                        <option value="{{ $cat->value }}" {{ request('dispute_category') === $cat->value ? 'selected' : '' }}>{{ $cat->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="flex-1 px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'dispute_category', 'status']))
                    <a href="{{ route('admin.lead-disputes.index') }}" class="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Disputes Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Dispute #</th>
                        <th class="py-3 px-4">Claimant Partner</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Claimed Contact / Lead</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Submitted</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($disputes as $disp)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.lead-disputes.show', $disp) }}" class="hover:underline">
                                    {{ $disp->dispute_number }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-tg-dark">{{ $disp->partner->company_name }}</div>
                                <div class="text-[11px] font-mono text-slate-400">{{ $disp->partner->partner_code }}</div>
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                {{ $disp->dispute_category->label() }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($disp->lead)
                                    <a href="{{ route('admin.leads.show', $disp->lead) }}" class="font-bold text-tg-purple hover:underline">
                                        Lead #{{ $disp->lead->lead_number }}
                                    </a>
                                    <div class="text-[11px] text-slate-500">{{ $disp->lead->customer_name }}</div>
                                @elseif($disp->customer_phone_claimed)
                                    <div class="font-mono text-slate-700">{{ $disp->customer_phone_claimed }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $disp->customer_email_claimed ?? 'No email' }}</div>
                                @else
                                    <span class="text-slate-400 italic">Unlinked Claim</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $disp->status->badgeClass() }}">
                                    {{ $disp->status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 font-mono text-slate-500 whitespace-nowrap text-[11px]">
                                {{ $disp->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <a href="{{ route('admin.lead-disputes.show', $disp) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                                    Manage &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-400 italic">
                                No disputes found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($disputes->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $disputes->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
