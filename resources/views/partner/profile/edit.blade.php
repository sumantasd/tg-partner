@extends('layouts.app')

@section('title', 'Partner Profile & Account Settings')

@section('content')
<div class="max-w-5xl mx-auto space-y-6 w-full min-w-0">

    <!-- Flash Notifications -->
    @if (session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs shadow-sm">
            <div class="font-bold flex items-center space-x-1.5 mb-1">
                <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <span>Please correct the errors below:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 text-[11px] text-rose-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile Header Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
            <div class="flex items-center space-x-4">
                <!-- Avatar with Upload Overlay -->
                <div class="relative group">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-tg-purple to-[#49226a] text-white font-extrabold text-2xl flex items-center justify-center shadow-md overflow-hidden">
                        @if ($user->profile_photo_url)
                            <img src="{{ $user->profile_photo_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover">
                        @else
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        @endif
                    </div>
                    <!-- Photo upload trigger button -->
                    <button type="button" onclick="document.getElementById('photo-input').click()"
                            class="absolute -bottom-1 -right-1 p-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-slate-600 hover:text-tg-purple hover:bg-slate-50 transition"
                            title="Update Profile Photo">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <!-- Hidden photo form -->
                    <form id="photo-form" action="{{ route('partner.profile.photo') }}" method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="document.getElementById('photo-form').submit()">
                    </form>
                </div>

                <div>
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <h1 class="text-xl sm:text-2xl font-bold text-tg-dark tracking-tight">{{ $user->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border font-mono">
                            {{ $partner->partner_code }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $partner->company_name ?: 'Individual Referral Partner' }} &bull; Member since {{ $user->created_at->format('M Y') }}
                    </div>

                    <!-- Verification Badges -->
                    <div class="flex items-center space-x-2 mt-2 flex-wrap gap-y-1">
                        @if ($partner->isFullyKycVerified())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                KYC &amp; Bank Verified
                            </span>
                        @elseif ($partner->isKycApproved())
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                KYC Approved
                            </span>
                        @else
                            <a href="{{ route('partner.kyc.show') }}" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200 hover:underline">
                                KYC Pending &rarr;
                            </a>
                        @endif

                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                            Commission Rate: {{ $partner->default_commission_rate }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="flex items-center space-x-6 border-t sm:border-t-0 sm:border-l border-slate-100 pt-4 sm:pt-0 sm:pl-6 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Available</span>
                    <span class="text-base font-bold text-emerald-600 font-mono">
                        ₹{{ number_format($balanceSummary['available_balance'] ?? 0, 2) }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Total Disbursed</span>
                    <span class="text-base font-bold text-tg-purple font-mono">
                        ₹{{ number_format($balanceSummary['total_paid'] ?? 0, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Profile Form & Password Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Personal & Business Info -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Profile Info Form -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-5">
                    <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Personal &amp; Business Profile</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Update your contact identity and commercial organization information.</p>
                </div>

                <form action="{{ route('partner.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Full Legal Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Company / Entity Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="company_name" value="{{ old('company_name', $partner->company_name) }}" required
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Business Website</label>
                        <input type="text" name="website" value="{{ old('website', $partner->website) }}" placeholder="https://example.com"
                               class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Registered Commercial Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $partner->address_line1) }}" placeholder="Building, Street, Suite"
                               class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">City</label>
                            <input type="text" name="city" value="{{ old('city', $partner->city) }}"
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">State</label>
                            <input type="text" name="state" value="{{ old('state', $partner->state) }}"
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">PIN / Postal Code</label>
                            <input type="text" name="postal_code" value="{{ old('postal_code', $partner->postal_code) }}"
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm btn-tactile">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Verified & Protected Contact Information Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Verified Contact Credentials</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Primary authentication identifiers. Protected against unauthorized modification.</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                        Protected
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Registered Email</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                        </div>
                        <div class="font-mono text-xs font-bold text-tg-dark">{{ $user->email }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Used for login, OTP, and security notices</div>
                    </div>

                    <!-- Phone -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Registered Phone</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                        </div>
                        <div class="font-mono text-xs font-bold text-tg-dark">{{ $partner->phone ?: $user->phone }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Used for KYC verification and disbursements</div>
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-800 text-[11px] flex items-start space-x-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>To update your registered email or mobile phone, contact Tech Googly compliance desk through the support channel to verify identity ownership.</span>
                </div>
            </div>
        </div>

        <!-- Column 3: Password & Security Settings -->
        <div class="space-y-6">

            <!-- Change Password Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Change Password</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Ensure your account is protected with a strong password.</p>
                </div>

                <form action="{{ route('partner.profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Current Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password" required
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
                            <button type="button" onclick="togglePasswordVisibility('current_password', this)"
                                    aria-label="Toggle password visibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">New Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
                            <button type="button" onclick="togglePasswordVisibility('password', this)"
                                    aria-label="Toggle password visibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </button>
                        </div>
                        <span class="text-[10px] text-slate-400 mt-1 block">Minimum 8 characters with letters and numbers</span>
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Confirm New Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" required
                                   class="w-full text-xs rounded-xl border-slate-200 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
                            <button type="button" onclick="togglePasswordVisibility('password_confirmation', this)"
                                    aria-label="Toggle password visibility"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full px-4 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm btn-tactile">
                            Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Quick Links Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm space-y-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Connected Modules</div>
                <a href="{{ route('partner.kyc.show') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-slate-700">
                    <span>KYC &amp; Bank Documents</span>
                    <span class="text-tg-purple">&rarr;</span>
                </a>
                <a href="{{ route('partner.payout-settings.edit') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-slate-700">
                    <span>Bank &amp; Payout Settings</span>
                    <span class="text-tg-purple">&rarr;</span>
                </a>
                <a href="{{ route('partner.commissions.index') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-slate-700">
                    <span>Commission Ledger</span>
                    <span class="text-tg-purple">&rarr;</span>
                </a>
            </div>

        </div>
    </div>
</div>
@endsection
