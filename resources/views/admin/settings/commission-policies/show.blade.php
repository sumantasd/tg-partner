@extends('layouts.app')

@section('title', "Commission Policy #{$policy->policy_code} — Admin Portal")

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2 text-xs text-slate-500 mb-1">
                <a href="{{ route('admin.dashboard') }}" class="hover:text-tg-purple">Dashboard</a>
                <span>/</span>
                <a href="{{ route('admin.settings.commission-policies.index') }}" class="hover:text-tg-purple">Commission Policies</a>
                <span>/</span>
                <span class="text-tg-purple font-semibold font-mono">{{ $policy->policy_code }}</span>
            </div>
            <div class="flex items-center space-x-3">
                <h1 class="text-2xl font-bold text-tg-dark">{{ $policy->name }}</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase {{ $policy->status === 'active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($policy->status === 'superseded' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-amber-50 text-amber-700 border border-amber-200') }}">
                    {{ ucfirst($policy->status) }}
                </span>
                @if($policy->is_default)
                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold uppercase bg-tg-purple text-white">Global Default</span>
                @endif
            </div>
        </div>

        <div class="flex items-center space-x-3">
            <form action="{{ route('admin.settings.commission-policies.toggle-status', $policy) }}" method="POST">
                @csrf
                <button type="submit"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold border transition {{ $policy->status === 'active' ? 'border-amber-300 text-amber-700 hover:bg-amber-50' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50' }}">
                    {{ $policy->status === 'active' ? 'Deactivate Policy' : 'Activate Policy' }}
                </button>
            </form>

            <a href="{{ route('admin.settings.commission-policies.revision.create', $policy) }}"
               class="px-4 py-2 rounded-xl bg-tg-purple hover:bg-tg-purple/90 text-white text-xs font-bold transition shadow-sm">
                + Create Next Revision
            </a>
        </div>
    </div>

    <!-- Core Specifications Card -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="md:col-span-2 rounded-3xl bg-white border border-slate-200/80 p-6 shadow-sm space-y-6">
            <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider text-slate-400">Policy Terms &amp; Valuation</h2>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Commission Type</span>
                    <span class="text-sm font-bold text-tg-dark">{{ $policy->commission_type->label() }}</span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Remuneration Value</span>
                    <span class="text-xl font-extrabold text-tg-purple">
                        {{ $policy->commission_type->value === 'percentage' ? number_format($policy->commission_value, 2) . '%' : '₹' . number_format($policy->commission_value, 2) }}
                    </span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wider">Fixed Frequency</span>
                    <span class="text-xs font-semibold text-slate-700">
                        {{ $policy->fixed_frequency ? $policy->fixed_frequency->label() : 'N/A (Percentage)' }}
                    </span>
                </div>
            </div>

            <div>
                <span class="text-xs font-bold text-slate-700 block mb-1">Description</span>
                <p class="text-xs text-slate-600 leading-relaxed">{{ $policy->description ?: 'No additional description provided.' }}</p>
            </div>

            @if($policy->notes)
            <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-amber-900">
                <span class="font-bold">Admin Notes:</span> {{ $policy->notes }}
            </div>
            @endif

            <!-- Revision Chain Tree -->
            <div class="border-t border-slate-100 pt-5 space-y-3">
                <h3 class="text-xs font-bold text-tg-dark uppercase tracking-wider text-slate-400">Version Lineage</h3>

                @if($policy->parentPolicy)
                    <div class="flex items-center space-x-2 text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-200">
                        <span class="text-slate-400">Revises prior version:</span>
                        <a href="{{ route('admin.settings.commission-policies.show', $policy->parentPolicy) }}" class="font-mono font-bold text-tg-purple hover:underline">
                            #{{ $policy->parentPolicy->policy_code }} (v{{ $policy->parentPolicy->version }})
                        </a>
                        <span class="text-slate-400">[{{ $policy->parentPolicy->status }}]</span>
                    </div>
                @endif

                @if($policy->revisions->count() > 0)
                    <div class="space-y-2">
                        <span class="text-xs font-bold text-slate-700 block">Subsequent Revisions:</span>
                        @foreach($policy->revisions as $rev)
                            <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                                <div class="flex items-center space-x-2">
                                    <span class="font-mono font-bold text-tg-purple">#{{ $rev->policy_code }}</span>
                                    <span class="font-semibold text-slate-700">v{{ $rev->version }} ({{ $rev->name }})</span>
                                </div>
                                <a href="{{ route('admin.settings.commission-policies.show', $rev) }}" class="text-tg-purple font-bold hover:underline">
                                    View Revision →
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-slate-400">This is the current head version in this lineage branch.</p>
                @endif
            </div>
        </div>

        <!-- Metadata & Audit Card -->
        <div class="rounded-3xl bg-white border border-slate-200/80 p-6 shadow-sm space-y-4">
            <h2 class="text-sm font-bold text-tg-dark uppercase tracking-wider text-slate-400">Audit &amp; Timeline (IST)</h2>

            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block font-medium">Effective Range:</span>
                    <span class="font-bold text-slate-700 block mt-0.5">
                        @istDate($policy->effective_from)
                        →
                        {{ $policy->effective_until ? \App\Support\DateTimeHelper::formatDate($policy->effective_until) : 'Indefinite' }}
                    </span>
                </div>

                <div class="border-t border-slate-100 pt-3">
                    <span class="text-slate-400 block font-medium">Created On:</span>
                    <span class="font-semibold text-slate-700 block mt-0.5">@istDateTime($policy->created_at)</span>
                    @if($policy->creator)
                        <span class="text-[11px] text-slate-400 block">By {{ $policy->creator->name }}</span>
                    @endif
                </div>

                @if($policy->updated_by)
                <div class="border-t border-slate-100 pt-3">
                    <span class="text-slate-400 block font-medium">Last Modified:</span>
                    <span class="font-semibold text-slate-700 block mt-0.5">@istDateTime($policy->updated_at)</span>
                    @if($policy->updater)
                        <span class="text-[11px] text-slate-400 block">By {{ $policy->updater->name }}</span>
                    @endif
                </div>
                @endif
            </div>

            <div class="pt-4 border-t border-slate-100">
                <a href="{{ route('admin.settings.commission-policies.index') }}"
                   class="block text-center w-full py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
                    ← Back to All Policies
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
