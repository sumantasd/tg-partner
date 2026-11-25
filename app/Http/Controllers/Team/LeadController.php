<?php

namespace App\Http\Controllers\Team;

use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadSlaStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadService;
use App\Services\LeadSlaService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
        protected LeadSlaService $slaService
    ) {}

    /**
     * Display a listing of leads assigned to the logged-in team member.
     */
    public function index(Request $request): View
    {
        $teamUser = $request->user();

        $query = Lead::where('assigned_to', $teamUser->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('interest')) {
            $query->where('interest_level', $request->interest);
        }

        if ($request->filled('sla')) {
            if ($request->sla === 'overdue') {
                $query->where('overall_sla_status', LeadSlaStatus::Overdue);
            } elseif ($request->sla === 'due_soon') {
                $query->where('overall_sla_status', LeadSlaStatus::DueSoon);
            } elseif ($request->sla === 'pending_contact') {
                $query->whereNull('first_contacted_at')->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected]);
            }
        }

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

        if ($request->has('followup_today') && $request->followup_today === '1') {
            $query->whereDate('next_followup_date', now()->toDateString());
        }

        if ($request->has('overdue') && $request->overdue === '1') {
            $query->whereDate('next_followup_date', '<', now()->toDateString())
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected]);
        }

        $leads = $query->orderByRaw('CASE WHEN next_followup_date IS NULL THEN 1 ELSE 0 END, next_followup_date ASC')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Team metrics
        $totalAssigned = Lead::where('assigned_to', $teamUser->id)->count();
        $wonCount = Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::Won)->count();

        $counts = [
            'total_assigned'   => $totalAssigned,
            'today_followup'   => Lead::where('assigned_to', $teamUser->id)
                ->whereDate('next_followup_date', now()->toDateString())
                ->count(),
            'overdue_followup' => Lead::where('assigned_to', $teamUser->id)
                ->whereDate('next_followup_date', '<', now()->toDateString())
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
                ->count(),
            'hot_leads'        => Lead::where('assigned_to', $teamUser->id)
                ->where('interest_level', LeadInterest::Hot)
                ->count(),
            'in_discussion'    => Lead::where('assigned_to', $teamUser->id)
                ->whereIn('status', [
                    LeadStatus::Contacted,
                    LeadStatus::InDiscussion,
                    LeadStatus::QuotationSent,
                    LeadStatus::Negotiating,
                ])->count(),
            'contacted'        => Lead::where('assigned_to', $teamUser->id)
                ->where('status', LeadStatus::Contacted)
                ->count(),
            'won'              => $wonCount,
            'lost'             => Lead::where('assigned_to', $teamUser->id)
                ->whereIn('status', [LeadStatus::Lost, LeadStatus::Rejected])
                ->count(),
            'conversion_rate'  => $totalAssigned > 0 ? round(($wonCount / $totalAssigned) * 100, 1) : 0.0,
        ];

        return view('team.leads.index', compact('leads', 'counts'));
    }

    /**
     * Display assigned lead details for telecalling and CRM follow-up.
     * Note: Team Members cannot access partner financial details, commissions or payouts.
     */
    public function show(Request $request, Lead $lead): View
    {
        // Enforce team member isolation: can only view their own assigned lead
        if ($lead->assigned_to !== $request->user()->id) {
            abort(403, 'You can only access customer leads explicitly assigned to you.');
        }

        $lead->load(['activities.user']);

        return view('team.leads.show', compact('lead'));
    }

    /**
     * Record a telecalling/interaction activity on the assigned lead.
     */
    public function storeActivity(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'activity_type' => ['required', Rule::enum(LeadActivityType::class)],
            'summary' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string'],
            'customer_interest' => ['nullable', Rule::enum(LeadInterest::class)],
            'next_followup_date' => ['nullable', 'date'],
            'call_duration_seconds' => ['nullable', 'integer', 'min:1'],
            'quotation_value' => ['nullable', 'numeric', 'min:0'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        try {
            $this->leadService->recordActivity($lead, $request->user(), $validated);
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['activity_type' => $e->getMessage()]);
        }

        return redirect()->route('team.leads.show', $lead)->with(
            'success',
            'Follow-up activity recorded successfully.'
        );
    }

    /**
     * Update lead pipeline status (or recommend Won/Lost).
     */
    public function updateStatus(Request $request, Lead $lead): RedirectResponse
    {
        if ($lead->assigned_to !== $request->user()->id) {
            abort(403, 'Unauthorized.');
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'reason' => ['nullable', 'string', 'max:500'],
            'quotation_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        $newStatus = LeadStatus::from($validated['status']);

        try {
            $this->leadService->updateStatus(
                $lead,
                $newStatus,
                $request->user(),
                $validated['reason'] ?? null
            );

            if (isset($validated['quotation_value']) && $validated['quotation_value'] !== '') {
                $lead->update(['quotation_value' => (float) $validated['quotation_value']]);
            }
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()->route('team.leads.show', $lead)->with(
            'success',
            "Lead status updated to {$newStatus->label()}."
        );
    }
}
