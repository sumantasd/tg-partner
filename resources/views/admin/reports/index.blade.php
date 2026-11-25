@extends('layouts.app')

@section('title', 'Reports & Analytics — Admin Control')

@section('content')
<div class="space-y-6 w-full max-w-full min-w-0">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Executive Reports & Analytics</h1>
            <p class="text-xs text-slate-500 mt-0.5">High-level financial performance, partner attribution, and pipeline conversion analytics.</p>
        </div>
    </div>

    <!-- Executive Metrics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Verified Collections</div>
            <div class="text-2xl font-bold text-tg-purple mt-1">₹{{ number_format($metrics['total_revenue'], 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">From all cleared client payments</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Total Partner Commissions</div>
            <div class="text-2xl font-bold text-tg-pink mt-1">₹{{ number_format($metrics['total_commission'], 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Authoritative liability earned</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Net Retained Margin</div>
            <div class="text-2xl font-bold text-tg-green mt-1">₹{{ number_format($metrics['net_margin'], 2) }}</div>
            <div class="text-[11px] text-slate-400 mt-1">Collections less partner commissions</div>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Pipeline Conversion Rate</div>
            <div class="text-2xl font-bold text-tg-blue mt-1">{{ $metrics['conversion_rate'] }}%</div>
            <div class="text-[11px] text-slate-400 mt-1">{{ $metrics['won_leads'] }} won of {{ $metrics['total_leads'] }} leads</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Performing Referral Partners -->
        <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden p-6">
            <h2 class="text-base font-bold text-tg-dark">Top Performing Partners</h2>
            <p class="text-xs text-slate-500 mt-0.5">Ranked by total commission earnings generated through referrals.</p>

            <div class="mt-4 divide-y divide-slate-100">
                @forelse($topPartners as $partner)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-3">
                            <div class="w-8 h-8 rounded-xl bg-tg-purple-light text-tg-purple font-bold flex items-center justify-center text-xs">
                                {{ strtoupper(substr($partner->company_name, 0, 2)) }}
                            </div>
                            <div>
                                <a href="{{ route('admin.partners.show', $partner) }}" class="font-bold text-tg-dark hover:text-tg-purple">
                                    {{ $partner->company_name }}
                                </a>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $partner->partner_code }} &bull; {{ $partner->leads_count }} leads</div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-tg-purple">₹{{ number_format($partner->commissions_sum_amount ?? 0, 2) }}</div>
                            <div class="text-[10px] text-slate-400">{{ $partner->projects_count }} projects</div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400">No partner activity recorded yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Service Category Demand -->
        <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden p-6">
            <h2 class="text-base font-bold text-tg-dark">Service Categories Breakdown</h2>
            <p class="text-xs text-slate-500 mt-0.5">Demand distribution across requested service domains.</p>

            <div class="mt-4 space-y-3">
                @php
                    $colors = ['bg-tg-purple', 'bg-tg-pink', 'bg-tg-blue', 'bg-tg-green', 'bg-slate-500'];
                    $colorIndex = 0;
                @endphp
                @forelse($serviceBreakdown as $category => $count)
                    @php
                        $pct = $metrics['total_leads'] > 0 ? round(($count / $metrics['total_leads']) * 100, 1) : 0;
                        $colorClass = $colors[$colorIndex % count($colors)];
                        $colorIndex++;
                    @endphp
                    <div>
                        <div class="flex justify-between text-xs font-semibold mb-1">
                            <span class="text-tg-dark">{{ $category }}</span>
                            <span class="text-slate-500">{{ $count }} leads ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2">
                            <div class="{{ $colorClass }} h-2 rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400">No service categories logged yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
