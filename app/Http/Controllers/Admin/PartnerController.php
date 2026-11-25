<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\PartnerAgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PartnerController extends Controller
{
    /**
     * Display a paginated list of partners with search and filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $query = Partner::with(['user', 'approver', 'creator'])
            ->withCount([
                'leads',
                'projects',
                'commissions',
            ])
            ->latest();

        if ($status === 'pending') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Pending));
        } elseif ($status === 'active') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
        } elseif ($status === 'suspended') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Suspended));
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('partner_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $partners = $query->paginate(15)->withQueryString();

        $counts = [
            'all'       => Partner::count(),
            'pending'   => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Pending))->count(),
            'active'    => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))->count(),
            'suspended' => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Suspended))->count(),
        ];

        return view('admin.partners.index', compact('partners', 'status', 'search', 'counts'));
    }

    /**
     * Show form to create a new partner account directly by Admin.
     */
    public function create(): View
    {
        return view('admin.partners.create');
    }

    /**
     * Store a newly created partner in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'                   => ['required', 'string', 'max:30'],
            'password'                => ['required', 'confirmed', Password::defaults()],
            'company_name'            => ['nullable', 'string', 'max:255'],
            'address_line1'           => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'state'                   => ['nullable', 'string', 'max:100'],
            'postal_code'             => ['nullable', 'string', 'max:20'],
            'status'                  => ['required', Rule::enum(UserStatus::class)],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'internal_notes'          => ['nullable', 'string'],
        ]);

        $partner = DB::transaction(function () use ($validated, $request) {
            $status = UserStatus::from($validated['status']);

            // Strictly enforce UserRole::Partner - never allow privilege escalation
            $user = User::create([
                'name'              => $validated['name'],
                'email'             => strtolower(trim($validated['email'])),
                'phone'             => $validated['phone'],
                'password'          => Hash::make($validated['password']),
                'role'              => UserRole::Partner,
                'status'            => $status,
                'email_verified_at' => now(),
            ]);

            $partnerCode = $this->generatePartnerCode();
            $isAdminActive = $status === UserStatus::Active;

            return Partner::create([
                'user_id'                 => $user->id,
                'partner_code'            => $partnerCode,
                'company_name'            => $validated['company_name'] ?? $validated['name'],
                'phone'                   => $validated['phone'],
                'address_line1'           => $validated['address_line1'] ?? null,
                'city'                    => $validated['city'] ?? null,
                'state'                   => $validated['state'] ?? null,
                'postal_code'             => $validated['postal_code'] ?? null,
                'country'                 => 'India',
                'default_commission_rate' => $validated['default_commission_rate'] ?? 10.00,
                'created_by'              => $request->user()->id,
                'approved_at'             => $isAdminActive ? now() : null,
                'approved_by'             => $isAdminActive ? $request->user()->id : null,
                'internal_notes'          => $validated['internal_notes'] ?? null,
            ]);
        });

        if ($validated['status'] === UserStatus::Active->value) {
            app(PartnerAgreementService::class)->generateAgreement($partner, $request->user()->id);
        }

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::PartnerCreated,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} ({$partner->company_name}) created by Admin.",
            subject: $partner,
            newValues: [
                'partner_code' => $partner->partner_code,
                'company_name' => $partner->company_name,
                'email' => $partner->user->email,
                'status' => $partner->user->status->value,
                'default_commission_rate' => $partner->default_commission_rate,
            ]
        );

        return redirect()->route('admin.partners.show', $partner)
            ->with('success', "Partner #{$partner->partner_code} ({$partner->company_name}) successfully created.");
    }

    /**
     * Display partner detailed dossier and statistics.
     */
    public function show(Partner $partner): View
    {
        $partner->load([
            'user',
            'approver',
            'creator',
            'leads' => fn ($q) => $q->latest()->take(10),
            'projects' => fn ($q) => $q->with('customer')->latest()->take(10),
            'commissions' => fn ($q) => $q->latest()->take(10),
        ]);

        $referredCustomers = Customer::where('first_partner_id', $partner->id)->latest()->take(10)->get();

        $stats = [
            'total_leads'     => Lead::where('partner_id', $partner->id)->count(),
            'won_leads'       => Lead::where('partner_id', $partner->id)->where('status', LeadStatus::Won)->count(),
            'customers_count' => Customer::where('first_partner_id', $partner->id)->count(),
            'projects_count'  => Project::where('partner_id', $partner->id)->count(),
            'earned_comm'     => (float) $partner->commissions()->where('status', '!=', CommissionStatus::Cancelled)->sum('amount'),
            'paid_comm'       => (float) $partner->commissions()->where('status', CommissionStatus::Paid)->sum('amount'),
            'payable_comm'    => (float) $partner->commissions()->whereIn('status', [CommissionStatus::Approved, CommissionStatus::Payable])->sum('amount'),
        ];

        return view('admin.partners.show', compact('partner', 'referredCustomers', 'stats'));
    }

    /**
     * Show form to edit an existing partner.
     */
    public function edit(Partner $partner): View
    {
        $partner->load('user');
        return view('admin.partners.edit', compact('partner'));
    }

    /**
     * Update an existing partner in storage.
     */
    public function update(Request $request, Partner $partner): RedirectResponse
    {
        $validated = $request->validate([
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($partner->user_id)],
            'phone'                   => ['required', 'string', 'max:30'],
            'password'                => ['nullable', 'confirmed', Password::defaults()],
            'company_name'            => ['nullable', 'string', 'max:255'],
            'address_line1'           => ['nullable', 'string', 'max:255'],
            'city'                    => ['nullable', 'string', 'max:100'],
            'state'                   => ['nullable', 'string', 'max:100'],
            'postal_code'             => ['nullable', 'string', 'max:20'],
            'status'                  => ['required', Rule::enum(UserStatus::class)],
            'default_commission_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'internal_notes'          => ['nullable', 'string'],
        ]);

        $oldUserAttrs = $partner->user->getAttributes();
        $oldPartnerAttrs = $partner->getAttributes();

        DB::transaction(function () use ($validated, $partner, $request) {
            $userUpdate = [
                'name'   => $validated['name'],
                'email'  => strtolower(trim($validated['email'])),
                'phone'  => $validated['phone'],
                'status' => UserStatus::from($validated['status']),
            ];

            if (!empty($validated['password'])) {
                $userUpdate['password'] = Hash::make($validated['password']);
            }

            $partner->user->update($userUpdate);

            $partnerUpdate = [
                'company_name'            => $validated['company_name'] ?? $validated['name'],
                'phone'                   => $validated['phone'],
                'address_line1'           => $validated['address_line1'] ?? null,
                'city'                    => $validated['city'] ?? null,
                'state'                   => $validated['state'] ?? null,
                'postal_code'             => $validated['postal_code'] ?? null,
                'default_commission_rate' => $validated['default_commission_rate'] ?? 10.00,
                'internal_notes'          => $validated['internal_notes'] ?? null,
            ];

            if ($validated['status'] === UserStatus::Active->value && !$partner->approved_at) {
                $partnerUpdate['approved_at'] = now();
                $partnerUpdate['approved_by'] = $request->user()->id;
            }

            $partner->update($partnerUpdate);
        });

        app(\App\Services\AuditLogService::class)->recordModelUpdate(
            model: $partner,
            oldAttributes: $oldPartnerAttrs,
            action: \App\Enums\AuditLogAction::PartnerUpdated,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} details updated by Admin."
        );

        if (!empty($validated['password'])) {
            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::PasswordChanged,
                module: \App\Enums\AuditLogModule::Auth,
                description: "Password for Partner #{$partner->partner_code} ({$partner->user->name}) was reset by Admin.",
                subject: $partner->user,
                actor: $request->user()
            );
        }

        return redirect()->route('admin.partners.show', $partner)
            ->with('success', "Partner #{$partner->partner_code} updated successfully.");
    }

    /**
     * Approve a pending partner.
     */
    public function approve(Partner $partner, PartnerAgreementService $agreementService): RedirectResponse
    {
        $partner->user->update([
            'status' => UserStatus::Active,
        ]);

        $partner->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        $agreement = $agreementService->generateAgreement($partner, auth()->id());

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::PartnerApproved,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} ({$partner->company_name}) approved by Admin.",
            subject: $partner,
            oldValues: ['status' => UserStatus::Pending->value],
            newValues: ['status' => UserStatus::Active->value, 'approved_at' => now()->toIso8601String()]
        );

        return back()->with('success', "Partner #{$partner->partner_code} ({$partner->company_name}) has been approved and Agreement #{$agreement->agreement_number} generated.");
    }

    /**
     * Reject a pending partner.
     */
    public function reject(Request $request, Partner $partner): RedirectResponse
    {
        $reason = $request->input('reason', 'Application did not meet partnership criteria.');

        $partner->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $partner->update([
            'internal_notes' => trim(($partner->internal_notes ?? '') . "\n[" . now()->toDateTimeString() . " by " . auth()->user()->name . "] Rejected: " . $reason),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::PartnerRejected,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} application rejected by Admin. Reason: {$reason}",
            subject: $partner,
            metadata: ['reason' => $reason]
        );

        return back()->with('info', "Partner #{$partner->partner_code} application has been rejected.");
    }

    /**
     * Suspend an active partner.
     */
    public function suspend(Request $request, Partner $partner): RedirectResponse
    {
        $reason = $request->input('reason', 'Account suspended by administrator.');

        $partner->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $partner->update([
            'internal_notes' => trim(($partner->internal_notes ?? '') . "\n[" . now()->toDateTimeString() . " by " . auth()->user()->name . "] Suspended: " . $reason),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::AccountSuspended,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} suspended by Admin. Reason: {$reason}",
            subject: $partner,
            oldValues: ['status' => UserStatus::Active->value],
            newValues: ['status' => UserStatus::Suspended->value],
            metadata: ['reason' => $reason]
        );

        return back()->with('warning', "Partner #{$partner->partner_code} has been suspended.");
    }

    /**
     * Reactivate a suspended partner.
     */
    public function activate(Partner $partner): RedirectResponse
    {
        $partner->user->update([
            'status' => UserStatus::Active,
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::AccountReactivated,
            module: \App\Enums\AuditLogModule::Partner,
            description: "Partner #{$partner->partner_code} reactivated by Admin.",
            subject: $partner,
            oldValues: ['status' => UserStatus::Suspended->value],
            newValues: ['status' => UserStatus::Active->value]
        );

        return back()->with('success', "Partner #{$partner->partner_code} has been reactivated.");
    }

    /**
     * Generate unique partner code: TGP-YYYY-XXXX
     */
    protected function generatePartnerCode(): string
    {
        do {
            $code = 'TGP-' . date('Y') . '-' . strtoupper(Str::random(4));
        } while (Partner::where('partner_code', $code)->exists());

        return $code;
    }
}
