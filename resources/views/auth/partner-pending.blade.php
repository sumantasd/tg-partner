@extends('layouts.auth')

@section('title', 'Account Under Review')
@section('subtitle', 'Partner Registration Pending Verification')

@section('content')
<div class="text-center py-4 space-y-4">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-amber-50 text-amber-600 border border-amber-200 mb-2">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </div>

    <h3 class="text-lg font-bold text-tg-dark">Application Pending Review</h3>

    <p class="text-xs text-slate-600 leading-relaxed">
        Thank you for registering with the <strong>Tech Googly Partner Program</strong>. Your account is currently pending verification and activation by our partnership administration team.
    </p>

    <div class="rounded-xl bg-slate-50 p-4 border border-slate-200 text-xs text-slate-600 text-left space-y-1">
        <p><span class="font-bold text-tg-dark">What happens next:</span></p>
        <ul class="list-disc list-inside space-y-0.5 text-slate-500">
            <li>Our team verifies your organization profile.</li>
            <li>Your default commission tier is configured.</li>
            <li>You receive access to submit and track client leads.</li>
        </ul>
    </div>

    <form action="{{ route('logout') }}" method="POST" class="pt-4">
        @csrf
        <button type="submit"
                class="w-full inline-flex justify-center rounded-xl bg-slate-100 border border-slate-200 px-4 py-2.5 text-xs font-semibold text-tg-dark hover:bg-slate-200 transition">
            Sign Out of Account
        </button>
    </form>
</div>
@endsection
