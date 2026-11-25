@extends('layouts.app')

@section('title', 'Convert Won Lead to Customer — ' . $lead->lead_number)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.leads.index') }}" class="hover:text-tg-purple">Leads</a>
        <span>/</span>
        <a href="{{ route('admin.leads.show', $lead) }}" class="hover:text-tg-purple font-mono font-bold">{{ $lead->lead_number }}</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Convert to Customer</span>
    </div>

    <!-- Header Banner -->
    <div class="rounded-3xl bg-gradient-to-r from-tg-purple to-tg-purple/90 p-6 sm:p-8 text-white shadow-lg">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-sm text-white mb-2">
                    <svg class="w-3.5 h-3.5 mr-1 text-tg-green" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Won Deal Ready for Account Provisioning
                </span>
                <h1 class="text-2xl font-bold">Convert Won Lead to Customer Profile</h1>
                <p class="text-xs text-white/80 mt-1">
                    Review and verify customer details pre-filled from Lead <span class="font-mono font-bold">{{ $lead->lead_number }}</span>. Referral attribution to Partner <strong class="text-white">{{ $lead->partner->company_name }}</strong> will be permanently preserved.
                </p>
            </div>
            <div class="bg-white/10 backdrop-blur-sm p-4 rounded-2xl border border-white/20 text-center min-w-[140px]">
                <div class="text-[10px] uppercase font-bold text-white/70">Referred By</div>
                <div class="font-bold text-sm text-white">{{ $lead->partner->partner_code }}</div>
                <div class="text-[11px] text-white/80 truncate max-w-[140px]">{{ $lead->partner->company_name }}</div>
            </div>
        </div>
    </div>

    <!-- Conversion Form -->
    <form action="{{ route('admin.leads.convert', $lead) }}" method="POST" class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm space-y-6">
        @csrf

        <div class="border-b border-slate-100 pb-4">
            <h2 class="text-base font-bold text-tg-dark">Customer Contact Information</h2>
            <p class="text-xs text-slate-500">Ensure the name, phone number, and company name are accurate for legal invoices and contracts.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer Name *</label>
                <input type="text" name="name" required value="{{ old('name', $lead->customer_name) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Business Name</label>
                <input type="text" name="company_name" value="{{ old('company_name', $lead->customer_company) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('company_name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Primary Phone Number *</label>
                <input type="text" name="phone" required value="{{ old('phone', $lead->customer_phone) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Number</label>
                <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $lead->customer_phone) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('whatsapp_number') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $lead->customer_email) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                <input type="text" name="city" value="{{ old('city', $lead->city) }}"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                @error('city') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <h2 class="text-base font-bold text-tg-dark mb-1">Billing / Office Address</h2>
            <p class="text-xs text-slate-500 mb-4">Optional postal details for invoicing.</p>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div class="sm:col-span-3">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Street Address</label>
                    <input type="text" name="address_line1" value="{{ old('address_line1') }}" placeholder="Office No., Building, Street..."
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">State</label>
                    <input type="text" name="state" value="{{ old('state') }}" placeholder="e.g. West Bengal, Maharashtra"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Postal Code</label>
                    <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="e.g. 700001"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Country</label>
                    <input type="text" name="country" value="{{ old('country', 'India') }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>
            </div>
        </div>

        <div class="border-t border-slate-100 pt-5">
            <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Conversion Notes</label>
            <textarea name="notes" rows="2" placeholder="Any special agreement, SLA or contract references..."
                      class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('notes', "Converted from Won Lead #{$lead->lead_number}. Service required: {$lead->service_required}") }}</textarea>
        </div>

        <div class="pt-4 flex items-center justify-end space-x-3">
            <a href="{{ route('admin.leads.show', $lead) }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Confirm & Create Customer Profile
            </button>
        </div>
    </form>
</div>
@endsection
