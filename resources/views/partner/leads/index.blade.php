@extends('layouts.app')

@section('title', 'My Submitted Leads')

@section('content')
<div class="space-y-6">
    <!-- Header with Action -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">My Customer Leads</h1>
            <p class="text-xs text-slate-500 mt-1">
                Track submitted client referrals, follow-up progress, and deal milestones handled by Tech Googly sales consultants.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('partner.leads.create') }}"
               class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Submit New Client Lead
            </a>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
        <a href="{{ route('partner.leads.index') }}"
           class="p-4 rounded-2xl bg-white border {{ !request('status') ? 'border-tg-purple ring-2 ring-tg-purple/20' : 'border-slate-200' }} shadow-sm hover:border-slate-300 transition">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Referrals</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">{{ $statusCounts['all'] }}</div>
        </a>
        <a href="{{ route('partner.leads.index', ['status' => 'submitted']) }}"
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'submitted' ? 'border-amber-500 ring-2 ring-amber-100' : 'border-slate-200' }} shadow-sm hover:border-slate-300 transition">
            <div class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Submitted</div>
            <div class="text-2xl font-bold text-amber-600 mt-1">{{ $statusCounts['submitted'] }}</div>
        </a>
        <a href="{{ route('partner.leads.index', ['status' => 'in_discussion']) }}"
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'in_discussion' ? 'border-tg-blue ring-2 ring-tg-blue/20' : 'border-slate-200' }} shadow-sm hover:border-slate-300 transition">
            <div class="text-[11px] font-bold text-tg-blue uppercase tracking-wider">In Pipeline</div>
            <div class="text-2xl font-bold text-tg-blue mt-1">{{ $statusCounts['in_discussion'] }}</div>
        </a>
        <a href="{{ route('partner.leads.index', ['status' => 'won']) }}"
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'won' ? 'border-tg-green ring-2 ring-tg-green/20' : 'border-slate-200' }} shadow-sm hover:border-slate-300 transition">
            <div class="text-[11px] font-bold text-tg-green uppercase tracking-wider">Won / Converted</div>
            <div class="text-2xl font-bold text-tg-green mt-1">{{ $statusCounts['won'] }}</div>
        </a>
        <a href="{{ route('partner.leads.index', ['status' => 'lost']) }}"
           class="p-4 rounded-2xl bg-white border {{ request('status') === 'lost' ? 'border-slate-400 ring-2 ring-slate-100' : 'border-slate-200' }} shadow-sm hover:border-slate-300 transition">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Closed / Lost</div>
            <div class="text-2xl font-bold text-slate-600 mt-1">{{ $statusCounts['lost'] }}</div>
        </a>
    </div>

    <!-- Filter and Search Bar -->
    <div class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('partner.leads.index') }}" class="flex-1 flex flex-col sm:flex-row gap-3 w-full">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by Lead ID, Customer Name, Company, or Service..."
                       class="w-full rounded-xl border border-slate-300 pl-10 pr-4 py-2 text-xs focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none">
                <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <div class="flex items-center space-x-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Search
                </button>
                @if(request('search') || request('status'))
                    <a href="{{ route('partner.leads.index') }}" class="px-3 py-2 text-xs font-semibold text-slate-500 hover:text-tg-dark">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Leads Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-slate-600 font-semibold text-xs">
                    <tr>
                        <th class="px-6 py-3.5">Lead Reference</th>
                        <th class="px-6 py-3.5">Customer & Organization</th>
                        <th class="px-6 py-3.5">Service Requested</th>
                        <th class="px-6 py-3.5">Estimated Budget</th>
                        <th class="px-6 py-3.5">Deal Status</th>
                        <th class="px-6 py-3.5">Assigned Consultant</th>
                        <th class="px-6 py-3.5 text-right">Timeline</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-700">
                    @forelse ($leads as $lead)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('partner.leads.show', $lead) }}" class="hover:underline">
                                    {{ $lead->lead_number }}
                                </a>
                                <div class="text-[11px] font-sans text-slate-400 mt-0.5">
                                    {{ $lead->created_at->format('M d, Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-tg-dark">{{ $lead->customer_name }}</div>
                                @if($lead->customer_company)
                                    <div class="text-xs text-slate-500">{{ $lead->customer_company }}</div>
                                @endif
                                @if($lead->city)
                                    <div class="text-[11px] text-slate-400">{{ $lead->city }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs font-medium text-tg-dark">
                                {{ $lead->service_required }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-tg-dark text-xs">
                                @if($lead->estimated_value)
                                    {{ $lead->currency }} {{ number_format($lead->estimated_value, 2) }}
                                @else
                                    <span class="text-xs text-slate-400">Undisclosed</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusBadge = match($lead->status) {
                                        \App\Enums\LeadStatus::Submitted => 'bg-amber-50 text-amber-700 border-amber-200',
                                        \App\Enums\LeadStatus::Assigned => 'bg-tg-blue-light text-tg-blue border-tg-blue-border',
                                        \App\Enums\LeadStatus::Contacted,
                                        \App\Enums\LeadStatus::InDiscussion => 'bg-tg-purple-light text-tg-purple border-tg-purple-border',
                                        \App\Enums\LeadStatus::QuotationSent,
                                        \App\Enums\LeadStatus::Negotiating => 'bg-tg-pink-light text-tg-pink border-tg-pink-border',
                                        \App\Enums\LeadStatus::Won => 'bg-tg-green-light text-tg-green border-tg-green-border',
                                        \App\Enums\LeadStatus::Lost,
                                        \App\Enums\LeadStatus::Rejected => 'bg-slate-100 text-slate-600 border-slate-200',
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold border {{ $statusBadge }}">
                                    {{ $lead->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                @if($lead->assignedTo)
                                    <div class="font-bold text-tg-dark">{{ $lead->assignedTo->name }}</div>
                                    <div class="text-slate-400 text-[11px]">Enterprise Consultant</div>
                                @else
                                    <span class="text-slate-400 italic">Under Management Review</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('partner.leads.show', $lead) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold text-tg-purple bg-tg-purple-light hover:bg-tg-purple hover:text-white border border-tg-purple-border transition">
                                    View Progress &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="max-w-sm mx-auto">
                                    <div class="w-12 h-12 rounded-2xl bg-tg-purple-light text-tg-purple flex items-center justify-center mx-auto mb-3">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <div class="font-bold text-tg-dark">No client leads found</div>
                                    <p class="text-xs text-slate-500 mt-1 mb-4">You have not submitted any customer leads under this filter criteria.</p>
                                    <a href="{{ route('partner.leads.create') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                                        Submit A New Lead
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($leads->hasPages())
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
