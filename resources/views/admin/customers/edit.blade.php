@extends('layouts.app')

@section('title', 'Edit Customer — ' . $customer->customer_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.customers.index') }}" class="hover:text-tg-purple">Customers</a>
        <span>/</span>
        <a href="{{ route('admin.customers.show', $customer) }}" class="font-mono hover:text-tg-purple">{{ $customer->customer_code }}</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Edit</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-tg-dark">Edit Customer Profile</h1>
                <p class="text-xs text-slate-500 mt-1">Update profile information for customer {{ $customer->customer_code }}.</p>
            </div>
            <span class="font-mono text-xs font-bold px-3 py-1.5 rounded-xl bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                {{ $customer->customer_code }}
            </span>
        </div>

        <form action="{{ route('admin.customers.update', $customer) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Customer Identity & Contact</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer Name *</label>
                        <input type="text" name="name" required value="{{ old('name', $customer->name) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Company / Business Name</label>
                        <input type="text" name="company_name" value="{{ old('company_name', $customer->company_name) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('company_name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number *</label>
                        <input type="text" name="phone" required value="{{ old('phone', $customer->phone) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number', $customer->whatsapp_number) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('whatsapp_number') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Account Status *</label>
                        <select name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="active" {{ old('status', $customer->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $customer->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                        @error('status') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Referral Partner Attribution (Permanent & Protected) -->
            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-3">Referral Partner Attribution</h2>
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80">
                    @if($customer->firstPartner)
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-tg-purple text-white font-bold flex items-center justify-center text-sm shadow-sm">
                                {{ strtoupper(substr($customer->firstPartner->company_name, 0, 2)) }}
                            </div>
                            <div>
                                <div class="font-bold text-tg-dark text-xs">{{ $customer->firstPartner->company_name }}</div>
                                <div class="text-[11px] text-slate-500 font-mono">{{ $customer->firstPartner->partner_code }} &bull; Contact: {{ $customer->firstPartner->user->name }}</div>
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2 font-medium">
                            🔒 Original referral partner attribution is permanently sealed to ensure commission integrity.
                        </p>
                    @else
                        <div class="text-xs text-slate-600 font-semibold">
                            Direct Customer (No Partner Attribution)
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1">This customer was onboarded directly without a partner referral.</p>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Address & Notes</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Street Address</label>
                        <input type="text" name="address_line1" value="{{ old('address_line1', $customer->address_line1) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                        <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">State</label>
                        <input type="text" name="state" value="{{ old('state', $customer->state) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Postal Code</label>
                        <input type="text" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Country</label>
                        <input type="text" name="country" value="{{ old('country', $customer->country ?? 'India') }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Remarks / Notes</label>
                        <textarea name="notes" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.customers.show', $customer) }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple/90 shadow-sm transition">
                    Save Customer Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
