@extends('layouts.app')

@section('title', 'Admin Dossier — Dispute #' . $dispute->dispute_number)

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">
    <!-- Header -->
    <div>
        <a href="{{ route('admin.lead-disputes.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to Disputes List
        </a>
        <div class="sm:flex sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-tg-dark tracking-tight font-mono">{{ $dispute->dispute_number }}</h1>
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold border {{ $dispute->status->badgeClass() }}">
                        {{ $dispute->status->label() }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Submitted on {{ $dispute->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} &bull; Claimant: <a href="{{ route('admin.partners.show', $dispute->partner) }}" class="font-bold text-tg-purple hover:underline">{{ $dispute->partner->company_name }}</a> ({{ $dispute->partner->partner_code }})
                </p>
            </div>
            @if($dispute->evidence_path)
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('admin.lead-disputes.evidence', $dispute) }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-tg-purple bg-tg-purple-light hover:bg-tg-purple/20 border border-tg-purple-border transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Evidence Document
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Resolution Banner if completed -->
    @if($dispute->status === \App\Enums\LeadDisputeStatus::Resolved)
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5 shadow-sm">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Dispute Resolved by Admin ({{ $dispute->resolver?->name ?? 'Admin' }})</h3>
                    <p class="text-xs text-emerald-800 mt-1 font-medium">{{ $dispute->resolution_reason }}</p>
                    <p class="text-[11px] text-emerald-600 mt-2 font-mono">Resolved at {{ $dispute->resolved_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    @elseif($dispute->status === \App\Enums\LeadDisputeStatus::Rejected)
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-xs font-bold text-rose-900 uppercase tracking-wider">Dispute Rejected by Admin ({{ $dispute->resolver?->name ?? 'Admin' }})</h3>
                    <p class="text-xs text-rose-800 mt-1 font-medium">{{ $dispute->resolution_reason }}</p>
                    <p class="text-[11px] text-rose-600 mt-2 font-mono">Decision date: {{ $dispute->resolved_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Col 1 & 2: Dispute Details & Notes -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Explanation & Evidence Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center justify-between">
                    <span>Claimant Explanation & Evidence</span>
                    <span class="text-slate-500 font-normal">{{ $dispute->dispute_category->label() }}</span>
                </h2>

                <div class="text-xs text-slate-800 whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200">
                    {{ $dispute->explanation }}
                </div>

                @if($dispute->evidence_path)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between bg-white">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ $dispute->evidence_filename ?? 'Evidence Document' }}</div>
                                <div class="text-[10px] text-slate-400">Attached evidence file (Stored securely)</div>
                            </div>
                        </div>
                        <a href="{{ route('admin.lead-disputes.evidence', $dispute) }}" class="text-xs font-bold text-tg-purple hover:underline">
                            Download Document
                        </a>
                    </div>
                @endif
            </div>

            <!-- Associated Lead Card (if linked) -->
            @if($dispute->lead)
                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider">
                            Associated Lead Details (#{{ $dispute->lead->lead_number }})
                        </h2>
                        <a href="{{ route('admin.leads.show', $dispute->lead) }}" class="text-xs font-bold text-tg-purple hover:underline">
                            Open Full Lead Dossier &rarr;
                        </a>
                    </div>

                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Customer Name</dt>
                            <dd class="font-bold text-tg-dark mt-0.5">{{ $dispute->lead->customer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Customer Phone</dt>
                            <dd class="font-mono font-bold text-tg-purple mt-0.5">{{ $dispute->lead->customer_phone }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Current Owner Partner</dt>
                            <dd class="font-bold text-tg-dark mt-0.5">{{ $dispute->lead->partner?->company_name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Original Submitting Partner</dt>
                            <dd class="font-bold text-slate-700 mt-0.5">{{ $dispute->lead->originalPartner?->company_name ?? 'Unassigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Lead Sales Status</dt>
                            <dd class="mt-0.5 font-bold text-tg-purple">{{ $dispute->lead->status->label() }}</dd>
                        </div>
                        <div>
                            <dt class="text-[10px] uppercase font-bold text-slate-400">Lead Ownership Status</dt>
                            <dd class="mt-0.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $dispute->lead->ownership_status ? $dispute->lead->ownership_status->badgeClass() : 'bg-slate-100' }}">
                                    {{ $dispute->lead->ownership_status ? $dispute->lead->ownership_status->label() : 'Protected' }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>
            @endif

            <!-- Internal Admin Review Notes Log -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-tg-pink mr-2"></span>
                    Internal Admin Review Notes (Private to Administrators)
                </h2>

                @if($dispute->admin_notes)
                    <div class="text-xs text-slate-700 whitespace-pre-line bg-amber-50/50 p-4 rounded-xl border border-amber-200 font-mono">
                        {{ $dispute->admin_notes }}
                    </div>
                @else
                    <p class="text-xs text-slate-400 italic">No internal administrative notes recorded yet.</p>
                @endif

                <form action="{{ route('admin.lead-disputes.notes', $dispute) }}" method="POST" class="space-y-3 pt-2">
                    @csrf
                    <textarea name="admin_notes" rows="3" required placeholder="Log internal investigation findings, verification calls, or evidence cross-checks..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-tg-purple outline-none"></textarea>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 transition">
                        + Add Internal Admin Note
                    </button>
                </form>
            </div>
        </div>

        <!-- Col 3: Actions & Decision Center -->
        <div class="space-y-6">
            <!-- Claimant Partner Summary -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-3">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Claimant Partner
                </h2>
                <div class="text-xs space-y-2">
                    <div>
                        <span class="text-slate-400 block font-bold text-[10px] uppercase">Company</span>
                        <a href="{{ route('admin.partners.show', $dispute->partner) }}" class="font-bold text-tg-dark hover:text-tg-purple transition">
                            {{ $dispute->partner->company_name }}
                        </a>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-bold text-[10px] uppercase">Partner Code</span>
                        <span class="font-mono font-bold text-tg-purple">{{ $dispute->partner->partner_code }}</span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-bold text-[10px] uppercase">User Account</span>
                        <span>{{ $dispute->partner->user->name }} ({{ $dispute->partner->user->email }})</span>
                    </div>
                </div>
            </div>

            <!-- Review Actions & Decision Modal / Card -->
            <div class="bg-white rounded-2xl p-6 border-2 border-tg-purple/20 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-tg-purple uppercase tracking-wider pb-3 border-b border-slate-100">
                    Arbitration Decision Controls
                </h2>

                @if($dispute->status === \App\Enums\LeadDisputeStatus::Pending)
                    <form action="{{ route('admin.lead-disputes.status', $dispute) }}" method="POST">
                        @csrf
                        <input type="hidden" name="status" value="under_review">
                        <button type="submit" class="w-full py-2 px-3 rounded-xl text-xs font-bold text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200 transition">
                            Mark Under Active Review
                        </button>
                    </form>
                @endif

                @if(in_array($dispute->status, [\App\Enums\LeadDisputeStatus::Pending, \App\Enums\LeadDisputeStatus::UnderReview]))
                    <!-- Resolve Dispute Form -->
                    <div class="pt-2 border-t border-slate-100 space-y-3">
                        <h3 class="text-xs font-bold text-emerald-800 uppercase tracking-wider">Resolve Dispute</h3>
                        <form action="{{ route('admin.lead-disputes.resolve', $dispute) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mandatory Resolution Decision Reason *</label>
                                <textarea name="resolution_reason" rows="3" required placeholder="Specify why the dispute is resolved and terms agreed..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-emerald-600 outline-none"></textarea>
                            </div>

                            @if($dispute->lead)
                                <label class="flex items-start space-x-2 p-2.5 rounded-xl bg-purple-50 border border-purple-200 cursor-pointer">
                                    <input type="checkbox" name="reassign_to_claimant" value="1" class="mt-0.5 rounded text-tg-purple focus:ring-tg-purple">
                                    <span class="text-xs text-purple-900 font-semibold leading-tight">
                                        Reassign Lead #{{ $dispute->lead->lead_number }} to Claimant ({{ $dispute->partner->company_name }})
                                    </span>
                                </label>
                            @endif

                            <button type="submit" class="w-full py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition shadow-sm">
                                Confirm &amp; Resolve Dispute
                            </button>
                        </form>
                    </div>

                    <!-- Reject Dispute Form -->
                    <div class="pt-3 border-t border-slate-100 space-y-3">
                        <h3 class="text-xs font-bold text-rose-800 uppercase tracking-wider">Reject Dispute</h3>
                        <form action="{{ route('admin.lead-disputes.reject', $dispute) }}" method="POST" class="space-y-3">
                            @csrf
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-700 mb-1">Mandatory Rejection Reason *</label>
                                <textarea name="resolution_reason" rows="2" required placeholder="Provide reason for rejecting the dispute claim..." class="w-full rounded-xl border border-slate-300 p-2.5 text-xs focus:border-rose-600 outline-none"></textarea>
                            </div>
                            <button type="submit" class="w-full py-2 px-3 rounded-xl text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 transition shadow-sm">
                                Reject Dispute Claim
                            </button>
                        </form>
                    </div>
                @else
                    <p class="text-xs text-slate-500 italic">Dispute decision has been finalized. Records are permanently archived for auditing.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
