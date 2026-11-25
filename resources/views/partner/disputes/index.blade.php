@extends('layouts.app')

@section('title', 'Ownership Disputes')

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Lead Ownership Disputes</h1>
            <p class="text-xs text-slate-500 mt-1">
                Protecting your customer referrals. If an active lead or referral conflict requires administrative review, submit and track your disputes here.
            </p>
        </div>
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('partner.disputes.create') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Raise New Dispute
            </a>
        </div>
    </div>

    <!-- Disputes Listing Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider">
                My Submitted Disputes ({{ $disputes->total() }})
            </h2>
            <div class="flex items-center space-x-2">
                <a href="{{ route('partner.disputes.index') }}" class="px-2.5 py-1 rounded-lg text-xs {{ !request('status') ? 'bg-tg-purple text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">All</a>
                <a href="{{ route('partner.disputes.index', ['status' => 'pending']) }}" class="px-2.5 py-1 rounded-lg text-xs {{ request('status') === 'pending' ? 'bg-amber-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">Pending</a>
                <a href="{{ route('partner.disputes.index', ['status' => 'under_review']) }}" class="px-2.5 py-1 rounded-lg text-xs {{ request('status') === 'under_review' ? 'bg-sky-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">Under Review</a>
                <a href="{{ route('partner.disputes.index', ['status' => 'resolved']) }}" class="px-2.5 py-1 rounded-lg text-xs {{ request('status') === 'resolved' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-600 hover:bg-slate-100' }}">Resolved</a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                        <th class="py-3 px-4">Dispute #</th>
                        <th class="py-3 px-4">Category</th>
                        <th class="py-3 px-4">Claimed Contact / Lead</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4">Submitted Date</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    @forelse ($disputes as $dispute)
                        <tr class="hover:bg-slate-50/80 transition">
                            <td class="py-3.5 px-4 font-mono font-bold text-tg-purple">
                                {{ $dispute->dispute_number }}
                            </td>
                            <td class="py-3.5 px-4 font-medium text-slate-800">
                                {{ $dispute->dispute_category->label() }}
                            </td>
                            <td class="py-3.5 px-4">
                                @if($dispute->lead)
                                    <div class="font-bold text-tg-dark">Lead #{{ $dispute->lead->lead_number }}</div>
                                    <div class="text-[11px] text-slate-500">{{ $dispute->lead->customer_name }}</div>
                                @elseif($dispute->customer_phone_claimed)
                                    <div class="font-mono text-slate-700">{{ $dispute->customer_phone_claimed }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $dispute->customer_email_claimed ?? 'No email' }}</div>
                                @else
                                    <span class="text-slate-400 italic">General Referral Claim</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $dispute->status->badgeClass() }}">
                                    {{ $dispute->status->label() }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-500 font-mono text-[11px]">
                                {{ $dispute->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                            </td>
                            <td class="py-3.5 px-4 text-right">
                                <a href="{{ route('partner.disputes.show', $dispute) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-tg-purple bg-tg-purple-light hover:bg-tg-purple/20 border border-tg-purple-border transition">
                                    View Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400 italic">
                                No ownership disputes raised. Genuine leads submitted are automatically protected under your account.
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
