<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Project;
use App\Services\CommissionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PaymentController extends Controller
{
    /**
     * List all customer payments with multi-faceted filtering, search, and metrics.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $method = $request->query('method');
        $customerId = $request->query('customer_id');
        $projectId = $request->query('project_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = CustomerPayment::with(['customer', 'project', 'partner.user', 'verifiedBy', 'recordedBy', 'commission'])
            ->latest('payment_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('transaction_reference', 'like', "%{$search}%")
                  ->orWhereHas('customer', fn ($sq) => $sq->where('name', 'like', "%{$search}%"))
                  ->orWhereHas('project', fn ($sq) => $sq->where('project_title', 'like', "%{$search}%"));
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($method) {
            $query->where('payment_method', $method);
        }

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($dateFrom) {
            $query->whereDate('payment_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('payment_date', '<=', $dateTo);
        }

        $payments = $query->paginate(15)->withQueryString();
        $customers = Customer::orderBy('name')->get();
        $projects = Project::orderBy('project_title')->get();

        $metrics = [
            'total_payments'    => CustomerPayment::count(),
            'total_amount'      => (float) CustomerPayment::sum('amount'),
            'pending_count'     => CustomerPayment::where('status', CustomerPaymentStatus::Recorded)->count(),
            'pending_amount'    => (float) CustomerPayment::where('status', CustomerPaymentStatus::Recorded)->sum('amount'),
            'verified_count'    => CustomerPayment::where('status', CustomerPaymentStatus::Verified)->count(),
            'verified_amount'   => (float) CustomerPayment::where('status', CustomerPaymentStatus::Verified)->sum('amount'),
            'rejected_count'    => CustomerPayment::where('status', CustomerPaymentStatus::Rejected)->count(),
        ];

        return view('admin.payments.index', compact(
            'payments', 'customers', 'projects', 'search', 'status', 'method',
            'customerId', 'projectId', 'dateFrom', 'dateTo', 'metrics'
        ));
    }

    /**
     * Show form to record a new customer payment.
     */
    public function create(Request $request): View
    {
        $projectId = $request->query('project_id');
        $selectedProject = $projectId ? Project::with(['customer', 'partner'])->findOrFail($projectId) : null;
        $projects = Project::with(['customer', 'partner'])->orderBy('project_title')->get();

        return view('admin.payments.create', compact('selectedProject', 'projects'));
    }

    /**
     * Store a recorded customer payment.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'project_id'            => ['required', 'exists:projects,id'],
            'payment_type'          => ['required', Rule::enum(CustomerPaymentType::class)],
            'billing_month'         => ['nullable', 'string', 'max:7'],
            'billing_period'        => ['nullable', 'string', 'max:100'],
            'payment_method'        => ['required', Rule::enum(PaymentMethod::class)],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'payment_date'          => ['required', 'date'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string'],
            'proof_attachment'      => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'], // Max 5MB
        ]);

        $project = Project::with(['customer', 'partner'])->findOrFail($validated['project_id']);

        $proofPath = null;
        if ($request->hasFile('proof_attachment')) {
            $proofPath = $request->file('proof_attachment')->store('payment_proofs', 'local');
        }

        // Sequential payment number
        $year = date('Y');
        $prefix = "CPAY-{$year}-";
        $latest = CustomerPayment::where('payment_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(payment_number, 11) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();
        $nextSeq = ($latest && preg_match('/^CPAY-\d{4}-(\d+)$/', $latest->payment_number, $matches))
            ? (int) $matches[1] + 1 : 1;
        $paymentNumber = sprintf('%s%04d', $prefix, $nextSeq);

        $billingMonth = $validated['billing_month'] ?? date('Y-m', strtotime($validated['payment_date']));
        $billingPeriod = $validated['billing_period'] ?? date('F Y', strtotime($validated['payment_date']));

        $payment = CustomerPayment::create([
            'payment_number'        => $paymentNumber,
            'project_id'            => $project->id,
            'customer_id'           => $project->customer_id,
            'partner_id'            => $project->partner_id,
            'payment_type'          => $validated['payment_type'],
            'billing_month'         => $billingMonth,
            'billing_period'        => $billingPeriod,
            'amount'                => $validated['amount'],
            'currency'              => $project->currency ?? 'INR',
            'payment_method'        => $validated['payment_method'],
            'transaction_reference' => $validated['transaction_reference'] ?? null,
            'payment_date'          => $validated['payment_date'],
            'proof_attachment_path' => $proofPath,
            'recorded_by'           => $request->user()->id,
            'status'                => CustomerPaymentStatus::Recorded,
            'commission_generated'  => false,
            'notes'                 => $validated['notes'] ?? null,
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::CustomerPaymentRecorded,
            module: \App\Enums\AuditLogModule::Financial,
            description: "Customer Payment #{$payment->payment_number} for ₹" . number_format($payment->amount, 2) . " recorded by {$request->user()->name}.",
            subject: $payment,
            newValues: [
                'payment_number' => $payment->payment_number,
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status->value,
            ],
            actor: $request->user()
        );

        return redirect()->route('admin.payments.show', $payment)
            ->with('success', "Payment #{$payment->payment_number} recorded successfully in Pending Verification state.");
    }

    /**
     * Show payment details with verification controls and proof.
     */
    public function show(CustomerPayment $payment): View
    {
        $payment->load([
            'project.partner.user',
            'customer',
            'recordedBy',
            'verifiedBy',
            'commission',
        ]);

        return view('admin.payments.show', compact('payment'));
    }

    /**
     * Show form to edit an unverified payment.
     */
    public function edit(CustomerPayment $payment): View|RedirectResponse
    {
        if ($payment->status === CustomerPaymentStatus::Verified) {
            return redirect()->route('admin.payments.show', $payment)
                ->with('error', "Verified payments cannot be edited directly. Use payment reversal if correction is required.");
        }

        $projects = Project::with(['customer', 'partner'])->orderBy('project_title')->get();

        return view('admin.payments.edit', compact('payment', 'projects'));
    }

    /**
     * Update an unverified payment.
     */
    public function update(Request $request, CustomerPayment $payment): RedirectResponse
    {
        if ($payment->status === CustomerPaymentStatus::Verified) {
            return redirect()->route('admin.payments.show', $payment)
                ->with('error', "Verified payments cannot be edited.");
        }

        $validated = $request->validate([
            'payment_type'          => ['required', Rule::enum(CustomerPaymentType::class)],
            'billing_month'         => ['nullable', 'string', 'max:7'],
            'billing_period'        => ['nullable', 'string', 'max:100'],
            'payment_method'        => ['required', Rule::enum(PaymentMethod::class)],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'payment_date'          => ['required', 'date'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string'],
            'proof_attachment'      => ['nullable', 'file', 'mimes:jpeg,jpg,png,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('proof_attachment')) {
            if ($payment->proof_attachment_path && Storage::disk('local')->exists($payment->proof_attachment_path)) {
                Storage::disk('local')->delete($payment->proof_attachment_path);
            }
            $validated['proof_attachment_path'] = $request->file('proof_attachment')->store('payment_proofs', 'local');
        }

        $payment->update($validated);

        return redirect()->route('admin.payments.show', $payment)
            ->with('success', "Payment #{$payment->payment_number} updated successfully.");
    }

    /**
     * Verify payment and automatically generate commission.
     */
    public function verify(Request $request, CustomerPayment $payment, CommissionService $commissionService): RedirectResponse
    {
        try {
            $commission = $commissionService->verifyPaymentAndGenerateCommission($payment, $request->user()->id);

            $msg = "Payment #{$payment->payment_number} has been verified successfully.";
            if ($commission) {
                $msg .= " Commission #{$commission->commission_number} of ₹" . number_format($commission->amount, 2) . " generated for Partner.";
            }

            return redirect()->route('admin.payments.show', $payment)->with('success', $msg);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reject payment with mandatory reason.
     */
    public function reject(Request $request, CustomerPayment $payment, CommissionService $commissionService): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $commissionService->rejectPayment($payment, $validated['rejection_reason'], $request->user()->id);

            return redirect()->route('admin.payments.show', $payment)
                ->with('info', "Payment #{$payment->payment_number} has been rejected.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Reverse an already verified payment.
     */
    public function reverse(Request $request, CustomerPayment $payment, CommissionService $commissionService): RedirectResponse
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $commissionService->reverseVerifiedPayment($payment, $validated['reversal_reason'], $request->user()->id);

            return redirect()->route('admin.payments.show', $payment)
                ->with('warning', "Payment #{$payment->payment_number} and associated commission have been safely reversed.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Proportional partial refund adjustment for a verified payment.
     */
    public function partialRefund(Request $request, CustomerPayment $payment, CommissionService $commissionService): RedirectResponse
    {
        $validated = $request->validate([
            'refund_amount' => ['required', 'numeric', 'min:0.01'],
            'refund_reason' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $commissionService->adjustCommissionForPartialRefund(
                $payment,
                (float) $validated['refund_amount'],
                $validated['refund_reason'],
                $request->user()->id
            );

            return redirect()->route('admin.payments.show', $payment)
                ->with('success', "Partial refund of ₹" . number_format($validated['refund_amount'], 2) . " processed and commission adjusted accordingly.");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Securely download or stream payment proof attachment (restricted to Admin).
     */
    public function downloadProof(CustomerPayment $payment): BinaryFileResponse|RedirectResponse
    {
        if (! $payment->proof_attachment_path || ! Storage::disk('local')->exists($payment->proof_attachment_path)) {
            return back()->with('error', 'Proof attachment file not found.');
        }

        $fullPath = Storage::disk('local')->path($payment->proof_attachment_path);
        return response()->file($fullPath);
    }
}
