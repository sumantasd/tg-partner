<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadDisputeCategory;
use App\Enums\LeadDisputeStatus;
use App\Http\Controllers\Controller;
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
     * Display a listing of all lead disputes with status metrics and filters.
     */
    public function index(Request $request): View
    {
        $query = LeadDispute::with(['partner.user', 'lead.partner', 'resolver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('dispute_category')) {
            $query->where('dispute_category', $request->dispute_category);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('dispute_number', 'like', "%{$search}%")
                    ->orWhere('customer_phone_claimed', 'like', "%{$search}%")
                    ->orWhere('customer_email_claimed', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('company_name', 'like', "%{$search}%");
                    });
            });
        }

        $disputes = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        $counts = [
            'total'        => LeadDispute::count(),
            'pending'      => LeadDispute::where('status', LeadDisputeStatus::Pending)->count(),
            'under_review' => LeadDispute::where('status', LeadDisputeStatus::UnderReview)->count(),
            'resolved'     => LeadDispute::where('status', LeadDisputeStatus::Resolved)->count(),
            'rejected'     => LeadDispute::where('status', LeadDisputeStatus::Rejected)->count(),
        ];

        return view('admin.disputes.index', compact('disputes', 'counts'));
    }

    /**
     * Display dispute details, claimant evidence, ownership background, and resolution actions.
     */
    public function show(LeadDispute $dispute): View
    {
        $dispute->load([
            'partner.user',
            'lead.partner.user',
            'lead.originalPartner.user',
            'lead.ownershipHistories.previousPartner',
            'lead.ownershipHistories.newPartner',
            'resolver',
        ]);

        return view('admin.disputes.show', compact('dispute'));
    }

    /**
     * Mark dispute status (e.g. Under Review).
     */
    public function updateStatus(Request $request, LeadDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(LeadDisputeStatus::class)],
        ]);

        $this->disputeService->updateStatus($dispute, LeadDisputeStatus::from($validated['status']), $request->user());

        return redirect()->route('admin.lead-disputes.show', $dispute)->with(
            'success',
            'Dispute status updated successfully.'
        );
    }

    /**
     * Log internal administrator review notes.
     */
    public function addNotes(Request $request, LeadDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['required', 'string', 'min:2', 'max:5000'],
        ]);

        $this->disputeService->addAdminNotes($dispute, $validated['admin_notes'], $request->user());

        return redirect()->route('admin.lead-disputes.show', $dispute)->with(
            'success',
            'Internal admin review notes recorded.'
        );
    }

    /**
     * Resolve dispute with mandatory decision reason and optional reassignment.
     */
    public function resolve(Request $request, LeadDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_reason'    => ['required', 'string', 'min:5', 'max:2000'],
            'reassign_to_claimant' => ['nullable', 'boolean'],
        ]);

        $this->disputeService->resolveDispute($dispute, $validated, $request->user());

        return redirect()->route('admin.lead-disputes.show', $dispute)->with(
            'success',
            "Dispute #{$dispute->dispute_number} resolved successfully."
        );
    }

    /**
     * Reject dispute with mandatory rejection reason.
     */
    public function reject(Request $request, LeadDispute $dispute): RedirectResponse
    {
        $validated = $request->validate([
            'resolution_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        $this->disputeService->rejectDispute($dispute, $validated['resolution_reason'], $request->user());

        return redirect()->route('admin.lead-disputes.show', $dispute)->with(
            'warning',
            "Dispute #{$dispute->dispute_number} has been rejected."
        );
    }

    /**
     * Download uploaded dispute evidence file securely.
     */
    public function downloadEvidence(LeadDispute $dispute): StreamedResponse
    {
        if (!$dispute->evidence_path || !Storage::disk('local')->exists($dispute->evidence_path)) {
            abort(404, 'Evidence file not found.');
        }

        return Storage::disk('local')->download(
            $dispute->evidence_path,
            $dispute->evidence_filename ?? 'evidence.pdf'
        );
    }
}
