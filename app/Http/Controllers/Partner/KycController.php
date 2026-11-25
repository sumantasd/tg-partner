<?php

namespace App\Http\Controllers\Partner;

use App\Enums\KycStatus;
use App\Http\Controllers\Controller;
use App\Models\PartnerKyc;
use App\Services\KycService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class KycController extends Controller
{
    public function __construct(
        protected KycService $kycService
    ) {}

    /**
     * Show Partner KYC status, verification badges, and submitted details.
     */
    public function show(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $kyc = $partner->kyc;
        $histories = $kyc ? $kyc->histories()->with('admin')->get() : collect();

        return view('partner.kyc.show', compact('partner', 'kyc', 'histories'));
    }

    /**
     * Show KYC submission form.
     * Enforces submission lock: redirects if already submitted and locked.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }
        $partner->load('user');

        $kyc = $partner->kyc;

        if ($kyc && $kyc->isLocked()) {
            return redirect()
                ->route('partner.kyc.show')
                ->with('warning', 'Your KYC application has already been submitted and is locked for review. You cannot edit submitted details.');
        }

        if ($kyc && $kyc->isCorrectionRequested()) {
            return redirect()->route('partner.kyc.correction');
        }

        return view('partner.kyc.form', [
            'partner'      => $partner,
            'kyc'          => $kyc,
            'isCorrection' => false,
        ]);
    }

    /**
     * Store initial KYC submission.
     */
    public function store(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($partner->kyc && $partner->kyc->isLocked()) {
            return redirect()
                ->route('partner.kyc.show')
                ->withErrors(['kyc' => 'Your KYC application is locked under review and cannot be modified.']);
        }

        $partnerType = $request->input('partner_type', 'individual');
        $isIndividual = in_array($partnerType, ['individual', 'individual_sole_proprietor'], true);
        $isAadhaar = $request->input('identity_document_type') === 'aadhaar';
        $addressDocRequired = $request->has('id_contains_current_address') && ! $request->boolean('id_contains_current_address');

        $rules = [
            'legal_name'                   => ['required', 'string', 'max:150'],
            'partner_type'                 => ['required', 'in:individual,individual_sole_proprietor,business,partnership_firm,llp,private_limited,public_limited,other'],
            'dob_or_incorporation_date'    => ['required', 'date', 'before:today'],
            'authorized_signatory_name'    => [$isIndividual ? 'nullable' : 'required', 'string', 'max:150'],
            'business_registration_number' => [$isIndividual ? 'nullable' : 'required', 'string', 'max:100'],
            'gstin'                        => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'pan_number'                   => ['required', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'registered_address'           => ['required', 'string', 'max:500'],
            'city'                         => ['required', 'string', 'max:100'],
            'state'                        => ['required', 'string', 'max:100'],
            'postal_code'                  => ['required', 'regex:/^[1-9][0-9]{5}$/'],
            'country'                      => ['nullable', 'string', 'max:100'],
            'identity_document_type'       => ['required', 'in:aadhaar,passport,voter_id,driving_license'],
            'pan_document'                 => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'identity_document'            => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'identity_document_back'       => [$isAadhaar ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'id_contains_current_address'  => ['nullable', 'boolean'],
            'address_document'             => [$addressDocRequired ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'bank_proof_document'          => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'bank_account_holder_name'     => ['required', 'string', 'max:150'],
            'bank_name'                    => ['required', 'string', 'max:150'],
            'bank_account_number'          => ['required', 'string', 'min:9', 'max:24', 'confirmed'],
            'bank_ifsc_code'               => ['required', 'string', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'upi_id'                       => ['nullable', 'string', 'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/'],
            'preferred_payout_method'      => ['required', 'in:bank_transfer,upi'],
            'declaration_accepted'         => ['accepted'],
        ];

        $validated = $request->validate($rules, [
            'pan_number.regex'                => 'Please enter a valid 10-character Indian PAN number (e.g. ABCDE1234F).',
            'postal_code.regex'               => 'Please enter a valid 6-digit Indian PIN code.',
            'gstin.regex'                     => 'Please enter a valid 15-character Indian GSTIN format (e.g. 19ABCDE1234F1Z5).',
            'bank_ifsc_code.regex'            => 'Please enter a valid 11-character Indian IFSC code (e.g. HDFC0001234).',
            'upi_id.regex'                    => 'Please enter a valid UPI ID (e.g. yourname@okhdfcbank).',
            'bank_account_number.confirmed'   => 'The bank account number confirmation does not match.',
            'pan_document.max'                => 'PAN document file size cannot exceed 5MB.',
            'identity_document.max'           => 'Primary identity proof document file size cannot exceed 5MB.',
            'identity_document_back.required' => 'Aadhaar Card requires both Front and Back document uploads.',
            'identity_document_back.max'      => 'Aadhaar Back document file size cannot exceed 5MB.',
            'address_document.required'       => 'Address Proof document is required when your primary ID does not show your current address.',
            'address_document.max'            => 'Address proof document file size cannot exceed 5MB.',
            'bank_proof_document.max'         => 'Bank proof document file size cannot exceed 5MB.',
            'declaration_accepted.accepted'   => 'You must accept the declaration and consent statement before submitting your application.',
        ]);

        try {
            $files = [
                'pan_document'        => $request->file('pan_document'),
                'identity_document'   => $request->file('identity_document'),
                'bank_proof_document' => $request->file('bank_proof_document'),
            ];

            if ($request->hasFile('identity_document_back')) {
                $files['identity_document_back'] = $request->file('identity_document_back');
            }

            if ($request->hasFile('address_document')) {
                $files['address_document'] = $request->file('address_document');
            }

            // Name correlation check
            $holderClean = strtolower(preg_replace('/[^a-z0-9]/', '', $validated['bank_account_holder_name']));
            $legalClean = strtolower(preg_replace('/[^a-z0-9]/', '', $validated['legal_name']));
            $signatoryClean = ! empty($validated['authorized_signatory_name']) ? strtolower(preg_replace('/[^a-z0-9]/', '', $validated['authorized_signatory_name'])) : '';

            $nameMatches = ($holderClean === $legalClean || str_contains($holderClean, $legalClean) || str_contains($legalClean, $holderClean));
            if (! $nameMatches && $signatoryClean) {
                $nameMatches = ($holderClean === $signatoryClean || str_contains($holderClean, $signatoryClean) || str_contains($signatoryClean, $holderClean));
            }

            $warningMessage = null;
            if (! $nameMatches) {
                $warningMessage = 'Notice: Bank Account Holder Name does not closely match Legal/Signatory Name. Our compliance team will manually review your bank passbook/cheque during verification.';
            }

            $this->kycService->submitKyc(
                partner: $partner,
                data: $validated,
                files: $files,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            $redirect = redirect()
                ->route('partner.kyc.show')
                ->with('success', 'Your KYC and bank details have been submitted successfully. All fields are now locked under review.');

            if ($warningMessage) {
                $redirect->with('warning', $warningMessage);
            }

            return $redirect;
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['kyc' => $e->getMessage()]);
        }
    }

    /**
     * Show KYC correction form when requested by Admin.
     */
    public function correction(Request $request): View|RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $kyc = $partner->kyc;

        if (! $kyc || ! $kyc->isCorrectionRequested()) {
            return redirect()
                ->route('partner.kyc.show')
                ->with('info', 'No correction has been requested for your KYC application.');
        }

        return view('partner.kyc.form', [
            'partner'      => $partner,
            'kyc'          => $kyc,
            'isCorrection' => true,
        ]);
    }

    /**
     * Resubmit corrected KYC details.
     */
    public function resubmit(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $kyc = $partner->kyc;

        if (! $kyc || ! $kyc->isCorrectionRequested()) {
            return redirect()
                ->route('partner.kyc.show')
                ->withErrors(['kyc' => 'Correction is not authorized for this KYC application.']);
        }

        $rules = [
            'legal_name'                   => ['nullable', 'string', 'max:150'],
            'authorized_signatory_name'    => ['nullable', 'string', 'max:150'],
            'business_registration_number' => ['nullable', 'string', 'max:100'],
            'gstin'                        => ['nullable', 'string', 'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/'],
            'dob_or_incorporation_date'    => ['nullable', 'date', 'before:today'],
            'pan_number'                   => ['nullable', 'string', 'regex:/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/'],
            'registered_address'           => ['nullable', 'string', 'max:500'],
            'city'                         => ['nullable', 'string', 'max:100'],
            'state'                        => ['nullable', 'string', 'max:100'],
            'postal_code'                  => ['nullable', 'regex:/^[1-9][0-9]{5}$/'],
            'bank_account_holder_name'     => ['nullable', 'string', 'max:150'],
            'bank_name'                    => ['nullable', 'string', 'max:150'],
            'bank_account_number'          => ['nullable', 'string', 'min:9', 'max:24', 'confirmed'],
            'bank_ifsc_code'               => ['nullable', 'string', 'regex:/^[A-Za-z]{4}0[A-Za-z0-9]{6}$/'],
            'upi_id'                       => ['nullable', 'string', 'regex:/^[a-zA-Z0-9.\-_]{2,256}@[a-zA-Z]{2,64}$/'],
            'preferred_payout_method'      => ['nullable', 'in:bank_transfer,upi'],
            'pan_document'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'identity_document'            => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'identity_document_back'       => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'address_document'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'bank_proof_document'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'id_contains_current_address'  => ['nullable', 'boolean'],
            'declaration_accepted'         => ['nullable', 'boolean'],
        ];

        $validated = $request->validate($rules, [
            'pan_number.regex'     => 'Please enter a valid 10-character Indian PAN number (e.g. ABCDE1234F).',
            'bank_ifsc_code.regex' => 'Please enter a valid 11-character Indian IFSC code (e.g. HDFC0001234).',
            'bank_account_number.confirmed' => 'The bank account number confirmation does not match.',
        ]);

        try {
            $files = [];
            if ($request->hasFile('pan_document')) $files['pan_document'] = $request->file('pan_document');
            if ($request->hasFile('identity_document')) $files['identity_document'] = $request->file('identity_document');
            if ($request->hasFile('identity_document_back')) $files['identity_document_back'] = $request->file('identity_document_back');
            if ($request->hasFile('address_document')) $files['address_document'] = $request->file('address_document');
            if ($request->hasFile('bank_proof_document')) $files['bank_proof_document'] = $request->file('bank_proof_document');

            $this->kycService->resubmitCorrection(
                partner: $partner,
                data: $validated,
                files: $files,
                ip: $request->ip(),
                userAgent: $request->userAgent()
            );

            return redirect()
                ->route('partner.kyc.show')
                ->with('success', 'Your corrected KYC application has been locked and resubmitted for Admin review.');
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['kyc' => $e->getMessage()]);
        }
    }

    /**
     * Securely download or stream private KYC document.
     * Enforces ownership authorization.
     */
    public function downloadDocument(Request $request, string $type): Response
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $kyc = $partner->kyc;
        if (! $kyc) {
            abort(404, 'No KYC application found.');
        }

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
