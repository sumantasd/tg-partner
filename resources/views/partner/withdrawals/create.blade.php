@extends('layouts.app')

@section('title', 'Request Commission Withdrawal — Partner Portal')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 w-full min-w-0">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('partner.withdrawals.index') }}" class="text-xs font-bold text-tg-purple hover:underline">&larr; Back to Withdrawals</a>
            </div>
            <h1 class="text-2xl font-bold text-tg-dark tracking-tight mt-1">Request Commission Withdrawal</h1>
            <p class="text-xs text-slate-500 mt-0.5">Submit an official request to disburse your verified commission earnings to your bank account or UPI.</p>
        </div>
    </div>

    <!-- Compliance Gate Warning if KYC / Bank is not fully verified -->
    @if (! ($balanceSummary['is_fully_verified'] ?? false))
        <div class="rounded-2xl bg-rose-50 border border-rose-200 p-5 shadow-sm">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-rose-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-rose-900">Compliance Verification Required</h3>
                    <p class="text-xs text-rose-800 mt-1">
                        Commission withdrawals cannot be processed until both your KYC identity documents and bank account verification have been officially approved by Tech Googly compliance administrators.
                    </p>
                    <div class="mt-3">
                        <a href="{{ route('partner.kyc.show') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-rose-600 text-white font-bold text-xs hover:bg-rose-700 transition shadow-sm">
                            Complete KYC &amp; Bank Verification &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Payout Destination Warning if Incomplete -->
    @if (! $hasPayoutDetails)
        <div class="rounded-2xl bg-amber-50 border border-amber-200 p-5 shadow-sm">
            <div class="flex items-start space-x-3">
                <svg class="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-amber-900">Payout Destination Not Configured</h3>
                    <p class="text-xs text-amber-800 mt-1">You must configure your bank account or UPI ID details before submitting a withdrawal request.</p>
                    <div class="mt-3">
                        <a href="{{ route('partner.payout-settings.edit') }}" class="inline-flex items-center px-4 py-2 rounded-xl bg-amber-600 text-white font-bold text-xs hover:bg-amber-700 transition shadow-sm">
                            Configure Bank &amp; UPI Details &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- 4 Live Calculation Preview Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <!-- 1. Available Balance -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Available Balance</div>
            <div class="text-lg sm:text-xl font-extrabold text-tg-purple mt-1" id="display-available">
                ₹{{ number_format($balanceSummary['available_balance'], 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Eligible verified earnings</div>
        </div>

        <!-- 2. Requested Amount -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Requested Amount</div>
            <div class="text-lg sm:text-xl font-extrabold text-tg-dark mt-1" id="display-requested">
                ₹{{ number_format(min($balanceSummary['available_balance'], old('amount', $balanceSummary['available_balance'])), 2) }}
            </div>
            <div class="text-[10px] text-slate-400 mt-0.5">Amount to disburse</div>
        </div>

        <!-- 3. Minimum Withdrawal -->
        <div class="rounded-2xl bg-white border border-slate-200/80 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400">Minimum Withdrawal</div>
            <div class="text-lg sm:text-xl font-bold text-slate-700 mt-1">₹500.00</div>
            <div class="text-[10px] text-slate-400 mt-0.5">System threshold</div>
        </div>

        <!-- 4. Remaining Balance After Request -->
        <div class="rounded-2xl bg-emerald-50/70 border border-emerald-200 p-4 shadow-sm">
            <div class="text-[10px] uppercase font-bold tracking-wider text-emerald-800">Remaining Balance</div>
            <div class="text-lg sm:text-xl font-extrabold text-emerald-900 mt-1" id="display-remaining">
                ₹{{ number_format(max(0, $balanceSummary['available_balance'] - min($balanceSummary['available_balance'], old('amount', $balanceSummary['available_balance']))), 2) }}
            </div>
            <div class="text-[10px] text-emerald-700 font-semibold mt-0.5">After this request</div>
        </div>
    </div>

    <!-- Main Request Form -->
    <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
        <form method="POST" action="{{ route('partner.withdrawals.store') }}" id="withdrawal-form" class="space-y-6">
            @csrf

            <!-- 1. Amount Field -->
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Withdrawal Amount (₹ INR) <span class="text-tg-pink">*</span>
                </label>
                <div class="relative max-w-md">
                    <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-sm font-bold text-slate-400">₹</span>
                    <input type="number" step="0.01" min="500" max="{{ $balanceSummary['available_balance'] }}"
                           name="amount" id="input-amount"
                           value="{{ old('amount', $balanceSummary['available_balance']) }}"
                           required
                           class="w-full pl-8 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">
                </div>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <span class="text-[11px] text-slate-400">Quick set:</span>
                    @if ($balanceSummary['available_balance'] >= 500)
                        <button type="button" onclick="setAmount(500)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700 transition">
                            ₹500 (Min)
                        </button>
                    @endif
                    @if ($balanceSummary['available_balance'] >= 1000)
                        <button type="button" onclick="setAmount(1000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700 transition">
                            ₹1,000
                        </button>
                    @endif
                    @if ($balanceSummary['available_balance'] >= 5000)
                        <button type="button" onclick="setAmount(5000)" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[11px] font-bold text-slate-700 transition">
                            ₹5,000
                        </button>
                    @endif
                    <button type="button" onclick="setAmount({{ $balanceSummary['available_balance'] }})" class="px-2.5 py-1 rounded-lg bg-tg-purple-light text-tg-purple hover:bg-tg-purple hover:text-white text-[11px] font-bold transition">
                        Full Balance (₹{{ number_format($balanceSummary['available_balance'], 2) }})
                    </button>
                </div>
                @error('amount')
                    <p class="text-xs text-tg-pink font-semibold mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <!-- 2. Payout Destination & Method Selection -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-2">
                    Payout Destination <span class="text-tg-pink">*</span>
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-2xl">
                    <!-- Bank Transfer Option -->
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition {{ old('payout_method', $partner->payout_method) === 'bank_transfer' ? 'border-tg-purple bg-tg-purple-light/40 ring-1 ring-tg-purple' : 'border-slate-200 hover:bg-slate-50' }}">
                        <div class="flex items-center space-x-3">
                            <input type="radio" name="payout_method" value="bank_transfer"
                                   {{ old('payout_method', $partner->payout_method) === 'bank_transfer' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple" onchange="updateMethodNotice(this.value)">
                            <div>
                                <span class="text-xs font-bold text-tg-dark block">Direct Bank Transfer (NEFT/RTGS/IMPS)</span>
                                <span class="text-[11px] text-slate-500 block">
                                    {{ $partner->bank_name ?: 'Bank not configured' }} &bull; {{ $partner->masked_account_number }}
                                </span>
                            </div>
                        </div>
                    </label>

                    <!-- UPI Option -->
                    <label class="relative flex flex-col p-4 rounded-2xl border cursor-pointer transition {{ old('payout_method', $partner->payout_method) === 'upi' ? 'border-tg-purple bg-tg-purple-light/40 ring-1 ring-tg-purple' : 'border-slate-200 hover:bg-slate-50' }}">
                        <div class="flex items-center space-x-3">
                            <input type="radio" name="payout_method" value="upi"
                                   {{ old('payout_method', $partner->payout_method) === 'upi' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple" onchange="updateMethodNotice(this.value)">
                            <div>
                                <span class="text-xs font-bold text-tg-dark block">UPI Instant Payout</span>
                                <span class="text-[11px] text-slate-500 block">
                                    {{ $partner->upi_id ?: 'UPI not configured' }}
                                </span>
                            </div>
                        </div>
                    </label>
                </div>

                <div class="mt-2 text-xs text-slate-500 flex items-center justify-between max-w-2xl">
                    <span>Destination details are frozen into an immutable historical snapshot at submission.</span>
                    <a href="{{ route('partner.payout-settings.edit') }}" class="text-tg-purple font-semibold hover:underline">
                        Edit Destination &rarr;
                    </a>
                </div>
            </div>

            <!-- 3. Optional Partner Note -->
            <div class="pt-2 border-t border-slate-100">
                <label class="block text-xs font-bold text-slate-700 mb-1">
                    Optional Note to Finance / Admin
                </label>
                <textarea name="partner_notes" rows="2" placeholder="Any specific instruction or internal reference for our accounts team..."
                          class="w-full px-4 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple outline-none">{{ old('partner_notes') }}</textarea>
            </div>

            <!-- 4. Eligible Commissions Breakdown -->
            @if ($eligibleCommissions->isNotEmpty())
                <div class="pt-2 border-t border-slate-100">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold text-slate-700">
                            Available Commissions Breakdown ({{ $eligibleCommissions->count() }})
                        </label>
                        <span class="text-[11px] text-slate-400">Allocated in First-In First-Out (FIFO) order</span>
                    </div>

                    <div class="rounded-2xl border border-slate-200 overflow-hidden text-xs">
                        <table class="min-w-full divide-y divide-slate-100">
                            <thead class="bg-slate-50 text-[10px] uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Commission #</th>
                                    <th class="px-4 py-2.5 text-left">Project</th>
                                    <th class="px-4 py-2.5 text-left">Milestone</th>
                                    <th class="px-4 py-2.5 text-right">Amount</th>
                                    <th class="px-4 py-2.5 text-right">Earned Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-600">
                                @foreach($eligibleCommissions as $ec)
                                    <tr class="hover:bg-slate-50/50">
                                        <td class="px-4 py-2 font-mono font-bold text-tg-purple">{{ $ec->commission_number }}</td>
                                        <td class="px-4 py-2">{{ $ec->project->project_title ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-slate-500">{{ $ec->milestone_name }}</td>
                                        <td class="px-4 py-2 text-right font-bold text-tg-green">₹{{ number_format($ec->amount, 2) }}</td>
                                        <td class="px-4 py-2 text-right text-slate-400">{{ $ec->created_at->format('M d, Y') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Submit Buttons -->
            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                <a href="{{ route('partner.withdrawals.index') }}" class="px-4 py-2.5 text-xs font-semibold text-slate-600 hover:text-tg-dark transition">
                    Cancel
                </a>
                <div class="flex items-center space-x-3">
                    @if (! ($balanceSummary['is_fully_verified'] ?? false))
                        <span class="text-xs text-rose-600 font-semibold">KYC / Bank verification required</span>
                    @endif
                    <button type="submit"
                            {{ ! $hasPayoutDetails || $balanceSummary['available_balance'] < 500 || ! ($balanceSummary['is_fully_verified'] ?? false) ? 'disabled' : '' }}
                            class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-sm transition disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed">
                        Submit Withdrawal Request &rarr;
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    const availableBalance = {{ (float) $balanceSummary['available_balance'] }};
    const inputAmount = document.getElementById('input-amount');
    const displayRequested = document.getElementById('display-requested');
    const displayRemaining = document.getElementById('display-remaining');

    function updateCalculations() {
        let val = parseFloat(inputAmount.value) || 0;
        if (val < 0) val = 0;
        let remaining = Math.max(0, availableBalance - val);

        displayRequested.textContent = '₹' + val.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        displayRemaining.textContent = '₹' + remaining.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function setAmount(amount) {
        inputAmount.value = amount.toFixed(2);
        updateCalculations();
    }

    inputAmount.addEventListener('input', updateCalculations);
</script>
@endsection
