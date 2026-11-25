@extends('layouts.app')

@section('title', 'Customers — Admin Portal')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Customer Management</h1>
            <p class="text-xs text-slate-500 mt-0.5">Manage customer accounts, project history and permanent partner referral attributions.</p>
        </div>
        <a href="{{ route('admin.customers.create') }}"
           class="inline-flex items-center px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Add New Customer
        </a>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Customers</div>
            <div class="text-2xl font-bold text-tg-dark mt-1">{{ number_format($metrics['total_customers']) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Active Accounts</div>
            <div class="text-2xl font-bold text-tg-green mt-1">{{ number_format($metrics['active_customers']) }}</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">With Active Projects</div>
            <div class="text-2xl font-bold text-tg-purple mt-1">{{ number_format($metrics['with_projects']) }}</div>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm">
        <form method="GET" action="{{ route('admin.customers.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, code, phone, email..."
                       class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
            </div>

            <div>
                <select name="partner_id" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <option value="">All Referral Partners</option>
                    @foreach($partners as $partner)
                        <option value="{{ $partner->id }}" {{ $partnerId == $partner->id ? 'selected' : '' }}>
                            {{ $partner->company_name }} ({{ $partner->partner_code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center space-x-2">
                <button type="submit" class="px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                    Filter
                </button>
                @if($search || $partnerId)
                    <a href="{{ route('admin.customers.index') }}" class="px-3 py-2 rounded-xl text-xs text-slate-500 hover:text-tg-pink font-semibold">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3.5">Customer Code</th>
                        <th class="px-4 py-3.5">Customer Name & Business</th>
                        <th class="px-4 py-3.5">Contact Details</th>
                        <th class="px-4 py-3.5">Referred By</th>
                        <th class="px-4 py-3.5 text-center">Projects</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-4 py-3.5 font-mono font-bold text-tg-purple whitespace-nowrap">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="hover:underline">
                                    {{ $customer->customer_code }}
                                </a>
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="font-bold text-tg-dark">{{ $customer->name }}</div>
                                @if($customer->company_name)
                                    <div class="text-[11px] text-slate-500">{{ $customer->company_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                <div class="text-slate-700">{{ $customer->phone }}</div>
                                @if($customer->email)
                                    <div class="text-[11px] text-slate-500">{{ $customer->email }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3.5">
                                @if($customer->firstPartner)
                                    <div class="font-bold text-tg-dark">{{ $customer->firstPartner->company_name }}</div>
                                    <div class="font-mono text-[10px] text-slate-400">{{ $customer->firstPartner->partner_code }}</div>
                                @else
                                    <span class="text-slate-400">Direct / No Partner</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700">
                                    {{ $customer->projects->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $customer->status === 'active' ? 'bg-tg-green-light text-tg-green border border-tg-green-border' : 'bg-slate-100 text-slate-600' }} uppercase">
                                    {{ $customer->status ?? 'Active' }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1.5">
                                <a href="{{ route('admin.customers.show', $customer) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                    View
                                </a>
                                <a href="{{ route('admin.customers.edit', $customer) }}" class="inline-flex items-center px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 font-bold text-xs text-slate-700 transition">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-bold text-tg-dark">No customers found</p>
                                <p class="text-xs text-slate-500 mt-1">Convert a won deal or create a customer directly.</p>
                                <div class="mt-4">
                                    <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center px-4 py-2 bg-tg-purple text-white text-xs font-bold rounded-xl shadow-sm">
                                        Add First Customer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
