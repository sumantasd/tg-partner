@extends('layouts.auth')

@section('title', 'Account Suspended')
@section('subtitle', 'Partner Access Temporarily Restricted')

@section('content')
<div class="text-center py-4 space-y-4">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-tg-pink-light text-tg-pink border border-tg-pink-border mb-2">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
        </svg>
    </div>

    <h3 class="text-lg font-bold text-tg-dark">Partner Account Suspended</h3>

    <p class="text-xs text-slate-600 leading-relaxed">
        Your partner access has been temporarily suspended by Tech Googly administration. You cannot access lead submissions or financial features at this time.
    </p>

    <p class="text-xs text-slate-500">
        If you believe this is in error, please reach out to your account representative or email <a href="mailto:support@techgoogly.com" class="text-tg-purple font-semibold underline">support@techgoogly.com</a>.
    </p>

    <form action="{{ route('logout') }}" method="POST" class="pt-4">
        @csrf
        <button type="submit"
                class="w-full inline-flex justify-center rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-semibold text-tg-dark hover:bg-slate-200 transition">
            Sign Out
        </button>
    </form>
</div>
@endsection
