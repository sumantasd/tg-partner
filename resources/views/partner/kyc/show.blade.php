@extends('layouts.app')

@section('title', 'KYC & Bank Verification — Partner Portal')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('partner.workspace') }}" class="hover:text-tg-purple transition">Dashboard</a>
                <span>&bull;</span>
                <span class="text-slate-600 font-semibold">KYC & Bank Verification</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">KYC & Bank Account Verification</h1>
            <p class="text-xs text-slate-500 mt-0.5">Mandatory compliance and banking verification required for commission withdrawals.</p>
        </div>

        <div class="flex items-center space-x-3">
            @if (! $kyc || $kyc->kyc_status->value === 'not_submitted')
                <a href="{{ route('partner.kyc.create') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Submit KYC Application &rarr;
                </a>
            @elseif ($kyc->isCorrectionRequested())
                <a href="{{ route('partner.kyc.correction') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 shadow-sm transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Submit Correction &rarr;
                </a>
            @else
                <span class="inline-flex items-center px-3.5 py-1.5 rounded-xl bg-slate-100 text-slate-500 text-xs font-bold border border-slate-200 cursor-not-allowed">
                    <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                    Submission Locked (v{{ $kyc->current_version }})
                </span>
            @endif
        </div>
    </div>

    <!-- Status Overview & Notification Banners -->
    @if ($kyc && $kyc->isFullyVerified())
        <div class="rounded-3xl bg-emerald-50 border border-emerald-200/90 p-6 shadow-sm">
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-500 text-white flex items-center justify-center font-bold flex-shrink-0">
                    ✓
                </div>
                <div>
                    <h3 class="text-sm font-bold text-emerald-950">Partner Fully Verified for Commission Withdrawals</h3>
                    <p class="text-xs text-emerald-800 mt-1">
                        Both your personal identity/PAN and receiving bank account details have been authenticated and verified by Tech Googly Compliance.
                        All commission withdrawals are active and unrestricted.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                        <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-emerald-300 font-semibold text-emerald-800">
                            KYC Approved: {{ $kyc->kyc_approved_at?->format('M d, Y') ?? 'Verified' }}
                        </span>
                        <span class="bg-white/80 px-2.5 py-1 rounded-lg border border-emerald-300 font-semibold text-emerald-800">
                            Bank Account Verified: {{ $kyc->bank_verified_at?->format('M d, Y') ?? 'Verified' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($kyc && $kyc->isCorrectionRequested())
        <div class="rounded-3xl bg-purple-50 border border-purple-200 p-6 shadow-sm">
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-2xl bg-purple-600 text-white flex items-center justify-center font-bold flex-shrink-0">
                    !
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-purple-950">Action Required: Correction Requested by Compliance</h3>
                    <p class="text-xs text-purple-900 mt-1"><strong>Admin Instructions:</strong> {{ $kyc->correction_reason }}</p>
                    @if (! empty($kyc->correction_fields))
                        <div class="mt-2 text-xs text-purple-800">
                            <span class="font-semibold">Specifically Allowed Correction Fields:</span>
                            <span class="font-mono bg-purple-100 px-2 py-0.5 rounded text-[11px]">{{ implode(', ', $kyc->correction_fields) }}</span>
                        </div>
                    @endif
                    <div class="mt-4">
                        <a href="{{ route('partner.kyc.correction') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 transition">
                            Edit and Resubmit Application &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($kyc && ($kyc->kyc_status->value === 'rejected' || $kyc->bank_verification_status->value === 'rejected'))
        <div class="rounded-3xl bg-rose-50 border border-rose-200 p-6 shadow-sm">
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-2xl bg-rose-600 text-white flex items-center justify-center font-bold flex-shrink-0">
                    ✕
                </div>
                <div>
                    <h3 class="text-sm font-bold text-rose-950">Verification Rejected</h3>
                    <p class="text-xs text-rose-900 mt-1"><strong>Reason:</strong> {{ $kyc->rejection_reason }}</p>
                    <p class="text-[11px] text-rose-700 mt-2">Commission withdrawals remain locked until compliant documentation is verified. If you believe this is in error, contact partner support or wait for Admin to authorize a correction cycle.</p>
                </div>
            </div>
        </div>
    @elseif ($kyc && $kyc->isLocked())
        <div class="rounded-3xl bg-amber-50 border border-amber-200 p-6 shadow-sm">
            <div class="flex items-start space-x-4">
                <div class="w-10 h-10 rounded-2xl bg-amber-500 text-white flex items-center justify-center font-bold flex-shrink-0">
                    ⏳
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-950">Application Locked Under Verification</h3>
                    <p class="text-xs text-amber-900 mt-1">
                        Your KYC application (Version {{ $kyc->current_version }}) was submitted on {{ $kyc->submitted_at?->format('M d, Y h:i A') ?? 'N/A' }} and is currently under review by our compliance team.
                        All details and uploaded documents are locked and read-only.
                    </p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-3xl bg-gradient-to-r from-tg-purple/10 to-tg-pink/10 border border-tg-purple/20 p-6 shadow-sm">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-sm font-bold text-tg-dark">KYC & Bank Verification Not Yet Submitted</h3>
                    <p class="text-xs text-slate-500 mt-1">Submit your PAN card, official identity proof, and receiving bank details to unlock commission withdrawals.</p>
                </div>
                <a href="{{ route('partner.kyc.create') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition flex-shrink-0">
                    Start Verification &rarr;
                </a>
            </div>
        </div>
    @endif

    <!-- Dual Status Badges Bar -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- 1. Identity & PAN Status -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Identity & PAN Status</span>
                <span class="text-sm font-extrabold text-tg-dark mt-0.5 block">
                    {{ $kyc ? $kyc->kyc_status->label() : 'Not Submitted' }}
                </span>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $kyc ? $kyc->kyc_status->badgeClasses() : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                    {{ $kyc ? $kyc->kyc_status->label() : 'Not Submitted' }}
                </span>
            </div>
        </div>

        <!-- 2. Bank Account Status -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Bank Account Verification</span>
                <span class="text-sm font-extrabold text-tg-dark mt-0.5 block">
                    {{ $kyc ? $kyc->bank_verification_status->label() : 'Not Submitted' }}
                </span>
            </div>
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $kyc ? $kyc->bank_verification_status->badgeClasses() : 'bg-slate-100 text-slate-600 border-slate-200' }}">
                    {{ $kyc ? $kyc->bank_verification_status->label() : 'Not Submitted' }}
                </span>
            </div>
        </div>
    </div>

    @if ($kyc && $kyc->kyc_status->value !== 'not_submitted')
        <!-- Details Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Left: Personal & PAN Details -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-tg-dark flex items-center">
                        <span class="w-2 h-2 rounded-full bg-tg-purple mr-2"></span>
                        Personal &amp; Identity Information
                    </h2>
                    <span class="text-[11px] font-bold text-tg-purple uppercase tracking-wider">
                        {{ $kyc->formattedPartnerType() }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">{{ $kyc->isBusiness() ? 'Legal Business Name' : 'Full Legal Name' }}</span>
                        <span class="font-bold text-tg-dark text-sm mt-0.5 block">{{ $kyc->legal_name }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">{{ $kyc->isBusiness() ? 'Date of Incorporation' : 'Date of Birth' }}</span>
                        <span class="font-medium text-slate-700 mt-0.5 block">
                            {{ $kyc->dob_or_incorporation_date ? $kyc->dob_or_incorporation_date->format('M d, Y') : '—' }}
                        </span>
                    </div>

                    @if ($kyc->authorized_signatory_name)
                        <div>
                            <span class="text-slate-400 block text-[11px]">Authorized Signatory</span>
                            <span class="font-bold text-slate-800 text-xs mt-0.5 block">{{ $kyc->authorized_signatory_name }}</span>
                        </div>
                    @endif

                    @if ($kyc->business_registration_number)
                        <div>
                            <span class="text-slate-400 block text-[11px]">Business Reg No (CIN/LLPIN)</span>
                            <span class="font-mono font-bold text-slate-800 text-xs mt-0.5 block">{{ $kyc->business_registration_number }}</span>
                        </div>
                    @endif

                    @if ($kyc->gstin)
                        <div class="col-span-2">
                            <span class="text-slate-400 block text-[11px]">GSTIN</span>
                            <span class="font-mono font-bold text-slate-800 text-xs mt-0.5 block">{{ $kyc->gstin }}</span>
                        </div>
                    @endif

                    <div>
                        <span class="text-slate-400 block text-[11px]">Permanent Account Number (PAN)</span>
                        <span class="font-mono font-bold text-tg-purple text-sm mt-0.5 block">{{ $kyc->masked_pan }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">Identity Document Type</span>
                        <span class="font-semibold text-slate-700 capitalize mt-0.5 block">
                            {{ $kyc->formattedIdentityType() }}
                        </span>
                    </div>
                </div>

                <div class="pt-2 text-xs">
                    <span class="text-slate-400 block text-[11px]">Registered Address</span>
                    <p class="font-medium text-slate-700 mt-0.5">
                        {{ $kyc->registered_address }}<br>
                        {{ $kyc->city }}, {{ $kyc->state }} - {{ $kyc->postal_code }}, {{ $kyc->country }}
                    </p>
                </div>

                <!-- Attached Identity Proofs -->
                <div class="pt-4 border-t border-slate-100 space-y-2">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Submitted Identity Files</span>
                    
                    <div class="flex flex-wrap gap-2">
                        @if ($kyc->pan_document_path)
                            <a href="{{ route('partner.kyc.document', 'pan') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-tg-purple hover:text-white transition">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download PAN Card
                            </a>
                        @endif

                        @if ($kyc->identity_document_path)
                            <a href="{{ route('partner.kyc.document', 'identity') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-tg-purple hover:text-white transition">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download {{ $kyc->identity_document_type === 'aadhaar' ? 'Aadhaar (Front)' : 'Identity Proof' }}
                            </a>
                        @endif

                        @if ($kyc->identity_document_back_path)
                            <a href="{{ route('partner.kyc.document', 'identity_back') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-tg-purple hover:text-white transition">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download Aadhaar (Back)
                            </a>
                        @endif

                        @if ($kyc->address_document_path)
                            <a href="{{ route('partner.kyc.document', 'address') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-tg-purple hover:text-white transition">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download Address Proof
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Bank Account Details -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-tg-dark flex items-center">
                        <span class="w-2 h-2 rounded-full bg-tg-blue mr-2"></span>
                        Receiving Bank Account Details
                    </h2>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider capitalize">
                        {{ str_replace('_', ' ', $kyc->preferred_payout_method) }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Bank Name</span>
                        <span class="font-bold text-tg-dark text-sm mt-0.5 block">{{ $kyc->bank_name }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">Account Holder Name</span>
                        <span class="font-bold text-tg-dark text-sm mt-0.5 block">{{ $kyc->bank_account_holder_name }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">Account Number (Masked)</span>
                        <span class="font-mono font-bold text-tg-dark text-sm mt-0.5 block">{{ $kyc->masked_account }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block text-[11px]">IFSC Code</span>
                        <span class="font-mono font-bold text-tg-purple text-sm mt-0.5 block">{{ $kyc->bank_ifsc_code }}</span>
                    </div>
                </div>

                @if ($kyc->upi_id)
                    <div class="pt-2 text-xs">
                        <span class="text-slate-400 block text-[11px]">UPI Instant Payout ID</span>
                        <span class="font-mono font-bold text-tg-purple mt-0.5 block">{{ $kyc->upi_id }}</span>
                    </div>
                @endif

                <!-- Attached Bank Proof -->
                <div class="pt-4 border-t border-slate-100 space-y-2">
                    <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Submitted Bank Proof</span>
                    
                    <div>
                        @if ($kyc->bank_proof_document_path)
                            <a href="{{ route('partner.kyc.document', 'bank_proof') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-tg-blue hover:text-white transition">
                                <svg class="w-3.5 h-3.5 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Download Cancelled Cheque / Bank Passbook
                            </a>
                        @else
                            <span class="text-slate-400 text-xs italic">No bank proof document uploaded</span>
                        @endif
                    </div>
                </div>

                <!-- Security Guarantee Notice -->
                <div class="mt-4 p-3 bg-slate-50 rounded-2xl border border-slate-100 text-[11px] text-slate-500 leading-relaxed">
                    <span class="font-bold text-slate-700">🔒 Encryption &amp; Security:</span>
                    Account and PAN numbers are encrypted at rest with AES-256. Documents are held in private disk storage accessible only by authenticated compliance reviewers.
                </div>
            </div>
        </div>

        <!-- Verification History & Audit Log -->
        @if ($histories->isNotEmpty())
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-4">Submission &amp; Audit Timeline</h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="pb-3">Version / Action</th>
                                <th class="pb-3">KYC Status</th>
                                <th class="pb-3">Bank Status</th>
                                <th class="pb-3">Action Date</th>
                                <th class="pb-3">Remarks / Reason</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-600">
                            @foreach ($histories as $history)
                                <tr>
                                    <td class="py-3 font-semibold text-tg-dark">
                                        v{{ $history->version }} &bull; <span class="capitalize">{{ str_replace('_', ' ', $history->action) }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $history->kyc_status->badgeClasses() }}">
                                            {{ $history->kyc_status->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $history->bank_verification_status->badgeClasses() }}">
                                            {{ $history->bank_verification_status->label() }}
                                        </span>
                                    </td>
                                    <td class="py-3 text-slate-400 font-mono text-[11px]">
                                        {{ $history->created_at->format('M d, Y h:i A') }}
                                    </td>
                                    <td class="py-3 text-slate-500">
                                        {{ $history->reason ?: ($history->action === 'submitted' ? 'Application submitted' : '—') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection
