@extends('layouts.app')

@section('title', 'Add New Customer — Admin Portal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.customers.index') }}" class="hover:text-tg-purple">Customers</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Add Customer</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h1 class="text-2xl font-bold text-tg-dark">Create New Customer Account</h1>
            <p class="text-xs text-slate-500 mt-1">Directly create a client account without an associated lead. Attribution to a partner is optional.</p>
        </div>

        <form action="{{ route('admin.customers.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Customer Identity & Contact</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer / Contact Name *</label>
                        <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Rajesh Singhal"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Business Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" placeholder="e.g. Singhal Enterprise Ltd"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('company_name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number *</label>
                        <input type="text" name="phone" required value="{{ old('phone') }}" placeholder="e.g. 9830055443"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="e.g. 9830055443"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('whatsapp_number') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="client@company.com"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Status *</label>
                        <select name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Referral Partner Attribution & Commercials</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Attributed Referral Partner</label>
                        <select name="first_partner_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="">None / Direct Customer (No Partner Attribution)</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('first_partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->company_name }} ({{ $partner->partner_code }} - {{ $partner->user->name }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-slate-400 mt-1 block">
                            If attributed, eligible future verified payments will credit commissions to this partner according to project commission rates.
                        </span>
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Address & Notes</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Street Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1') }}" placeholder="Street address or office location"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                        <input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Kolkata"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">State</label>
                        <input type="text" name="state" value="{{ old('state') }}" placeholder="e.g. West Bengal"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code') }}" placeholder="e.g. 700001"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Country</label>
                        <input type="text" name="country" value="{{ old('country', 'India') }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Remarks / Notes</label>
                        <textarea name="notes" rows="3" placeholder="Background details, client requirements, account notes..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.customers.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple/90 shadow-sm transition">
                    Create Customer
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
