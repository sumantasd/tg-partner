@extends('layouts.app')

@section('title', 'Lead #' . $lead->lead_number)

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Back Link and Header -->
    <div>
        <a href="{{ route('partner.leads.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to My Leads
        </a>
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-tg-dark tracking-tight font-mono">{{ $lead->lead_number }}</h1>
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
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border {{ $statusBadge }}">
                        {{ $lead->status->label() }}
                    </span>
                    @if($lead->ownership_status)
                        <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border {{ $lead->ownership_status->badgeClass() }}">
                            Ownership: {{ $lead->ownership_status->label() }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Submitted on {{ $lead->created_at->format('F d, Y \a\t h:i A') }} &bull; Partner Code: <span class="font-mono text-tg-purple font-bold">{{ $partner->partner_code }}</span>
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                @if($lead->assignedTo)
                    <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border px-4 py-2 text-right">
                        <div class="text-[10px] uppercase font-bold text-tg-purple tracking-wider">Assigned Consultant</div>
                        <div class="text-sm font-bold text-tg-dark">{{ $lead->assignedTo->name }}</div>
                    </div>
                @else
                    <div class="rounded-2xl bg-slate-100 border border-slate-200 px-4 py-2 text-right">
                        <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Consultant Status</div>
                        <div class="text-xs font-semibold text-slate-700">Under Review by Management</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Lead & Client Card -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Client & Scope Details
                </h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Customer Name</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">{{ $lead->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Company Name</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">{{ $lead->customer_company ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Contact Phone</dt>
                        <dd class="text-tg-purple font-mono font-bold mt-0.5">{{ $lead->customer_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Email Address</dt>
                        <dd class="text-tg-dark mt-0.5">{{ $lead->customer_email ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">City / Location</dt>
                        <dd class="text-tg-dark mt-0.5">{{ $lead->city ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Requested Service</dt>
                        <dd class="text-tg-purple font-bold mt-0.5">{{ $lead->service_required }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Estimated Budget</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">
                            {{ $lead->estimated_value ? $lead->currency . ' ' . number_format($lead->estimated_value, 2) : 'Undisclosed' }}
                        </dd>
                    </div>
                    @if($lead->quotation_value)
                        <div>
                            <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Quotation Value</dt>
                            <dd class="text-tg-pink font-bold text-sm mt-0.5">
                                {{ $lead->currency }} {{ number_format($lead->quotation_value, 2) }}
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 pt-4 border-t border-slate-100">
                    <h3 class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Project Requirements</h3>
                    <p class="text-xs text-slate-700 whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200">
                        {{ $lead->project_description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side: Referral Terms & Commission -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Referral Terms
                </h2>
                <div class="mt-4 space-y-3 text-xs">
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Partner Commission</span>
                        <span class="font-bold text-tg-purple">{{ $partner->default_commission_rate }}%</span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Protection Cooling</span>
                        <span class="font-bold text-tg-dark">
                            {{ $lead->ownership_expires_at ? $lead->ownership_expires_at->timezone('Asia/Kolkata')->format('d M Y') : '90 Days' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Ownership Status</span>
                        <span class="font-bold text-tg-purple">
                            {{ $lead->ownership_status ? $lead->ownership_status->label() : 'Protected' }}
                        </span>
                    </div>
                    <div class="flex justify-between py-1.5 border-b border-slate-50">
                        <span class="text-slate-500">Pipeline Status</span>
                        <span class="font-bold text-tg-purple">{{ $lead->status->label() }}</span>
                    </div>
                    <div class="pt-2">
                        <a href="{{ route('partner.disputes.create', ['lead_id' => $lead->id]) }}" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-xl text-xs font-bold text-tg-purple bg-tg-purple-light hover:bg-tg-purple/20 border border-tg-purple-border transition">
                            <svg class="w-3.5 h-3.5 mr-1.5 text-tg-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            Raise Ownership Dispute
                        </a>
                    </div>
                    @if($lead->status === \App\Enums\LeadStatus::Won)
                        <div class="mt-3 p-3.5 rounded-xl bg-tg-green-light border border-tg-green-border text-tg-green-hover text-xs">
                            <span class="font-bold block text-tg-green">Deal Converted!</span>
                            Customer has confirmed engagement. Earnings will be credited upon payment verification.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Partner-Visible Canonical Activity Timeline -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider mb-6 flex items-center">
            <svg class="w-4 h-4 text-tg-purple mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Deal Progress & Milestone Timeline
        </h2>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
            @forelse ($activities as $act)
                <div class="relative flex items-start space-x-3">
                    <span class="absolute -left-6 mt-1 w-5 h-5 rounded-full border-2 border-white bg-tg-purple shadow-sm flex items-center justify-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    </span>
                    <div class="flex-1 bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <div class="text-xs font-bold text-tg-dark">{{ $act->summary }}</div>
                            <time class="text-[11px] text-slate-400 font-mono">{{ $act->created_at->format('M d, Y h:i A') }}</time>
                        </div>
                        @if ($act->details)
                            <p class="text-xs text-slate-600 mt-2">{{ $act->details }}</p>
                        @endif
                        @if ($act->from_status && $act->to_status)
                            <div class="mt-2 text-[11px] text-tg-purple font-bold">
                                Status Transition: {{ ucfirst(str_replace('_', ' ', $act->from_status)) }} &rarr; {{ ucfirst(str_replace('_', ' ', $act->to_status)) }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">No public timeline updates yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
