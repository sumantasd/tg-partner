<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\User;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Display a comprehensive listing of all leads with search, filters, and pipeline metrics.
     */
    public function index(Request $request): View
    {
        $query = Lead::with(['partner.user', 'assignedTo', 'duplicateMatchedLead.partner']);

        // 1. Search by name, phone, email, or lead ID
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('lead_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%")
                    ->orWhere('customer_email', 'like', "%{$search}%")
                    ->orWhere('customer_company', 'like', "%{$search}%");
            });
        }

        // 2. Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 3. Filter by partner
        if ($request->filled('partner_id')) {
            $query->where('partner_id', $request->partner_id);
        }

        // 4. Filter by assigned team member
        if ($request->filled('assigned_to')) {
            if ($request->assigned_to === 'unassigned') {
                $query->whereNull('assigned_to');
            } else {
                $query->where('assigned_to', $request->assigned_to);
            }
        }

        // 5. Filter by duplicate flag
        if ($request->has('duplicates_only') && $request->duplicates_only === '1') {
            $query->where('is_duplicate_flag', true);
        }

        // 6. Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // 7. Filter delayed follow-ups
        if ($request->has('delayed') && $request->delayed === '1') {
            $query->whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<', now()->toDateString())
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected]);
        }

        $leads = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        // KPI pipeline metrics
        $counts = [
            'total' => Lead::count(),
            'submitted' => Lead::where('status', LeadStatus::Submitted)->count(),
            'assigned' => Lead::where('status', LeadStatus::Assigned)->count(),
            'in_pipeline' => Lead::whereIn('status', [
                LeadStatus::Contacted,
                LeadStatus::InDiscussion,
                LeadStatus::QuotationSent,
                LeadStatus::Negotiating,
            ])->count(),
            'won' => Lead::where('status', LeadStatus::Won)->count(),
            'lost' => Lead::where('status', LeadStatus::Lost)->count(),
            'duplicates' => Lead::where('is_duplicate_flag', true)->count(),
            'delayed' => Lead::whereNotNull('next_followup_date')
                ->whereDate('next_followup_date', '<', now()->toDateString())
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
                ->count(),
        ];

        $teamMembers = User::where('role', UserRole::TeamMember)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get();

        $partners = Partner::with('user')->orderBy('company_name')->get();

        return view('admin.leads.index', compact('leads', 'counts', 'teamMembers', 'partners'));
    }

    /**
     * Show form to directly create a new lead as an Administrator.
     */
    public function create(): View
    {
        $partners = Partner::with('user')
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->orderBy('company_name')
            ->get();

        $teamMembers = User::where('role', UserRole::TeamMember)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get();

        return view('admin.leads.create', compact('partners', 'teamMembers'));
    }

    /**
     * Store a newly created lead by Admin.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name'       => ['required', 'string', 'max:150'],
            'customer_company'    => ['nullable', 'string', 'max:200'],
            'customer_phone'      => ['required', 'string', 'max:30'],
            'whatsapp_number'     => ['nullable', 'string', 'max:30'],
            'customer_email'      => ['nullable', 'email', 'max:255'],
            'city'                => ['nullable', 'string', 'max:100'],
            'state'               => ['nullable', 'string', 'max:100'],
            'service_required'    => ['required', 'string', 'max:150'],
            'estimated_value'     => ['nullable', 'numeric', 'min:0'],
            'project_description' => ['nullable', 'string'],
            'partner_id'          => ['nullable', 'exists:partners,id'],
            'assigned_to'         => ['nullable', 'exists:users,id'],
            'lead_source'         => ['required', 'in:Direct,Website,Facebook,Instagram,Google,Referral,Other'],
            'next_followup_date'  => ['nullable', 'date'],
            'priority'            => ['required', 'in:Low,Medium,High,Urgent'],
        ]);

        $lead = $this->leadService->createAdminLead($validated, $request->user());

        if ($lead->is_duplicate_flag) {
            $matched = $lead->duplicateMatchedLead;
            $matchedText = $matched ? "Matches Lead #{$matched->lead_number} ({$matched->customer_name}) within the 90-day cooling period." : "Matches an existing referral.";
            return redirect()->route('admin.leads.show', $lead)
                ->with('warning', "Potential duplicate lead detected! {$matchedText} Please review conflict status.");
        }

        return redirect()->route('admin.leads.show', $lead)
            ->with('success', "Lead #{$lead->lead_number} created successfully.");
    }

    /**
     * Display the specified lead dossier (Admin control view).
     */
    public function show(Lead $lead): View
    {
        $lead->load([
            'partner.user',
            'originalPartner.user',
            'assignedTo',
            'assignedBy',
            'duplicateMatchedLead.partner.user',
            'activities.user',
            'ownershipHistories.previousPartner',
            'ownershipHistories.newPartner',
            'ownershipHistories.performer',
            'disputes.partner.user',
        ]);

        $teamMembers = User::where('role', UserRole::TeamMember)
            ->where('status', UserStatus::Active)
            ->orderBy('name')
            ->get();

        $partners = Partner::with('user')
            ->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))
            ->orderBy('company_name')
            ->get();

        return view('admin.leads.show', compact('lead', 'teamMembers', 'partners'));
    }

    /**
     * Assign or reassign lead to a team member.
     */
    public function assign(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => ['required', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $teamMember = User::findOrFail($validated['assigned_to']);

        $this->leadService->assignLead($lead, $teamMember, $request->user(), $validated['note'] ?? null);

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            "Lead #{$lead->lead_number} successfully assigned to {$teamMember->name}."
        );
    }

    /**
     * Update lead pipeline status (Admin override).
     */
    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'quotation_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $newStatus = LeadStatus::from($validated['status']);

        $this->leadService->updateStatus(
            $lead,
            $newStatus,
            $request->user(),
            $validated['reason'] ?? null
        );

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            "Lead status updated to {$newStatus->label()}."
        );
    }

    /**
     * Record internal private note or admin directive.
     */
    public function storeActivity(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
        ]);

        $this->leadService->recordActivity($lead, $request->user(), [
            'activity_type' => 'admin_comment',
            'summary' => $validated['summary'],
            'details' => $validated['details'] ?? null,
            'is_internal' => true,
        ]);

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            'Admin comment logged successfully.'
        );
    }

    /**
     * Resolve duplicate lead conflict.
     */
    public function resolveDuplicate(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:dismiss,reject,override_attribution'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->leadService->resolveDuplicate(
            $lead,
            $request->user(),
            $validated['action'],
            $validated['notes'] ?? null
        );

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            'Duplicate lead conflict resolved successfully.'
        );
    }

    /**
     * Reassign lead ownership to another Partner.
     */
    public function reassignOwnership(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'new_partner_id' => ['required', 'exists:partners,id'],
            'reason'         => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $newPartner = Partner::findOrFail($validated['new_partner_id']);

        $this->leadService->reassignOwnership($lead, $newPartner, $validated['reason'], $request->user());

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            "Lead ownership successfully transferred to {$newPartner->company_name}."
        );
    }

    /**
     * Extend lead ownership protection period.
     */
    public function extendOwnership(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'days'   => ['required', 'integer', 'in:15,30,60,90,180'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->leadService->extendOwnership($lead, (int) $validated['days'], $validated['reason'], $request->user());

        return redirect()->route('admin.leads.show', $lead)->with(
            'success',
            "Lead ownership protection extended by {$validated['days']} days."
        );
    }

    /**
     * Release lead ownership into unassigned pool.
     */
    public function releaseOwnership(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->leadService->releaseOwnership($lead, $validated['reason'], $request->user());

        return redirect()->route('admin.leads.show', $lead)->with(
            'warning',
            'Lead ownership released. The lead is now unassigned from partner protection.'
        );
    }
}
