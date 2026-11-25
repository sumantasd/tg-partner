@extends('layouts.app')

@section('title', 'My Converted Customers — Partner Portal')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">My Referred Customers</h1>
            <p class="text-xs text-slate-500 mt-0.5">Customers converted from your submitted leads. You earn commissions on verified payments from all projects with these clients.</p>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50/75 border-b border-slate-100 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Customer Code</th>
                        <th class="px-5 py-3.5">Client Name & Business</th>
                        <th class="px-5 py-3.5">Location</th>
                        <th class="px-5 py-3.5 text-center">Active Projects</th>
                        <th class="px-5 py-3.5">Account Status</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-4 font-mono font-bold text-tg-purple">
                                <a href="{{ route('partner.customers.show', $customer) }}" class="hover:underline">
                                    {{ $customer->customer_code }}
                                </a>
                            </td>
                            <td class="px-5 py-4">
                                <div class="font-bold text-tg-dark">{{ $customer->name }}</div>
                                @if($customer->company_name)
                                    <div class="text-[11px] text-slate-500">{{ $customer->company_name }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-600">
                                {{ $customer->city ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-tg-purple-light text-tg-purple">
                                    {{ $customer->projects->count() }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-tg-green-light text-tg-green border border-tg-green-border uppercase">
                                    {{ $customer->status ?? 'Active' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <a href="{{ route('partner.customers.show', $customer) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-tg-purple hover:text-white font-bold text-xs text-slate-700 transition">
                                    View Projects &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No converted customers yet. When submitted leads are marked Won by Tech Googly, your customers will appear here!
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
