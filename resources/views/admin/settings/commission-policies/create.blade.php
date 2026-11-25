@extends('layouts.app')

@section('title', 'Create Commission Policy — Admin Portal')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.settings.commission-policies.index') }}" class="hover:text-tg-purple">Commission Policies</a>
        <span>/</span>
        <span class="text-tg-purple font-semibold">New Policy</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-tg-purple-light flex items-center justify-center text-tg-purple font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-tg-dark">Create New Commission Policy</h1>
                <p class="text-xs text-slate-500">Configure remuneration terms, rate/amount, effective dates, and fixed frequency.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.commission-policies.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Policy Code *</label>
                    <input type="text" name="policy_code" required
                           value="{{ old('policy_code', 'POL-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)))) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs uppercase focus:ring-2 focus:ring-tg-purple">
                    @error('policy_code') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Policy Title / Name *</label>
                    <input type="text" name="name" required value="{{ old('name') }}" placeholder="e.g. Q4 Cloud ERP Special Commission"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                    @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" placeholder="Policy background, partner tiers, and scope..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">{{ old('description') }}</textarea>
                @error('description') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Commission Type *</label>
                    <select name="commission_type" id="commission_type_select" required
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-tg-purple">
                        <option value="percentage" {{ old('commission_type') === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                    @error('commission_type') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5" id="commission_value_label">Commission Rate (%) *</label>
                    <input type="number" step="0.01" min="0" name="commission_value" required value="{{ old('commission_value', 10.00) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-tg-purple">
                    @error('commission_value') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div id="fixed_frequency_block" style="display: {{ old('commission_type') === 'fixed' ? 'block' : 'none' }};">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Fixed Commission Frequency *</label>
                    <select name="fixed_frequency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                        <option value="">Select Frequency</option>
                        <option value="per_payment" {{ old('fixed_frequency') === 'per_payment' ? 'selected' : '' }}>Per Verified Payment</option>
                        <option value="per_project" {{ old('fixed_frequency') === 'per_project' ? 'selected' : '' }}>Per Project (One-time)</option>
                        <option value="per_milestone" {{ old('fixed_frequency') === 'per_milestone' ? 'selected' : '' }}>Per Milestone</option>
                    </select>
                    @error('fixed_frequency') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Effective From Date (IST) *</label>
                    <input type="date" name="effective_from" required value="{{ old('effective_from', now()->toDateString()) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                    @error('effective_from') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Effective Until Date (Optional)</label>
                    <input type="date" name="effective_until" value="{{ old('effective_until') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                    <span class="text-[10px] text-slate-400 mt-0.5 block">Leave blank for ongoing / indefinite validity.</span>
                    @error('effective_until') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-1">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Initial Status *</label>
                    <select name="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                        <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="flex items-center pt-6">
                    <label class="relative flex items-center space-x-2.5 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }}
                               class="w-4 h-4 text-tg-purple rounded border-slate-300 focus:ring-tg-purple">
                        <span class="text-xs font-bold text-slate-700">Designate as Global Default Policy</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Internal Admin Notes</label>
                <textarea name="notes" rows="2" placeholder="Administrative rationale for audit log..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">{{ old('notes') }}</textarea>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 leading-relaxed">
                <span class="font-bold text-tg-purple">Policy Integrity:</span> Existing payments and previously generated commissions are immutable and will retain their locked historical rate snapshots.
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3">
                <a href="{{ route('admin.settings.commission-policies.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                    Create Policy
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('commission_type_select');
        const freqBlock = document.getElementById('fixed_frequency_block');
        const valueLabel = document.getElementById('commission_value_label');

        typeSelect.addEventListener('change', function () {
            if (this.value === 'fixed') {
                freqBlock.style.display = 'block';
                valueLabel.textContent = 'Commission Amount (₹ INR) *';
            } else {
                freqBlock.style.display = 'none';
                valueLabel.textContent = 'Commission Rate (%) *';
            }
        });
    });
</script>
@endsection
