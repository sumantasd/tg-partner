<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CommissionController extends Controller
{
    /**
     * Display authoritative commission ledger across all partners with multi-dimensional filtering.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $partnerId = $request->query('partner_id');
        $projectId = $request->query('project_id');
        $customerId = $request->query('customer_id');
        $billingMonth = $request->query('billing_month');
        $commissionType = $request->query('commission_type');
        $policyVersion = $request->query('policy_version');
        $policySource = $request->query('policy_source');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $search = $request->query('search');
        $tab = $request->query('tab', 'ledger'); // ledger, project_summary, partner_summary, adjustments

        $query = Commission::with(['partner.user', 'project.customer', 'customerPayment', 'commissionPolicy', 'approvedBy', 'parentCommission'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        if ($customerId) {
            $query->whereHas('project', fn ($q) => $q->where('customer_id', $customerId));
        }

        if ($billingMonth) {
            $query->where('billing_month', $billingMonth);
        }

        if ($commissionType) {
            $query->where('commission_type', $commissionType);
        }

        if ($policyVersion) {
            $query->where('policy_version', $policyVersion);
        }

        if ($policySource) {
            $query->where('policy_source', $policySource);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        if ($tab === 'adjustments') {
            $query->where(function ($q) {
                $q->whereNotNull('parent_commission_id')
                  ->orWhereNotNull('adjustment_type')
                  ->orWhere('status', CommissionStatus::Cancelled);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('commission_number', 'like', "%{$search}%")
                  ->orWhere('milestone_name', 'like', "%{$search}%")
                  ->orWhere('billing_period', 'like', "%{$search}%")
                  ->orWhereHas('partner', fn ($sq) => $sq->where('company_name', 'like', "%{$search}%"))
                  ->orWhereHas('project', fn ($sq) => $sq->where('project_title', 'like', "%{$search}%"));
            });
        }

        $commissions = $query->paginate(15)->withQueryString();
        $partners = Partner::with('user')->orderBy('company_name')->get();
        $projects = Project::orderBy('project_title')->get();
        $customers = Customer::orderBy('name')->get();

        // Distinct billing months for filter dropdown
        $availableBillingMonths = Commission::whereNotNull('billing_month')
            ->distinct()
            ->orderByDesc('billing_month')
            ->pluck('billing_month');

        // Summaries
        $projectSummaries = Commission::select('project_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->where('status', '!=', CommissionStatus::Cancelled)
            ->groupBy('project_id')
            ->with('project.customer')
            ->get();

        $partnerSummaries = Commission::select('partner_id', DB::raw('count(*) as count'), DB::raw('sum(amount) as total_amount'))
            ->where('status', '!=', CommissionStatus::Cancelled)
            ->groupBy('partner_id')
            ->with('partner.user')
            ->get();

        $metrics = [
            'total_generated' => (float) Commission::where('status', '!=', CommissionStatus::Cancelled)->sum('amount'),
            'pending_amount'  => (float) Commission::where('status', CommissionStatus::Pending)->sum('amount'),
            'payable_amount'  => (float) Commission::whereIn('status', [CommissionStatus::Approved, CommissionStatus::Payable])->sum('amount'),
            'paid_amount'     => (float) Commission::where('status', CommissionStatus::Paid)->sum('amount'),
            'adjustments_count' => Commission::whereNotNull('parent_commission_id')->orWhereNotNull('adjustment_type')->count(),
        ];

        return view('admin.commissions.index', compact(
            'commissions', 'partners', 'projects', 'customers', 'availableBillingMonths',
            'projectSummaries', 'partnerSummaries', 'status', 'partnerId', 'projectId',
            'customerId', 'billingMonth', 'commissionType', 'policyVersion', 'policySource',
            'dateFrom', 'dateTo', 'search', 'tab', 'metrics'
        ));
    }

    /**
     * Show detailed financial record for a single commission.
     */
    public function show(Commission $commission): View
    {
        $commission->load([
            'partner.user',
            'project.customer',
            'customerPayment.verifiedBy',
            'commissionPolicy',
            'approvedBy',
            'parentCommission',
            'adjustments',
        ]);

        return view('admin.commissions.show', compact('commission'));
    }
}
