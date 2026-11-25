<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PayoutStatus;
use App\Enums\WithdrawalStatus;
use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\WithdrawalRequest;
use App\Services\WithdrawalService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayoutController extends Controller
{
    public function __construct(
        protected WithdrawalService $withdrawalService
    ) {}

    /**
     * Display authoritative list of partner withdrawal requests and processed payouts.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search');

        $query = WithdrawalRequest::with(['partner.user', 'reviewer', 'payout', 'commissions'])->latest();

        if ($status !== 'all') {
            if ($status === 'paid') {
                $query->whereIn('status', [WithdrawalStatus::Paid, WithdrawalStatus::Settled]);
            } elseif ($status === 'pending') {
                $query->where('status', WithdrawalStatus::Pending);
            } elseif ($status === 'approved') {
                $query->where('status', WithdrawalStatus::Approved);
            } elseif ($status === 'processing') {
                $query->where('status', WithdrawalStatus::Processing);
            } elseif ($status === 'rejected') {
                $query->where('status', WithdrawalStatus::Rejected);
            }
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('request_number', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('partner_code', 'like', "%{$search}%");
                    })
                    ->orWhereHas('payout', function ($poq) use ($search) {
                        $poq->where('transaction_reference', 'like', "%{$search}%");
                    });
            });
        }

        $withdrawals = $query->paginate(15)->withQueryString();

        $metrics = [
            'total_settled_amount' => (float) Payout::where('payment_status', PayoutStatus::Completed)->sum('amount'),
            'pending_requests_cnt' => WithdrawalRequest::where('status', WithdrawalStatus::Pending)->count(),
            'pending_requests_val' => (float) WithdrawalRequest::where('status', WithdrawalStatus::Pending)->sum('amount'),
            'approved_val'         => (float) WithdrawalRequest::where('status', WithdrawalStatus::Approved)->sum('amount'),
            'processing_val'       => (float) WithdrawalRequest::where('status', WithdrawalStatus::Processing)->sum('amount'),
            'paid_amount'          => (float) WithdrawalRequest::whereIn('status', [WithdrawalStatus::Paid, WithdrawalStatus::Settled])->sum('amount'),
            'rejected_count'       => WithdrawalRequest::where('status', WithdrawalStatus::Rejected)->count(),
            'completed_count'      => Payout::where('payment_status', PayoutStatus::Completed)->count(),
        ];

        return view('admin.payouts.index', compact('withdrawals', 'status', 'search', 'metrics'));
    }

    /**
     * Pending requests filter shortcut.
     */
    public function pending(Request $request): View
    {
        $request->merge(['status' => 'pending']);
        return $this->index($request);
    }

    /**
     * Inspect single withdrawal request details.
     */
    public function show(Request $request, WithdrawalRequest $payout): View
    {
        $withdrawal = $payout;
        $withdrawal->load(['partner.user', 'reviewer', 'payout.processor', 'commissions.project.customer']);

        return view('admin.payouts.show', compact('withdrawal'));
    }

    /**
     * Admin approves a pending request.
     */
    public function approve(Request $request, WithdrawalRequest $payout): RedirectResponse
    {
        $adminNotes = $request->input('admin_notes');

        try {
            $this->withdrawalService->approveWithdrawal($payout, auth()->id(), $adminNotes);

            return redirect()
                ->route('admin.payouts.show', $payout)
                ->with('success', "Withdrawal request #{$payout->request_number} has been approved.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin marks an approved request as Processing.
     */
    public function process(Request $request, WithdrawalRequest $payout): RedirectResponse
    {
        $adminNotes = $request->input('admin_notes');

        try {
            $this->withdrawalService->markProcessing($payout, auth()->id(), $adminNotes);

            return redirect()
                ->route('admin.payouts.show', $payout)
                ->with('success', "Withdrawal request #{$payout->request_number} is now marked as Processing.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin rejects a request with mandatory reason.
     */
    public function reject(Request $request, WithdrawalRequest $payout): RedirectResponse
    {
        $validated = $request->validate([
            'reason'      => ['required', 'string', 'max:1000'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'reason.required' => 'A rejection reason is mandatory when rejecting a partner withdrawal.',
        ]);

        try {
            $this->withdrawalService->rejectWithdrawal(
                withdrawal: $payout,
                reason: $validated['reason'],
                adminUserId: auth()->id(),
                adminNotes: $validated['admin_notes'] ?? null
            );

            return redirect()
                ->route('admin.payouts.show', $payout)
                ->with('success', "Withdrawal request #{$payout->request_number} has been rejected and the reserved balance released.");
        } catch (DomainException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Admin marks a payout as Paid / Completed.
     */
    public function complete(Request $request, WithdrawalRequest $payout): RedirectResponse
    {
        $validated = $request->validate([
            'transaction_reference' => ['required', 'string', 'max:100'],
            'payment_date'          => ['required', 'date'],
            'receipt'               => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'admin_notes'           => ['nullable', 'string', 'max:1000'],
        ], [
            'transaction_reference.required' => 'UTR / Bank transaction reference is mandatory to mark payout as Paid.',
            'payment_date.required'          => 'Payment dispatch date is required.',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('private/payout_receipts', 'local');
        }

        try {
            $payoutRecord = $this->withdrawalService->completePayout(
                withdrawal: $payout,
                transactionReference: $validated['transaction_reference'],
                paymentDate: $validated['payment_date'],
                adminUserId: auth()->id(),
                receiptPath: $receiptPath,
                adminNotes: $validated['admin_notes'] ?? null
            );

            return redirect()
                ->route('admin.payouts.show', $payout)
                ->with('success', "Payout marked as Paid! Payout voucher #{$payoutRecord->payout_number} generated.");
        } catch (DomainException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download payment proof securely (Admin only).
     */
    public function downloadProof(Request $request, WithdrawalRequest $payout): StreamedResponse|RedirectResponse
    {
        $payoutRecord = $payout->payout;
        if (! $payoutRecord || ! $payoutRecord->receipt_path) {
            abort(404, 'Payment proof is not available.');
        }

        if (! Storage::disk('local')->exists($payoutRecord->receipt_path)) {
            abort(404, 'Receipt file could not be found.');
        }

        return Storage::disk('local')->download(
            $payoutRecord->receipt_path,
            'TechGoogly_Admin_Receipt_' . $payout->request_number . '.' . pathinfo($payoutRecord->receipt_path, PATHINFO_EXTENSION)
        );
    }
}
