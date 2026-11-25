<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BankVerificationStatus;
use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\PartnerKyc;
use App\Services\KycService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * Admin KYC Dashboard with 6 KPI metrics, filter tabs, and search.
     */
    public function index(Request $request): View
    {
        $query = PartnerKyc::with(['partner', 'reviewer', 'kycApprover', 'bankVerifier']);

        // Search
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('legal_name', 'like', "%{$search}%")
                    ->orWhere('pan_number_masked', 'like', "%{$search}%")
                    ->orWhere('bank_account_holder_name', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('partner_code', 'like', "%{$search}%")
                            ->orWhere('company_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        // Status Filter Tab
        $status = $request->input('status');
        if ($status && in_array($status, ['pending', 'under_review', 'approved', 'rejected', 'correction_requested'], true)) {
            $query->where('kyc_status', $status);
        }

        $applications = $query->orderBy('submitted_at', 'desc')->paginate(15)->withQueryString();

        $kpis = [
            'total_applications'   => PartnerKyc::where('kyc_status', '!=', KycStatus::NotSubmitted)->count(),
            'pending_verification' => PartnerKyc::where('kyc_status', KycStatus::Pending)->count(),
            'under_review'         => PartnerKyc::where('kyc_status', KycStatus::UnderReview)->count(),
            'approved'             => PartnerKyc::where('kyc_status', KycStatus::Approved)->count(),
            'rejected'             => PartnerKyc::where('kyc_status', KycStatus::Rejected)->count(),
            'correction_requested' => PartnerKyc::where('kyc_status', KycStatus::CorrectionRequested)->count(),
        ];

        return view('admin.kyc.index', compact('applications', 'kpis', 'status'));
    }

    /**
     * Filter view: Pending & Under Review KYC Applications.
     */
    public function pending(Request $request): View
    {
        $request->merge(['status' => 'pending']);
        return $this->index($request);
    }

    /**
     * Filter view: Approved KYC Applications.
     */
    public function approved(Request $request): View
    {
        $request->merge(['status' => 'approved']);
        return $this->index($request);
    }

    /**
     * Filter view: Rejected KYC Applications.
     */
    public function rejected(Request $request): View
    {
        $request->merge(['status' => 'rejected']);
        return $this->index($request);
    }

    /**
     * Show detailed KYC verification panel for an application.
     */
    public function show(PartnerKyc $kyc): View
    {
        $kyc->load(['partner.user', 'reviewer', 'kycApprover', 'bankVerifier', 'histories.admin']);

        // Check if legal name or authorized signatory matches bank account holder name
        $nameMatch = false;
        if ($kyc->legal_name && $kyc->bank_account_holder_name) {
            $name1 = strtolower(preg_replace('/[^a-z0-9]/', '', $kyc->legal_name));
            $name2 = strtolower(preg_replace('/[^a-z0-9]/', '', $kyc->bank_account_holder_name));
            $nameMatch = ($name1 === $name2 || str_contains($name1, $name2) || str_contains($name2, $name1));
        }
        if (! $nameMatch && $kyc->authorized_signatory_name && $kyc->bank_account_holder_name) {
            $name3 = strtolower(preg_replace('/[^a-z0-9]/', '', $kyc->authorized_signatory_name));
            $name2 = strtolower(preg_replace('/[^a-z0-9]/', '', $kyc->bank_account_holder_name));
            $nameMatch = ($name3 === $name2 || str_contains($name3, $name2) || str_contains($name2, $name3));
        }

        return view('admin.kyc.show', compact('kyc', 'nameMatch'));
    }

    /**
     * Admin marks application as Under Review.
     */
    public function startReview(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        try {
            $this->kycService->startReview($kyc, $request->user());
            return back()->with('success', "Application is now marked Under Review by {$request->user()->name}.");
        } catch (DomainException $e) {
            return back()->withErrors(['review' => $e->getMessage()]);
        }
    }

    /**
     * Admin approves KYC documents and identity.
     */
    public function approveKyc(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        $internalNotes = $request->input('internal_admin_notes');

        $this->kycService->approveKyc($kyc, $request->user(), $internalNotes);

        return back()->with('success', 'KYC & Personal Identity details have been approved successfully.');
    }

    /**
     * Admin verifies bank account details and proof.
     */
    public function verifyBank(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        $internalNotes = $request->input('internal_admin_notes');

        $this->kycService->verifyBank($kyc, $request->user(), $internalNotes);

        return back()->with('success', 'Bank account details have been verified and synchronized to partner payout configuration.');
    }

    /**
     * Admin rejects KYC with mandatory reason.
     */
    public function rejectKyc(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason'     => ['required', 'string', 'min:5', 'max:1000'],
            'internal_admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => 'A mandatory rejection reason is required.',
        ]);

        try {
            $this->kycService->rejectKyc(
                kyc: $kyc,
                admin: $request->user(),
                reason: $validated['rejection_reason'],
                internalNotes: $validated['internal_admin_notes'] ?? null
            );

            return back()->with('success', 'KYC application has been marked as Rejected.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['rejection_reason' => $e->getMessage()]);
        }
    }

    /**
     * Admin rejects Bank Verification with mandatory reason.
     */
    public function rejectBank(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason'     => ['required', 'string', 'min:5', 'max:1000'],
            'internal_admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'rejection_reason.required' => 'A mandatory rejection reason is required.',
        ]);

        try {
            $this->kycService->rejectBank(
                kyc: $kyc,
                admin: $request->user(),
                reason: $validated['rejection_reason'],
                internalNotes: $validated['internal_admin_notes'] ?? null
            );

            return back()->with('success', 'Bank account verification has been marked as Rejected.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['rejection_reason' => $e->getMessage()]);
        }
    }

    /**
     * Admin requests correction with mandatory instructions and allowed fields.
     */
    public function requestCorrection(Request $request, PartnerKyc $kyc): RedirectResponse
    {
        $validated = $request->validate([
            'correction_reason'    => ['required', 'string', 'min:5', 'max:1000'],
            'allowed_fields'       => ['nullable', 'array'],
            'allowed_fields.*'     => ['string'],
            'internal_admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'correction_reason.required' => 'Mandatory correction instructions are required.',
        ]);

        try {
            $this->kycService->requestCorrection(
                kyc: $kyc,
                admin: $request->user(),
                reason: $validated['correction_reason'],
                allowedFields: $validated['allowed_fields'] ?? null,
                internalNotes: $validated['internal_admin_notes'] ?? null
            );

            return back()->with('success', 'Correction request has been dispatched to the partner.');
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['correction_reason' => $e->getMessage()]);
        }
    }

    /**
     * Securely download or stream private KYC document for Admin.
     */
    public function downloadDocument(Request $request, PartnerKyc $kyc, string $type): Response
    {
        $path = match ($type) {
            'pan'           => $kyc->pan_document_path,
            'identity'      => $kyc->identity_document_path,
            'identity_back' => $kyc->identity_document_back_path,
            'address'       => $kyc->address_document_path,
            'bank_proof'    => $kyc->bank_proof_document_path,
            default         => null,
        };

        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Requested document not found.');

        return Storage::disk('local')->download($path);
    }
}
