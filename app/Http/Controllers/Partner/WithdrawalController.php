<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WithdrawalController extends Controller
{
    public function __construct(
        protected WithdrawalService $withdrawalService
    ) {}

    /**
     * Partner Withdrawal Dashboard and History.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $status = $request->query('status');

        $query = WithdrawalRequest::where('partner_id', $partner->id)
            ->with(['payout', 'commissions'])
            ->latest();

        if ($status) {
            if ($status === 'paid') {
                $query->whereIn('status', ['paid', 'settled']);
            } else {
                $query->where('status', $status);
            }
        }

        $withdrawals = $query->paginate(12)->withQueryString();
        $balanceSummary = $this->withdrawalService->getPartnerBalanceSummary($partner);
        $hasPayoutDetails = $partner->hasValidPayoutDetails();

        return view('partner.withdrawals.index', compact('withdrawals', 'balanceSummary', 'status', 'hasPayoutDetails', 'partner'));
    }

    /**
     * Show withdrawal request creation form.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($partner->hasPendingAgreement()) {
            return redirect()->route('partner.agreement.show')
                ->with('warning', 'Action Required: You must review and digitally accept your Business Partner Agreement before requesting withdrawals.');
        }

        $balanceSummary = $this->withdrawalService->getPartnerBalanceSummary($partner);
        $eligibleCommissions = $this->withdrawalService->getEligibleCommissions($partner);
        $hasPayoutDetails = $partner->hasValidPayoutDetails();

        return view('partner.withdrawals.create', compact('partner', 'balanceSummary', 'eligibleCommissions', 'hasPayoutDetails'));
    }

    /**
     * Store a new withdrawal request.
     */
    public function store(Request $request): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($partner->hasPendingAgreement()) {
            return redirect()->route('partner.agreement.show')
                ->with('warning', 'Action Required: You must review and digitally accept your Business Partner Agreement before requesting withdrawals.');
        }

        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:500'],
            'payout_method'   => ['required', 'in:bank_transfer,upi'],
            'partner_notes'   => ['nullable', 'string', 'max:1000'],
            'commission_ids'  => ['nullable', 'array'],
            'commission_ids.*'=> ['integer'],
        ], [
            'amount.min' => 'The minimum withdrawal amount is ₹500.00.',
        ]);

        try {
            $withdrawal = $this->withdrawalService->requestWithdrawal(
                partner: $partner,
                amount: (float) $validated['amount'],
                payoutMethod: $validated['payout_method'],
                partnerNotes: $validated['partner_notes'] ?? null,
                selectedCommissionIds: $validated['commission_ids'] ?? null
            );

            return redirect()
                ->route('partner.withdrawals.show', $withdrawal)
                ->with('success', "Withdrawal request #{$withdrawal->request_number} for ₹" . number_format($withdrawal->amount, 2) . " submitted successfully.");
        } catch (DomainException|InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }
    }

    /**
     * View specific withdrawal details and progress timeline.
     */
    public function show(Request $request, WithdrawalRequest $withdrawal): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($withdrawal->partner_id !== $partner->id) {
            abort(403, 'Unauthorized. You can only view your own withdrawal requests.');
        }

        $withdrawal->load(['payout.processor', 'commissions.project.customer']);

        return view('partner.withdrawals.show', compact('withdrawal', 'partner'));
    }

    /**
     * Cancel a pending withdrawal request.
     */
    public function cancel(Request $request, WithdrawalRequest $withdrawal): RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($withdrawal->partner_id !== $partner->id) {
            abort(403, 'Unauthorized. You can only cancel your own withdrawal requests.');
        }

        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->withdrawalService->cancelWithdrawal(
                withdrawal: $withdrawal,
                partner: $partner,
                reason: $request->input('reason')
            );

            return redirect()
                ->route('partner.withdrawals.show', $withdrawal)
                ->with('success', "Withdrawal request #{$withdrawal->request_number} has been cancelled and your balance has been released.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download payment proof securely (authorized partner only).
     */
    public function downloadProof(Request $request, WithdrawalRequest $withdrawal): StreamedResponse|RedirectResponse
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($withdrawal->partner_id !== $partner->id) {
            abort(403, 'Unauthorized access to payment proof.');
        }

        $payout = $withdrawal->payout;
        if (! $payout || ! $payout->receipt_path) {
            abort(404, 'Payment proof is not available for this payout.');
        }

        if (! Storage::disk('local')->exists($payout->receipt_path)) {
            abort(404, 'Payment proof file could not be found.');
        }

        return Storage::disk('local')->download(
            $payout->receipt_path,
            'TechGoogly_Payout_' . $withdrawal->request_number . '.' . pathinfo($payout->receipt_path, PATHINFO_EXTENSION)
        );
    }
}
