@extends('layouts.app')

@section('title', "Review KYC Application — {$kyc->partner->company_name} — Admin Control")

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple transition">Dashboard</a>
                <span>&bull;</span>
                <a href="{{ route('admin.kyc.index') }}" class="hover:text-tg-purple transition">KYC Management</a>
                <span>&bull;</span>
                <span class="text-slate-600 font-semibold font-mono">{{ $kyc->partner->partner_code }}</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight flex items-center gap-3">
                <span>KYC Application: {{ $kyc->partner->company_name }}</span>
                <span class="text-xs font-mono font-bold px-2.5 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200">
                    Version {{ $kyc->current_version }}
                </span>
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Submitted on {{ $kyc->submitted_at?->format('M d, Y h:i A') ?? 'N/A' }} &bull; Partner Code: <span class="font-mono text-tg-purple font-semibold">{{ $kyc->partner->partner_code }}</span>
            </p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.kyc.index') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-white border border-slate-200 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                &larr; Back to Applications
            </a>
            <a href="{{ route('admin.partners.show', $kyc->partner) }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition">
                Partner Profile &rarr;
            </a>
        </div>
    </div>

    <!-- Dual Status Badges & Legal Name Match Analysis -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- 1. KYC Identity Status -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">KYC &amp; Identity Status</span>
                <span class="text-base font-extrabold text-tg-dark mt-0.5 block">{{ $kyc->kyc_status->label() }}</span>
                @if ($kyc->kyc_approved_at)
                    <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5">Verified on {{ $kyc->kyc_approved_at->format('M d, Y') }}</span>
                @endif
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $kyc->kyc_status->badgeClasses() }}">
                {{ $kyc->kyc_status->label() }}
            </span>
        </div>

        <!-- 2. Bank Verification Status -->
        <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Bank Account Verification</span>
                <span class="text-base font-extrabold text-tg-dark mt-0.5 block">{{ $kyc->bank_verification_status->label() }}</span>
                @if ($kyc->bank_verified_at)
                    <span class="text-[10px] text-emerald-600 font-semibold block mt-0.5">Verified on {{ $kyc->bank_verified_at->format('M d, Y') }}</span>
                @endif
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold border {{ $kyc->bank_verification_status->badgeClasses() }}">
                {{ $kyc->bank_verification_status->label() }}
            </span>
        </div>

        <!-- 3. Legal Name vs Bank Matcher -->
        <div class="rounded-2xl border p-5 shadow-sm flex items-center justify-between {{ $nameMatch ? 'bg-emerald-50/50 border-emerald-200 text-emerald-950' : 'bg-amber-50/50 border-amber-200 text-amber-950' }}">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider {{ $nameMatch ? 'text-emerald-700' : 'text-amber-700' }} block">
                    Account Name Correlation
                </span>
                <span class="text-xs font-bold mt-1 block">
                    {{ $nameMatch ? '✓ Exact / Closely Matching' : '⚠️ Potential Name Discrepancy' }}
                </span>
                <span class="text-[10px] text-slate-500 block mt-0.5">
                    Legal: <strong class="text-slate-800">{{ $kyc->legal_name }}</strong> vs Bank: <strong class="text-slate-800">{{ $kyc->bank_account_holder_name }}</strong>
                </span>
            </div>
            <span class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs flex-shrink-0 {{ $nameMatch ? 'bg-emerald-600 text-white' : 'bg-amber-500 text-white' }}">
                {{ $nameMatch ? '✓' : '!' }}
            </span>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Column 1: Identity & PAN Verification -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-tg-dark flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-tg-purple mr-2"></span>
                    1. Identity &amp; PAN Verification
                </h2>
                <span class="text-[11px] font-bold text-tg-purple uppercase tracking-wider">
                    Entity: {{ $kyc->formattedPartnerType() }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 text-xs">
                <div>
                    <span class="text-slate-400 block text-[11px]">{{ $kyc->isBusiness() ? 'Legal Business Name' : 'Partner Legal Name' }}</span>
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
                    <span class="text-slate-400 block text-[11px]">PAN Number (Decrypted for Admin)</span>
                    <span class="font-mono font-bold text-tg-purple text-sm mt-0.5 block tracking-wider">{{ $kyc->pan_number }}</span>
                    <span class="text-[10px] text-slate-400">Masked in UI: {{ $kyc->masked_pan }}</span>
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
                <p class="font-medium text-slate-700 mt-0.5 leading-relaxed">
                    {{ $kyc->registered_address }}<br>
                    {{ $kyc->city }}, {{ $kyc->state }} - {{ $kyc->postal_code }}, {{ $kyc->country }}
                </p>
                <span class="text-[10px] text-slate-400 block mt-1">
                    Primary ID matches address: <strong>{{ $kyc->id_contains_current_address ? 'Yes' : 'No (Separate Address Proof Required)' }}</strong>
                </span>
            </div>

            <!-- Uploaded Identity Files (Admin Authorized Downloads) -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Submitted Document Files</span>
                
                <div class="flex flex-wrap gap-2">
                    @if ($kyc->pan_document_path)
                        <a href="{{ route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'pan']) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-tg-purple/10 text-tg-purple text-xs font-bold hover:bg-tg-purple hover:text-white transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View PAN Card
                        </a>
                    @else
                        <span class="text-xs text-rose-500 font-semibold">No PAN document attached</span>
                    @endif

                    @if ($kyc->identity_document_path)
                        <a href="{{ route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'identity']) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-tg-purple/10 text-tg-purple text-xs font-bold hover:bg-tg-purple hover:text-white transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View {{ $kyc->identity_document_type === 'aadhaar' ? 'Aadhaar (Front)' : 'Identity Proof' }}
                        </a>
                    @endif

                    @if ($kyc->identity_document_back_path)
                        <a href="{{ route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'identity_back']) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-tg-purple/10 text-tg-purple text-xs font-bold hover:bg-tg-purple hover:text-white transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Aadhaar (Back)
                        </a>
                    @endif

                    @if ($kyc->address_document_path)
                        <a href="{{ route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'address']) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200 transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Address Proof
                        </a>
                    @endif
                </div>
            </div>

            <!-- Declaration verification note -->
            <div class="pt-3 border-t border-slate-100 text-[11px] text-slate-500">
                <span class="font-bold text-slate-700">Legal Declaration:</span>
                <span class="text-emerald-700 font-semibold">✓ Accepted &amp; Signed</span>
                on {{ $kyc->declaration_accepted_at?->format('M d, Y h:i A') ?? $kyc->submitted_at?->format('M d, Y h:i A') ?? 'Submission' }}
            </div>

            <!-- KYC Action Controls -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Identity Decision:</span>

                <div class="flex items-center space-x-2">
                    @if ($kyc->kyc_status->value !== 'approved')
                        <form method="POST" action="{{ route('admin.kyc.approve-kyc', $kyc) }}">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-emerald-600 text-white text-xs font-bold hover:bg-emerald-700 transition shadow-sm">
                                ✓ Approve Identity
                            </button>
                        </form>
                    @endif

                    @if ($kyc->kyc_status->value !== 'rejected')
                        <button type="button"
                                onclick="document.getElementById('rejectKycModal').classList.remove('hidden')"
                                class="px-3.5 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold hover:bg-rose-100 transition">
                            ✕ Reject Identity
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Column 2: Bank Account & Payout Verification -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-tg-dark flex items-center">
                    <span class="w-2.5 h-2.5 rounded-full bg-tg-blue mr-2"></span>
                    2. Bank Account &amp; Disbursement Verification
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
                    <span class="text-slate-400 block text-[11px]">Account Number (Full - Admin Eyes Only)</span>
                    <span class="font-mono font-bold text-slate-900 text-sm mt-0.5 block tracking-wider">{{ $kyc->bank_account_number }}</span>
                    <span class="text-[10px] text-slate-400">Masked for partner: {{ $kyc->masked_account }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[11px]">IFSC Code</span>
                    <span class="font-mono font-bold text-tg-purple text-sm mt-0.5 block tracking-wider">{{ $kyc->bank_ifsc_code }}</span>
                </div>
            </div>

            @if ($kyc->upi_id)
                <div class="pt-2 text-xs">
                    <span class="text-slate-400 block text-[11px]">UPI Instant Payout ID</span>
                    <span class="font-mono font-bold text-tg-purple mt-0.5 block">{{ $kyc->upi_id }}</span>
                </div>
            @endif

            <!-- Uploaded Bank Proof File -->
            <div class="pt-4 border-t border-slate-100 space-y-2">
                <span class="text-[11px] uppercase tracking-wider font-bold text-slate-400 block">Bank Account Proof File</span>
                
                <div>
                    @if ($kyc->bank_proof_document_path)
                        <a href="{{ route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'bank_proof']) }}"
                           target="_blank"
                           class="inline-flex items-center px-3 py-1.5 rounded-lg bg-tg-blue/10 text-tg-blue text-xs font-bold hover:bg-tg-blue hover:text-white transition">
                            <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            View Cancelled Cheque / Passbook
                        </a>
                    @else
                        <span class="text-xs text-rose-500 font-semibold">No bank proof document attached</span>
                    @endif
                </div>
            </div>

            <!-- Bank Action Controls -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700">Bank Decision:</span>

                <div class="flex items-center space-x-2">
                    @if ($kyc->bank_verification_status->value !== 'verified')
                        <form method="POST" action="{{ route('admin.kyc.verify-bank', $kyc) }}">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-tg-blue text-white text-xs font-bold hover:bg-tg-blue/90 transition shadow-sm">
                                ✓ Verify Bank Account
                            </button>
                        </form>
                    @endif

                    @if ($kyc->bank_verification_status->value !== 'rejected')
                        <button type="button"
                                onclick="document.getElementById('rejectBankModal').classList.remove('hidden')"
                                class="px-3.5 py-1.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold hover:bg-rose-100 transition">
                            ✕ Reject Bank
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Administrative Workflow Controls Bar -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider">Compliance Workflow Status:</h3>
            <p class="text-xs text-slate-500 mt-0.5">
                Current stage: <strong class="text-slate-800">{{ $kyc->kyc_status->label() }}</strong> &bull; Bank: <strong class="text-slate-800">{{ $kyc->bank_verification_status->label() }}</strong>
            </p>
        </div>

        <div class="flex items-center space-x-3">
            @if ($kyc->kyc_status->value === 'pending')
                <form method="POST" action="{{ route('admin.kyc.start-review', $kyc) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-sky-600 text-white text-xs font-bold hover:bg-sky-700 transition">
                        Mark Under Review
                    </button>
                </form>
            @endif

            <button type="button"
                    onclick="document.getElementById('correctionModal').classList.remove('hidden')"
                    class="px-4 py-2 rounded-xl bg-purple-50 border border-purple-200 text-purple-700 text-xs font-bold hover:bg-purple-100 transition">
                Request Correction from Partner &rarr;
            </button>
        </div>
    </div>

    <!-- Audit History & Immutable Version Log -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-4">Immutable Audit &amp; Version History</h2>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead>
                    <tr class="border-b border-slate-100 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                        <th class="pb-3">Version</th>
                        <th class="pb-3">Action</th>
                        <th class="pb-3">KYC Status</th>
                        <th class="pb-3">Bank Status</th>
                        <th class="pb-3">Admin / Actor</th>
                        <th class="pb-3">Timestamp</th>
                        <th class="pb-3">Reason / Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-600">
                    @forelse ($kyc->histories as $history)
                        <tr>
                            <td class="py-3 font-mono font-bold text-tg-dark">v{{ $history->version }}</td>
                            <td class="py-3 font-semibold text-slate-800 capitalize">{{ str_replace('_', ' ', $history->action) }}</td>
                            <td class="py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $history->kyc_status->badgeClasses() }}">
                                    {{ $history->kyc_status->label() }}
                                </span>
                            </td>
                            <td class="py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $history->bank_verification_status->badgeClasses() }}">
                                    {{ $history->bank_verification_status->label() }}
                                </span>
                            </td>
                            <td class="py-3 text-slate-700">
                                {{ $history->admin ? $history->admin->name : 'Partner' }}
                            </td>
                            <td class="py-3 font-mono text-[11px] text-slate-400">
                                {{ $history->created_at->format('M d, Y h:i A') }}
                            </td>
                            <td class="py-3 text-slate-600 max-w-xs truncate">
                                {{ $history->reason ?: ($history->internal_notes ?: '—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-slate-400 text-xs">
                                No audit history entries recorded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal 1: Reject KYC -->
<div id="rejectKycModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-base font-bold text-rose-700">Reject KYC &amp; Identity Verification</h3>
        <p class="text-xs text-slate-500">A mandatory explanation must be provided to the partner detailing why their identity documentation is non-compliant.</p>

        <form method="POST" action="{{ route('admin.kyc.reject-kyc', $kyc) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Rejection Reason <span class="text-rose-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200 px-3 py-2" placeholder="Explain the specific issue (e.g. illegible PAN card scan, name mismatch)..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Internal Admin Notes (Optional - Hidden from partner)</label>
                <textarea name="internal_admin_notes" rows="2" class="w-full text-xs rounded-xl border-slate-200 px-3 py-2" placeholder="Private notes for compliance records..."></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('rejectKycModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 shadow-sm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Reject Bank -->
<div id="rejectBankModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-base font-bold text-rose-700">Reject Bank Account Verification</h3>
        <p class="text-xs text-slate-500">Provide an explanation of why the submitted bank details or bank proof document failed verification.</p>

        <form method="POST" action="{{ route('admin.kyc.reject-bank', $kyc) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Bank Rejection Reason <span class="text-rose-500">*</span></label>
                <textarea name="rejection_reason" rows="3" required class="w-full text-xs rounded-xl border-slate-200 focus:border-rose-500 focus:ring focus:ring-rose-200 px-3 py-2" placeholder="Explain the issue (e.g. account holder name does not match legal name, invalid IFSC)..."></textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('rejectBankModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-rose-600 text-white text-xs font-bold hover:bg-rose-700 shadow-sm">Confirm Bank Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 3: Request Correction -->
<div id="correctionModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 shadow-2xl border border-slate-100 space-y-4">
        <h3 class="text-base font-bold text-purple-900">Authorize KYC Correction Cycle</h3>
        <p class="text-xs text-slate-500">This action temporarily unlocks the application for the partner so they can update specific requested information.</p>

        <form method="POST" action="{{ route('admin.kyc.request-correction', $kyc) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Mandatory Correction Instructions for Partner <span class="text-rose-500">*</span></label>
                <textarea name="correction_reason" rows="3" required class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200 px-3 py-2" placeholder="Explain clearly what the partner needs to rectify..."></textarea>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Select Specifically Allowed Correction Fields (Optional - Leave blank to allow all):</label>
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-700">
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="pan_document" class="rounded border-slate-300 text-tg-purple">
                        <span>PAN Card Document</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="bank_proof_document" class="rounded border-slate-300 text-tg-purple">
                        <span>Bank Proof Document</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="bank_account_number" class="rounded border-slate-300 text-tg-purple">
                        <span>Bank Account Number</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="bank_ifsc_code" class="rounded border-slate-300 text-tg-purple">
                        <span>IFSC Code</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="legal_name" class="rounded border-slate-300 text-tg-purple">
                        <span>Legal Name</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="allowed_fields[]" value="identity_document" class="rounded border-slate-300 text-tg-purple">
                        <span>Identity Document</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-2">
                <button type="button" onclick="document.getElementById('correctionModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">Cancel</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-purple-600 text-white text-xs font-bold hover:bg-purple-700 shadow-sm">Authorize Correction</button>
            </div>
        </form>
    </div>
</div>
@endsection
