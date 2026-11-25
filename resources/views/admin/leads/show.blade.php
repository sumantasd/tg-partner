@extends('layouts.app')

@section('title', 'Admin Dossier — Lead #' . $lead->lead_number)

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Top Back Link & Status Header -->
    <div>
        <a href="{{ route('admin.leads.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to Leads Pipeline
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
                    @if($lead->is_duplicate_flag)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                            ⚠️ Duplicate Conflict Flag
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Submitted {{ $lead->created_at->format('M d, Y h:i A') }} &bull; Partner: <strong class="text-tg-dark">{{ $lead->partner->company_name }}</strong> ({{ $lead->partner->partner_code }})
                </p>
            </div>
            <div class="mt-4 sm:mt-0 flex items-center space-x-3 text-xs">
                @if($lead->status === \App\Enums\LeadStatus::Won)
                    @if($lead->converted_customer_id)
                        <a href="{{ route('admin.customers.show', $lead->converted_customer_id) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-green text-white font-bold text-xs shadow-sm hover:bg-emerald-600 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            View Customer Profile
                        </a>
                    @else
                        <a href="{{ route('admin.leads.convert.form', $lead) }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white font-bold text-xs shadow-md hover:bg-tg-purple-hover transition animate-pulse">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                            Convert to Customer
                        </a>
                    @endif
                @endif
                @if($lead->assignedTo)
                    <div class="rounded-2xl bg-white border border-slate-200 px-4 py-2 shadow-sm">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold tracking-wider">Assigned Consultant</span>
                        <span class="font-bold text-tg-dark">{{ $lead->assignedTo->name }}</span>
                    </div>
                @else
                    <div class="rounded-2xl bg-amber-50 border border-amber-200 px-4 py-2 text-amber-800 font-bold">
                        Awaiting Consultant Assignment
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Duplicate Conflict Alert & Resolution Panel -->
    @if($lead->is_duplicate_flag && $lead->duplicateMatchedLead)
        <div class="rounded-2xl bg-amber-50/80 border-2 border-amber-300 p-6 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <h3 class="text-base font-bold text-amber-950 flex items-center">
                        <svg class="w-5 h-5 text-amber-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        Duplicate Referral Conflict Detected (90-Day Cooling Window)
                    </h3>
                    <p class="text-xs text-amber-900 mt-1">
                        Customer phone or email matches existing Lead <a href="{{ route('admin.leads.show', $lead->duplicateMatchedLead) }}" class="underline font-mono font-bold text-tg-purple">{{ $lead->duplicateMatchedLead->lead_number }}</a> submitted on {{ $lead->duplicateMatchedLead->created_at->format('M d, Y') }}.
                    </p>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="p-3.5 rounded-xl bg-white border border-amber-200">
                            <span class="text-slate-400 block font-bold text-[10px] uppercase tracking-wider">Original Referral Attribution</span>
                            <span class="font-bold text-tg-dark">{{ $lead->duplicateMatchedLead->partner->company_name }}</span> ({{ $lead->duplicateMatchedLead->partner->partner_code }})
                        </div>
                        <div class="p-3.5 rounded-xl bg-white border border-amber-200">
                            <span class="text-slate-400 block font-bold text-[10px] uppercase tracking-wider">Current Submitting Partner</span>
                            <span class="font-bold text-tg-dark">{{ $lead->partner->company_name }}</span> ({{ $lead->partner->partner_code }})
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resolution Actions -->
            <div class="mt-4 pt-4 border-t border-amber-200/80 flex flex-wrap items-center gap-3">
                <form action="{{ route('admin.leads.resolve-duplicate', $lead) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="dismiss">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-white border border-slate-300 text-xs font-bold text-tg-dark hover:bg-slate-50 transition shadow-sm">
                        Dismiss Flag (Verified Distinct Deal)
                    </button>
                </form>

                <form action="{{ route('admin.leads.resolve-duplicate', $lead) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-tg-pink text-white text-xs font-bold hover:bg-tg-pink-hover transition shadow-sm">
                        Reject as Duplicate Referral
                    </button>
                </form>

                <form action="{{ route('admin.leads.resolve-duplicate', $lead) }}" method="POST" class="inline">
                    @csrf
                    <input type="hidden" name="action" value="override_attribution">
                    <button type="submit" class="px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                        Authorize Attribution Override
                    </button>
                </form>
            </div>
        </div>
    @endif

    <!-- 2 Column Dossier Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Col 1 & 2: Dossier Details & Management Controls -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Client & Requirement Info -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Client & Technical Requirements
                </h2>

                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Customer Name</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">{{ $lead->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Organization / Company</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">{{ $lead->customer_company ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Direct Phone Number</dt>
                        <dd class="text-tg-purple font-mono font-bold text-sm mt-0.5">{{ $lead->customer_phone }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Customer Email</dt>
                        <dd class="text-tg-dark mt-0.5">{{ $lead->customer_email ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">City / Location</dt>
                        <dd class="text-tg-dark mt-0.5">{{ $lead->city ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Service Required</dt>
                        <dd class="text-tg-purple font-bold mt-0.5">{{ $lead->service_required }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Estimated Budget</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">
                            {{ $lead->estimated_value ? $lead->currency . ' ' . number_format($lead->estimated_value, 2) : 'Undisclosed' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Next Scheduled Follow-up</dt>
                        <dd class="font-mono font-bold mt-0.5 {{ $lead->next_followup_date?->isPast() ? 'text-tg-pink' : 'text-tg-dark' }}">
                            {{ $lead->next_followup_date?->format('M d, Y') ?? 'None Scheduled' }}
                        </dd>
                    </div>
                </dl>

                <div class="mt-6 pt-4 border-t border-slate-100">
                    <h3 class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Requirement Notes:</h3>
                    <p class="text-xs text-slate-700 whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200">
                        {{ $lead->project_description }}
                    </p>
                </div>
            </div>

            <!-- Management Actions: Assignment & Status Control -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-6">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Admin Management Controls
                </h2>

                <!-- 1. Assignment Form -->
                <div>
                    <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider mb-2">
                        {{ $lead->assigned_to ? 'Reassign Lead to Consultant' : 'Assign Lead to Consultant' }}
                    </h3>
                    <form action="{{ route('admin.leads.assign', $lead) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <select name="assigned_to" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                                    <option value="">-- Select Team Member --</option>
                                    @foreach ($teamMembers as $tm)
                                        <option value="{{ $tm->id }}" {{ $lead->assigned_to === $tm->id ? 'selected' : '' }}>
                                            {{ $tm->name }} ({{ $tm->email }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="text" name="note" placeholder="Optional directive or note for consultant..."
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover transition shadow-sm">
                            {{ $lead->assigned_to ? 'Confirm Reassignment' : 'Confirm Assignment' }}
                        </button>
                    </form>
                </div>

                <!-- 2. Status Control Form -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider mb-2">
                        Override Lead Status
                    </h3>
                    <form action="{{ route('admin.leads.status', $lead) }}" method="POST" class="space-y-3">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div>
                                <select name="status" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                                    @foreach (\App\Enums\LeadStatus::cases() as $st)
                                        <option value="{{ $st->value }}" {{ $lead->status === $st ? 'selected' : '' }}>
                                            {{ $st->label() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="number" step="0.01" name="quotation_value" value="{{ $lead->quotation_value }}" placeholder="Quotation Amount (INR)"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                            </div>
                            <div>
                                <input type="text" name="reason" placeholder="Reason / Lost reason / Note"
                                       class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-tg-dark text-white font-bold text-xs hover:bg-black transition shadow-sm">
                            Update Pipeline Status
                        </button>
                    </form>
                </div>

                <!-- 3. Internal Admin Comment -->
                <div class="pt-4 border-t border-slate-100">
                    <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider mb-2">
                        Add Internal Private Directive / Note
                    </h3>
                    <form action="{{ route('admin.leads.activity', $lead) }}" method="POST" class="space-y-3">
                        @csrf
                        <div>
                            <input type="text" name="summary" required placeholder="Directive summary (e.g. Expedite quotation, client requested demo)..."
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                        </div>
                        <div>
                            <textarea name="details" rows="2" placeholder="Optional detailed instructions for internal team..."
                                      class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none"></textarea>
                        </div>
                        <button type="submit" class="px-4 py-2 rounded-xl bg-tg-purple-hover text-white font-bold text-xs hover:bg-tg-purple transition shadow-sm">
                            Post Internal Directive
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Col 3: Referral Partner Dossier & Ownership Protection -->
        <div class="space-y-6">
            <!-- Lead Ownership Security & Protection Card -->
            <div class="bg-white rounded-2xl p-6 border-2 border-tg-purple/20 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-tg-purple uppercase tracking-wider flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-tg-purple" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        Ownership Protection
                    </h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[11px] font-bold border {{ $lead->ownership_status ? $lead->ownership_status->badgeClass() : 'bg-slate-100 text-slate-700' }}">
                        {{ $lead->ownership_status ? $lead->ownership_status->label() : 'Protected' }}
                    </span>
                </div>

                <div class="space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block font-bold text-[10px] uppercase">Current Owner</span>
                        @if($lead->partner)
                            <a href="{{ route('admin.partners.show', $lead->partner) }}" class="font-bold text-tg-dark text-sm hover:text-tg-purple transition">
                                {{ $lead->partner->company_name }}
                            </a>
                            <span class="font-mono text-slate-400 block text-[11px]">{{ $lead->partner->partner_code }}</span>
                        @else
                            <span class="text-slate-500 italic">Unassigned (Direct Lead)</span>
                        @endif
                    </div>

                    @if($lead->original_partner_id && $lead->original_partner_id !== $lead->partner_id)
                        <div class="p-2.5 rounded-xl bg-purple-50 border border-purple-200 text-purple-900">
                            <span class="text-purple-700 block font-bold text-[10px] uppercase">Original Referrer</span>
                            <span class="font-bold">{{ $lead->originalPartner?->company_name }}</span> ({{ $lead->originalPartner?->partner_code }})
                            <div class="text-[10px] text-purple-600 mt-0.5">Traceable attribution preserved permanently.</div>
                        </div>
                    @endif

                    <div class="flex justify-between py-1.5 border-b border-slate-100">
                        <span class="text-slate-500">Protection Valid Until</span>
                        <span class="font-bold text-tg-dark font-mono">
                            {{ $lead->ownership_expires_at ? $lead->ownership_expires_at->timezone('Asia/Kolkata')->format('d M Y') : 'Indefinite' }}
                        </span>
                    </div>

                    @if($lead->ownership_change_reason)
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200">
                            <span class="text-slate-400 block font-bold text-[10px] uppercase">Last Modification Reason</span>
                            <p class="text-slate-700 text-[11px] mt-0.5">{{ $lead->ownership_change_reason }}</p>
                        </div>
                    @endif
                </div>

                <!-- Admin Ownership Control Actions -->
                <div class="pt-3 border-t border-slate-100 space-y-2">
                    <button type="button" onclick="document.getElementById('reassign-modal').classList.remove('hidden')" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Reassign Partner Ownership
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="document.getElementById('extend-modal').classList.remove('hidden')" class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                            Extend Days
                        </button>
                        <button type="button" onclick="document.getElementById('release-modal').classList.remove('hidden')" class="inline-flex items-center justify-center px-3 py-1.5 rounded-xl text-xs font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition">
                            Release
                        </button>
                    </div>
                </div>
            </div>

            <!-- Related Disputes Card (if any) -->
            @if($lead->disputes->isNotEmpty())
                <div class="bg-white rounded-2xl p-6 border border-amber-200 shadow-sm space-y-3">
                    <h2 class="text-xs font-bold text-amber-900 uppercase tracking-wider flex items-center justify-between">
                        <span>Related Disputes ({{ $lead->disputes->count() }})</span>
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    </h2>
                    <div class="space-y-2">
                        @foreach($lead->disputes as $disp)
                            <a href="{{ route('admin.lead-disputes.show', $disp) }}" class="block p-3 rounded-xl border border-slate-200 hover:border-tg-purple transition bg-slate-50/50 text-xs">
                                <div class="flex items-center justify-between font-mono font-bold text-tg-purple">
                                    <span>{{ $disp->dispute_number }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $disp->status->badgeClass() }}">
                                        {{ $disp->status->label() }}
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-600 mt-1">Claimant: <strong>{{ $disp->partner->company_name }}</strong></div>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Referral Partner Dossier -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Partner Commercials
                </h2>
                <div class="mt-4 space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block font-bold text-[10px] uppercase">Partner Company</span>
                        <span class="font-bold text-tg-dark text-sm">{{ $lead->partner?->company_name ?? 'Direct Lead' }}</span>
                    </div>
                    @if($lead->partner)
                        <div>
                            <span class="text-slate-400 block font-bold text-[10px] uppercase">Contact Person</span>
                            <span class="font-semibold text-tg-dark">{{ $lead->partner->user->name }}</span>
                            <span class="text-slate-500 block font-mono text-[11px]">{{ $lead->partner->user->email }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Partner Code</span>
                            <span class="font-mono font-bold text-tg-purple">{{ $lead->partner->partner_code }}</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Commission Rate</span>
                            <span class="font-bold text-tg-dark">{{ $lead->partner->default_commission_rate }}%</span>
                        </div>
                        <div class="flex justify-between py-1.5 border-b border-slate-100">
                            <span class="text-slate-500">Partner Account Status</span>
                            <span class="font-bold text-tg-green">{{ $lead->partner->user->status->label() }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Ownership Audit History Table -->
    @if($lead->ownershipHistories->isNotEmpty())
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
            <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider mb-4 flex items-center">
                <svg class="w-4 h-4 text-tg-purple mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c0 3.517-1.009 6.799-2.753 9.571m-3.44-2.04l.054-.09A13.916 13.916 0 008 11a4 4 0 118 0c0 1.017-.07 2.019-.203 3m-2.118 6.844A21.88 21.88 0 0015.171 17m3.839 1.132c.645-2.266.99-4.659.99-7.132A8 8 0 004 11m0 0c0 2.473.345 4.866.99 7.132"/>
                </svg>
                Permanent Lead Ownership History & Traceability
            </h2>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                            <th class="py-2.5 px-3">Date & Time</th>
                            <th class="py-2.5 px-3">Action</th>
                            <th class="py-2.5 px-3">Previous Owner</th>
                            <th class="py-2.5 px-3">Assigned Owner</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Mandatory Reason</th>
                            <th class="py-2.5 px-3">Performed By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($lead->ownershipHistories as $hist)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-3 font-mono text-slate-500 whitespace-nowrap">
                                    {{ $hist->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-tg-dark">
                                    {{ ucfirst(str_replace('_', ' ', $hist->action)) }}
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    {{ $hist->previousPartner?->company_name ?? '—' }}
                                </td>
                                <td class="py-3 px-3 font-bold text-tg-purple">
                                    {{ $hist->newPartner?->company_name ?? 'Unassigned' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $hist->to_status ? $hist->to_status->badgeClass() : 'bg-slate-100 text-slate-600' }}">
                                        {{ $hist->to_status ? $hist->to_status->label() : '—' }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-slate-700 max-w-xs">
                                    {{ $hist->reason ?? '—' }}
                                </td>
                                <td class="py-3 px-3 text-slate-600 whitespace-nowrap">
                                    {{ $hist->performer?->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Ownership Action Modals -->
    <!-- Modal 1: Reassign Ownership -->
    <div id="reassign-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-bold text-tg-dark">Reassign Lead Ownership</h3>
            <p class="text-xs text-slate-500 mt-1">
                Transfer ownership of Lead #{{ $lead->lead_number }} to another authorized partner. Previous ownership is permanently archived.
            </p>
            <form action="{{ route('admin.leads.reassign-ownership', $lead) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">Select New Partner Owner *</label>
                    <select name="new_partner_id" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs bg-white focus:border-tg-purple outline-none">
                        <option value="">-- Select Active Partner --</option>
                        @foreach($partners as $p)
                            @if($p->id !== $lead->partner_id)
                                <option value="{{ $p->id }}">{{ $p->company_name }} ({{ $p->partner_code }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">Mandatory Reassignment Reason *</label>
                    <textarea name="reason" rows="3" required placeholder="Explain why ownership is being transferred (e.g. dispute resolution, territory change, partner agreement)..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-tg-purple outline-none"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="document.getElementById('reassign-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">Confirm Reassignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Extend Validity -->
    <div id="extend-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-bold text-tg-dark">Extend Lead Ownership Validity</h3>
            <p class="text-xs text-slate-500 mt-1">
                Extend active referral protection window for this lead.
            </p>
            <form action="{{ route('admin.leads.extend-ownership', $lead) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">Extend By Days *</label>
                    <select name="days" required class="w-full rounded-xl border border-slate-300 p-2.5 text-xs bg-white focus:border-tg-purple outline-none">
                        <option value="15">+15 Days</option>
                        <option value="30">+30 Days (1 Month)</option>
                        <option value="60">+60 Days (2 Months)</option>
                        <option value="90" selected>+90 Days (3 Months)</option>
                        <option value="180">+180 Days (6 Months)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">Mandatory Extension Reason *</label>
                    <textarea name="reason" rows="3" required placeholder="Reason for extension (e.g. active tender, extended client evaluation)..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-tg-purple outline-none"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="document.getElementById('extend-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">Extend Protection</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Release Ownership -->
    <div id="release-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-base font-bold text-rose-900">Release Lead Ownership</h3>
            <p class="text-xs text-slate-500 mt-1">
                Release partner protection for Lead #{{ $lead->lead_number }}. The lead will be marked released and will no longer be locked to this partner.
            </p>
            <form action="{{ route('admin.leads.release-ownership', $lead) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">Mandatory Release Reason *</label>
                    <textarea name="reason" rows="3" required placeholder="Explain why ownership is being released..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-rose-600 outline-none"></textarea>
                </div>
                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="document.getElementById('release-modal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm">Release Ownership</button>
                </div>
            </form>
        </div>
    </div>
    </div>

    <!-- Full Canonical Timeline (Internal + Public) -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
        <h2 class="text-base font-bold text-tg-dark tracking-tight mb-6 flex items-center justify-between">
            <span class="flex items-center">
                <svg class="w-4 h-4 text-tg-purple mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Canonical Activity Timeline
            </span>
            <span class="text-xs font-normal text-slate-500">All events, calls, status changes, and private directives</span>
        </h2>

        <div class="relative pl-6 space-y-6 before:absolute before:left-2.5 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
            @forelse ($lead->activities as $act)
                <div class="relative flex items-start space-x-3">
                    <span class="absolute -left-6 mt-1.5 w-5 h-5 rounded-full border-2 border-white {{ $act->is_internal ? 'bg-tg-pink' : 'bg-tg-purple' }} shadow-sm flex items-center justify-center">
                        <span class="w-1.5 h-1.5 rounded-full bg-white"></span>
                    </span>
                    <div class="flex-1 {{ $act->is_internal ? 'bg-tg-pink-light/40 border-tg-pink-border' : 'bg-slate-50 border-slate-200' }} rounded-xl p-4 border">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div class="flex items-center space-x-2">
                                <span class="text-xs font-bold text-tg-dark">{{ $act->summary }}</span>
                                @if($act->is_internal)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                                        🔒 INTERNAL ONLY
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                        Visible to Partner
                                    </span>
                                @endif
                            </div>
                            <time class="text-[11px] text-slate-400 font-mono">{{ $act->created_at->format('M d, Y h:i A') }}</time>
                        </div>

                        @if ($act->details)
                            <p class="text-xs text-slate-700 mt-2">{{ $act->details }}</p>
                        @endif

                        <div class="mt-2.5 flex flex-wrap items-center gap-3 text-[11px] text-slate-500 pt-2 border-t {{ $act->is_internal ? 'border-tg-pink-border/50' : 'border-slate-200' }}">
                            <span>By: <strong class="text-tg-dark">{{ $act->user->name }}</strong> ({{ $act->user->role->label() }})</span>
                            @if($act->call_duration_seconds)
                                <span>&bull; Duration: <strong>{{ gmdate("i:s", $act->call_duration_seconds) }}</strong> mins</span>
                            @endif
                            @if($act->customer_interest)
                                <span>&bull; Interest: <strong>{{ $act->customer_interest->label() }}</strong></span>
                            @endif
                            @if($act->next_followup_date)
                                <span>&bull; Next Follow-up: <strong>{{ $act->next_followup_date->format('M d, Y') }}</strong></span>
                            @endif
                            @if ($act->from_status && $act->to_status)
                                <span>&bull; Pipeline: <strong>{{ ucfirst(str_replace('_', ' ', $act->from_status)) }} &rarr; {{ ucfirst(str_replace('_', ' ', $act->to_status)) }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">No activities recorded yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
