@extends('layouts.app')

@section('title', 'Inactivity Policy Settings')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.inactivity.index') }}" class="text-xs font-semibold text-tg-purple hover:underline">&larr; Back to Inactivity Monitor</a>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">Inactivity Policy Configuration</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure warning, alert, and critical inactivity thresholds for referral partners and telecalling sales staff.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-semibold flex items-center">
            <svg class="w-4 h-4 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.inactivity.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- General Inactivity Switch -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label for="inactivity_enabled" class="text-xs font-bold text-slate-800 block">Inactivity System &amp; Daily Evaluations</label>
                    <span class="text-[11px] text-slate-500">Enable automatic status transitions, deduplicated threshold notifications, and audit logging.</span>
                </div>
                <select name="inactivity_enabled" id="inactivity_enabled" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs bg-white focus:border-tg-purple outline-none font-bold">
                    <option value="1" {{ $config['enabled'] ? 'selected' : '' }}>Enabled (Active)</option>
                    <option value="0" {{ ! $config['enabled'] ? 'selected' : '' }}>Disabled (Paused)</option>
                </select>
            </div>
        </div>

        <!-- Partner Inactivity Policy Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-bold text-slate-800 flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-tg-purple" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Partner Inactivity Policy Thresholds
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Thresholds for referral partners based on last lead submission or platform activity.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        At Risk Warning (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="partner_warning_days" min="1" max="180" value="{{ old('partner_warning_days', $config['partner_warning_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 30 days. Sends in-app reminder to partner.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Inactivity Alert (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="partner_alert_days" min="1" max="270" value="{{ old('partner_alert_days', $config['partner_alert_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 60 days. Alerts partner &amp; notifies Admin.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Critical Threshold (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="partner_critical_days" min="1" max="365" value="{{ old('partner_critical_days', $config['partner_critical_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 90 days. Critical alert for administrative action.</span>
                </div>
            </div>
        </div>

        <!-- Staff Inactivity Policy Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-bold text-slate-800 flex items-center">
                    <svg class="w-4 h-4 mr-1.5 text-tg-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Sales Staff Inactivity Policy Thresholds
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Thresholds for internal telecalling sales team members based on CRM customer interactions.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        At Risk Warning (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="staff_warning_days" min="1" max="180" value="{{ old('staff_warning_days', $config['staff_warning_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 30 days. Reminds staff to log client interactions.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Inactivity Alert (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="staff_alert_days" min="1" max="270" value="{{ old('staff_alert_days', $config['staff_alert_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 60 days. Escalates to Admin overview.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Critical Threshold (Days) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="staff_critical_days" min="1" max="365" value="{{ old('staff_critical_days', $config['staff_critical_days']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default: 90 days. Flagged for manager review.</span>
                </div>
            </div>
        </div>

        <!-- Submit Buttons -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.inactivity.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                Save Inactivity Thresholds
            </button>
        </div>
    </form>
</div>
@endsection
