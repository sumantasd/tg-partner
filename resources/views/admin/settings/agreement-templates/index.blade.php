@extends('layouts.app')

@section('title', 'Agreement Templates — Admin Portal')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
                <span>/</span>
                <span class="text-tg-purple font-semibold">Agreement Templates</span>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark">Partner Agreement Templates</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage legally versioned templates and clause definitions for Business Partner Agreements.</p>
        </div>

        <div>
            <a href="{{ route('admin.settings.agreement-templates.create') }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                + New Template
            </a>
        </div>
    </div>

    <!-- Active Agreement Template Spotlight -->
    @if($activeTemplate)
    <div class="rounded-3xl p-6 bg-white border-2 border-tg-purple shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-1">
            <div class="flex items-center space-x-2">
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider bg-tg-purple text-white">
                    Active System Template
                </span>
                <span class="font-mono text-xs text-tg-purple font-bold">#{{ $activeTemplate->template_code }}</span>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">v{{ $activeTemplate->version }}</span>
            </div>
            <h2 class="text-lg font-bold text-tg-dark">{{ $activeTemplate->title }}</h2>
            <p class="text-xs text-slate-500">Effective from @istDate($activeTemplate->effective_from) {{ $activeTemplate->effective_until ? 'to ' . \App\Support\DateTimeHelper::formatDate($activeTemplate->effective_until) : '(Ongoing)' }}</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.settings.agreement-templates.show', $activeTemplate) }}"
               class="px-4 py-2 rounded-xl bg-tg-purple text-white hover:bg-tg-purple/90 text-xs font-bold transition shadow-sm">
                Preview Template →
            </a>
            <a href="{{ route('admin.settings.agreement-templates.edit', $activeTemplate) }}"
               class="px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-bold transition">
                Edit Content
            </a>
        </div>
    </div>
    @endif

    <!-- Templates Table -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto table-scroll-container">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <th class="py-3.5 px-4">Template Code</th>
                        <th class="py-3.5 px-4">Title</th>
                        <th class="py-3.5 px-4">Version</th>
                        <th class="py-3.5 px-4">Effective Range (IST)</th>
                        <th class="py-3.5 px-4">Status</th>
                        <th class="py-3.5 px-4">Active</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($templates as $t)
                        <tr class="hover:bg-slate-50/60 transition {{ $t->is_active ? 'bg-tg-purple/5' : '' }}">
                            <td class="py-3.5 px-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.settings.agreement-templates.show', $t) }}" class="hover:underline">
                                    {{ $t->template_code }}
                                </a>
                            </td>
                            <td class="py-3.5 px-4 font-semibold text-tg-dark">
                                {{ $t->title }}
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                                v{{ $t->version }}
                            </td>
                            <td class="py-3.5 px-4 text-slate-600">
                                @istDate($t->effective_from)
                                →
                                {{ $t->effective_until ? \App\Support\DateTimeHelper::formatDate($t->effective_until) : 'Indefinite' }}
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $t->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($t->status === 'draft' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200') }}">
                                    {{ ucfirst($t->status) }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                @if($t->is_active)
                                    <span class="inline-flex items-center text-emerald-600 font-bold text-xs">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        Active
                                    </span>
                                @else
                                    <span class="text-slate-400 text-xs">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-2 whitespace-nowrap">
                                <a href="{{ route('admin.settings.agreement-templates.show', $t) }}"
                                   class="text-tg-purple hover:text-tg-purple/80 font-bold hover:underline">
                                    Preview
                                </a>
                                <span class="text-slate-300">|</span>
                                <a href="{{ route('admin.settings.agreement-templates.edit', $t) }}"
                                   class="text-slate-600 hover:text-tg-purple font-medium hover:underline">
                                    Edit
                                </a>
                                @if(!$t->is_active)
                                    <span class="text-slate-300">|</span>
                                    <form action="{{ route('admin.settings.agreement-templates.activate', $t) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="text-emerald-600 hover:text-emerald-700 font-bold hover:underline">
                                            Make Active
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-8 text-slate-400">
                                No agreement templates found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($templates->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $templates->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
