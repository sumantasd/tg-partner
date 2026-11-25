@extends('layouts.app')

@section('title', 'Commission Rate Configuration — Admin Portal')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.commissions.index') }}" class="hover:text-tg-purple">Commissions</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Rate Configuration</span>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-tg-purple-light flex items-center justify-center text-tg-purple">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-tg-dark">Default Commission Rate</h1>
                <p class="text-xs text-slate-500">Global percentage rate applied to verified customer payments.</p>
            </div>
        </div>

        <!-- Current Rate Highlight -->
        <div class="mt-6 p-6 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
            <div>
                <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Active System Default Rate</span>
                <div class="text-3xl font-bold text-tg-purple mt-1">{{ number_format($currentRate, 2) }}%</div>
                @if($setting && $setting->updatedBy)
                    <p class="text-[11px] text-slate-400 mt-1">
                        Last adjusted by <strong>{{ $setting->updatedBy->name }}</strong> on {{ $setting->updated_at->format('M d, Y h:i A') }}
                    </p>
                @endif
            </div>

            <div class="text-right text-xs text-slate-500 max-w-[200px]">
                Applies only to <strong>subsequently verified</strong> payments. Historical commissions retain their locked rate snapshot.
            </div>
        </div>

        <!-- Update Form -->
        <form action="{{ route('admin.settings.commission.update') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">New Default Rate (0.00% to 100.00%) *</label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" max="100" name="default_commission_rate" required
                           value="{{ old('default_commission_rate', $currentRate) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple pr-10">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-slate-400 font-bold">%</div>
                </div>
                @error('default_commission_rate') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                <span class="font-bold">Financial Safeguard:</span> Changing this value will immediately take effect for future customer payment verifications. Existing commissions and payments will never be recalculated.
            </div>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Save New Commission Rate &rarr;
                </button>
            </div>
        </form>
    </div>

    <!-- Company Letterhead & Agreement Identity Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3">
            <div class="w-10 h-10 rounded-2xl bg-purple-100 flex items-center justify-center text-tg-purple">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <div>
                <h2 class="text-xl font-bold text-tg-dark">Company Letterhead &amp; Agreement Identity</h2>
                <p class="text-xs text-slate-500">Official corporate details affixed to generated partner agreements and legal PDF letterheads.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.letterhead.update') }}" method="POST" class="mt-6 space-y-4">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Company Legal Name *</label>
                    <input type="text" name="company_name" required
                           value="{{ old('company_name', $companySettings['company_name'] ?? 'Tech Googly') }}"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('company_name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Brand Tagline *</label>
                    <input type="text" name="company_tagline" required
                           value="{{ old('company_tagline', $companySettings['company_tagline'] ?? 'Innovation For You') }}"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('company_tagline') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Official Website *</label>
                    <input type="text" name="company_website" required
                           value="{{ old('company_website', $companySettings['company_website'] ?? 'techgoogly.com') }}"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('company_website') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Support / Contact Email (Optional)</label>
                    <input type="email" name="company_email" placeholder="support@techgoogly.com"
                           value="{{ old('company_email', $companySettings['company_email'] ?? '') }}"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('company_email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Contact Phone (Optional)</label>
                    <input type="text" name="company_phone" placeholder="+91..."
                           value="{{ old('company_phone', $companySettings['company_phone'] ?? '') }}"
                           class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('company_phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Corporate Address (Optional)</label>
                <textarea name="company_address" rows="2" placeholder="Registered office address (leave blank if unconfigured)..."
                          class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('company_address', $companySettings['company_address'] ?? '') }}</textarea>
                @error('company_address') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <p class="text-[11px] text-slate-400">
                These details are displayed on the header letterhead of generated Partner Agreements. Any unconfigured optional fields are omitted automatically.
            </p>

            <div class="pt-2 flex justify-end">
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Save Letterhead Details &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
