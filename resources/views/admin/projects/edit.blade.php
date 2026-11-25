@extends('layouts.app')

@section('title', 'Edit Project — ' . $project->project_code)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.projects.index') }}" class="hover:text-tg-purple">Projects</a>
        <span>/</span>
        <a href="{{ route('admin.projects.show', $project) }}" class="hover:text-tg-purple font-mono font-bold">{{ $project->project_code }}</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Edit</span>
    </div>

    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <h1 class="text-2xl font-bold text-tg-dark">Edit Project Information</h1>
        <p class="text-xs text-slate-500 mt-1">Update scope, milestone value, status, or assigned consultant for #{{ $project->project_code }}.</p>

        <form action="{{ route('admin.projects.update', $project) }}" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Project Title *</label>
                    <input type="text" name="project_title" required value="{{ old('project_title', $project->project_title) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Referral Partner</label>
                    <select name="partner_id" id="partner_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple" onchange="handlePartnerSelectionChange(this.value)">
                        <option value="">No Partner / Platform Direct</option>
                        @foreach($partners as $p)
                            <option value="{{ $p->id }}" {{ (old('partner_id', $project->partner_id) == $p->id) ? 'selected' : '' }}>
                                {{ $p->company_name }} ({{ $p->partner_code }})
                            </option>
                        @endforeach
                    </select>

                    <!-- Partner Commission Info Card -->
                    <div id="partner_commission_info_box" class="mt-2.5 p-3 rounded-xl border border-purple-100 bg-purple-50/70 transition flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2.5 min-w-0">
                            <span class="w-7 h-7 rounded-lg bg-purple-100 text-tg-purple flex items-center justify-center font-extrabold text-xs flex-shrink-0">
                                %
                            </span>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-800 text-xs truncate" id="partner_rate_display">
                                    Partner Commission: <span class="text-tg-purple font-extrabold" id="partner_rate_val">10.00%</span>
                                </div>
                                <div class="text-[11px] text-slate-500 truncate" id="partner_source_display">
                                    Source: <span class="font-semibold text-slate-700" id="partner_source_val">Global Default Policy</span>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-1.5 flex-shrink-0">
                            <span id="partner_loading_spinner" class="hidden text-tg-purple animate-spin">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            </span>
                            <span id="partner_badge_pill" class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-white border border-purple-200 text-tg-purple shadow-2xs">
                                Global Default
                            </span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Assigned Consultant</label>
                    <select name="assigned_to" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        <option value="">Unassigned</option>
                        @foreach($teamMembers as $m)
                            <option value="{{ $m->id }}" {{ old('assigned_to', $project->assigned_to) == $m->id ? 'selected' : '' }}>
                                {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Category</label>
                    <input type="text" name="service_category" value="{{ old('service_category', $project->service_category) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <!-- Payment Configuration (Single vs Monthly) -->
                <div class="sm:col-span-2 pt-4 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-tg-dark">Payment Configuration</h3>
                    <p class="text-xs text-slate-500">Choose between a Single one-time payment or a Monthly recurring schedule.</p>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Project Payment Type *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="radio" name="payment_type" id="payment_type_single" value="single" {{ old('payment_type', $project->payment_type?->value ?? 'single') === 'single' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple" onchange="handlePaymentTypeChange('single')">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-tg-dark block">Single Payment</span>
                                <span class="text-[10px] text-slate-400">One-time payment verified in full or lump sum disbursements</span>
                            </div>
                        </label>
                        <label class="flex items-center p-3 rounded-xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition">
                            <input type="radio" name="payment_type" id="payment_type_monthly" value="monthly" {{ old('payment_type', $project->payment_type?->value ?? '') === 'monthly' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple" onchange="handlePaymentTypeChange('monthly')">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-tg-dark block">Monthly Payment</span>
                                <span class="text-[10px] text-slate-400">Recurring monthly billing with automated schedule generation</span>
                            </div>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Total Project Amount (INR) *</label>
                    <input type="number" step="0.01" name="final_deal_value" id="final_deal_value" required value="{{ old('final_deal_value', $project->final_deal_value) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Project Status *</label>
                    <select name="status" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @foreach(\App\Enums\ProjectStatus::cases() as $case)
                            <option value="{{ $case->value }}" {{ old('status', $project->status->value) === $case->value ? 'selected' : '' }}>
                                {{ $case->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Monthly Payment Configuration (Conditional) -->
                <div id="monthly_config_wrapper" class="sm:col-span-2 p-5 rounded-2xl bg-purple-50/60 border border-purple-100 {{ old('payment_type', $project->payment_type?->value ?? 'single') === 'monthly' ? '' : 'hidden' }}">
                    <h4 class="text-xs font-bold text-purple-900 mb-3 flex items-center space-x-1.5">
                        <span>🗓️ Monthly Payment Schedule Parameters</span>
                    </h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Monthly Payment Amount (INR) *</label>
                            <input type="number" step="0.01" name="monthly_amount" id="monthly_amount" value="{{ old('monthly_amount', $project->monthly_amount ?? '') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple" oninput="updateSchedulePreview()">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Total Duration (Months) *</label>
                            <input type="number" name="duration_months" id="duration_months" min="1" max="120" value="{{ old('duration_months', $project->duration_months ?? 12) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple" oninput="updateSchedulePreview()">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Billing Start Date *</label>
                            <input type="date" name="billing_start_date" id="billing_start_date" value="{{ old('billing_start_date', $project->billing_start_date?->toDateString() ?? date('Y-m-d')) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple" onchange="updateSchedulePreview()">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Monthly Due Day (1-31)</label>
                            <input type="number" name="monthly_due_day" id="monthly_due_day" min="1" max="31" value="{{ old('monthly_due_day', $project->monthly_due_day ?? 5) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple" oninput="updateSchedulePreview()">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">First Payment Due Date</label>
                            <input type="date" name="first_payment_due_date" id="first_payment_due_date" value="{{ old('first_payment_due_date', $project->first_payment_due_date?->toDateString() ?? '') }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple" onchange="updateSchedulePreview()">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Grace Period (Days)</label>
                            <input type="number" name="grace_period_days" id="grace_period_days" min="0" value="{{ old('grace_period_days', $project->grace_period_days ?? 0) }}"
                                   class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple">
                        </div>
                    </div>

                    <!-- Calculated Schedule Preview Table -->
                    <div class="mt-4 pt-3 border-t border-purple-200/60">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800">Generated Schedule Preview:</span>
                            <span id="preview_total_summary" class="text-[11px] font-bold text-purple-700 bg-white px-2.5 py-0.5 rounded-full border border-purple-200">Total: ₹0.00</span>
                        </div>
                        <div class="max-h-40 overflow-y-auto rounded-xl border border-purple-200/60 bg-white">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-purple-100/60 text-slate-700 font-semibold border-b border-purple-200/60">
                                    <tr>
                                        <th class="px-3 py-1.5">#</th>
                                        <th class="px-3 py-1.5">Billing Month</th>
                                        <th class="px-3 py-1.5">Due Date</th>
                                        <th class="px-3 py-1.5 text-right">Expected Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="preview_table_body" class="divide-y divide-slate-100 text-slate-700">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Dynamic Commission Configuration -->
                <div class="sm:col-span-2 pt-4 border-t border-slate-100">
                    <h3 class="text-sm font-bold text-tg-dark">Commission Policy & Terms</h3>
                    <p class="text-xs text-slate-500">Choose between the applicable default commission policy or bespoke project terms.</p>
                </div>

                <!-- Exactly Two Options -->
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-2">Commission Terms Model *</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-start p-3.5 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition" id="label_model_default">
                            <input type="radio" name="commission_model" id="model_global_default" value="global_default"
                                   {{ old('commission_model', $project->commission_model ?? 'global_default') === 'global_default' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple mt-0.5" onchange="handleCommissionModelChange('global_default')">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-tg-dark block">Use Default Commission Policy</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Applies partner-assigned agreement policy or platform default</span>
                            </div>
                        </label>

                        <label class="flex items-start p-3.5 rounded-2xl border border-slate-200 cursor-pointer hover:bg-slate-50 transition" id="label_model_custom">
                            <input type="radio" name="commission_model" id="model_custom" value="custom"
                                   {{ old('commission_model', $project->commission_model) === 'custom' ? 'checked' : '' }}
                                   class="text-tg-purple focus:ring-tg-purple mt-0.5" onchange="handleCommissionModelChange('custom')">
                            <div class="ml-3">
                                <span class="text-xs font-bold text-tg-dark block">Custom Project Override</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Set bespoke percentage or fixed amount terms for this project</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Option A: Default Commission Summary Box -->
                <div id="default_commission_summary_box" class="sm:col-span-2 p-4 rounded-2xl border border-purple-200/70 bg-gradient-to-br from-purple-50/70 via-white to-pink-50/30">
                    <div class="flex items-center justify-between mb-3 border-b border-purple-100 pb-2">
                        <div class="flex items-center space-x-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-tg-dark uppercase tracking-wider">Effective Commission Summary</span>
                        </div>
                        <span class="text-[10px] font-semibold text-slate-400">Locked to Authoritative Policy</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div class="bg-white/90 p-3 rounded-xl border border-purple-100/60 shadow-2xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Effective Commission</span>
                            <span class="text-base font-extrabold text-tg-purple block mt-0.5" id="summary_effective_rate">{{ $currentResolvedCommission['commission_rate_formatted'] ?? number_format($project->agreed_commission_rate ?? $defaultCommissionRate, 2) . '%' }}</span>
                            <span class="text-[10px] text-slate-500" id="summary_effective_rate_sub">Applied to verified payments</span>
                        </div>
                        <div class="bg-white/90 p-3 rounded-xl border border-purple-100/60 shadow-2xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Commission Type</span>
                            <span class="text-sm font-bold text-slate-800 block mt-0.5" id="summary_commission_type">{{ $currentResolvedCommission['commission_type_label'] ?? ucfirst($project->agreed_commission_type?->value ?? 'Percentage') }}</span>
                            <span class="text-[10px] text-slate-500">Calculated on transaction value</span>
                        </div>
                        <div class="bg-white/90 p-3 rounded-xl border border-purple-100/60 shadow-2xs">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Policy Source</span>
                            <span class="text-sm font-bold text-tg-dark block mt-0.5 truncate" id="summary_policy_source">{{ $currentResolvedCommission['source_label'] ?? 'Partner Agreement' }}</span>
                            <span class="text-[10px] text-purple-600 font-mono" id="summary_policy_code">{{ $currentResolvedCommission['policy_code'] ?? 'POL-DEF-V1' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Option B: Custom Project Override Configuration Fields -->
                <div id="custom_commission_fields_box" class="hidden sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl border border-slate-200 bg-slate-50/60">
                    <div class="sm:col-span-2 flex items-center justify-between border-b border-slate-200 pb-2">
                        <span class="text-xs font-bold text-tg-dark uppercase tracking-wider">Custom Project Override Configuration</span>
                        <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200">Preempts Default Policy</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Custom Commission Type *</label>
                        <select name="custom_commission_type" id="custom_commission_type" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple" onchange="handleCustomTypeChange(this.value)">
                            <option value="percentage" {{ old('custom_commission_type', $project->custom_commission_type?->value ?? 'percentage') === 'percentage' ? 'selected' : '' }}>Percentage Based (%)</option>
                            <option value="fixed" {{ old('custom_commission_type', $project->custom_commission_type?->value) === 'fixed' ? 'selected' : '' }}>Fixed Amount (INR)</option>
                        </select>
                        @error('custom_commission_type') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5" id="label_custom_rate">Custom Commission Rate (%) *</label>
                        <input type="number" step="0.01" name="custom_commission_rate" id="custom_commission_rate" value="{{ old('custom_commission_rate', $project->custom_commission_rate ?? $project->agreed_commission_rate) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-tg-dark focus:ring-2 focus:ring-tg-purple focus:border-tg-purple"
                               placeholder="e.g. 15.00">
                        @error('custom_commission_rate') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div id="fixed_frequency_wrapper" class="hidden">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Fixed Commission Frequency *</label>
                        <select name="custom_fixed_frequency" id="custom_fixed_frequency" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="per_payment" {{ old('custom_fixed_frequency', $project->custom_fixed_frequency?->value ?? 'per_payment') === 'per_payment' ? 'selected' : '' }}>Per Verified Payment</option>
                            <option value="per_project" {{ old('custom_fixed_frequency', $project->custom_fixed_frequency?->value) === 'per_project' ? 'selected' : '' }}>Per Project (Awarded Once)</option>
                            <option value="per_milestone" {{ old('custom_fixed_frequency', $project->custom_fixed_frequency?->value) === 'per_milestone' ? 'selected' : '' }}>Per Milestone Payment</option>
                        </select>
                        @error('custom_fixed_frequency') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Commission Effective Date</label>
                        <input type="date" name="commission_effective_date" id="commission_effective_date" value="{{ old('commission_effective_date', $project->commission_effective_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('commission_effective_date') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Commission Notes / Rationale</label>
                        <input type="text" name="commission_notes" id="commission_notes" value="{{ old('commission_notes', $project->commission_notes) }}" placeholder="e.g. Negotiated 12% override for strategic referral"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('commission_notes') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Estimated Budget</label>
                    <input type="number" step="0.01" name="estimated_budget" value="{{ old('estimated_budget', $project->estimated_budget) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Expected Completion Date</label>
                    <input type="date" name="expected_completion_date" value="{{ old('expected_completion_date', $project->expected_completion_date?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Project Scope & Description *</label>
                    <textarea name="project_description" rows="3" required
                              class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('project_description', $project->project_description) }}</textarea>
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-end space-x-3">
                <a href="{{ route('admin.projects.show', $project) }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple-hover shadow-md transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const partnerCommissions = @json($partnerCommissions);
const globalCommission = @json($globalCommission);
let currentPartnerData = null;

function applyCommissionDataToUI(data) {
    if (!data) return;

    // 1. Update Partner Selection Information Badge
    const rateVal = document.getElementById('partner_rate_val');
    const sourceVal = document.getElementById('partner_source_val');
    const badgePill = document.getElementById('partner_badge_pill');

    if (rateVal) rateVal.textContent = data.commission_rate_formatted || (data.commission_rate + '%');
    if (sourceVal) sourceVal.textContent = data.source_label || 'Global Default Policy';
    if (badgePill) {
        if (data.source === 'partner_policy') {
            badgePill.textContent = 'Partner Policy';
            badgePill.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 shadow-2xs';
        } else {
            badgePill.textContent = 'Global Default';
            badgePill.className = 'text-[10px] font-bold px-2.5 py-1 rounded-full bg-white border border-purple-200 text-tg-purple shadow-2xs';
        }
    }

    // 2. Update Effective Policy Summary Box (Option A)
    const summaryRate = document.getElementById('summary_effective_rate');
    const summaryType = document.getElementById('summary_commission_type');
    const summarySource = document.getElementById('summary_policy_source');
    const summaryCode = document.getElementById('summary_policy_code');

    if (summaryRate) summaryRate.textContent = data.commission_rate_formatted || (data.commission_rate + '%');
    if (summaryType) summaryType.textContent = data.commission_type_label || (data.commission_type === 'fixed' ? 'Fixed Amount' : 'Percentage');
    if (summarySource) summarySource.textContent = data.source_label || 'Global Default Policy';
    if (summaryCode) summaryCode.textContent = data.policy_code || (data.policy_name || 'Standard Platform Terms');
}

function handlePartnerSelectionChange(partnerId) {
    const spinner = document.getElementById('partner_loading_spinner');

    // Fast path: use preloaded dictionary
    if (partnerId && partnerCommissions[partnerId]) {
        currentPartnerData = partnerCommissions[partnerId];
        applyCommissionDataToUI(currentPartnerData);
    } else if (!partnerId) {
        currentPartnerData = globalCommission;
        applyCommissionDataToUI(currentPartnerData);
    }

    // Dynamic AJAX call to verify/refresh latest active data from backend
    if (spinner) spinner.classList.remove('hidden');

    const url = `{{ route('admin.projects.partner-commission') }}?partner_id=${encodeURIComponent(partnerId || '')}`;
    fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (spinner) spinner.classList.add('hidden');
        if (data && data.status === 'success') {
            currentPartnerData = data;
            applyCommissionDataToUI(data);
        }
    })
    .catch(err => {
        if (spinner) spinner.classList.add('hidden');
        console.warn('Asynchronous partner commission lookup failed; using preloaded values:', err);
    });
}

function handleCommissionModelChange(model) {
    const defaultBox = document.getElementById('default_commission_summary_box');
    const customBox = document.getElementById('custom_commission_fields_box');
    const labelDefault = document.getElementById('label_model_default');
    const labelCustom = document.getElementById('label_model_custom');

    if (model === 'custom') {
        if (defaultBox) defaultBox.classList.add('hidden');
        if (customBox) customBox.classList.remove('hidden');
        if (labelDefault) labelDefault.classList.remove('border-tg-purple', 'bg-purple-50/40');
        if (labelCustom) labelCustom.classList.add('border-tg-purple', 'bg-purple-50/40');

        const customType = document.getElementById('custom_commission_type');
        if (customType) handleCustomTypeChange(customType.value);
    } else {
        if (defaultBox) defaultBox.classList.remove('hidden');
        if (customBox) customBox.classList.add('hidden');
        if (labelDefault) labelDefault.classList.add('border-tg-purple', 'bg-purple-50/40');
        if (labelCustom) labelCustom.classList.remove('border-tg-purple', 'bg-purple-50/40');
    }
}

function handleCustomTypeChange(type) {
    const freqWrapper = document.getElementById('fixed_frequency_wrapper');
    const rateLabel = document.getElementById('label_custom_rate');
    const rateInput = document.getElementById('custom_commission_rate');

    if (type === 'fixed') {
        if (freqWrapper) freqWrapper.classList.remove('hidden');
        if (rateLabel) rateLabel.textContent = 'Fixed Commission Amount (₹) *';
        if (rateInput && !rateInput.value) rateInput.placeholder = 'e.g. 5000.00';
    } else {
        if (freqWrapper) freqWrapper.classList.add('hidden');
        if (rateLabel) rateLabel.textContent = 'Custom Commission Rate (%) *';
        if (rateInput && !rateInput.value) rateInput.placeholder = 'e.g. 15.00';
    }
}

function handlePaymentTypeChange(type) {
    const wrapper = document.getElementById('monthly_config_wrapper');

    if (type === 'monthly') {
        wrapper.classList.remove('hidden');
        updateSchedulePreview();
    } else {
        wrapper.classList.add('hidden');
    }
}

function updateSchedulePreview() {
    const monthlyAmount = parseFloat(document.getElementById('monthly_amount').value) || 0;
    const duration = parseInt(document.getElementById('duration_months').value) || 12;
    const startDateStr = document.getElementById('billing_start_date').value;
    const dueDay = parseInt(document.getElementById('monthly_due_day').value) || 5;
    const firstDueDateStr = document.getElementById('first_payment_due_date').value;

    const tbody = document.getElementById('preview_table_body');
    const totalBadge = document.getElementById('preview_total_summary');

    const totalAmount = monthlyAmount * duration;
    totalBadge.textContent = 'Total: ₹' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    tbody.innerHTML = '';
    const baseDate = startDateStr ? new Date(startDateStr) : new Date();

    for (let i = 1; i <= Math.min(duration, 36); i++) {
        const d = new Date(baseDate.getFullYear(), baseDate.getMonth() + (i - 1), 1);
        const monthName = d.toLocaleString('default', { month: 'long', year: 'numeric' });

        let dueDateStr;
        if (i === 1 && firstDueDateStr) {
            dueDateStr = firstDueDateStr;
        } else {
            const maxDays = new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
            const day = Math.min(dueDay, maxDays);
            dueDateStr = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
        }

        const tr = document.createElement('tr');
        tr.className = i % 2 === 0 ? 'bg-slate-50/50' : '';
        tr.innerHTML = `
            <td class="px-3 py-1 font-mono text-[10px] text-slate-400">#${i}</td>
            <td class="px-3 py-1 font-semibold text-slate-700">${monthName}</td>
            <td class="px-3 py-1 font-mono text-slate-500">${dueDateStr}</td>
            <td class="px-3 py-1 text-right font-bold text-tg-purple">₹${monthlyAmount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        `;
        tbody.appendChild(tr);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('payment_type_monthly') && document.getElementById('payment_type_monthly').checked) {
        handlePaymentTypeChange('monthly');
    }

    // Initialize partner commission on page load
    const partnerSelect = document.getElementById('partner_id');
    const initialPartnerId = partnerSelect ? partnerSelect.value : '';
    if (initialPartnerId) {
        handlePartnerSelectionChange(initialPartnerId);
    } else {
        applyCommissionDataToUI(globalCommission);
    }

    // Initialize commission terms model
    const isCustom = document.getElementById('model_custom').checked;
    handleCommissionModelChange(isCustom ? 'custom' : 'global_default');
});
</script>
@endsection
