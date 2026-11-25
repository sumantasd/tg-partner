@extends('layouts.app')

@section('title', 'Customer Profile — ' . $customer->customer_code)

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.customers.index') }}" class="hover:text-tg-purple">Customers</a>
            <span>/</span>
            <span class="font-mono font-bold text-tg-purple">{{ $customer->customer_code }}</span>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('admin.customers.edit', $customer) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs hover:bg-slate-200 transition">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Customer
            </a>

            <a href="{{ route('admin.projects.create', ['customer_id' => $customer->id]) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Create New Project
            </a>
        </div>
    </div>

    <!-- Customer Overview Card -->
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-6">
            <div>
                <div class="flex items-center space-x-3">
                    <h1 class="text-2xl font-bold text-tg-dark tracking-tight">{{ $customer->name }}</h1>
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
                <p class="text-xs text-slate-400 mt-2">
                    Customer created {{ $customer->created_at->format('M d, Y h:i A') }}
                    @if($customer->creator) &bull; Converted by {{ $customer->creator->name }} @endif
                </p>
            </div>

            <!-- Partner Attribution Card -->
            <div class="bg-slate-50 rounded-2xl border border-slate-200 p-4 min-w-[220px]">
                <div class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Original Referral Partner</div>
                @if($customer->firstPartner)
                    <div class="font-bold text-sm text-tg-dark mt-1">{{ $customer->firstPartner->company_name }}</div>
                    <div class="font-mono text-xs font-bold text-tg-purple">{{ $customer->firstPartner->partner_code }}</div>
                    <div class="text-[11px] text-slate-500 mt-1">{{ $customer->firstPartner->phone }}</div>
                @else
                    <div class="text-xs text-slate-400 mt-1">Direct Client (No Partner)</div>
                @endif

                @if($customer->lead)
                    <div class="mt-3 pt-3 border-t border-slate-200 text-xs">
                        <span class="text-slate-400 block text-[10px] uppercase font-bold">Source Won Lead</span>
                        <a href="{{ route('admin.leads.show', $customer->lead) }}" class="font-mono font-bold text-tg-purple hover:underline">
                            {{ $customer->lead->lead_number }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Contact & Address Details Grid -->
        <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Phone Number</span>
                <span class="font-bold text-tg-dark">{{ $customer->phone }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">WhatsApp</span>
                <span class="font-bold text-tg-dark">{{ $customer->whatsapp_number ?? $customer->phone }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Email Address</span>
                <span class="font-bold text-tg-dark">{{ $customer->email ?? '—' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Location</span>
                <span class="font-bold text-tg-dark">{{ implode(', ', array_filter([$customer->city, $customer->state, $customer->country])) ?: '—' }}</span>
            </div>
        </div>

        @if($customer->address_line1)
            <div class="mt-3 text-xs text-slate-600 bg-slate-50 rounded-xl p-3 border border-slate-100">
                <span class="font-bold text-slate-700">Billing Address:</span> {{ $customer->address_line1 }}
            </div>
        @endif
    </div>

    <!-- Linked Projects Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-tg-dark">Customer Projects ({{ $customer->projects->count() }})</h2>
                <p class="text-xs text-slate-500">Every project can have milestone payments and generated partner commissions.</p>
            </div>
            <a href="{{ route('admin.projects.create', ['customer_id' => $customer->id]) }}"
               class="text-xs font-bold text-tg-purple hover:underline">
                + Add Another Project
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Project Code</th>
                        <th class="px-5 py-3.5">Title & Category</th>
                        <th class="px-5 py-3.5">Assigned Staff</th>
                        <th class="px-5 py-3.5">Deal Value</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customer->projects as $project)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('admin.projects.show', $project) }}" class="hover:underline">
                                    {{ $project->project_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $project->project_title }}</div>
                                <div class="text-[11px] text-slate-500">{{ $project->service_category ?? 'General' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                {{ $project->assignedTo?->name ?? 'Unassigned' }}
                            </td>
                            <td class="px-5 py-4 font-bold text-tg-dark">
                                ₹{{ number_format($project->final_deal_value, 2) }}
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-[10px] font-bold border {{ $project->status->badgeClasses() }}">
                                    {{ $project->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('admin.projects.show', $project) }}" class="font-bold text-tg-purple hover:underline">
                                    Manage Project &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                No projects recorded for this customer yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
