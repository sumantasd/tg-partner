@extends('layouts.app')

@section('title', 'My Projects — Partner Portal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Active Projects Portfolio</h1>
            <p class="text-xs text-slate-500 mt-0.5">Projects created for your converted client accounts with real-time status and commission breakdown.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Project Code</th>
                        <th class="px-5 py-3.5">Project Title & Customer</th>
                        <th class="px-5 py-3.5">Contract Value</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Commission Earned</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($projects as $project)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('partner.projects.show', $project) }}" class="hover:underline">
                                    {{ $project->project_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $project->project_title }}</div>
                                <div class="text-[11px] text-slate-500">
                                    Customer: <strong class="text-slate-700">{{ $project->customer->name ?? $project->client_name }}</strong>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-dark">
                                ₹{{ number_format($project->final_deal_value, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $project->status->badgeClasses() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                @php
                                    $commTotal = $project->commissions->sum('amount');
                                @endphp
                                <span class="font-bold text-tg-green text-sm">₹{{ number_format($commTotal, 2) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                No projects recorded for your account yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($projects->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $projects->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
