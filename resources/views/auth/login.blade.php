@extends('layouts.auth')

@section('title', 'Partner Sign In')
@section('subtitle', 'Sign in to Tech Googly Partner Portal')

@section('content')
<form action="{{ route('login') }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Business Email Address</label>
        <div class="mt-1.5">
            <input id="email" name="email" type="email" autocomplete="email" required
                   value="{{ old('email') }}"
                   placeholder="e.g. partner@example.com"
                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-tg-dark shadow-sm placeholder:text-slate-400 focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>
    </div>

    <div>
        <div class="flex items-center justify-between">
            <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Password</label>
            <div class="text-xs">
                <a href="{{ route('password.request') }}" class="font-semibold text-tg-purple hover:text-tg-purple-hover transition">
                    Forgot password?
                </a>
            </div>
        </div>
        <div class="mt-1.5 relative">
            <input id="password" name="password" type="password" autocomplete="current-password" required
                   placeholder="Enter your password"
                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 pr-11 text-tg-dark shadow-sm placeholder:text-slate-400 focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
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

    <div class="flex items-center">
        <input id="remember" name="remember" type="checkbox"
               class="h-4 w-4 rounded border-slate-300 text-tg-purple focus:ring-tg-purple">
        <label for="remember" class="ml-2 block text-xs font-medium text-slate-600">Remember this device</label>
    </div>

    <div>
        <button type="submit"
                class="flex w-full justify-center rounded-xl bg-tg-purple px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tg-purple-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tg-purple transition">
            Sign In to Partner Portal
        </button>
    </div>
</form>

<div class="mt-6 border-t border-slate-100 pt-6 text-center">
    <p class="text-xs text-slate-600">
        New to Tech Googly Partner Program?
        <a href="{{ route('register') }}" class="font-bold text-tg-purple hover:text-tg-purple-hover ml-1">
            Register as Business Partner &rarr;
        </a>
    </p>
</div>
@endsection
