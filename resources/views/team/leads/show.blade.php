@extends('layouts.app')

@section('title', 'CRM Lead Dossier — ' . $lead->lead_number)

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Back Link and Header -->
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
        <a href="{{ route('team.leads.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-3 touch-target">
            &larr; Back to Assigned Leads
        </a>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-2.5 flex-wrap gap-y-2">
                    <h1 class="text-xl sm:text-2xl font-black text-tg-dark tracking-tight font-mono">{{ $lead->lead_number }}</h1>
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
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                        {{ $lead->status->label() }}
                    </span>
                    @if($lead->interest_level)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                            Interest: {{ $lead->interest_level->label() }}
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Assigned on {{ $lead->assigned_at?->format('F d, Y \a\t h:i A') ?? 'N/A' }} &bull; Customer: <strong class="text-tg-dark">{{ $lead->customer_name }}</strong>
                </p>
            </div>
            <!-- Quick Connect Buttons -->
            <div class="flex items-center space-x-2 flex-wrap gap-y-2">
                <a href="tel:{{ $lead->customer_phone }}"
                   class="inline-flex items-center px-3.5 py-2.5 rounded-xl bg-tg-green text-white text-xs font-bold hover:bg-tg-green-hover shadow-sm transition btn-tactile touch-target">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    Call
                </a>

                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $lead->customer_phone);
                    if(strlen($cleanPhone) === 10) { $cleanPhone = '91' . $cleanPhone; }
                @endphp
                <a href="https://wa.me/{{ $cleanPhone }}" target="_blank"
                   class="inline-flex items-center px-3.5 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 shadow-sm transition btn-tactile touch-target">
                    WhatsApp
                </a>

                @if($lead->customer_email)
                    <a href="mailto:{{ $lead->customer_email }}"
                       class="inline-flex items-center px-3.5 py-2.5 rounded-xl bg-tg-dark text-white text-xs font-bold hover:bg-black shadow-sm transition btn-tactile touch-target">
                        Email
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Client Profile & CRM Action Panels -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Col 1: Customer Contact & Requirements Dossier -->
        <div class="space-y-6">
            <!-- SLA Compliance Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-tg-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Lead SLA Status
                    </h2>
                    @if($lead->overall_sla_status)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $lead->overall_sla_status->badgeClasses() }}">
                            {{ $lead->overall_sla_status->label() }}
                        </span>
                    @endif
                </div>

                <div class="space-y-3 text-xs">
                    <!-- First Contact SLA -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">First Contact SLA</span>
                            @if($lead->first_contact_sla_status)
                                <span class="text-[10px] font-bold {{ $lead->first_contact_sla_status === \App\Enums\LeadSlaStatus::Met ? 'text-emerald-600' : ($lead->first_contact_sla_status === \App\Enums\LeadSlaStatus::Overdue ? 'text-rose-600' : 'text-amber-600') }}">
                                    {{ $lead->first_contact_sla_status->label() }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 font-mono text-xs font-bold text-tg-dark">
                            {{ $lead->getFirstContactRemainingHuman() }}
                        </div>
                        <div class="text-[11px] text-slate-400 mt-0.5">
                            Target: {{ $lead->first_contact_due_at?->format('d M Y, h:i A') ?? 'N/A' }}
                        </div>
                        @if($lead->first_contacted_at)
                            <div class="text-[11px] text-emerald-600 font-medium mt-1">
                                Contacted: {{ $lead->first_contacted_at->format('d M Y, h:i A') }}
                            </div>
                        @endif
                    </div>

                    <!-- Follow-up SLA -->
                    @if($lead->followup_due_at)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Follow-up SLA</span>
                                @if($lead->followup_sla_status)
                                    <span class="text-[10px] font-bold {{ $lead->followup_sla_status === \App\Enums\LeadSlaStatus::Overdue ? 'text-rose-600' : 'text-emerald-600' }}">
                                        {{ $lead->followup_sla_status->label() }}
                                    </span>
                                @endif
                            </div>
                            <div class="mt-1 font-mono text-xs font-bold text-tg-dark">
                                {{ $lead->getFollowupRemainingHuman() }}
                            </div>
                            <div class="text-[11px] text-slate-400 mt-0.5">
                                Due: {{ $lead->followup_due_at->format('d M Y, h:i A') }}
                            </div>
                            @if($lead->missed_followups_count > 0)
                                <div class="text-[11px] text-rose-600 font-bold mt-1">
                                    ⚠️ {{ $lead->missed_followups_count }} missed follow-up{{ $lead->missed_followups_count > 1 ? 's' : '' }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Customer Information
                </h2>

                <dl class="space-y-3 mt-4 text-xs">
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Customer Full Name</dt>
                        <dd class="text-tg-dark font-bold text-sm mt-0.5">{{ $lead->customer_name }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Organization / Company</dt>
                        <dd class="text-tg-dark font-semibold mt-0.5">{{ $lead->customer_company ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Mobile / Phone</dt>
                        <dd class="text-tg-purple font-mono font-bold mt-0.5">
                            <a href="tel:{{ $lead->customer_phone }}" class="hover:underline">{{ $lead->customer_phone }}</a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Email Address</dt>
                        <dd class="text-tg-dark mt-0.5">
                            @if($lead->customer_email)
                                <a href="mailto:{{ $lead->customer_email }}" class="hover:underline font-mono">{{ $lead->customer_email }}</a>
                            @else
                                <span class="text-slate-400 italic">None Provided</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Location / City</dt>
                        <dd class="text-tg-dark mt-0.5">{{ $lead->city ?? 'Not Specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Service Required</dt>
                        <dd class="text-tg-purple font-bold mt-0.5">{{ $lead->service_required }}</dd>
                    </div>
                    <div>
                        <dt class="text-slate-400 font-medium uppercase tracking-wider text-[10px]">Estimated Client Budget</dt>
                        <dd class="text-tg-dark font-bold mt-0.5">
                            {{ $lead->estimated_value ? '₹ ' . number_format($lead->estimated_value, 2) : 'Undisclosed' }}
                        </dd>
                    </div>
                    @if($lead->quotation_value)
                        <div class="p-3 rounded-xl bg-tg-purple-light border border-tg-purple-border">
                            <dt class="text-tg-purple font-bold uppercase tracking-wider text-[10px]">Quotation Sent</dt>
                            <dd class="text-tg-purple font-bold text-sm mt-0.5">
                                ₹ {{ number_format($lead->quotation_value, 2) }}
                            </dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-4 pt-4 border-t border-slate-100">
                    <span class="text-slate-400 block font-bold text-[10px] uppercase tracking-wider mb-1">Requirements & Scope:</span>
                    <p class="text-xs text-slate-700 whitespace-pre-line bg-slate-50 p-3 rounded-xl border border-slate-200">
                        {{ $lead->project_description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Col 2 & 3: CRM Interaction Forms & Canonical Timeline -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Form 1: Telecalling Activity Logging -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span>Log Call, Meeting or Telecalling Activity</span>
                    <span class="text-[11px] text-slate-400 font-normal">Records into canonical timeline</span>
                </h2>

                <form action="{{ route('team.leads.activity', $lead) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Interaction Type <span class="text-tg-pink">*</span></label>
                            <select name="activity_type" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                                <option value="call">Phone Call</option>
                                <option value="whatsapp">WhatsApp Discussion</option>
                                <option value="email">Email Communication</option>
                                <option value="meeting">Client Meeting / Demo</option>
                                <option value="quotation">Quotation Sent</option>
                                <option value="follow_up">Scheduled Follow-up</option>
                                <option value="note">Internal Private Note</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Customer Interest Level</label>
                            <select name="customer_interest" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                                <option value="">-- No Change --</option>
                                @foreach (\App\Enums\LeadInterest::cases() as $int)
                                    <option value="{{ $int->value }}" {{ $lead->interest_level === $int ? 'selected' : '' }}>
                                        {{ $int->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Next Follow-up Date</label>
                            <input type="date" name="next_followup_date" value="{{ $lead->next_followup_date?->format('Y-m-d') }}"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Call Duration (Seconds)</label>
                            <input type="number" name="call_duration_seconds" min="1" placeholder="e.g. 300"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Quotation Value (INR, if discussed)</label>
                            <input type="number" step="0.01" name="quotation_value" value="{{ $lead->quotation_value }}" placeholder="e.g. 150000"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Activity Summary <span class="text-tg-pink">*</span></label>
                        <input type="text" name="summary" required placeholder="e.g. Discovery call completed with Mr. Rajesh. Requirements gathered."
                               class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Discussion Notes</label>
                        <textarea name="details" rows="2" placeholder="Key discussion points, customer feedback, objections, or deliverables discussed..."
                                  class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none"></textarea>
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="inline-flex items-center text-xs text-slate-600">
                            <input type="checkbox" name="is_internal" value="1" class="rounded border-slate-300 text-tg-purple focus:ring-tg-purple mr-2">
                            <span>Keep this note internal (Hidden from partner)</span>
                        </label>

                        <button type="submit" class="px-5 py-2 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover transition shadow-sm">
                            Save Activity Note
                        </button>
                    </div>
                </form>
            </div>

            <!-- Form 2: Pipeline Status Progression -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span>Update Pipeline Status & Recommendations</span>
                    <span class="text-[11px] text-slate-400 font-normal">Records from_status & to_status transition</span>
                </h2>

                <form action="{{ route('team.leads.status', $lead) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">New Pipeline Status <span class="text-tg-pink">*</span></label>
                            <select name="status" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs bg-white focus:border-tg-purple outline-none">
                                <option value="contacted" {{ $lead->status === \App\Enums\LeadStatus::Contacted ? 'selected' : '' }}>Contacted</option>
                                <option value="in_discussion" {{ $lead->status === \App\Enums\LeadStatus::InDiscussion ? 'selected' : '' }}>In Discussion</option>
                                <option value="quotation_sent" {{ $lead->status === \App\Enums\LeadStatus::QuotationSent ? 'selected' : '' }}>Quotation Sent</option>
                                <option value="negotiating" {{ $lead->status === \App\Enums\LeadStatus::Negotiating ? 'selected' : '' }}>Negotiating</option>
                                <option value="won" {{ $lead->status === \App\Enums\LeadStatus::Won ? 'selected' : '' }}>Won (Recommend Converted)</option>
                                <option value="lost" {{ $lead->status === \App\Enums\LeadStatus::Lost ? 'selected' : '' }}>Lost (Recommend Closed)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Quotation Value (INR)</label>
                            <input type="number" step="0.01" name="quotation_value" value="{{ $lead->quotation_value }}" placeholder="e.g. 250000"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Status Reason / Closing Note</label>
                            <input type="text" name="reason" placeholder="e.g. Customer signed proposal"
                                   class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-tg-dark text-white font-bold text-xs hover:bg-black transition shadow-sm">
                            Submit Pipeline Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Complete Activity Timeline on This Lead -->
    <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm">
        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider mb-6 flex items-center">
            <svg class="w-4 h-4 text-tg-purple mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Lead Interaction History
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
                                        🔒 Internal Note
                                    </span>
                                @endif
                            </div>
                            <time class="text-[11px] text-slate-400 font-mono">{{ $act->created_at->format('M d, Y h:i A') }}</time>
                        </div>

                        @if ($act->details)
                            <p class="text-xs text-slate-700 mt-2">{{ $act->details }}</p>
                        @endif

                        <div class="mt-2.5 flex flex-wrap items-center gap-3 text-[11px] text-slate-500 pt-2 border-t {{ $act->is_internal ? 'border-tg-pink-border/50' : 'border-slate-200' }}">
                            <span>By: <strong class="text-tg-dark">{{ $act->user->name }}</strong></span>
                            @if($act->call_duration_seconds)
                                <span>&bull; Duration: <strong>{{ gmdate("i:s", $act->call_duration_seconds) }}</strong> mins</span>
                            @endif
                            @if($act->customer_interest)
                                <span>&bull; Customer Interest: <strong>{{ $act->customer_interest->label() }}</strong></span>
                            @endif
                            @if($act->next_followup_date)
                                <span>&bull; Next Follow-up: <strong>{{ $act->next_followup_date->format('M d, Y') }}</strong></span>
                            @endif
                            @if ($act->from_status && $act->to_status)
                                <span>&bull; Transition: <strong>{{ ucfirst(str_replace('_', ' ', $act->from_status)) }} &rarr; {{ ucfirst(str_replace('_', ' ', $act->to_status)) }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-400 italic">No activities recorded on this lead yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
