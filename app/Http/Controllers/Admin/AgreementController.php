<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AgreementStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Services\PartnerAgreementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class AgreementController extends Controller
{
    public function __construct(
        protected PartnerAgreementService $agreementService
    ) {}

    /**
     * Display a listing of all partner agreements with filtering and search.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $search = trim($request->query('search', ''));

        $query = PartnerAgreement::with(['partner.user', 'creator', 'revoker'])->latest();

        if ($status !== 'all' && AgreementStatus::tryFrom($status)) {
            $query->where('status', $status);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('agreement_number', 'like', "%{$search}%")
                  ->orWhereHas('partner', function ($pq) use ($search) {
                      $pq->where('partner_code', 'like', "%{$search}%")
                         ->orWhere('company_name', 'like', "%{$search}%")
                         ->orWhereHas('user', function ($uq) use ($search) {
                             $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                         });
                  });
            });
        }

        $agreements = $query->paginate(15)->withQueryString();

        $counts = [
            'all'                => PartnerAgreement::count(),
            'pending_acceptance' => PartnerAgreement::where('status', AgreementStatus::PendingAcceptance)->count(),
            'accepted'           => PartnerAgreement::where('status', AgreementStatus::Accepted)->count(),
            'revoked'            => PartnerAgreement::where('status', AgreementStatus::Revoked)->count(),
            'superseded'         => PartnerAgreement::where('status', AgreementStatus::Superseded)->count(),
        ];

        return view('admin.agreements.index', compact('agreements', 'status', 'search', 'counts'));
    }

    /**
     * Display detailed agreement dossier and audit trail.
     */
    public function show(PartnerAgreement $agreement): View
    {
        $agreement->load(['partner.user', 'template', 'commissionPolicy', 'creator', 'revoker']);

        return view('admin.agreements.show', compact('agreement'));
    }

    /**
     * Securely download official PDF agreement.
     */
    public function download(PartnerAgreement $agreement): Response
    {
        if (!Storage::disk('local')->exists($agreement->pdf_path)) {
            $this->agreementService->generateAndStorePdf($agreement);
        }

        $fileName = "Tech_Googly_Partner_Agreement_{$agreement->agreement_number}.pdf";

        return Storage::disk('local')->download($agreement->pdf_path, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Securely stream PDF for inline browser preview.
     */
    public function preview(PartnerAgreement $agreement): Response
    {
        if (!Storage::disk('local')->exists($agreement->pdf_path)) {
            $this->agreementService->generateAndStorePdf($agreement);
        }

        $fileName = "Tech_Googly_Partner_Agreement_{$agreement->agreement_number}.pdf";

        return Storage::disk('local')->response($agreement->pdf_path, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Issue agreement for an existing approved partner.
     */
    public function issueForPartner(Request $request, Partner $partner): RedirectResponse
    {
        $force = $request->boolean('force', false);
        $agreement = $this->agreementService->generateAgreement($partner, auth()->id(), ['force' => $force]);

        return redirect()->route('admin.agreements.show', $agreement)
            ->with('success', "Agreement #{$agreement->agreement_number} successfully issued for Partner #{$partner->partner_code}.");
    }

    /**
     * Revoke an existing agreement with a mandatory reason.
     */
    public function revoke(Request $request, PartnerAgreement $agreement): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->agreementService->revokeAgreement($agreement, $validated['reason'], auth()->id());

        return back()->with('success', "Agreement #{$agreement->agreement_number} has been revoked.");
    }

    /**
     * Reissue agreement (supersedes existing and creates new).
     */
    public function reissue(Request $request, PartnerAgreement $agreement): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $newAgreement = $this->agreementService->reissueAgreement($agreement, auth()->id(), $validated['reason'] ?? null);

        return redirect()->route('admin.agreements.show', $newAgreement)
            ->with('success', "Agreement reissued as #{$newAgreement->agreement_number} (v{$newAgreement->version}). Previous agreement marked superseded.");
    }
}
