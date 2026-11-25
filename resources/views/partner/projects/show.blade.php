@extends('layouts.app')

@section('title', 'Project Details — ' . $project->project_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('partner.projects.index') }}" class="hover:text-tg-purple">My Projects</a>
        <span>/</span>
        <span class="font-mono font-bold text-tg-purple">{{ $project->project_code }}</span>
    </div>

    <!-- Project Overview -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold text-tg-dark">{{ $project->project_title }}</h1>
            <span class="font-mono px-3 py-1 rounded-md text-xs font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                {{ $project->project_code }}
            </span>
            <span class="px-2.5 py-0.5 rounded text-xs font-bold border {{ $project->status->badgeClasses() }}">
                {{ $project->status->label() }}
            </span>
        </div>

        <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Customer</span>
                <span class="font-bold text-tg-dark">{{ $project->customer->name ?? $project->client_name }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Deal Value</span>
                <span class="font-bold text-tg-dark">₹{{ number_format($project->final_deal_value, 2) }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Total Commission Earned</span>
                <span class="font-bold text-tg-green text-sm">₹{{ number_format($project->commissions->sum('amount'), 2) }}</span>
            </div>
        </div>
    </div>

    <!-- Commissions Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-tg-dark">Commissions for this Project</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Commission #</th>
                        <th class="px-5 py-3">Rate Applied</th>
                        <th class="px-5 py-3">Calculation Basis</th>
                        <th class="px-5 py-3">Amount Earned</th>
                        <th class="px-5 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($project->commissions as $comm)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-tg-purple">
                                {{ $comm->commission_number }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-tg-purple">
                                {{ $comm->rate_applied }}%
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                ₹{{ number_format($comm->calculation_basis, 2) }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-tg-green">
                                ₹{{ number_format($comm->amount, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                                    {{ $comm->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-slate-400">
                                No commissions generated yet for this project.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
