<?php

namespace App\Http\Controllers\Partner;

use App\Enums\AgreementStatus;
use App\Http\Controllers\Controller;
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
     * Show Partner Agreement view with status banner, PDF download, and digital acceptance form.
     */
    public function show(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        // If no agreement exists, automatically generate one for this approved partner
        $agreement = $partner->agreements()->latest()->first();
        if (!$agreement) {
            $agreement = $this->agreementService->generateAgreement($partner);
        }

        return view('partner.agreement.show', compact('partner', 'agreement'));
    }

    /**
     * Digitally accept the pending agreement.
     */
    public function accept(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $request->validate([
            'accepted' => ['required', 'accepted'],
        ], [
            'accepted.accepted' => 'You must check the declaration box to digitally accept and sign the agreement.',
            'accepted.required' => 'You must check the declaration box to digitally accept and sign the agreement.',
        ]);

        $agreement = $partner->agreements()
            ->where('status', AgreementStatus::PendingAcceptance)
            ->latest()
            ->first();

        if (!$agreement) {
            return back()->with('info', 'There is no pending agreement requiring your acceptance.');
        }

        $this->agreementService->acceptAgreement(
            $agreement,
            $request->user(),
            $request->ip(),
            $request->userAgent() ?? 'Unknown Browser'
        );

        return redirect()->route('partner.agreement.show')
            ->with('success', "Agreement #{$agreement->agreement_number} has been digitally signed and accepted. Your partnership covenants are fully active!");
    }

    /**
     * Securely download official PDF agreement.
     */
    public function download(Request $request): Response
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $agreement = $partner->agreements()->latest()->firstOrFail();

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
    public function preview(Request $request): Response
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $agreement = $partner->agreements()->latest()->firstOrFail();

        if (!Storage::disk('local')->exists($agreement->pdf_path)) {
            $this->agreementService->generateAndStorePdf($agreement);
        }

        $fileName = "Tech_Googly_Partner_Agreement_{$agreement->agreement_number}.pdf";

        return Storage::disk('local')->response($agreement->pdf_path, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
}
