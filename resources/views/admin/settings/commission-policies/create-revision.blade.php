@extends('layouts.app')

@section('title', "Create Revision of #{$parent->policy_code} — Admin Portal")

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.settings.commission-policies.index') }}" class="hover:text-tg-purple">Commission Policies</a>
        <span>/</span>
        <a href="{{ route('admin.settings.commission-policies.show', $parent) }}" class="hover:text-tg-purple font-mono">{{ $parent->policy_code }}</a>
        <span>/</span>
        <span class="text-tg-purple font-semibold">New Revision (v{{ $parent->version + 1 }})</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-tg-purple-light flex items-center justify-center text-tg-purple font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-tg-dark">Create Next Version Revision</h1>
                <p class="text-xs text-slate-500">Revising policy <strong>#{{ $parent->policy_code }}</strong>. Parent version will automatically be marked superseded.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.commission-policies.revision.store', $parent) }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">New Revision Code</label>
                    <input type="text" readonly
                           value="{{ $parent->policy_code }}-V{{ $parent->version + 1 }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-mono text-xs font-bold text-slate-500">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Revision Title *</label>
                    <input type="text" name="name" required value="{{ old('name', $parent->name) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                    @error('name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Description</label>
                <textarea name="description" rows="2" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">{{ old('description', $parent->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Commission Type *</label>
                    <select name="commission_type" id="revision_type_select" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-tg-purple">
                        <option value="percentage" {{ old('commission_type', $parent->commission_type->value) === 'percentage' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed" {{ old('commission_type', $parent->commission_type->value) === 'fixed' ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5" id="revision_value_label">Commission Rate *</label>
                    <input type="number" step="0.01" min="0" name="commission_value" required value="{{ old('commission_value', $parent->commission_value) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold focus:ring-2 focus:ring-tg-purple">
                    @error('commission_value') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div id="revision_freq_block" style="display: {{ old('commission_type', $parent->commission_type->value) === 'fixed' ? 'block' : 'none' }};">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Fixed Frequency *</label>
                    <select name="fixed_frequency" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                        <option value="per_payment" {{ old('fixed_frequency', $parent->fixed_frequency?->value) === 'per_payment' ? 'selected' : '' }}>Per Verified Payment</option>
                        <option value="per_project" {{ old('fixed_frequency', $parent->fixed_frequency?->value) === 'per_project' ? 'selected' : '' }}>Per Project (One-time)</option>
                        <option value="per_milestone" {{ old('fixed_frequency', $parent->fixed_frequency?->value) === 'per_milestone' ? 'selected' : '' }}>Per Milestone</option>
                    </select>
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
                </div>
            </div>

            <div class="pt-1">
                <label class="relative flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="is_default" value="1" {{ old('is_default', $parent->is_default) ? 'checked' : '' }}
                           class="w-4 h-4 text-tg-purple rounded border-slate-300 focus:ring-tg-purple">
                    <span class="text-xs font-bold text-slate-700">Designate as Global Default Policy</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Revision Rationale / Changelog Notes</label>
                <textarea name="notes" rows="2" placeholder="Reason for adjusting rate or terms..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3">
                <a href="{{ route('admin.settings.commission-policies.show', $parent) }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                    Publish Version {{ $parent->version + 1 }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const typeSelect = document.getElementById('revision_type_select');
        const freqBlock = document.getElementById('revision_freq_block');
        const valueLabel = document.getElementById('revision_value_label');

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
