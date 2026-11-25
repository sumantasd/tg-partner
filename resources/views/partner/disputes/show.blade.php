@extends('layouts.app')

@section('title', 'Dispute #' . $dispute->dispute_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Back Link & Header -->
    <div>
        <a href="{{ route('partner.disputes.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to Disputes
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
                    Submitted on {{ $dispute->created_at->timezone('Asia/Kolkata')->format('d M Y, h:i A') }} &bull; Category: <span class="font-bold text-tg-dark">{{ $dispute->dispute_category->label() }}</span>
                </p>
            </div>
            @if($dispute->evidence_path)
                <div class="mt-4 sm:mt-0">
                    <a href="{{ route('partner.disputes.evidence', $dispute) }}" class="inline-flex items-center px-3.5 py-2 rounded-xl text-xs font-bold text-tg-purple bg-tg-purple-light hover:bg-tg-purple/20 border border-tg-purple-border transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Download Evidence File
                    </a>
                </div>
            @endif
        </div>
    </div>

    <!-- Dispute Status Banner if Resolved / Rejected -->
    @if($dispute->status === \App\Enums\LeadDisputeStatus::Resolved)
        <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-5">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-xs font-bold text-emerald-900 uppercase tracking-wider">Dispute Resolved by Admin</h3>
                    <p class="text-xs text-emerald-800 mt-1 font-medium">{{ $dispute->resolution_reason }}</p>
                    <p class="text-[11px] text-emerald-600 mt-2 font-mono">Resolved on {{ $dispute->resolved_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    @elseif($dispute->status === \App\Enums\LeadDisputeStatus::Rejected)
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-xs font-bold text-rose-900 uppercase tracking-wider">Dispute Rejected</h3>
                    <p class="text-xs text-rose-800 mt-1 font-medium">{{ $dispute->resolution_reason }}</p>
                    <p class="text-[11px] text-rose-600 mt-2 font-mono">Decision date: {{ $dispute->resolved_at?->timezone('Asia/Kolkata')->format('d M Y, h:i A') }}</p>
                </div>
            </div>
        </div>
    @elseif($dispute->status === \App\Enums\LeadDisputeStatus::UnderReview)
        <div class="rounded-2xl bg-sky-50 border border-sky-200 p-5">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-sky-600 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <h3 class="text-xs font-bold text-sky-900 uppercase tracking-wider">Under Active Review</h3>
                    <p class="text-xs text-sky-800 mt-1">Our administrative team is actively reviewing the submitted referral dates and supporting proof.</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Content Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Main Dossier -->
        <div class="md:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Dispute Explanation & Claim Details
                </h2>

                <div class="text-xs text-slate-700 whitespace-pre-line bg-slate-50 p-4 rounded-xl border border-slate-200">
                    {{ $dispute->explanation }}
                </div>

                @if($dispute->evidence_path)
                    <div class="p-3.5 rounded-xl border border-slate-200 flex items-center justify-between bg-slate-50/50">
                        <div class="flex items-center space-x-2.5">
                            <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div>
                                <div class="text-xs font-bold text-slate-800">{{ $dispute->evidence_filename ?? 'Evidence Document' }}</div>
                                <div class="text-[10px] text-slate-400">Attached proof file</div>
                            </div>
                        </div>
                        <a href="{{ route('partner.disputes.evidence', $dispute) }}" class="text-xs font-bold text-tg-purple hover:underline">
                            Download
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Meta Card -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm">
                <h2 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-3 border-b border-slate-100">
                    Claim Identification
                </h2>

                <dl class="space-y-3 mt-4 text-xs">
                    <div>
                        <dt class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Claimed Phone</dt>
                        <dd class="font-mono font-bold text-slate-800 mt-0.5">{{ $dispute->customer_phone_claimed ?? 'Not specified' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Claimed Email</dt>
                        <dd class="font-mono text-slate-800 mt-0.5">{{ $dispute->customer_email_claimed ?? 'Not specified' }}</dd>
                    </div>
                    @if($dispute->lead)
                        <div>
                            <dt class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">Associated Lead</dt>
                            <dd class="mt-0.5">
                                <a href="{{ route('partner.leads.show', $dispute->lead) }}" class="font-bold text-tg-purple hover:underline">
                                    #{{ $dispute->lead->lead_number }}
                                </a>
                                <div class="text-[11px] text-slate-500">{{ $dispute->lead->customer_name }}</div>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
