<?php

namespace App\Http\Controllers\Partner;

use App\Enums\CommissionStatus;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommissionController extends Controller
{
    /**
     * Display partner's own commission ledger with earnings summary.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }
        $status = $request->query('status');
        $billingMonth = $request->query('billing_month');

        $query = Commission::where('partner_id', $partner->id)
            ->with(['project.customer', 'customerPayment', 'commissionPolicy', 'parentCommission'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($billingMonth) {
            $query->where('billing_month', $billingMonth);
        }

        $commissions = $query->paginate(15)->withQueryString();

        $availableBillingMonths = Commission::where('partner_id', $partner->id)
            ->whereNotNull('billing_month')
            ->distinct()
            ->orderByDesc('billing_month')
            ->pluck('billing_month');

        $metrics = [
            'total_earned' => (float) Commission::where('partner_id', $partner->id)
                ->where('status', '!=', CommissionStatus::Cancelled)
                ->sum('amount'),
            'payable'      => (float) Commission::where('partner_id', $partner->id)
                ->whereIn('status', [CommissionStatus::Approved, CommissionStatus::Payable])
                ->whereNull('withdrawal_request_id')
                ->whereNull('payout_id')
                ->sum('amount'),
            'paid'         => (float) Commission::where('partner_id', $partner->id)
                ->where('status', CommissionStatus::Paid)
                ->sum('amount'),
            'pending'      => (float) Commission::where('partner_id', $partner->id)
                ->where('status', CommissionStatus::Pending)
                ->sum('amount'),
        ];

        return view('partner.commissions.index', compact('commissions', 'status', 'billingMonth', 'availableBillingMonths', 'metrics'));
    }

    /**
     * Show partner's commission detail.
     */
    public function show(Request $request, Commission $commission): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        if ($commission->partner_id !== $partner->id) {
            abort(403, 'Unauthorized. You can only view your own commission statements.');
        }

        $commission->load(['project.customer', 'customerPayment', 'commissionPolicy', 'parentCommission', 'adjustments']);

        return view('partner.commissions.show', compact('commission'));
    }
}
