@extends('layouts.app')

@section('title', 'Edit Team Member — ' . $teamMember->name)

@section('content')
<div class="max-w-4xl mx-auto space-y-6 w-full min-w-0">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.team-members.index') }}" class="inline-flex items-center text-tg-purple hover:underline font-bold">
                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Team Members
            </a>
            <span>/</span>
            <a href="{{ route('admin.team-members.show', $teamMember) }}" class="hover:text-tg-purple font-semibold">{{ $teamMember->name }}</a>
            <span>/</span>
            <span class="text-tg-dark font-bold">Edit Profile</span>
        </div>

        <a href="{{ route('admin.team-members.show', $teamMember) }}"
           class="inline-flex items-center px-3.5 py-1.5 rounded-xl border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 text-xs font-semibold shadow-sm transition">
            View Details &rarr;
        </a>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="border-b border-slate-100 pb-5 mb-6">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-tg-purple-light text-tg-purple font-extrabold flex items-center justify-center text-sm shadow-sm">
                    {{ strtoupper(substr($teamMember->name, 0, 2)) }}
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Edit Team Member Profile</h1>
                    <p class="text-xs text-slate-500 mt-0.5">Update staff credentials, contact details, employee ID, and CRM permissions.</p>
                </div>
            </div>
        </div>

        <form action="{{ route('admin.team-members.update', $teamMember) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Staff Identification & Credentials -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4 flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    Profile &amp; Personal Credentials
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <!-- 1. Full Name -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Full Name <span class="text-tg-pink">*</span>
                        </label>
                        <input type="text" name="name" required value="{{ old('name', $teamMember->name) }}"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('name') ? 'border-tg-pink focus:ring-tg-pink' : 'border-slate-300 focus:ring-tg-purple' }} text-xs font-semibold text-tg-dark focus:ring-2 outline-none">
                        @error('name')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 2. Official Email -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Official Email <span class="text-tg-pink">*</span>
                        </label>
                        <input type="email" name="email" required value="{{ old('email', $teamMember->email) }}"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('email') ? 'border-tg-pink focus:ring-tg-pink' : 'border-slate-300 focus:ring-tg-purple' }} text-xs font-semibold text-tg-dark focus:ring-2 outline-none">
                        @error('email')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 3. Mobile Number -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Mobile Number
                        </label>
                        <input type="text" name="phone" value="{{ old('phone', $teamMember->phone ?: $teamMember->mobile_number) }}" placeholder="e.g. 9831122334"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('phone') ? 'border-tg-pink focus:ring-tg-pink' : 'border-slate-300 focus:ring-tg-purple' }} text-xs font-mono text-tg-dark focus:ring-2 outline-none">
                        @error('phone')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 4. Employee ID -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Employee ID
                        </label>
                        <input type="text" name="employee_id" value="{{ old('employee_id', $teamMember->employee_id ?: $teamMember->employee_id_display) }}" placeholder="e.g. TG-TM-001"
                               class="w-full px-4 py-2.5 rounded-xl border {{ $errors->has('employee_id') ? 'border-tg-pink focus:ring-tg-pink' : 'border-slate-300 focus:ring-tg-purple' }} text-xs font-mono text-tg-dark focus:ring-2 outline-none">
                        @error('employee_id')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 5. Account Status -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Account Status <span class="text-tg-pink">*</span>
                        </label>
                        <select name="status" required
                                class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-tg-dark focus:ring-2 focus:ring-tg-purple outline-none">
                            <option value="active" {{ old('status', $teamMember->status->value) === 'active' ? 'selected' : '' }}>Active (Authorized to login)</option>
                            <option value="suspended" {{ old('status', $teamMember->status->value) === 'suspended' ? 'selected' : '' }}>Suspended / Inactive (Login blocked)</option>
                        </select>
                        @error('status')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 6. Role (Readonly Display) -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            System Role (Protected)
                        </label>
                        <div class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-xs font-mono font-bold text-slate-500">
                            {{ $teamMember->role->label() }} (team_member)
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1">Role assignments are strictly isolated to prevent escalation.</p>
                    </div>

                    <!-- 7. Designation / Role Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Designation / Job Title
                        </label>
                        <input type="text" name="designation" value="{{ old('designation', $teamMember->designation) }}" placeholder="e.g. Senior Sales Specialist"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-tg-dark focus:ring-2 focus:ring-tg-purple outline-none">
                        @error('designation')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 8. Department -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Department
                        </label>
                        <select name="department" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-tg-dark focus:ring-2 focus:ring-tg-purple outline-none">
                            @foreach(['Sales', 'Operations', 'Support', 'Accounts', 'Technical'] as $dept)
                                <option value="{{ $dept }}" {{ old('department', $teamMember->department) === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                            @endforeach
                        </select>
                        @error('department')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 9. Joining Date -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">
                            Joining Date
                        </label>
                        <input type="date" name="joining_date" value="{{ old('joining_date', $teamMember->joining_date?->format('Y-m-d') ?: ($teamMember->created_at?->format('Y-m-d'))) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs text-tg-dark focus:ring-2 focus:ring-tg-purple outline-none">
                        @error('joining_date')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Password Reset (Optional) -->
            <div class="pt-6 border-t border-slate-100">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-1 flex items-center">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    Security &amp; Password Update (Optional)
                </h2>
                <p class="text-[11px] text-slate-400 mb-4">Leave both password fields completely blank to keep the member's current existing password.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">New Password</label>
                        <div class="relative">
                            <input type="password" id="edit_team_member_password" name="password" placeholder="Leave blank to keep unchanged"
                                   class="w-full px-4 py-2.5 pr-11 rounded-xl border {{ $errors->has('password') ? 'border-tg-pink focus:ring-tg-pink' : 'border-slate-300 focus:ring-tg-purple' }} text-xs focus:ring-2 outline-none">
                            <button type="button"
                                    data-password-toggle="edit_team_member_password"
                                    onclick="togglePasswordVisibility('edit_team_member_password', this)"
                                    aria-label="Show password"
                                    aria-controls="edit_team_member_password"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password</label>
                        <div class="relative">
                            <input type="password" id="edit_team_member_password_confirmation" name="password_confirmation" placeholder="Repeat new password"
                                   class="w-full px-4 py-2.5 pr-11 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple outline-none">
                            <button type="button"
                                    data-password-toggle="edit_team_member_password_confirmation"
                                    onclick="togglePasswordVisibility('edit_team_member_password_confirmation', this)"
                                    aria-label="Show password"
                                    aria-controls="edit_team_member_password_confirmation"
                                    aria-pressed="false"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-tg-purple focus:text-tg-purple focus:outline-none transition cursor-pointer">
                                <svg class="w-4 h-4 eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg class="w-4 h-4 eye-off-icon hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-between pt-6 border-t border-slate-100">
                <a href="{{ route('admin.team-members.show', $teamMember) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-sm">
                    &larr; Cancel &amp; Back to Details
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition">
                    Save Changes &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
