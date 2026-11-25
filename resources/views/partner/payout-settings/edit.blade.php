@extends('layouts.app')

@section('title', 'Payout & Bank Settings — Partner Portal')

@section('content')
<div class="max-w-3xl mx-auto space-y-6 w-full min-w-0">
    <!-- Header -->
    <div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('partner.withdrawals.index') }}" class="text-xs font-bold text-tg-purple hover:underline">&larr; Back to Withdrawals</a>
        </div>
        <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">Bank &amp; Payout Settings</h1>
        <p class="text-xs text-slate-500 mt-0.5">Manage your receiving bank account and UPI details. All sensitive details are securely encrypted and protected.</p>
    </div>

    <!-- Security & Masking Notice -->
    <div class="rounded-2xl bg-tg-purple-light border border-tg-purple-border p-4 text-xs text-tg-purple flex items-start space-x-3 shadow-sm">
        <svg class="w-5 h-5 flex-shrink-0 text-tg-purple mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
        <div>
            <h4 class="font-bold">Zero-Exposure Security Guarantee</h4>
            <p class="text-[11px] text-tg-purple/80 mt-0.5">
                Your bank account details are securely encrypted and protected. Account numbers are masked across the platform, showing only the last 4 digits. Team members and unauthorized parties cannot access your confidential financial information.
            </p>
        </div>
    </div>

    <!-- Current Configured Status Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <h2 class="text-xs uppercase font-bold tracking-wider text-slate-400 mb-4">Currently Saved Destination</h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
            <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-100">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">Bank Account Destination</span>
                <div class="font-bold text-tg-dark text-sm mt-1">
                    {{ $partner->bank_name ?: 'Not Configured' }}
                </div>
                <div class="text-[11px] text-slate-600 font-mono mt-0.5">
                    {{ $partner->masked_account_number }}
                </div>
                <div class="text-[11px] text-slate-500 mt-1">
                    Holder: <span class="font-semibold text-tg-dark">{{ $partner->bank_account_name ?: 'N/A' }}</span>
                </div>
                <div class="text-[11px] text-slate-500 font-mono">
                    IFSC: <span class="font-bold text-tg-purple">{{ $partner->ifsc_code ?: 'N/A' }}</span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-100">
                <span class="text-[10px] uppercase font-bold tracking-wider text-slate-400 block">UPI Instant Destination</span>
                <div class="font-mono font-bold text-tg-purple text-sm mt-1">
                    {{ $partner->upi_id ?: 'Not Configured' }}
                </div>
                <div class="text-[11px] text-slate-500 mt-2">
                    Default Preference:
                    <span class="font-bold capitalize text-tg-dark">{{ str_replace('_', ' ', $partner->payout_method) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Form -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h2 class="text-sm font-bold text-tg-dark mb-4">Update Payout Destination</h2>

        <form method="POST" action="{{ route('partner.payout-settings.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Preferred Method -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Preferred Payout Method <span class="text-tg-pink">*</span></label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-center space-x-3 p-3.5 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                        <input type="radio" name="payout_method" value="bank_transfer"
                               {{ old('payout_method', $partner->payout_method) === 'bank_transfer' ? 'checked' : '' }}
                               class="text-tg-purple focus:ring-tg-purple">
                        <div>
                            <span class="text-xs font-bold text-tg-dark block">Direct Bank Transfer</span>
                            <span class="text-[10px] text-slate-400">NEFT / RTGS / IMPS to bank account</span>
                        </div>
                    </label>

                    <label class="flex items-center space-x-3 p-3.5 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                        <input type="radio" name="payout_method" value="upi"
                               {{ old('payout_method', $partner->payout_method) === 'upi' ? 'checked' : '' }}
                               class="text-tg-purple focus:ring-tg-purple">
                        <div>
                            <span class="text-xs font-bold text-tg-dark block">UPI Instant Payout</span>
                            <span class="text-[10px] text-slate-400">Disbursed via VPA / UPI ID</span>
                        </div>
                    </label>
                </div>
                @error('payout_method')
                    <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Bank Details Section -->
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">Bank Transfer Details</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Account Holder Name</label>
                        <input type="text" name="bank_account_name"
                               value="{{ old('bank_account_name', $partner->bank_account_name) }}"
                               placeholder="As per bank passbook / statement"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                        @error('bank_account_name')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name"
                               value="{{ old('bank_name', $partner->bank_name) }}"
                               placeholder="e.g. HDFC Bank, ICICI Bank, SBI"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                        @error('bank_name')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">
                            Bank Account Number
                            @if ($partner->bank_account_last4)
                                <span class="text-[10px] text-slate-400 font-normal">(Leave blank to keep saved account)</span>
                            @endif
                        </label>
                        <input type="text" inputmode="numeric" name="bank_account_number" autocomplete="off"
                               placeholder="{{ $partner->bank_account_last4 ? '•••• •••• •••• ' . $partner->bank_account_last4 : 'Enter 9-18 digit account number' }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-mono tracking-wider focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                        @error('bank_account_number')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Confirm Account Number</label>
                        <input type="text" inputmode="numeric" name="bank_account_number_confirmation" autocomplete="off"
                               placeholder="Re-enter bank account number"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-mono tracking-wider focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                        @error('bank_account_number_confirmation')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Bank IFSC Code</label>
                    <input type="text" name="bank_routing_code"
                           value="{{ old('bank_routing_code', $partner->ifsc_code) }}"
                           placeholder="11 characters (e.g. HDFC0001234, SBIN0001234)"
                           maxlength="11"
                           class="w-full sm:w-64 px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-mono uppercase focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                    @error('bank_routing_code')
                        <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- UPI Details Section -->
            <div class="pt-4 border-t border-slate-100 space-y-4">
                <h3 class="text-xs uppercase font-bold tracking-wider text-slate-400">UPI Payout Details</h3>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Virtual Payment Address (UPI ID)</label>
                    <input type="text" name="upi_id"
                           value="{{ old('upi_id', $partner->upi_id) }}"
                           placeholder="e.g. partnername@okaxis, business@okhdfcbank"
                           class="w-full sm:w-80 px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-slate-900 placeholder:text-slate-400 text-xs font-mono focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                    @error('upi_id')
                        <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Submit -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('partner.withdrawals.index') }}" class="text-xs font-semibold text-slate-500 hover:text-tg-dark">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                    Save Payout Details
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
