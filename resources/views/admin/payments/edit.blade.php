@extends('layouts.app')

@section('title', 'Edit Payment — ' . $payment->payment_number)

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.payments.index') }}" class="hover:text-tg-purple">Payments</a>
        <span>/</span>
        <a href="{{ route('admin.payments.show', $payment) }}" class="hover:text-tg-purple font-mono font-bold">{{ $payment->payment_number }}</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Edit</span>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-tg-dark">Edit Unverified Payment Details</h1>
        <p class="text-xs text-slate-500 mt-1">Adjust payment method, amount, date, or attach a revised proof file for #{{ $payment->payment_number }}.</p>

        <form action="{{ route('admin.payments.update', $payment) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Stage / Type *</label>
                    <select name="payment_type" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @foreach(\App\Enums\CustomerPaymentType::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('payment_type', $payment->payment_type->value) === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Method *</label>
                    <select name="payment_method" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @foreach(\App\Enums\PaymentMethod::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('payment_method', $payment->payment_method) === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Amount (INR) *</label>
                    <input type="number" step="0.01" name="amount" required value="{{ old('amount', $payment->amount) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Received Date *</label>
                    <input type="date" name="payment_date" required value="{{ old('payment_date', $payment->payment_date->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Transaction ID / Bank UTR / Receipt Ref</label>
                    <input type="text" name="transaction_reference" value="{{ old('transaction_reference', $payment->transaction_reference) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Replace Payment Proof Attachment (PDF, JPG, PNG)</label>
                    <input type="file" name="proof_attachment" accept=".jpeg,.jpg,.png,.pdf"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs bg-slate-50 focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Payment Notes</label>
                    <textarea name="notes" rows="2"
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('notes', $payment->notes) }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.payments.show', $payment) }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
