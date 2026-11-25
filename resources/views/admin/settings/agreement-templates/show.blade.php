@extends('layouts.app')

@section('title', "Agreement Template #{$template->template_code} — Admin Portal")

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.settings.agreement-templates.index') }}" class="hover:text-tg-purple">Agreement Templates</a>
                <span>/</span>
                <span class="text-tg-purple font-semibold font-mono">{{ $template->template_code }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-bold text-tg-dark">{{ $template->title }}</h1>
                <span class="font-mono text-xs px-2.5 py-0.5 rounded-full font-bold bg-slate-100 text-slate-700 border border-slate-200">
                    v{{ $template->version }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $template->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ ucfirst($template->status) }}
                </span>
                @if($template->is_active)
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-tg-purple text-white">Active Template</span>
                @endif
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <a href="{{ route('admin.settings.agreement-templates.edit', $template) }}"
               class="px-3.5 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-bold transition">
                Edit Content
            </a>
            @if(!$template->is_active)
                <form action="{{ route('admin.settings.agreement-templates.activate', $template) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition shadow-sm">
                        Activate Template
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Metadata Strip -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-white border border-slate-200/80 shadow-sm text-xs">
        <div>
            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Effective From</span>
            <span class="font-semibold text-slate-700">@istDate($template->effective_from)</span>
        </div>
        <div>
            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Effective Until</span>
            <span class="font-semibold text-slate-700">{{ $template->effective_until ? \App\Support\DateTimeHelper::formatDate($template->effective_until) : 'Indefinite' }}</span>
        </div>
        <div>
            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Authored By</span>
            <span class="font-semibold text-slate-700">{{ $template->creator ? $template->creator->name : 'System Admin' }}</span>
        </div>
        <div>
            <span class="text-slate-400 block text-[10px] font-bold uppercase tracking-wider">Created At (IST)</span>
            <span class="font-semibold text-slate-700">@istDateTime($template->created_at)</span>
        </div>
    </div>

    <!-- Live Rendered Preview -->
    <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <span class="text-xs font-bold text-slate-700">Live Rendered Document Preview (with Sample Partner Data)</span>
            </div>
            <span class="text-[11px] font-mono text-slate-400">PDF Generation Engine Ready (Batch 2)</span>
        </div>

        <div class="p-8 sm:p-12 font-serif text-slate-800 text-sm leading-relaxed whitespace-pre-line bg-white select-text max-w-none">
            {!! nl2br(e($renderedContent)) !!}
        </div>
    </div>

    <div class="flex justify-end">
        <a href="{{ route('admin.settings.agreement-templates.index') }}"
           class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
            ← Back to Template Roster
        </a>
    </div>
</div>
@endsection
