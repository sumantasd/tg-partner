@extends('layouts.app')

@section('title', 'New Agreement Template — Admin Portal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-2 text-xs text-slate-500">
        <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
        <span>/</span>
        <a href="{{ route('admin.settings.agreement-templates.index') }}" class="hover:text-tg-purple">Agreement Templates</a>
        <span>/</span>
        <span class="text-tg-purple font-semibold">New Template</span>
    </div>

    <!-- Main Form Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-10 h-10 rounded-2xl bg-tg-purple-light flex items-center justify-center text-tg-purple font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <h1 class="text-xl font-bold text-tg-dark">Author Agreement Template</h1>
                <p class="text-xs text-slate-500">Draft versioned legal terms, clauses, and dynamic partner placeholders.</p>
            </div>
        </div>

        <form action="{{ route('admin.settings.agreement-templates.store') }}" method="POST" class="space-y-5">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Template Code *</label>
                    <input type="text" name="template_code" required
                           value="{{ old('template_code', 'TPL-AGR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(2)))) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs uppercase focus:ring-2 focus:ring-tg-purple">
                    @error('template_code') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Title *</label>
                    <input type="text" name="title" required value="{{ old('title', 'Standard Business Partner Agreement') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                    @error('title') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Version *</label>
                    <input type="text" name="version" required value="{{ old('version', '1.0') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 font-mono text-xs focus:ring-2 focus:ring-tg-purple">
                    @error('version') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Placeholder Guide Pill -->
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                <span class="font-bold text-slate-700 block mb-1">Supported Dynamic Placeholders:</span>
                <div class="flex flex-wrap gap-1.5 font-mono text-[11px] text-tg-purple">
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{agreement_number}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{effective_date}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{company_name}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{partner_name}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{partner_code}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{commission_rate}</span>
                    <span class="px-2 py-0.5 rounded bg-white border border-slate-200">{commission_type}</span>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Agreement Content (Markdown / Text) *</label>
                <textarea name="content" rows="14" required
                          class="w-full px-4 py-3 rounded-2xl border border-slate-300 font-mono text-xs text-slate-800 leading-relaxed focus:ring-2 focus:ring-tg-purple">{{ old('content') }}</textarea>
                @error('content') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Effective From (IST) *</label>
                    <input type="date" name="effective_from" required value="{{ old('effective_from', now()->toDateString()) }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Effective Until (Optional)</label>
                    <input type="date" name="effective_until" value="{{ old('effective_until') }}"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Status *</label>
                    <select name="status" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                        <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft (Review)</option>
                        <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                    </select>
                </div>
            </div>

            <div class="pt-1">
                <label class="relative flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }}
                           class="w-4 h-4 text-tg-purple rounded border-slate-300 focus:ring-tg-purple">
                    <span class="text-xs font-bold text-slate-700">Set as Active Default Agreement Template</span>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Administrative Notes</label>
                <textarea name="notes" rows="2" placeholder="Legal review status or internal notes..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">{{ old('notes') }}</textarea>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-3">
                <a href="{{ route('admin.settings.agreement-templates.index') }}"
                   class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-5 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                    Save Template
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
