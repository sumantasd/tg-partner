@extends('layouts.app')

@section('title', 'Staff Profile & Security Settings')

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
                            class="absolute -bottom-1 -right-1 p-1.5 rounded-full bg-white border border-slate-200 shadow-sm text-slate-600 hover:text-tg-purple hover:bg-slate-50 transition touch-target"
                            title="Update Profile Photo">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </button>
                    <!-- Hidden photo form -->
                    <form id="photo-form" action="{{ route('team.profile.photo') }}" method="POST" enctype="multipart/form-data" class="hidden">
                        @csrf
                        <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="document.getElementById('photo-form').submit()">
                    </form>
                </div>

                <div>
                    <div class="flex items-center space-x-2 flex-wrap gap-y-1">
                        <h1 class="text-xl sm:text-2xl font-bold text-tg-dark tracking-tight">{{ $user->name }}</h1>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-tg-blue-light text-tg-blue border border-tg-blue-border font-mono">
                            {{ $user->employee_id_display }}
                        </span>
                    </div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $user->designation ?? 'Sales CRM Specialist' }} &bull; {{ $user->department ?? 'Telecalling Desk' }}
                    </div>

                    <!-- Badges -->
                    <div class="flex items-center space-x-2 mt-2 flex-wrap gap-y-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                            {{ $user->status->label() }}
                        </span>

                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                            Role: {{ $user->role->label() }}
                        </span>

                        @if($user->joining_date)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600">
                                Joined: {{ $user->joining_date->format('M d, Y') }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Performance Overview -->
            <div class="flex items-center space-x-6 border-t sm:border-t-0 sm:border-l border-slate-100 pt-4 sm:pt-0 sm:pl-6 text-xs">
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Assigned Leads</span>
                    <span class="text-base font-bold text-tg-dark font-mono">
                        {{ $leadStats['assigned'] }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Won Deals</span>
                    <span class="text-base font-bold text-emerald-600 font-mono">
                        {{ $leadStats['won'] }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider block">Conversion</span>
                    <span class="text-base font-bold text-tg-purple font-mono">
                        {{ $leadStats['conversion_rate'] }}%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 2 Column Layout: Profile Details & Password Form -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Column 1 & 2: Personal & Official Information -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Personal Contact Info Form -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-5">
                    <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Personal Contact Information</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Update your display name and contact mobile number.</p>
                </div>

                <form action="{{ route('team.profile.update') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Full Name <span class="text-rose-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                                   class="w-full text-xs rounded-xl border border-slate-300 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Contact Mobile Number</label>
                            <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="e.g. 9830012345"
                                   class="w-full text-xs rounded-xl border border-slate-300 focus:border-tg-purple focus:ring-tg-purple px-3.5 py-2.5">
                        </div>
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm btn-tactile">
                            Save Profile Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Read-Only Company & Employment Credentials Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Official Employment Records</h2>
                        <p class="text-[11px] text-slate-500 mt-0.5">Corporate identity and HR parameters managed exclusively by Admin.</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                        Admin-Controlled
                    </span>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <!-- Employee ID -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Employee ID</span>
                        <div class="font-mono text-xs font-bold text-tg-dark">{{ $user->employee_id_display }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Official corporate identification code</div>
                    </div>

                    <!-- Official Email -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Work Email</span>
                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                        </div>
                        <div class="font-mono text-xs font-bold text-tg-dark">{{ $user->email }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Primary login and audit identifier</div>
                    </div>

                    <!-- Designation -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Designation</span>
                        <div class="text-xs font-bold text-tg-dark">{{ $user->designation ?? 'Sales CRM Specialist' }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Assigned professional position</div>
                    </div>

                    <!-- Department -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Department</span>
                        <div class="text-xs font-bold text-tg-dark">{{ $user->department ?? 'Telecalling Desk' }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Operational business unit</div>
                    </div>

                    <!-- Joining Date -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Date of Joining</span>
                        <div class="text-xs font-bold text-tg-dark">{{ $user->joining_date?->format('F d, Y') ?? 'Not Specified' }}</div>
                        <div class="text-[10px] text-slate-400 mt-1">Tenure commencement date</div>
                    </div>

                    <!-- Account Status -->
                    <div class="p-4 rounded-2xl bg-slate-50/75 border border-slate-200/80">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block mb-1">Account Status</span>
                        <div class="text-xs font-bold text-emerald-700 flex items-center">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5"></span>
                            {{ $user->status->label() }}
                        </div>
                        <div class="text-[10px] text-slate-400 mt-1">Active telecalling permissions enabled</div>
                    </div>
                </div>

                <div class="mt-4 p-3 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-800 text-[11px] flex items-start space-x-2">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>To update your official employee ID, designation, department, or work email, contact the Tech Googly Admin control desk.</span>
                </div>
            </div>
        </div>

        <!-- Column 3: Change Password & Navigation Shortcuts -->
        <div class="space-y-6">

            <!-- Change Password Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider">Change Password</h2>
                    <p class="text-[11px] text-slate-500 mt-0.5">Ensure your account is protected with a strong password.</p>
                </div>

                <form action="{{ route('team.profile.password') }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')

                    <!-- Current Password -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Current Password <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password" required
                                   class="w-full text-xs rounded-xl border border-slate-300 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
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
                                   class="w-full text-xs rounded-xl border border-slate-300 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
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
                                   class="w-full text-xs rounded-xl border border-slate-300 focus:border-tg-purple focus:ring-tg-purple pr-10 pl-3.5 py-2.5">
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

            <!-- Connected CRM Shortcuts Card -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm space-y-2">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Connected CRM Modules</div>
                <a href="{{ route('team.workspace') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-slate-700">
                    <span>CRM Sales Dashboard</span>
                    <span class="text-tg-purple">&rarr;</span>
                </a>
                <a href="{{ route('team.leads.index') }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-slate-700">
                    <span>My Assigned Leads</span>
                    <span class="text-tg-purple">&rarr;</span>
                </a>
                <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}" class="flex items-center justify-between p-2.5 rounded-xl hover:bg-slate-50 transition text-xs font-bold text-amber-600">
                    <span>Today's Follow-up Schedule</span>
                    <span class="text-amber-600">&rarr;</span>
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
