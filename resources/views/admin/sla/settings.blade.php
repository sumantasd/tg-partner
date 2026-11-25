@extends('layouts.app')

@section('title', 'Lead SLA Settings')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="sm:flex sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('admin.lead-sla.index') }}" class="text-xs font-semibold text-tg-purple hover:underline">&larr; Back to SLA Dashboard</a>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">Lead SLA Configuration</h1>
            <p class="text-xs text-slate-500 mt-1">
                Configure organizational response targets, business hours windows, and escalation thresholds in Indian Standard Time (Asia/Kolkata).
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

    <form action="{{ route('admin.lead-sla.settings.update') }}" method="POST" class="space-y-6">
        @csrf

        <!-- General SLA Toggles & Targets -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-bold text-slate-800">SLA Enforcement &amp; Response Targets</h2>
                <p class="text-xs text-slate-500 mt-0.5">Define maximum allowed elapsed time before leads are flagged as overdue.</p>
            </div>

            <!-- Enable/Disable -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <label for="sla_enabled" class="text-xs font-bold text-slate-800 block">SLA Tracking &amp; Escalations</label>
                    <span class="text-[11px] text-slate-500">Enable automatic SLA deadline calculation, overdue indicators, and cron escalations.</span>
                </div>
                <select name="sla_enabled" id="sla_enabled" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs bg-white focus:border-tg-purple outline-none font-bold">
                    <option value="1" {{ $config['sla_enabled'] ? 'selected' : '' }}>Enabled (Active)</option>
                    <option value="0" {{ ! $config['sla_enabled'] ? 'selected' : '' }}>Disabled (Paused)</option>
                </select>
            </div>

            <!-- Targets Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        First Contact Target (Hours) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="sla_first_contact_hours" min="1" max="168" value="{{ old('sla_first_contact_hours', $config['sla_first_contact_hours']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Maximum hours to establish genuine contact on newly assigned leads. Default: 24 hours.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Reassigned Lead First Contact Target (Hours) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="sla_reassigned_first_contact_hours" min="1" max="168" value="{{ old('sla_reassigned_first_contact_hours', $config['sla_reassigned_first_contact_hours']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Target when lead is reassigned from another team member. Default: 12 hours.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Scheduled Follow-up Target (Hours) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="sla_followup_hours" min="1" max="336" value="{{ old('sla_followup_hours', $config['sla_followup_hours']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Default follow-up window if no explicit next follow-up date is scheduled. Default: 48 hours.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Due Soon Warning Threshold (Hours) <span class="text-rose-500">*</span>
                    </label>
                    <input type="number" name="sla_due_soon_threshold_hours" min="1" max="48" value="{{ old('sla_due_soon_threshold_hours', $config['sla_due_soon_threshold_hours']) }}" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none">
                    <span class="text-[11px] text-slate-400 mt-1 block">Highlight lead with yellow "Due Soon" badge when deadline is within this threshold. Default: 4 hours.</span>
                </div>
            </div>
        </div>

        <!-- Calculation Mode & Working Hours -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="border-b border-slate-100 pb-4">
                <h2 class="text-sm font-bold text-slate-800">Calculation Mode &amp; Operating Hours</h2>
                <p class="text-xs text-slate-500 mt-0.5">Control whether deadlines advance 24/7 or only during official working days and business hours.</p>
            </div>

            <!-- Calculation Mode -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Calculation Mode</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label class="flex items-start p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-tg-purple transition">
                        <input type="radio" name="sla_calculation_mode" value="calendar_hours" {{ $config['sla_calculation_mode'] === 'calendar_hours' ? 'checked' : '' }} class="mt-0.5 text-tg-purple focus:ring-tg-purple">
                        <div class="ml-3">
                            <span class="text-xs font-bold text-slate-800 block">Calendar Hours (Continuous 24/7)</span>
                            <span class="text-[11px] text-slate-500">Deadlines count elapsed standard hours continuously without pausing on nights or weekends.</span>
                        </div>
                    </label>
                    <label class="flex items-start p-3.5 rounded-xl border border-slate-200 cursor-pointer hover:border-tg-purple transition">
                        <input type="radio" name="sla_calculation_mode" value="business_hours" {{ $config['sla_calculation_mode'] === 'business_hours' ? 'checked' : '' }} class="mt-0.5 text-tg-purple focus:ring-tg-purple">
                        <div class="ml-3">
                            <span class="text-xs font-bold text-slate-800 block">Business Hours (IST Office Schedule)</span>
                            <span class="text-[11px] text-slate-500">Deadlines only accrue during working hours and automatically pause outside business windows &amp; holidays.</span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Business Hours Timing -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Business Hours Start (IST) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="sla_business_start_time" value="{{ old('sla_business_start_time', $config['sla_business_start_time']) }}" placeholder="09:30" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none font-mono">
                    <span class="text-[11px] text-slate-400 mt-1 block">Format: HH:MM in 24-hr format (e.g. 09:30).</span>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Business Hours End (IST) <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="sla_business_end_time" value="{{ old('sla_business_end_time', $config['sla_business_end_time']) }}" placeholder="18:30" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-xs focus:border-tg-purple outline-none font-mono">
                    <span class="text-[11px] text-slate-400 mt-1 block">Format: HH:MM in 24-hr format (e.g. 18:30).</span>
                </div>
            </div>

            <!-- Working Days -->
            <div class="pt-2">
                <label class="block text-xs font-bold text-slate-700 mb-2">Operating Working Days</label>
                <div class="grid grid-cols-2 sm:grid-cols-7 gap-2">
                    @php
                        $daysMap = [
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                            7 => 'Sunday',
                        ];
                    @endphp
                    @foreach($daysMap as $dayNum => $dayName)
                        <label class="flex items-center p-2.5 rounded-xl border border-slate-200 text-xs cursor-pointer hover:bg-slate-50 transition">
                            <input type="checkbox" name="sla_working_days[]" value="{{ $dayNum }}" {{ in_array($dayNum, $config['sla_working_days'], true) ? 'checked' : '' }} class="rounded border-slate-300 text-tg-purple focus:ring-tg-purple">
                            <span class="ml-2 font-medium text-slate-700">{{ substr($dayName, 0, 3) }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="text-[11px] text-slate-400 mt-1.5 block">Standard Indian tech working week: Monday through Saturday (Sunday off).</span>
            </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end space-x-3 pt-2">
            <a href="{{ route('admin.lead-sla.index') }}" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 transition shadow-sm">
                Save SLA Configuration
            </button>
        </div>
    </form>
</div>
@endsection
