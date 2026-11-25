@extends('layouts.app')

@section('title', 'Raise Ownership Dispute')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <!-- Header -->
    <div>
        <a href="{{ route('partner.disputes.index') }}" class="inline-flex items-center text-xs font-bold text-tg-purple hover:underline mb-2">
            &larr; Back to Disputes
        </a>
        <h1 class="text-2xl font-bold text-tg-dark tracking-tight">Raise Lead Ownership Dispute</h1>
        <p class="text-xs text-slate-500 mt-1">
            If a customer referral you introduced was rejected as a duplicate or attribution was improperly modified, submit authorization evidence for Admin review.
        </p>
    </div>

    <!-- Dispute Submission Form -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <form action="{{ route('partner.disputes.store') }}" method="POST" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">
            @csrf

            @if($lead)
                <input type="hidden" name="lead_id" value="{{ $lead->id }}">
                <div class="p-4 rounded-xl bg-tg-purple-light border border-tg-purple-border flex items-center justify-between">
                    <div>
                        <div class="text-[10px] font-bold text-tg-purple uppercase tracking-wider">Disputing Ownership for Lead</div>
                        <div class="text-sm font-bold text-tg-dark">#{{ $lead->lead_number }} — {{ $lead->customer_name }}</div>
                        <div class="text-xs text-slate-500">Service: {{ $lead->service_required }}</div>
                    </div>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-bold border {{ $lead->ownership_status ? $lead->ownership_status->badgeClass() : 'bg-slate-100 text-slate-700' }}">
                        {{ $lead->ownership_status ? $lead->ownership_status->label() : 'Protected' }}
                    </span>
                </div>
            @endif

            <div>
                <label for="dispute_category" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                    Dispute Category <span class="text-tg-pink">*</span>
                </label>
                <select name="dispute_category" id="dispute_category" required
                        class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm bg-white focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    <option value="">-- Select Dispute Category --</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->value }}" {{ old('dispute_category') === $cat->value ? 'selected' : '' }}>
                            {{ $cat->label() }}
                        </option>
                    @endforeach
                </select>
                @error('dispute_category')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="customer_phone_claimed" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                        Claimed Customer Phone
                    </label>
                    <input type="tel" name="customer_phone_claimed" id="customer_phone_claimed"
                           value="{{ old('customer_phone_claimed', $prefillPhone) }}"
                           placeholder="e.g. 9876543210"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    @error('customer_phone_claimed')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="customer_email_claimed" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                        Claimed Customer Email
                    </label>
                    <input type="email" name="customer_email_claimed" id="customer_email_claimed"
                           value="{{ old('customer_email_claimed', $prefillEmail) }}"
                           placeholder="e.g. customer@example.com"
                           class="w-full rounded-xl border border-slate-300 px-3.5 py-2.5 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">
                    @error('customer_email_claimed')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="explanation" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                    Detailed Explanation & Justification <span class="text-tg-pink">*</span>
                </label>
                <textarea name="explanation" id="explanation" rows="5" required
                          placeholder="Provide details about your referral relationship, initial contact date, customer communication, or why this lead rightfully belongs to your account..."
                          class="w-full rounded-xl border border-slate-300 p-3 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple outline-none transition">{{ old('explanation') }}</textarea>
                <p class="text-[11px] text-slate-400 mt-1">Minimum 10 characters. Be concise and specific with dates and client context.</p>
                @error('explanation')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="evidence" class="block text-xs font-semibold uppercase tracking-wider text-tg-dark mb-1">
                    Supporting Proof / Evidence File (Optional)
                </label>
                <input type="file" name="evidence" id="evidence" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx"
                       class="w-full rounded-xl border border-slate-300 p-2 text-sm file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-tg-purple file:text-white hover:file:bg-tg-purple/90 transition">
                <p class="text-[11px] text-slate-400 mt-1">Upload client email screenshot, WhatsApp confirmation, or authorization letter. Max 10MB (PDF, PNG, JPG, DOCX). Stored privately.</p>
                @error('evidence')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('partner.disputes.index') }}" class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                    Cancel
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-tg-purple hover:bg-tg-purple/90 shadow-sm transition">
                    Submit Dispute for Admin Review
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
