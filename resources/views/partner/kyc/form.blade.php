@extends('layouts.app')

@section('title', ($isCorrection ? 'Resubmit KYC Corrections' : 'Submit KYC & Bank Verification') . ' — Partner Portal')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto pb-16 px-1 sm:px-0">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-400 mb-1">
                <a href="{{ route('partner.workspace') }}" class="hover:text-tg-purple transition">Dashboard</a>
                <span>&bull;</span>
                <a href="{{ route('partner.kyc.show') }}" class="hover:text-tg-purple transition">KYC Status</a>
                <span>&bull;</span>
                <span class="text-slate-600 font-semibold">{{ $isCorrection ? 'Correction' : 'Submission' }}</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">
                {{ $isCorrection ? 'Resubmit KYC Application (Corrections)' : 'Partner KYC & Bank Verification' }}
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Mandatory identity and financial compliance verification for partner commission disbursements.
            </p>
        </div>

        <a href="{{ route('partner.kyc.show') }}" class="inline-flex items-center text-xs font-semibold text-slate-600 hover:text-tg-purple transition self-start sm:self-auto">
            &larr; Back to KYC Status
        </a>
    </div>

    <!-- Critical Security Lock Notice Banner -->
    <div class="rounded-2xl bg-amber-50/90 border border-amber-300 p-4 sm:p-5 shadow-sm text-xs text-amber-950 leading-relaxed">
        <div class="flex items-start space-x-3">
            <span class="text-lg flex-shrink-0">⚠️</span>
            <div class="space-y-1">
                <strong class="font-bold block text-amber-950">Important Submission &amp; Locking Policy:</strong>
                <p class="text-amber-900 text-xs">
                    Upon submission, your application and all banking credentials will be permanently locked under review.
                    Documents and financial records cannot be modified, replaced, or deleted until Admin completes review or explicitly issues an authorized correction request.
                </p>
            </div>
        </div>
    </div>

    @if ($isCorrection && $kyc)
        <!-- Admin Correction Instructions Box -->
        <div class="rounded-2xl bg-purple-50 border border-purple-200 p-5 shadow-sm text-xs text-purple-950">
            <div class="flex items-center space-x-2 mb-2">
                <span class="w-2.5 h-2.5 rounded-full bg-tg-purple"></span>
                <h3 class="font-bold text-sm text-purple-900">Admin Correction Request (Version {{ $kyc->current_version }})</h3>
            </div>
            <div class="bg-white/80 p-3.5 rounded-xl border border-purple-200 font-medium text-slate-800 text-xs">
                {{ $kyc->correction_reason }}
            </div>
            @if (! empty($kyc->correction_fields))
                <p class="text-[11px] text-purple-700 mt-2">
                    <strong>Editable fields in this cycle:</strong>
                    <span class="font-mono bg-purple-100 px-2 py-0.5 rounded text-[11px] text-purple-900">
                        {{ implode(', ', $kyc->correction_fields) }}
                    </span>
                </p>
            @endif
        </div>
    @endif

    <!-- Prefilled Verified Account Information Card -->
    <div class="bg-white rounded-2xl border border-slate-300 p-4 sm:p-5 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-3 border-b border-slate-100">
            <div>
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Verified Account Contact Credentials</span>
                <p class="text-xs text-slate-600 mt-0.5">Prefilled from your registered partner account profile. Read-only for compliance.</p>
            </div>
            <span class="inline-flex items-center text-[11px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-1 rounded-full self-start sm:self-auto">
                <svg class="w-3.5 h-3.5 mr-1 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                Authenticated Profile
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-3">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Registered Mobile Number</label>
                <div class="relative">
                    <input type="text"
                           value="{{ $partner->phone ?? 'Not provided' }}"
                           readonly
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed px-3.5 py-2.5 font-mono">
                    <span class="absolute right-3 top-2.5 text-xs text-emerald-600 font-bold flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified
                    </span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Registered Email Address</label>
                <div class="relative">
                    <input type="email"
                           value="{{ $partner->user->email ?? auth()->user()->email }}"
                           readonly
                           class="w-full text-xs rounded-xl border border-slate-200 bg-slate-100 text-slate-600 cursor-not-allowed px-3.5 py-2.5 font-mono">
                    <span class="absolute right-3 top-2.5 text-xs text-emerald-600 font-bold flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Body -->
    <form method="POST"
          id="kycForm"
          action="{{ $isCorrection ? route('partner.kyc.resubmit') : route('partner.kyc.store') }}"
          enctype="multipart/form-data"
          class="space-y-6">
        @csrf

        <!-- ========================================== -->
        <!-- 1. PERSONAL / BUSINESS INFORMATION -->
        <!-- ========================================== -->
        <div class="bg-white rounded-2xl border border-slate-300 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-tg-purple/10 text-tg-purple flex items-center justify-center font-bold text-xs">1</span>
                    <h2 class="text-sm font-bold text-tg-dark">Personal &amp; Entity Identification</h2>
                </div>
                <span class="text-[11px] text-tg-purple font-semibold hidden sm:inline">Government Record Match</span>
            </div>

            <!-- Partner Entity Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Partner Entity Type <span class="text-rose-500 font-bold">*</span>
                </label>
                @php
                    $currentType = old('partner_type', $kyc?->partner_type ?? 'individual_sole_proprietor');
                    // Backwards compatible normalization
                    if ($currentType === 'individual') $currentType = 'individual_sole_proprietor';
                    if ($currentType === 'business') $currentType = 'private_limited';
                @endphp
                <select name="partner_type"
                        id="partnerTypeSelect"
                        {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('partner_type')) ? 'disabled' : '' }}
                        class="w-full text-xs rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('partner_type')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}">
                    <option value="individual_sole_proprietor" {{ $currentType === 'individual_sole_proprietor' ? 'selected' : '' }}>
                        Individual / Sole Proprietor
                    </option>
                    <option value="partnership_firm" {{ $currentType === 'partnership_firm' ? 'selected' : '' }}>
                        Partnership Firm
                    </option>
                    <option value="llp" {{ $currentType === 'llp' ? 'selected' : '' }}>
                        Limited Liability Partnership (LLP)
                    </option>
                    <option value="private_limited" {{ $currentType === 'private_limited' ? 'selected' : '' }}>
                        Private Limited Company (Pvt Ltd)
                    </option>
                    <option value="public_limited" {{ $currentType === 'public_limited' ? 'selected' : '' }}>
                        Public Limited Company (Ltd)
                    </option>
                    <option value="other" {{ $currentType === 'other' ? 'selected' : '' }}>
                        Other Registered Business Entity
                    </option>
                </select>
                @if ($isCorrection && $kyc && ! $kyc->isFieldEditable('partner_type'))
                    <input type="hidden" name="partner_type" value="{{ $currentType }}">
                @endif
                @error('partner_type')
                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Legal Name -->
                <div>
                    <label id="legalNameLabel" class="block text-xs font-bold text-slate-700 mb-1">
                        Full Legal Name (as per PAN Card) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text"
                           id="legalNameInput"
                           name="legal_name"
                           value="{{ old('legal_name', $kyc?->legal_name ?? $partner->company_name) }}"
                           required
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('legal_name')) ? 'readonly' : '' }}
                           class="w-full text-xs rounded-xl border @error('legal_name') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('legal_name')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="Full name matching government documents">
                    @error('legal_name')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- DOB or Incorp Date -->
                <div>
                    <label id="dobLabel" class="block text-xs font-bold text-slate-700 mb-1">
                        Date of Birth <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="date"
                           name="dob_or_incorporation_date"
                           value="{{ old('dob_or_incorporation_date', $kyc?->dob_or_incorporation_date?->format('Y-m-d')) }}"
                           required
                           max="{{ date('Y-m-d', strtotime('-1 day')) }}"
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('dob_or_incorporation_date')) ? 'readonly' : '' }}
                           class="w-full text-xs rounded-xl border @error('dob_or_incorporation_date') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('dob_or_incorporation_date')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}">
                    @error('dob_or_incorporation_date')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Business Dynamic Fields (Shown only for Non-Individual entities) -->
            <div id="businessFieldsContainer" class="space-y-4 pt-1 hidden">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
                    <span class="font-bold text-slate-700">Entity Details:</span> Provide authorized business registration and signatory credentials as filed with MCA / Registrar.
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Authorized Signatory Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Authorized Signatory Name <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text"
                               id="signatoryNameInput"
                               name="authorized_signatory_name"
                               value="{{ old('authorized_signatory_name', $kyc?->authorized_signatory_name) }}"
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('authorized_signatory_name')) ? 'readonly' : '' }}
                               class="w-full text-xs rounded-xl border @error('authorized_signatory_name') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('authorized_signatory_name')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="Full name of director / partner / signatory">
                        @error('authorized_signatory_name')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Business Registration Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Business Registration Number (CIN / LLPIN / Reg No) <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text"
                               name="business_registration_number"
                               value="{{ old('business_registration_number', $kyc?->business_registration_number) }}"
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('business_registration_number')) ? 'readonly' : '' }}
                               class="w-full text-xs font-mono uppercase rounded-xl border @error('business_registration_number') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('business_registration_number')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="e.g. U72200WB2020PTC123456">
                        @error('business_registration_number')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- GSTIN (Optional) -->
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            GST Identification Number (GSTIN) <span class="text-slate-400 font-normal text-[11px]">(Optional)</span>
                        </label>
                        <input type="text"
                               name="gstin"
                               maxlength="15"
                               value="{{ old('gstin', $kyc?->gstin) }}"
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('gstin')) ? 'readonly' : '' }}
                               class="w-full text-xs font-mono uppercase rounded-xl border @error('gstin') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('gstin')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="15-character GSTIN (e.g. 19ABCDE1234F1Z5)">
                        <p class="text-[10px] text-slate-400 mt-1">If your business is GST-registered, enter the valid 15-character GST number.</p>
                        @error('gstin')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- PAN Number -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Permanent Account Number (PAN) <span class="text-rose-500 font-bold">*</span>
                </label>
                <input type="text"
                       name="pan_number"
                       id="panNumberInput"
                       value="{{ old('pan_number', $kyc?->pan_number) }}"
                       maxlength="10"
                       required
                       style="text-transform: uppercase;"
                       {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('pan_number')) ? 'readonly' : '' }}
                       class="w-full text-xs font-mono font-bold tracking-wider uppercase rounded-xl border @error('pan_number') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('pan_number')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                       placeholder="ABCDE1234F">
                <p class="text-[10px] text-slate-400 mt-1">10-character Indian PAN code (5 letters, 4 numbers, 1 letter).</p>
                @error('pan_number')
                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Official Registered Address -->
            <div class="space-y-3 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Registered Permanent / Official Address <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <textarea name="registered_address"
                              rows="2"
                              required
                              {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('registered_address')) ? 'readonly' : '' }}
                              class="w-full text-xs rounded-xl border @error('registered_address') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('registered_address')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                              placeholder="Building/Door No, Street, Landmark, Area">{{ old('registered_address', $kyc?->registered_address ?? $partner->address_line1) }}</textarea>
                    @error('registered_address')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            City <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text"
                               name="city"
                               value="{{ old('city', $kyc?->city ?? $partner->city) }}"
                               required
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('city')) ? 'readonly' : '' }}
                               class="w-full text-xs rounded-xl border @error('city') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('city')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="e.g. Kolkata">
                        @error('city')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            State <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text"
                               name="state"
                               value="{{ old('state', $kyc?->state ?? $partner->state) }}"
                               required
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('state')) ? 'readonly' : '' }}
                               class="w-full text-xs rounded-xl border @error('state') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('state')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="e.g. West Bengal">
                        @error('state')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            PIN Code <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text"
                               name="postal_code"
                               maxlength="6"
                               value="{{ old('postal_code', $kyc?->postal_code ?? $partner->postal_code) }}"
                               required
                               {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('postal_code')) ? 'readonly' : '' }}
                               class="w-full text-xs font-mono rounded-xl border @error('postal_code') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('postal_code')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                               placeholder="6-digit PIN code">
                        @error('postal_code')
                            <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 2. IDENTITY DOCUMENT UPLOADS -->
        <!-- ========================================== -->
        <div class="bg-white rounded-2xl border border-slate-300 p-5 sm:p-6 shadow-sm space-y-5">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-tg-purple/10 text-tg-purple flex items-center justify-center font-bold text-xs">2</span>
                    <h2 class="text-sm font-bold text-tg-dark">Identity &amp; Address Proof Documents</h2>
                </div>
                <span class="text-[11px] text-slate-400 font-medium">PDF, JPG, PNG &bull; Max 5MB each</span>
            </div>

            <!-- PAN Card Document Upload -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    PAN Card Document (Front View) <span class="text-rose-500 font-bold">*</span>
                </label>
                <div class="file-drop-zone relative rounded-xl border-2 border-dashed border-slate-300 hover:border-tg-purple bg-slate-50/50 p-4 transition text-center group cursor-pointer"
                     onclick="document.getElementById('panDocumentInput').click()">
                    <input type="file"
                           id="panDocumentInput"
                           name="pan_document"
                           accept=".pdf,.jpg,.jpeg,.png"
                           {{ $isCorrection ? '' : 'required' }}
                           onchange="handleFileSelect(this, 'panDocumentStatus', 'panFileName')"
                           class="hidden">
                    <div class="flex flex-col items-center justify-center space-y-1">
                        <div class="w-8 h-8 rounded-full bg-tg-purple/10 text-tg-purple flex items-center justify-center text-sm group-hover:scale-110 transition">
                            📄
                        </div>
                        <div class="text-xs text-slate-700 font-medium">
                            <span class="text-tg-purple font-bold">Click to upload</span> or drag and drop
                        </div>
                        <p class="text-[10px] text-slate-400">Clear front photo or color scan of PAN Card (Max 5MB)</p>
                    </div>
                    <div id="panDocumentStatus" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-center space-x-1">
                        <span>✓ Selected:</span>
                        <span id="panFileName" class="font-mono text-[11px]"></span>
                    </div>
                </div>
                @if ($kyc?->pan_document_path)
                    <p class="text-[11px] text-slate-500 mt-1">
                        Current document uploaded: <span class="font-mono text-tg-purple font-semibold">PAN Card Document (Version {{ $kyc->current_version }})</span>
                    </p>
                @endif
                @error('pan_document')
                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Primary Identity Proof Type Selector -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Primary Identity Proof Type <span class="text-rose-500 font-bold">*</span>
                </label>
                <select name="identity_document_type"
                        id="idProofTypeSelect"
                        {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('identity_document_type')) ? 'disabled' : '' }}
                        class="w-full text-xs rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('identity_document_type')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}">
                    <option value="aadhaar" {{ old('identity_document_type', $kyc?->identity_document_type ?? 'aadhaar') === 'aadhaar' ? 'selected' : '' }}>
                        Aadhaar Card (Requires Front &amp; Back Sides)
                    </option>
                    <option value="passport" {{ old('identity_document_type', $kyc?->identity_document_type) === 'passport' ? 'selected' : '' }}>
                        Passport (Photo &amp; Address Page)
                    </option>
                    <option value="voter_id" {{ old('identity_document_type', $kyc?->identity_document_type) === 'voter_id' ? 'selected' : '' }}>
                        Voter ID Card
                    </option>
                    <option value="driving_license" {{ old('identity_document_type', $kyc?->identity_document_type) === 'driving_license' ? 'selected' : '' }}>
                        Driving Licence
                    </option>
                </select>
                @if ($isCorrection && $kyc && ! $kyc->isFieldEditable('identity_document_type'))
                    <input type="hidden" name="identity_document_type" value="{{ $kyc->identity_document_type }}">
                @endif
                @error('identity_document_type')
                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>

            <!-- Identity Upload Boxes (Aadhaar Front & Back vs Single ID) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Front / Single Upload Box -->
                <div>
                    <label id="identityFrontLabel" class="block text-xs font-bold text-slate-700 mb-1">
                        Aadhaar Card (Front Side) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <div class="file-drop-zone relative rounded-xl border-2 border-dashed border-slate-300 hover:border-tg-purple bg-slate-50/50 p-4 transition text-center group cursor-pointer"
                         onclick="document.getElementById('identityDocumentInput').click()">
                        <input type="file"
                               id="identityDocumentInput"
                               name="identity_document"
                               accept=".pdf,.jpg,.jpeg,.png"
                               {{ $isCorrection ? '' : 'required' }}
                               onchange="handleFileSelect(this, 'idFrontStatus', 'idFrontFileName')"
                               class="hidden">
                        <div class="flex flex-col items-center justify-center space-y-1">
                            <div class="w-8 h-8 rounded-full bg-tg-purple/10 text-tg-purple flex items-center justify-center text-sm group-hover:scale-110 transition">
                                🪪
                            </div>
                            <div class="text-xs text-slate-700 font-medium">
                                <span class="text-tg-purple font-bold">Choose Front File</span>
                            </div>
                            <p id="identityFrontHelp" class="text-[10px] text-slate-400">PDF, JPG, PNG &bull; Max 5MB</p>
                        </div>
                        <div id="idFrontStatus" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-center space-x-1">
                            <span>✓ Selected:</span>
                            <span id="idFrontFileName" class="font-mono text-[11px]"></span>
                        </div>
                    </div>
                    @if ($kyc?->identity_document_path)
                        <p class="text-[11px] text-slate-500 mt-1">
                            Uploaded: <span class="font-mono text-tg-purple font-semibold">Primary ID Document (v{{ $kyc->current_version }})</span>
                        </p>
                    @endif
                    @error('identity_document')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Aadhaar Back Upload Box (Conditional) -->
                <div id="aadhaarBackBox">
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Aadhaar Card (Back Side) <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <div class="file-drop-zone relative rounded-xl border-2 border-dashed border-slate-300 hover:border-tg-purple bg-slate-50/50 p-4 transition text-center group cursor-pointer"
                         onclick="document.getElementById('identityBackInput').click()">
                        <input type="file"
                               id="identityBackInput"
                               name="identity_document_back"
                               accept=".pdf,.jpg,.jpeg,.png"
                               onchange="handleFileSelect(this, 'idBackStatus', 'idBackFileName')"
                               class="hidden">
                        <div class="flex flex-col items-center justify-center space-y-1">
                            <div class="w-8 h-8 rounded-full bg-tg-purple/10 text-tg-purple flex items-center justify-center text-sm group-hover:scale-110 transition">
                                🔄
                            </div>
                            <div class="text-xs text-slate-700 font-medium">
                                <span class="text-tg-purple font-bold">Choose Back File</span>
                            </div>
                            <p class="text-[10px] text-slate-400">Showing official QR code and residential address</p>
                        </div>
                        <div id="idBackStatus" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-center space-x-1">
                            <span>✓ Selected:</span>
                            <span id="idBackFileName" class="font-mono text-[11px]"></span>
                        </div>
                    </div>
                    @if ($kyc?->identity_document_back_path)
                        <p class="text-[11px] text-slate-500 mt-1">
                            Uploaded: <span class="font-mono text-tg-purple font-semibold">Aadhaar Back Document</span>
                        </p>
                    @endif
                    @error('identity_document_back')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Address Proof Conditionality Checkbox / Toggle -->
            <div class="pt-3 border-t border-slate-100 space-y-3">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                    <label class="flex items-start space-x-3 cursor-pointer">
                        <input type="checkbox"
                               name="id_contains_current_address"
                               id="idAddressToggle"
                               value="1"
                               {{ old('id_contains_current_address', $kyc?->id_contains_current_address ?? true) ? 'checked' : '' }}
                               class="mt-0.5 rounded text-tg-purple focus:ring-tg-purple border-slate-300 w-4 h-4">
                        <div class="text-xs">
                            <span class="font-bold text-slate-800 block">Primary ID document reflects my current permanent address</span>
                            <span class="text-slate-500 block text-[11px] mt-0.5">
                                Uncheck this if your current address is different from the address printed on your primary identity card.
                            </span>
                        </div>
                    </label>
                </div>

                <!-- Address Proof Upload Box -->
                <div>
                    <label id="addressProofLabel" class="block text-xs font-bold text-slate-700 mb-1">
                        Separate Address Proof Document <span id="addressProofRequiredIndicator" class="text-slate-400 font-normal text-[11px]">(Optional)</span>
                    </label>
                    <div class="file-drop-zone relative rounded-xl border-2 border-dashed border-slate-300 hover:border-tg-purple bg-slate-50/50 p-4 transition text-center group cursor-pointer"
                         onclick="document.getElementById('addressDocumentInput').click()">
                        <input type="file"
                               id="addressDocumentInput"
                               name="address_document"
                               accept=".pdf,.jpg,.jpeg,.png"
                               onchange="handleFileSelect(this, 'addressStatus', 'addressFileName')"
                               class="hidden">
                        <div class="flex flex-col items-center justify-center space-y-1">
                            <div class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center text-sm group-hover:scale-110 transition">
                                🏠
                            </div>
                            <div class="text-xs text-slate-700 font-medium">
                                <span class="text-tg-purple font-bold">Choose Address Proof File</span>
                            </div>
                            <p class="text-[10px] text-slate-400">Utility bill, Bank statement, or Rent agreement (dated within last 3 months)</p>
                        </div>
                        <div id="addressStatus" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-center space-x-1">
                            <span>✓ Selected:</span>
                            <span id="addressFileName" class="font-mono text-[11px]"></span>
                        </div>
                    </div>
                    @if ($kyc?->address_document_path)
                        <p class="text-[11px] text-slate-500 mt-1">
                            Uploaded: <span class="font-mono text-tg-purple font-semibold">Separate Address Document</span>
                        </p>
                    @endif
                    @error('address_document')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 3. BANK ACCOUNT INFORMATION -->
        <!-- ========================================== -->
        <div class="bg-white rounded-2xl border border-slate-300 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <div class="flex items-center space-x-2">
                    <span class="w-6 h-6 rounded-lg bg-tg-blue/10 text-tg-blue flex items-center justify-center font-bold text-xs">3</span>
                    <h2 class="text-sm font-bold text-tg-dark">Receiving Bank &amp; Payout Destination</h2>
                </div>
                <span class="text-[11px] text-tg-blue font-semibold hidden sm:inline">Commission Payout Channel</span>
            </div>

            <!-- Name Mismatch Warning Banner -->
            <div id="nameMismatchAlert" class="rounded-xl bg-amber-50 border border-amber-300 p-3.5 text-xs text-amber-950 hidden">
                <div class="flex items-start space-x-2.5">
                    <span class="text-base flex-shrink-0">⚠️</span>
                    <div>
                        <strong class="font-bold block text-amber-950">Bank Account Holder Name Alert:</strong>
                        <p class="text-[11px] text-amber-900 mt-0.5">
                            The Bank Account Holder Name entered does not closely match your Legal / Business Name.
                            Our compliance team will manually scrutinize your bank passbook/cheque to verify authorized account ownership before approving payouts.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Account Holder Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Bank Account Holder Name <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text"
                           id="bankAccountHolderInput"
                           name="bank_account_holder_name"
                           value="{{ old('bank_account_holder_name', $kyc?->bank_account_holder_name ?? $partner->bank_account_name) }}"
                           required
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_holder_name')) ? 'readonly' : '' }}
                           class="w-full text-xs rounded-xl border @error('bank_account_holder_name') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_holder_name')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="Full name as printed in Bank Passbook / Cheque">
                    <p class="text-[10px] text-slate-400 mt-1">Must match your entity or signatory records.</p>
                    @error('bank_account_holder_name')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Bank Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Bank Name <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text"
                           name="bank_name"
                           value="{{ old('bank_name', $kyc?->bank_name ?? $partner->bank_name) }}"
                           required
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_name')) ? 'readonly' : '' }}
                           class="w-full text-xs rounded-xl border @error('bank_name') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_name')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="e.g. HDFC Bank, ICICI Bank, State Bank of India">
                    @error('bank_name')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Account Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Bank Account Number <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="password"
                           id="bankAccountNumberInput"
                           name="bank_account_number"
                           required
                           autocomplete="off"
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_number')) ? 'readonly' : '' }}
                           class="w-full text-xs font-mono rounded-xl border @error('bank_account_number') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_number')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="9 to 24 digit bank account number">
                    <p class="text-[10px] text-slate-400 mt-1">Masked after submission for banking security.</p>
                    @error('bank_account_number')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Account Number -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Confirm Bank Account Number <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text"
                           id="bankAccountConfirmInput"
                           name="bank_account_number_confirmation"
                           required
                           autocomplete="off"
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_number')) ? 'readonly' : '' }}
                           class="w-full text-xs font-mono rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_account_number')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="Re-enter bank account number">
                    <div id="accountMatchFeedback" class="text-[10px] mt-1 font-semibold hidden"></div>
                </div>

                <!-- IFSC Code -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Bank IFSC Code <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <input type="text"
                           name="bank_ifsc_code"
                           id="bankIfscInput"
                           value="{{ old('bank_ifsc_code', $kyc?->bank_ifsc_code ?? $partner->bank_routing_code) }}"
                           maxlength="11"
                           required
                           style="text-transform: uppercase;"
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_ifsc_code')) ? 'readonly' : '' }}
                           class="w-full text-xs font-mono font-bold tracking-wider uppercase rounded-xl border @error('bank_ifsc_code') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('bank_ifsc_code')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="e.g. HDFC0001234">
                    <p class="text-[10px] text-slate-400 mt-1">11-character Indian bank branch code (4 letters, 0, 6 characters).</p>
                    @error('bank_ifsc_code')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Preferred Payout Method -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Default Payout Method <span class="text-rose-500 font-bold">*</span>
                    </label>
                    <select name="preferred_payout_method"
                            {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('preferred_payout_method')) ? 'disabled' : '' }}
                            class="w-full text-xs rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-slate-800 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('preferred_payout_method')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}">
                        <option value="bank_transfer" {{ old('preferred_payout_method', $kyc?->preferred_payout_method ?? 'bank_transfer') === 'bank_transfer' ? 'selected' : '' }}>
                            Direct Bank Transfer (NEFT / RTGS / IMPS)
                        </option>
                        <option value="upi" {{ old('preferred_payout_method', $kyc?->preferred_payout_method) === 'upi' ? 'selected' : '' }}>
                            UPI Instant Payout
                        </option>
                    </select>
                </div>

                <!-- UPI ID (Optional) -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        UPI ID / Virtual Payment Address (VPA) <span class="text-slate-400 font-normal text-[11px]">(Optional for UPI payouts)</span>
                    </label>
                    <input type="text"
                           name="upi_id"
                           value="{{ old('upi_id', $kyc?->upi_id ?? $partner->payout_details) }}"
                           {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('upi_id')) ? 'readonly' : '' }}
                           class="w-full text-xs font-mono rounded-xl border @error('upi_id') border-rose-500 text-rose-900 @else border-slate-300 @enderror bg-white px-3.5 py-2.5 text-slate-800 placeholder-slate-400 focus:border-[#6B3D95] focus:ring-1 focus:ring-[#6B3D95] focus:outline-none transition shadow-sm {{ ($isCorrection && $kyc && ! $kyc->isFieldEditable('upi_id')) ? 'bg-slate-100 text-slate-500 cursor-not-allowed border-slate-200' : '' }}"
                           placeholder="yourname@okhdfcbank">
                    @error('upi_id')
                        <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Cancelled Cheque / Bank Passbook Document Upload -->
            <div class="pt-2">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Cancelled Cheque or Bank Passbook Proof <span class="text-rose-500 font-bold">*</span>
                </label>
                <div class="file-drop-zone relative rounded-xl border-2 border-dashed border-slate-300 hover:border-tg-purple bg-slate-50/50 p-4 transition text-center group cursor-pointer"
                     onclick="document.getElementById('bankProofInput').click()">
                    <input type="file"
                           id="bankProofInput"
                           name="bank_proof_document"
                           accept=".pdf,.jpg,.jpeg,.png"
                           {{ $isCorrection ? '' : 'required' }}
                           onchange="handleFileSelect(this, 'bankProofStatus', 'bankProofFileName')"
                           class="hidden">
                    <div class="flex flex-col items-center justify-center space-y-1">
                        <div class="w-8 h-8 rounded-full bg-tg-blue/10 text-tg-blue flex items-center justify-center text-sm group-hover:scale-110 transition">
                            🏦
                        </div>
                        <div class="text-xs text-slate-700 font-medium">
                            <span class="text-tg-blue font-bold">Choose Bank Document</span> or drag and drop
                        </div>
                        <p class="text-[10px] text-slate-400">Proof must visibly show Account Number, Holder Name &amp; IFSC code (PDF/JPG/PNG &bull; Max 5MB)</p>
                    </div>
                    <div id="bankProofStatus" class="mt-2 text-xs text-emerald-700 font-semibold hidden flex items-center justify-center space-x-1">
                        <span>✓ Selected:</span>
                        <span id="bankProofFileName" class="font-mono text-[11px]"></span>
                    </div>
                </div>
                @if ($kyc?->bank_proof_document_path)
                    <p class="text-[11px] text-slate-500 mt-1">
                        Uploaded: <span class="font-mono text-tg-blue font-semibold">Bank Proof Document (v{{ $kyc->current_version }})</span>
                    </p>
                @endif
                @error('bank_proof_document')
                    <p class="text-[11px] text-rose-500 mt-1 font-medium">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- ========================================== -->
        <!-- 4. MANDATORY DECLARATION & CONSENT -->
        <!-- ========================================== -->
        <div class="bg-white rounded-2xl border border-slate-300 p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex items-center space-x-2 pb-3 border-b border-slate-100">
                <span class="w-6 h-6 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">4</span>
                <h2 class="text-sm font-bold text-tg-dark">Legal Declaration &amp; Consent</h2>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                <label class="flex items-start space-x-3 cursor-pointer">
                    <input type="checkbox"
                           name="declaration_accepted"
                           id="declarationCheckbox"
                           value="1"
                           required
                           class="mt-1 rounded text-tg-purple focus:ring-tg-purple border-slate-300 w-4 h-4 flex-shrink-0">
                    <span class="text-xs text-slate-700 leading-relaxed font-medium">
                        I hereby declare that all information and documents submitted by me are true, accurate and belong to me or my registered business. I authorize Tech Googly to verify the submitted information and documents for partner identity and commission payout purposes.
                    </span>
                </label>
                @error('declaration_accepted')
                    <p class="text-[11px] text-rose-500 mt-2 font-medium ml-7">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-[11px] text-slate-500 pt-1">
                <span>Date of Submission: <strong class="text-slate-700">{{ now()->format('F d, Y') }}</strong></span>
                <span class="text-amber-800 font-medium">Notice: Once submitted, fields are locked and cannot be edited without Admin correction request.</span>
            </div>
        </div>

        <!-- Submit & Actions Box -->
        <div class="bg-white rounded-2xl border border-slate-300 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-xs text-slate-500">
                Ensure all documents are clearly legible. Unclear or invalid submissions will be rejected.
            </div>

            <div class="flex items-center space-x-3 self-stretch sm:self-auto justify-end">
                <a href="{{ route('partner.kyc.show') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>

                <button type="submit"
                        id="submitKycBtn"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition flex items-center space-x-1.5">
                    <span>{{ $isCorrection ? 'Lock & Resubmit Corrections' : 'Lock & Submit KYC Application' }}</span>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Dynamic Client-Side Interactions Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const partnerTypeSelect = document.getElementById('partnerTypeSelect');
    const legalNameLabel = document.getElementById('legalNameLabel');
    const dobLabel = document.getElementById('dobLabel');
    const legalNameInput = document.getElementById('legalNameInput');
    const businessFieldsContainer = document.getElementById('businessFieldsContainer');
    const signatoryNameInput = document.getElementById('signatoryNameInput');

    const idProofTypeSelect = document.getElementById('idProofTypeSelect');
    const identityFrontLabel = document.getElementById('identityFrontLabel');
    const aadhaarBackBox = document.getElementById('aadhaarBackBox');
    const identityBackInput = document.getElementById('identityBackInput');

    const idAddressToggle = document.getElementById('idAddressToggle');
    const addressProofLabel = document.getElementById('addressProofLabel');
    const addressProofRequiredIndicator = document.getElementById('addressProofRequiredIndicator');
    const addressDocumentInput = document.getElementById('addressDocumentInput');

    const bankAccountHolderInput = document.getElementById('bankAccountHolderInput');
    const nameMismatchAlert = document.getElementById('nameMismatchAlert');

    const bankAccountNumberInput = document.getElementById('bankAccountNumberInput');
    const bankAccountConfirmInput = document.getElementById('bankAccountConfirmInput');
    const accountMatchFeedback = document.getElementById('accountMatchFeedback');

    const panNumberInput = document.getElementById('panNumberInput');
    const bankIfscInput = document.getElementById('bankIfscInput');

    // 1. Entity Type Dynamic Switching
    function updateEntityType() {
        const val = partnerTypeSelect.value;
        const isIndividual = (val === 'individual' || val === 'individual_sole_proprietor');

        if (isIndividual) {
            legalNameLabel.innerHTML = 'Full Legal Name (as per PAN Card) <span class="text-rose-500 font-bold">*</span>';
            legalNameInput.placeholder = 'Full legal personal name as printed on PAN card';
            dobLabel.innerHTML = 'Date of Birth <span class="text-rose-500 font-bold">*</span>';
            businessFieldsContainer.classList.add('hidden');
            if (signatoryNameInput) signatoryNameInput.required = false;
        } else {
            legalNameLabel.innerHTML = 'Legal Business / Company Name <span class="text-rose-500 font-bold">*</span>';
            legalNameInput.placeholder = 'Full registered firm / company name';
            dobLabel.innerHTML = 'Date of Incorporation / Registration <span class="text-rose-500 font-bold">*</span>';
            businessFieldsContainer.classList.remove('hidden');
            if (signatoryNameInput) signatoryNameInput.required = true;
        }
        checkNameMismatch();
    }

    if (partnerTypeSelect) {
        partnerTypeSelect.addEventListener('change', updateEntityType);
        updateEntityType();
    }

    // 2. Primary Identity Document Type (Aadhaar front+back vs other)
    function updateIdentityProofType() {
        const val = idProofTypeSelect.value;
        if (val === 'aadhaar') {
            identityFrontLabel.innerHTML = 'Aadhaar Card (Front Side) <span class="text-rose-500 font-bold">*</span>';
            aadhaarBackBox.classList.remove('hidden');
            @if (! $isCorrection)
                if (identityBackInput) identityBackInput.required = true;
            @endif
        } else {
            const typeNames = {
                passport: 'Passport (Photo / Identity Page)',
                voter_id: 'Voter ID Card (Front View)',
                driving_license: 'Driving Licence (Front View)'
            };
            identityFrontLabel.innerHTML = (typeNames[val] || 'Primary Identity Proof') + ' <span class="text-rose-500 font-bold">*</span>';
            aadhaarBackBox.classList.add('hidden');
            if (identityBackInput) identityBackInput.required = false;
        }
    }

    if (idProofTypeSelect) {
        idProofTypeSelect.addEventListener('change', updateIdentityProofType);
        updateIdentityProofType();
    }

    // 3. Address Proof Conditionality
    function updateAddressCondition() {
        const showsAddress = idAddressToggle.checked;
        if (showsAddress) {
            addressProofRequiredIndicator.className = 'text-slate-400 font-normal text-[11px]';
            addressProofRequiredIndicator.textContent = '(Optional)';
            if (addressDocumentInput) addressDocumentInput.required = false;
        } else {
            addressProofRequiredIndicator.className = 'text-rose-500 font-bold text-xs';
            addressProofRequiredIndicator.textContent = '* (Mandatory - Current address differs from Primary ID)';
            @if (! $isCorrection)
                if (addressDocumentInput) addressDocumentInput.required = true;
            @endif
        }
    }

    if (idAddressToggle) {
        idAddressToggle.addEventListener('change', updateAddressCondition);
        updateAddressCondition();
    }

    // 4. Bank Account Holder Name Correlation Check
    function cleanString(str) {
        return (str || '').toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    function checkNameMismatch() {
        const holder = cleanString(bankAccountHolderInput.value);
        const legal = cleanString(legalNameInput.value);
        const signatory = signatoryNameInput ? cleanString(signatoryNameInput.value) : '';

        if (! holder || (! legal && ! signatory)) {
            nameMismatchAlert.classList.add('hidden');
            return;
        }

        const matchLegal = holder === legal || holder.includes(legal) || legal.includes(holder);
        const matchSignatory = signatory && (holder === signatory || holder.includes(signatory) || signatory.includes(holder));

        if (! matchLegal && ! matchSignatory) {
            nameMismatchAlert.classList.remove('hidden');
        } else {
            nameMismatchAlert.classList.add('hidden');
        }
    }

    if (bankAccountHolderInput) {
        bankAccountHolderInput.addEventListener('input', checkNameMismatch);
    }
    if (legalNameInput) {
        legalNameInput.addEventListener('input', checkNameMismatch);
    }
    if (signatoryNameInput) {
        signatoryNameInput.addEventListener('input', checkNameMismatch);
    }

    // 5. Bank Account Number Confirmation Real-time Match
    function checkAccountMatch() {
        const acc = bankAccountNumberInput.value;
        const confirm = bankAccountConfirmInput.value;

        if (! confirm) {
            accountMatchFeedback.classList.add('hidden');
            return;
        }

        accountMatchFeedback.classList.remove('hidden');
        if (acc === confirm) {
            accountMatchFeedback.className = 'text-[10px] mt-1 font-semibold text-emerald-600 flex items-center';
            accountMatchFeedback.innerHTML = '<span>✓ Account numbers match perfectly</span>';
        } else {
            accountMatchFeedback.className = 'text-[10px] mt-1 font-semibold text-rose-500 flex items-center';
            accountMatchFeedback.innerHTML = '<span>✕ Account numbers do not match</span>';
        }
    }

    if (bankAccountNumberInput && bankAccountConfirmInput) {
        bankAccountNumberInput.addEventListener('input', checkAccountMatch);
        bankAccountConfirmInput.addEventListener('input', checkAccountMatch);
    }

    // 6. Force uppercase formatting on PAN and IFSC
    if (panNumberInput) {
        panNumberInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }

    if (bankIfscInput) {
        bankIfscInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    }
});

// Helper for custom file upload zones
function handleFileSelect(input, statusId, nameId) {
    const statusEl = document.getElementById(statusId);
    const nameEl = document.getElementById(nameId);

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

        if (file.size > 5 * 1024 * 1024) {
            alert('File exceeds maximum allowed limit of 5MB: ' + file.name + ' (' + fileSizeMB + ' MB)');
            input.value = '';
            statusEl.classList.add('hidden');
            return;
        }

        nameEl.textContent = file.name + ' (' + fileSizeMB + ' MB)';
        statusEl.classList.remove('hidden');
    }
}
</script>
@endsection
