@extends('layouts.auth')

@section('title', 'Business Partner Registration')
@section('subtitle', 'Join the Tech Googly Partner Network')
@section('max-width', 'sm:max-w-lg')

@section('content')
<form action="{{ route('register') }}" method="POST" class="space-y-4">
    @csrf

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="name" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Contact Person Name <span class="text-tg-pink">*</span></label>
            <input id="name" name="name" type="text" required value="{{ old('name') }}" placeholder="e.g. Ramesh Patel"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Official Business Email <span class="text-tg-pink">*</span></label>
            <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="e.g. ramesh@company.com"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="company_name" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Company / Organization <span class="text-tg-pink">*</span></label>
            <input id="company_name" name="company_name" type="text" required value="{{ old('company_name') }}" placeholder="e.g. Patel Infotech Ltd"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>

        <div>
            <label for="phone" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Phone / WhatsApp Number <span class="text-tg-pink">*</span></label>
            <input id="phone" name="phone" type="tel" required value="{{ old('phone') }}" placeholder="e.g. 9876543210"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="city" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">City / Location</label>
            <input id="city" name="city" type="text" value="{{ old('city') }}" placeholder="e.g. Ahmedabad"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>

        <div>
            <label for="state" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">State / Region</label>
            <input id="state" name="state" type="text" value="{{ old('state') }}" placeholder="e.g. Gujarat"
                   class="mt-1 block w-full rounded-xl border border-slate-300 px-3.5 py-2 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Create Password <span class="text-tg-pink">*</span></label>
            <div class="mt-1 relative">
                <input id="password" name="password" type="password" required placeholder="Minimum 8 characters"
                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 pr-11 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
                <button type="button"
                        data-password-toggle="password"
                        onclick="togglePasswordVisibility('password', this)"
                        aria-label="Show password"
                        aria-controls="password"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                    <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg class="w-5 h-5 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Confirm Password <span class="text-tg-pink">*</span></label>
            <div class="mt-1 relative">
                <input id="password_confirmation" name="password_confirmation" type="password" required placeholder="Repeat password"
                       class="block w-full rounded-xl border border-slate-300 px-3.5 py-2 pr-11 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
                <button type="button"
                        data-password-toggle="password_confirmation"
                        onclick="togglePasswordVisibility('password_confirmation', this)"
                        aria-label="Show password"
                        aria-controls="password_confirmation"
                        aria-pressed="false"
                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                    <svg class="w-5 h-5 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                    </svg>
                    <svg class="w-5 h-5 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-tg-purple-light p-3.5 border border-tg-purple-border text-xs text-tg-purple-hover flex items-start space-x-2">
        <svg class="w-4 h-4 text-tg-purple flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <span class="font-bold text-tg-purple">Review Policy:</span> Partner accounts are reviewed by Tech Googly administration before access to customer lead referrals and earnings is activated.
        </div>
    </div>

    <div class="pt-2">
        <button type="submit"
                class="flex w-full justify-center rounded-xl bg-tg-purple px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tg-purple-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tg-purple transition">
            Register as Business Partner
        </button>
    </div>
</form>

<div class="mt-6 border-t border-slate-100 pt-6 text-center">
    <p class="text-xs text-slate-600">
        Already have a partner account?
        <a href="{{ route('login') }}" class="font-bold text-tg-purple hover:text-tg-purple-hover ml-1">
            Sign in &rarr;
        </a>
    </p>
</div>
@endsection
