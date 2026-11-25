@extends('layouts.app')

@section('title', 'Record Customer Payment — Admin Portal')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.payments.index') }}" class="hover:text-tg-purple">Payments</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Record Customer Payment</span>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-tg-dark">Record Customer Payment</h1>
        <p class="text-xs text-slate-500 mt-1">
            New payments are logged in <strong>Pending Verification</strong> state. Once verified, partner commission is calculated and generated automatically.
        </p>

        <form action="{{ route('admin.payments.store') }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Project *</label>
                    <select name="project_id" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        <option value="">Select Target Project</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}" {{ (old('project_id', $selectedProject?->id) == $p->id) ? 'selected' : '' }}>
                                {{ $p->project_code }} — {{ $p->project_title }} (Customer: {{ $p->customer->name ?? $p->client_name }})
                            </option>
                        @endforeach
                    </select>
                    @error('project_id') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Stage / Type *</label>
                    <select name="payment_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @foreach(\App\Enums\CustomerPaymentType::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('payment_type', 'advance') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @foreach(\App\Enums\PaymentMethod::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('payment_method', 'bank_transfer') === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Amount (INR) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount') }}" placeholder="e.g. 50000.00"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('amount') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Received Date *</label>
                    <input type="date" name="payment_date" required value="{{ old('payment_date', date('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    @error('payment_date') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Transaction ID / Bank UTR / Receipt Ref</label>
                    <input type="text" name="transaction_reference" value="{{ old('transaction_reference') }}" placeholder="e.g. UTR123456789, Cheque #000123"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Proof Attachment (PDF, JPG, PNG — max 5MB)</label>
                    <input type="file" name="proof_attachment" accept=".jpeg,.jpg,.png,.pdf"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                    <span class="text-[10px] text-slate-400 mt-1 block">Stored securely in non-public storage. Only authorized admins can download.</span>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Notes / Description</label>
                    <textarea name="notes" rows="2" placeholder="e.g. 50% advance for milestone 1..."
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.payments.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Save Payment Record &rarr;
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
