<?php

namespace App\Http\Controllers\Partner;

use App\Enums\LeadDisputeCategory;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadDispute;
use App\Services\LeadDisputeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LeadDisputeController extends Controller
{
    public function __construct(
        protected LeadDisputeService $disputeService
    ) {}

    /**
     * Display a listing of disputes raised by the partner.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;

        $query = LeadDispute::where('partner_id', $partner->id)
            ->with(['lead']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $disputes = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('partner.disputes.index', compact('partner', 'disputes'));
    }

    /**
     * Show form to raise a new ownership dispute.
     */
    public function create(Request $request): View
    {
        $partner = $request->user()->partner;

        $lead = null;
        if ($request->filled('lead_id')) {
            $lead = Lead::find($request->lead_id);
        }

        $prefillPhone = $request->query('phone', session('claimed_phone'));
        $prefillEmail = $request->query('email', session('claimed_email'));

        $categories = LeadDisputeCategory::cases();

        return view('partner.disputes.create', compact('partner', 'lead', 'prefillPhone', 'prefillEmail', 'categories'));
    }

    /**
     * Store a newly created dispute.
     */
    public function store(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;

        $validated = $request->validate([
            'lead_id'                => ['nullable', 'integer', 'exists:leads,id'],
            'dispute_category'       => ['required', Rule::enum(LeadDisputeCategory::class)],
            'customer_phone_claimed' => ['nullable', 'string', 'max:30'],
            'customer_email_claimed' => ['nullable', 'email', 'max:255'],
            'explanation'            => ['required', 'string', 'min:10', 'max:5000'],
            'evidence'               => ['nullable', 'file', 'mimes:pdf,png,jpg,jpeg,doc,docx', 'max:10240'],
        ]);

        $dispute = $this->disputeService->createDispute($partner, $validated, $request->file('evidence'));

        return redirect()->route('partner.disputes.show', $dispute)->with(
            'success',
            "Dispute #{$dispute->dispute_number} submitted successfully. Our program management team has been notified and will review your submission."
        );
    }

    /**
     * Display details of a partner's dispute.
     */
    public function show(Request $request, LeadDispute $dispute): View
    {
        $partner = $request->user()->partner;

        // IDOR Protection: Partner can only view their own disputes
        if ($dispute->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to dispute.');
        }

        $dispute->load(['lead.assignedTo', 'resolver']);

        return view('partner.disputes.show', compact('partner', 'dispute'));
    }

    /**
     * Download supporting evidence file privately.
     */
    public function downloadEvidence(Request $request, LeadDispute $dispute): StreamedResponse
    {
        $partner = $request->user()->partner;

        // IDOR Protection
        if ($dispute->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to dispute evidence.');
        }

        if (!$dispute->evidence_path || !Storage::disk('local')->exists($dispute->evidence_path)) {
            abort(404, 'Evidence file not found.');
        }

        return Storage::disk('local')->download(
            $dispute->evidence_path,
            $dispute->evidence_filename ?? 'evidence.pdf'
        );
    }
}
