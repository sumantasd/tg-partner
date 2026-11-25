@extends('layouts.auth')

@section('title', 'Reset Password')
@section('subtitle', 'Request Partner Account Password Recovery')

@section('content')
<p class="text-xs text-slate-600 mb-5 text-center">
    Enter your registered email address and we will dispatch password recovery instructions.
</p>

<form action="{{ route('password.email') }}" method="POST" class="space-y-5">
    @csrf

    <div>
        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark">Registered Email Address</label>
        <div class="mt-1.5">
            <input id="email" name="email" type="email" required value="{{ old('email') }}" placeholder="e.g. partner@example.com"
                   class="block w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-tg-dark shadow-sm focus:border-tg-purple focus:ring-2 focus:ring-tg-purple/20 text-sm outline-none transition">
        </div>
    </div>

    <div>
        <button type="submit"
                class="flex w-full justify-center rounded-xl bg-tg-purple px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-tg-purple-hover focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-tg-purple transition">
            Send Recovery Instructions
        </button>
    </div>
</form>

<div class="mt-6 border-t border-slate-100 pt-6 text-center">
    <a href="{{ route('login') }}" class="text-xs font-bold text-tg-purple hover:text-tg-purple-hover">
        &larr; Back to Sign In
    </a>
</div>
@endsection
