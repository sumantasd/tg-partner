@extends('layouts.app')

@section('title', 'Add New Lead — Admin Control')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center space-x-3 text-xs text-slate-500">
        <a href="{{ route('admin.leads.index') }}" class="hover:text-tg-purple">Leads Pipeline</a>
        <span>/</span>
        <span class="text-tg-purple font-bold">Add New Lead</span>
    </div>

    <!-- Main Card -->
    <div class="rounded-3xl bg-white border border-slate-200/80 p-6 sm:p-8 shadow-sm">
        <div class="border-b border-slate-100 pb-4 mb-6">
            <h1 class="text-2xl font-bold text-tg-dark">Direct Lead Entry</h1>
            <p class="text-xs text-slate-500 mt-1">Capture inbound customer inquiry directly into the central sales pipeline.</p>
        </div>

        <form action="{{ route('admin.leads.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Customer Contact Details -->
            <div>
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Customer Contact Information</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Customer Name *</label>
                        <input type="text" name="customer_name" required value="{{ old('customer_name') }}" placeholder="e.g. Rahul Sharma"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('customer_name') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Business / Company Name</label>
                        <input type="text" name="customer_company" value="{{ old('customer_company') }}" placeholder="e.g. Sharma Textiles Pvt Ltd"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('customer_company') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number *</label>
                        <input type="text" name="customer_phone" required value="{{ old('customer_phone') }}" placeholder="e.g. 9830012345"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('customer_phone') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">WhatsApp Number</label>
                        <input type="text" name="whatsapp_number" value="{{ old('whatsapp_number') }}" placeholder="e.g. 9830012345"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('whatsapp_number') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="rahul@sharmatextiles.com"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('customer_email') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">City</label>
                            <input type="text" name="city" value="{{ old('city') }}" placeholder="e.g. Kolkata"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1.5">State</label>
                            <input type="text" name="state" value="{{ old('state') }}" placeholder="e.g. West Bengal"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Service & Project Requirements -->
            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Service & Commercial Requirements</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Service Category *</label>
                        <select name="service_required" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="">Select Service Category</option>
                            @foreach(['Custom Software Development', 'Web Application Development', 'Mobile App Development', 'E-commerce Solutions', 'Digital Marketing & SEO', 'Cloud & DevOps Solutions', 'UI/UX Design & Branding', 'IT Consulting & Support'] as $cat)
                                <option value="{{ $cat }}" {{ old('service_required') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                        @error('service_required') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Estimated Budget (₹ INR)</label>
                        <input type="number" step="0.01" min="0" name="estimated_value" value="{{ old('estimated_value') }}" placeholder="e.g. 75000"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('estimated_value') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Requirements / Project Description</label>
                        <textarea name="project_description" rows="3" placeholder="Provide details about the customer's requirements, features requested, or timeline..."
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">{{ old('project_description') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Attribution, Assignment & Priority -->
            <div class="border-t border-slate-100 pt-6">
                <h2 class="text-xs font-bold uppercase tracking-wider text-tg-purple mb-4">Attribution, CRM Assignment & Priority</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Assign Referral Partner</label>
                        <select name="partner_id" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="">None / Direct Company Lead</option>
                            @foreach($partners as $partner)
                                <option value="{{ $partner->id }}" {{ old('partner_id') == $partner->id ? 'selected' : '' }}>
                                    {{ $partner->company_name }} ({{ $partner->partner_code }} - {{ $partner->user->name }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-slate-400 mt-1 block">If attributed to a partner, deals converted from this lead preserve permanent referral credit.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Assign Internal Team Member</label>
                        <select name="assigned_to" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            <option value="">Leave Unassigned (Pending Assignment)</option>
                            @foreach($teamMembers as $staff)
                                <option value="{{ $staff->id }}" {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }} ({{ $staff->designation ?? 'Sales' }} - {{ $staff->department ?? 'General' }})
                                </option>
                            @endforeach
                        </select>
                        <span class="text-[11px] text-slate-400 mt-1 block">Staff member will immediately receive telecalling CRM access to this lead.</span>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Lead Source *</label>
                        <select name="lead_source" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            @foreach(['Direct', 'Website', 'Facebook', 'Instagram', 'Google', 'Referral', 'Other'] as $src)
                                <option value="{{ $src }}" {{ old('lead_source', 'Direct') === $src ? 'selected' : '' }}>{{ $src }}</option>
                            @endforeach
                        </select>
                        @error('lead_source') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Priority *</label>
                        <select name="priority" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                            @foreach(['Low', 'Medium', 'High', 'Urgent'] as $prio)
                                <option value="{{ $prio }}" {{ old('priority', 'Medium') === $prio ? 'selected' : '' }}>{{ $prio }}</option>
                            @endforeach
                        </select>
                        @error('priority') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Follow-up Date (Optional)</label>
                        <input type="date" name="next_followup_date" value="{{ old('next_followup_date') }}" min="{{ date('Y-m-d') }}"
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-tg-purple focus:border-tg-purple">
                        @error('next_followup_date') <span class="text-xs text-tg-pink font-semibold mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                <a href="{{ route('admin.leads.index') }}"
                   class="px-5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold text-slate-700 hover:bg-slate-50 transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-6 py-2.5 rounded-xl bg-tg-purple text-white text-xs font-bold hover:bg-tg-purple/90 shadow-sm transition">
                    Create Lead
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
