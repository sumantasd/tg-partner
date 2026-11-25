@extends('layouts.app')

@section('title', 'Customer Details — ' . $customer->customer_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('partner.customers.index') }}" class="hover:text-tg-purple">My Customers</a>
        <span>/</span>
        <span class="font-mono font-bold text-tg-purple">{{ $customer->customer_code }}</span>
    </div>

    <!-- Customer Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex items-center space-x-3">
            <h1 class="text-2xl font-bold text-tg-dark">{{ $customer->name }}</h1>
            <span class="font-mono px-3 py-1 rounded-md text-xs font-bold bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                {{ $customer->customer_code }}
            </span>
            <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-tg-green-light text-tg-green border border-tg-green-border uppercase">
                {{ $customer->status ?? 'Active' }}
            </span>
        </div>
        @if($customer->company_name)
            <p class="text-sm font-semibold text-slate-600 mt-1">{{ $customer->company_name }}</p>
        @endif

        <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Location</span>
                <span class="font-bold text-tg-dark">{{ $customer->city ?? '—' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Referral Attribution</span>
                <span class="font-bold text-tg-green">Attributed to Your Account</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Active Projects</span>
                <span class="font-bold text-tg-purple">{{ $customer->projects->count() }} Projects</span>
            </div>
        </div>
    </div>

    <!-- Projects Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-tg-dark">Projects for {{ $customer->name }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Project Code</th>
                        <th class="px-5 py-3">Project Title</th>
                        <th class="px-5 py-3">Contract Value</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customer->projects as $project)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 font-mono font-bold text-tg-purple">
                                {{ $project->project_code }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-tg-dark">
                                {{ $project->project_title }}
                            </td>
                            <td class="px-5 py-3.5 font-bold text-slate-700">
                                ₹{{ number_format($project->final_deal_value, 2) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border {{ $project->status->badgeClasses() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('partner.projects.show', $project) }}" class="font-bold text-tg-purple hover:underline">
                                    View Project &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-6 text-center text-slate-400">
                                No active projects for this customer yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
