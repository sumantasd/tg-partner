@extends('layouts.app')

@section('title', 'Edit Partner — ' . $partner->partner_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.partners.index') }}" class="hover:text-tg-purple">Partners</a>
        <span>/</span>
        <a href="{{ route('admin.partners.show', $partner) }}" class="font-mono hover:text-tg-purple">{{ $partner->partner_code }}</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Edit</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-tg-dark">Edit Partner Profile</h1>
                <p class="text-xs text-slate-500 mt-1">Update business details, account status and commission terms for {{ $partner->partner_code }}.</p>
            </div>
            <span class="font-mono text-xs font-bold px-3 py-1.5 rounded-xl bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                {{ $partner->partner_code }}
            </span>
        </div>

        <form action="{{ route('admin.partners.update', $partner) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Contact & User Account</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Person Name *</label>
                        <input type="text" name="name" required value="{{ old('name', $partner->user->name) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Official Login Email *</label>
                        <input type="email" name="email" required value="{{ old('email', $partner->user->email) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Mobile Number *</label>
                        <input type="text" name="phone" required value="{{ old('phone', $partner->phone) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Status *</label>
                        <select name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="active" {{ old('status', $partner->user->status->value) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="pending" {{ old('status', $partner->user->status->value) === 'pending' ? 'selected' : '' }}>Pending Review</option>
                            <option value="suspended" {{ old('status', $partner->user->status->value) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        @error('status') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Reset Password (Optional)</label>
                        <div class="relative">
                            <input type="password" id="partner_edit_password" name="password" placeholder="Leave blank to keep current password"
                                   class="w-full px-4 py-2.5 pr-11 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                            <button type="button"
                                    data-password-toggle="partner_edit_password"
                                    onclick="togglePasswordVisibility('partner_edit_password', this)"
                                    aria-label="Show password"
                                    aria-controls="partner_edit_password"
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
                        @error('password') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm Reset Password</label>
                        <div class="relative">
                            <input type="password" id="partner_edit_password_confirmation" name="password_confirmation" placeholder="Confirm new password"
                                   class="w-full px-4 py-2.5 pr-11 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                            <button type="button"
                                    data-password-toggle="partner_edit_password_confirmation"
                                    onclick="togglePasswordVisibility('partner_edit_password_confirmation', this)"
                                    aria-label="Show password"
                                    aria-controls="partner_edit_password_confirmation"
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

            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Business & Commercial Terms</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Business Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $partner->company_name) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Default Commission Rate (%) *</label>
                        <input type="number" step="0.01" min="0" max="100" name="default_commission_rate" required
                               value="{{ old('default_commission_rate', $partner->default_commission_rate) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        <span class="text-[11px] text-slate-400 mt-1 block">Default rate for new referred projects. Existing recorded commissions are immutable.</span>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Office / Street Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $partner->address_line1) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                        <input type="text" name="city" value="{{ old('city', $partner->city) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">State</label>
                        <input type="text" name="state" value="{{ old('state', $partner->state) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $partner->postal_code) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Remarks / Notes</label>
                        <textarea name="internal_notes" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('internal_notes', $partner->internal_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.partners.show', $partner) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple/90 shadow-sm transition">
                    Save Partner Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
