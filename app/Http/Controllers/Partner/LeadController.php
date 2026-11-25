<?php

namespace App\Http\Controllers\Partner;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Services\LeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Display a listing of the partner's submitted leads.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;

        $query = Lead::where('partner_id', $partner->id)
            ->with(['assignedTo']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('lead_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_company', 'like', "%{$search}%")
                    ->orWhere('service_required', 'like', "%{$search}%");
            });
        }

        $leads = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        // Calculate count metrics for partner dashboard tabs
        $statusCounts = [
            'all' => Lead::where('partner_id', $partner->id)->count(),
            'submitted' => Lead::where('partner_id', $partner->id)->where('status', LeadStatus::Submitted)->count(),
            'in_discussion' => Lead::where('partner_id', $partner->id)->whereIn('status', [
                LeadStatus::Assigned,
                LeadStatus::Contacted,
                LeadStatus::InDiscussion,
                LeadStatus::QuotationSent,
                LeadStatus::Negotiating,
            ])->count(),
            'won' => Lead::where('partner_id', $partner->id)->where('status', LeadStatus::Won)->count(),
            'lost' => Lead::where('partner_id', $partner->id)->whereIn('status', [
                LeadStatus::Lost,
                LeadStatus::Rejected,
            ])->count(),
        ];

        return view('partner.leads.index', compact('leads', 'statusCounts', 'partner'));
    }

    /**
     * Show the form for creating a new lead.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $partner = $request->user()->partner;

        if ($partner->hasPendingAgreement()) {
            return redirect()->route('partner.agreement.show')
                ->with('warning', 'Action Required: You must review and digitally accept your Business Partner Agreement before submitting new leads.');
        }

        $services = [
            'Custom Web & ERP Application',
            'Mobile App Development (iOS & Android)',
            'E-Commerce & Digital Commerce',
            'Cloud & DevOps Infrastructure',
            'Digital Transformation & Consulting',
            'UI/UX & Product Design',
            'AI & Workflow Automation',
            'Enterprise Maintenance & Support',
        ];

        return view('partner.leads.create', compact('partner', 'services'));
    }

    /**
     * Store a newly created lead in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;

        if ($partner->hasPendingAgreement()) {
            return redirect()->route('partner.agreement.show')
                ->with('warning', 'Action Required: You must review and digitally accept your Business Partner Agreement before submitting new leads.');
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_company' => ['nullable', 'string', 'max:200'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'service_required' => ['required', 'string', 'max:150'],
            'project_description' => ['required', 'string', 'min:10'],
            'estimated_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        // Duplicate Cooling & Partner Protection Check
        $duplicateCheck = $this->leadService->detectDuplicate(
            $validated['customer_phone'],
            $validated['customer_email'] ?? null,
            $partner->id
        );

        if ($duplicateCheck['is_duplicate']) {
            if (! $duplicateCheck['same_partner']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors([
                        'customer_phone' => 'This customer lead already exists in the Tech Googly Partner Program and cannot be submitted again at this time.',
                    ])
                    ->with('duplicate_rejected', true)
                    ->with('claimed_phone', $validated['customer_phone'])
                    ->with('claimed_email', $validated['customer_email'] ?? null);
            } else {
                $matched = $duplicateCheck['matched_lead'];
                return redirect()->route('partner.leads.show', $matched)->with(
                    'warning',
                    "You have already submitted this customer lead (#{$matched->lead_number}). You can track its progress below."
                );
            }
        }

        $lead = $this->leadService->createLead($partner, $validated, $request->user());

        return redirect()->route('partner.leads.show', $lead)->with(
            'success',
            "Lead #{$lead->lead_number} submitted successfully! Our enterprise sales team will review and initiate follow-ups."
        );
    }

    /**
     * Display the specified lead (Partner view).
     */
    public function show(Request $request, Lead $lead): View
    {
        $partner = $request->user()->partner;

        // Security check: Partners can only view their own leads
        if ($lead->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to lead.');
        }

        // Canonical timeline: Retrieve ONLY partner-visible activities.
        // Partners must never receive internal notes or team private comments.
        $activities = $lead->activities()
            ->partnerVisible()
            ->with('user')
            ->orderByDesc('created_at')
            ->get();

        return view('partner.leads.show', compact('lead', 'activities', 'partner'));
    }
}
