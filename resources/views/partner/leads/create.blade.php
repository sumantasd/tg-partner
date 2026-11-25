@extends('layouts.app')

@section('title', 'Submit New Client Referral')

@section('content')
<div class="max-w-3xl mx-auto">
    <!-- Header -->
    <div class="mb-6">
        <a href="{{ route('partner.leads.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to My Leads
        </a>
        <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Submit Client Referral</h1>
        <p class="text-xs text-slate-500 mt-1">
            Provide client requirements and contact details. Tech Googly consultants will contact the client and keep you updated throughout the deal lifecycle.
        </p>
    </div>

    @if(session('duplicate_rejected') || $errors->has('customer_phone'))
        <div class="mb-6 rounded-2xl bg-amber-50 border border-amber-200 p-5 shadow-sm">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 mt-0.5">
                    <svg class="w-5 h-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-amber-900">Lead Protection Notice</h4>
                    <p class="text-xs text-amber-800 mt-1">
                        {{ $errors->first('customer_phone') ?: 'This customer lead already exists in the Tech Googly Partner Program and cannot be submitted again at this time.' }}
                    </p>
                    <div class="mt-3 flex items-center space-x-3">
                        <a href="{{ route('partner.disputes.create', ['phone' => session('claimed_phone') ?? old('customer_phone'), 'email' => session('claimed_email') ?? old('customer_email')]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-amber-800 text-white hover:bg-amber-900 transition">
                            Raise Ownership Dispute &rarr;
                        </a>
                        <span class="text-[11px] text-amber-700">Believe this referral is rightfully yours? Provide authorization proof for Admin verification.</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Submission Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200/90 overflow-hidden">
        <form action="{{ route('partner.leads.store') }}" method="POST" class="p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Section 1: Customer Contact Information -->
            <div>
                <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center">
                    <span class="w-6 h-6 rounded-lg bg-tg-purple text-white inline-flex items-center justify-center text-xs font-bold mr-2.5 shadow-sm">1</span>
                    Customer & Organization Details
                </h3>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Customer / Decision Maker Name <span class="text-tg-pink">*</span>
                        </label>
                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name') }}" required
                               placeholder="e.g. Rajesh Sharma"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    </div>

                    <div>
                        <label for="customer_company" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Company / Organization Name
                        </label>
                        <input type="text" name="customer_company" id="customer_company" value="{{ old('customer_company') }}"
                               placeholder="e.g. Apex Logistics Pvt Ltd"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    </div>

                    <div>
                        <label for="customer_phone" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Phone / WhatsApp Number <span class="text-tg-pink">*</span>
                        </label>
                        <input type="tel" name="customer_phone" id="customer_phone" value="{{ old('customer_phone') }}" required
                               placeholder="e.g. 9876543210"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                        <p class="text-[11px] text-slate-400 mt-1">10-digit primary mobile or phone number</p>
                    </div>

                    <div>
                        <label for="customer_email" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Official Email Address
                        </label>
                        <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email') }}"
                               placeholder="e.g. rajesh@apexlogistics.in"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="city" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            City / Location
                        </label>
                        <input type="text" name="city" id="city" value="{{ old('city') }}"
                               placeholder="e.g. Mumbai, Bengaluru, Delhi NCR, London"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    </div>
                </div>
            </div>

            <!-- Section 2: Project Scope & Budget -->
            <div>
                <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center">
                    <span class="w-6 h-6 rounded-lg bg-tg-purple text-white inline-flex items-center justify-center text-xs font-bold mr-2.5 shadow-sm">2</span>
                    Service Scope & Requirements
                </h3>

                <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="service_required" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Requested Service / Category <span class="text-tg-pink">*</span>
                        </label>
                        <select name="service_required" id="service_required" required
                                class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                            <option value="">-- Select Service Category --</option>
                            @foreach ($services as $service)
                                <option value="{{ $service }}" {{ old('service_required') === $service ? 'selected' : '' }}>
                                    {{ $service }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="estimated_value" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Estimated Client Budget (INR)
                        </label>
                        <input type="number" step="0.01" min="0" name="estimated_value" id="estimated_value" value="{{ old('estimated_value') }}"
                               placeholder="e.g. 250000"
                               class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                        <p class="text-[11px] text-slate-400 mt-1">Leave blank if client has not disclosed budget</p>
                    </div>

                    <div class="sm:col-span-2">
                        <label for="project_description" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                            Detailed Requirements & Client Notes <span class="text-tg-pink">*</span>
                        </label>
                        <textarea name="project_description" id="project_description" rows="4" required
                                  placeholder="Describe the client's current pain points, tech requirements, expected deliverables, timeline, or key discussions..."
                                  class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">{{ old('project_description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Referral Policy Notice -->
            <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border p-4 text-xs text-slate-700">
                <div class="font-bold text-tg-purple mb-1">Referral Protection & 90-Day Cooling Policy:</div>
                <ul class="list-disc list-inside space-y-0.5 text-slate-600">
                    <li>Referrals are logged under your Partner Code (<strong>{{ $partner->partner_code }}</strong>).</li>
                    <li>Duplicate detection evaluates normalized phone and email across our canonical database.</li>
                    <li>Upon successful customer engagement, your commission rate of <strong class="text-tg-purple">{{ $partner->default_commission_rate }}%</strong> is credited to your ledger.</li>
                </ul>
            </div>

            <!-- Form Buttons -->
            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-200">
                <a href="{{ route('partner.leads.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-300 text-slate-700 text-xs font-bold hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                    Submit Lead For Review
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
