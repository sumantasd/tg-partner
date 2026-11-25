@extends('layouts.app')

@section('title', 'Partner Dossier — ' . $partner->partner_code)

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Actions Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3 text-xs text-slate-500">
            <a href="{{ route('admin.partners.index') }}" class="hover:text-tg-purple font-medium">Partners</a>
            <span>/</span>
            <span class="font-mono text-tg-purple font-bold">{{ $partner->partner_code }}</span>
        </div>
        <div class="flex items-center flex-wrap gap-2.5">
            <a href="{{ route('admin.partners.edit', $partner) }}"
               class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
                <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                Edit Partner
            </a>

            @if($partner->user->isPending())
                <form action="{{ route('admin.partners.approve', $partner) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-green hover:bg-tg-green/90 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Approve Partner
                    </button>
                </form>

                <button type="button" onclick="document.getElementById('reject-modal').classList.remove('hidden')"
                        class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-tg-pink bg-tg-pink-light border border-tg-pink-border hover:bg-tg-pink hover:text-white transition">
                    Reject Application
                </button>
            @elseif($partner->user->isActive())
                <form action="{{ route('admin.partners.suspend', $partner) }}" method="POST" class="inline" onsubmit="return confirm('Suspend this partner account? Access will be temporarily revoked.');">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition">
                        Suspend Account
                    </button>
                </form>
            @elseif($partner->user->isSuspended())
                <form action="{{ route('admin.partners.activate', $partner) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-green hover:bg-tg-green/90 shadow-sm transition">
                        Re-activate Account
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Partner Header Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-start justify-between gap-6 pb-6 border-b border-slate-100">
            <div class="flex items-start space-x-4">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-tg-purple to-tg-pink text-white font-extrabold text-2xl flex items-center justify-center flex-shrink-0 shadow-sm">
                    {{ strtoupper(substr($partner->company_name ?? $partner->user->name, 0, 2)) }}
                </div>
                <div>
                    <div class="flex items-center space-x-3 flex-wrap gap-y-1">
                        <h1 class="text-2xl font-extrabold text-tg-dark tracking-tight">{{ $partner->company_name }}</h1>
                        <span class="font-mono text-xs font-bold px-2.5 py-1 rounded-lg bg-tg-purple-light text-tg-purple border border-tg-purple-border">
                            {{ $partner->partner_code }}
                        </span>
                        @if($partner->user->isActive())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-tg-green-light text-tg-green border border-tg-green-border">
                                Active Partner
                            </span>
                        @elseif($partner->user->isPending())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Pending Approval
                            </span>
                        @elseif($partner->user->isSuspended())
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-tg-pink-light text-tg-pink border border-tg-pink-border">
                                Suspended
                            </span>
                        @endif
                    </div>
                    <p class="text-xs text-slate-500 mt-1 flex items-center gap-3 flex-wrap">
                        <span><strong>Contact:</strong> {{ $partner->user->name }}</span>
                        <span>•</span>
                        <span><strong>Email:</strong> {{ $partner->user->email }}</span>
                        <span>•</span>
                        <span><strong>Phone:</strong> {{ $partner->phone }}</span>
                        @if($partner->city)
                            <span>•</span>
                            <span><strong>Location:</strong> {{ $partner->city }}{{ $partner->state ? ', ' . $partner->state : '' }}</span>
                        @endif
                    </p>
                </div>
            </div>

            <div class="text-right flex-shrink-0 bg-slate-50 p-4 rounded-2xl border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Default Commission</div>
                <div class="text-2xl font-extrabold text-tg-purple mt-0.5">{{ number_format($partner->default_commission_rate, 1) }}%</div>
                <div class="text-[11px] text-slate-500 mt-1">Applied to eligible projects</div>
            </div>
        </div>

        <!-- Meta info grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 text-xs text-slate-600">
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Onboarded At</span>
                <span class="font-semibold text-tg-dark">{{ $partner->created_at->format('d M Y, h:i A') }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Onboarded By</span>
                <span class="font-semibold text-tg-dark">{{ $partner->creator ? $partner->creator->name : 'Self Registered' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Approval Date</span>
                <span class="font-semibold text-tg-dark">{{ $partner->approved_at ? $partner->approved_at->format('d M Y') : 'Pending' }}</span>
            </div>
            <div>
                <span class="text-slate-400 block text-[10px] uppercase font-bold">Approved By</span>
                <span class="font-semibold text-tg-dark">{{ $partner->approver ? $partner->approver->name : 'N/A' }}</span>
            </div>
        </div>

        @if($partner->internal_notes)
            <div class="mt-4 p-3.5 bg-slate-50 rounded-xl border border-slate-200/60 text-xs">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500 block mb-1">Internal Notes</span>
                <p class="text-slate-700 whitespace-pre-line">{{ $partner->internal_notes }}</p>
            </div>
        @endif
    </div>

    <!-- Partner Agreement Status Card -->
    @php $latestAgreement = $partner->latestAgreement; @endphp
    <div class="rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-10 h-10 rounded-xl bg-tg-purple/10 text-tg-purple flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
                <div class="flex items-center space-x-2">
                    <span class="text-xs font-bold text-slate-800">Partner Agreement:</span>
                    @if ($latestAgreement)
                        <span class="font-mono text-xs font-bold text-tg-purple">{{ $latestAgreement->agreement_number }}</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold border {{ $latestAgreement->status->badgeClass() }}">
                            {{ $latestAgreement->status->label() }}
                        </span>
                    @else
                        <span class="text-xs text-slate-400">Not Issued Yet</span>
                    @endif
                </div>
                <div class="text-[11px] text-slate-500 mt-0.5">
                    @if ($latestAgreement)
                        Rate: {{ number_format($latestAgreement->commission_rate_snapshot, 2) }}% &bull;
                        Effective: {{ $latestAgreement->effective_date->format('d M Y') }}
                        @if ($latestAgreement->isAccepted())
                            &bull; <span class="text-emerald-700 font-semibold">Accepted {{ $latestAgreement->accepted_at?->format('d M Y') }}</span>
                        @endif
                    @else
                        Generate and issue a formal business partner agreement for this partner.
                    @endif
                </div>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            @if ($latestAgreement)
                <a href="{{ route('admin.agreements.preview', $latestAgreement) }}" target="_blank"
                   class="px-3 py-1.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                    Preview PDF
                </a>
                <a href="{{ route('admin.agreements.show', $latestAgreement) }}"
                   class="px-3.5 py-1.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition">
                    View Agreement
                </a>
            @else
                <form action="{{ route('admin.partners.issue-agreement', $partner) }}" method="POST">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover transition shadow-sm">
                        Issue Agreement Now
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Financial & Operational KPI Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Leads</div>
            <div class="text-xl font-bold text-tg-dark mt-1">{{ number_format($stats['total_leads']) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Won Leads</div>
            <div class="text-xl font-bold text-tg-green mt-1">{{ number_format($stats['won_leads']) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Customers</div>
            <div class="text-xl font-bold text-tg-purple mt-1">{{ number_format($stats['customers_count']) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Projects</div>
            <div class="text-xl font-bold text-tg-dark mt-1">{{ number_format($stats['projects_count']) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Earned (₹)</div>
            <div class="text-xl font-bold text-tg-purple mt-1">₹{{ number_format($stats['earned_comm'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Paid (₹)</div>
            <div class="text-xl font-bold text-tg-green mt-1">₹{{ number_format($stats['paid_comm'], 2) }}</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Payable (₹)</div>
            <div class="text-xl font-bold text-amber-600 mt-1">₹{{ number_format($stats['payable_comm'], 2) }}</div>
        </div>
    </div>

    <!-- Detailed Tabs / Data Tables -->
    <div class="space-y-6">
        <!-- Referred Customers Table -->
        <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-tg-dark">Attributed Customers</h2>
                    <p class="text-xs text-slate-500">Customers referred by this partner whose attribution is permanently locked.</p>
                </div>
                <span class="text-xs font-bold text-tg-purple bg-tg-purple-light px-2.5 py-1 rounded-lg">
                    {{ $referredCustomers->count() }} Customers
                </span>
            </div>
            @if($referredCustomers->isEmpty())
                <div class="p-8 text-center text-xs text-slate-400">No customers directly referred or converted yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="p-4">Customer Code</th>
                                <th class="p-4">Customer Name</th>
                                <th class="p-4">Company</th>
                                <th class="p-4">Phone</th>
                                <th class="p-4">Status</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($referredCustomers as $customer)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-4 font-mono font-bold text-tg-purple">{{ $customer->customer_code }}</td>
                                    <td class="p-4 font-semibold text-tg-dark">{{ $customer->name }}</td>
                                    <td class="p-4">{{ $customer->company_name ?? '—' }}</td>
                                    <td class="p-4">{{ $customer->phone }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $customer->status === 'active' ? 'bg-tg-green-light text-tg-green' : 'bg-slate-100 text-slate-600' }}">
                                            {{ ucfirst($customer->status) }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="text-tg-purple hover:underline font-bold">View Dossier</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Recent Submitted Leads -->
        <div class="rounded-3xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-tg-dark">Recent Submitted Leads</h2>
                    <p class="text-xs text-slate-500">Most recent leads submitted by this partner.</p>
                </div>
                <a href="{{ route('admin.leads.index', ['search' => $partner->partner_code]) }}" class="text-xs font-bold text-tg-purple hover:underline">
                    View All Leads &rarr;
                </a>
            </div>
            @if($partner->leads->isEmpty())
                <div class="p-8 text-center text-xs text-slate-400">No leads submitted yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                                <th class="p-4">Lead #</th>
                                <th class="p-4">Customer</th>
                                <th class="p-4">Phone</th>
                                <th class="p-4">Service</th>
                                <th class="p-4">Status</th>
                                <th class="p-4">Submitted</th>
                                <th class="p-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach($partner->leads as $lead)
                                <tr class="hover:bg-slate-50/50">
                                    <td class="p-4 font-mono font-bold text-tg-purple">{{ $lead->lead_number }}</td>
                                    <td class="p-4 font-semibold text-tg-dark">{{ $lead->customer_name }}</td>
                                    <td class="p-4">{{ $lead->customer_phone }}</td>
                                    <td class="p-4">{{ $lead->service_required ?? '—' }}</td>
                                    <td class="p-4">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                                            {{ $lead->status->label() }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-slate-500">{{ $lead->created_at->format('d M Y') }}</td>
                                    <td class="p-4 text-right">
                                        <a href="{{ route('admin.leads.show', $lead) }}" class="text-tg-purple hover:underline font-bold">Inspect</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Reject Reason Modal -->
<div id="reject-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
        <h3 class="text-lg font-bold text-tg-dark">Reject Partner Application</h3>
        <p class="text-xs text-slate-500 mt-1">Provide a reason for rejection. This will be recorded for audit purposes.</p>

        <form action="{{ route('admin.partners.reject', $partner) }}" method="POST" class="mt-4 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Rejection Reason</label>
                <textarea name="reason" rows="3" required placeholder="Explain why this application was not approved..."
                          class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple"></textarea>
            </div>
            <div class="flex items-center justify-end space-x-3">
                <button type="button" onclick="document.getElementById('reject-modal').classList.add('hidden')"
                        class="px-4 py-2 rounded-xl text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-tg-pink hover:bg-tg-pink/90">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
