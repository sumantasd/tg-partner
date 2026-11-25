<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\CustomerPaymentStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Display executive business intelligence, performance analytics, and financial reports.
     */
    public function index(Request $request): View
    {
        $totalLeads = Lead::count();
        $wonLeads = Lead::where('status', LeadStatus::Won)->count();
        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0;

        $totalRevenue = (float) CustomerPayment::where('status', CustomerPaymentStatus::Verified)->sum('amount');
        $totalCommission = (float) Commission::where('status', '!=', CommissionStatus::Cancelled)->sum('amount');
        $netMargin = $totalRevenue - $totalCommission;

        $topPartners = Partner::with('user')
            ->withCount(['leads', 'projects'])
            ->withSum(['commissions' => fn ($q) => $q->where('status', '!=', CommissionStatus::Cancelled)], 'amount')
            ->orderByDesc('commissions_sum_amount')
            ->take(5)
            ->get();

        $serviceBreakdown = Lead::whereNotNull('service_required')
            ->selectRaw('service_required, count(*) as count')
            ->groupBy('service_required')
            ->pluck('count', 'service_required');

        $metrics = [
            'total_revenue'    => $totalRevenue,
            'total_commission' => $totalCommission,
            'net_margin'       => $netMargin,
            'total_leads'      => $totalLeads,
            'won_leads'        => $wonLeads,
            'conversion_rate'  => $conversionRate,
            'total_customers'  => Customer::count(),
            'total_projects'   => Project::count(),
        ];

        return view('admin.reports.index', compact('metrics', 'topPartners', 'serviceBreakdown'));
    }
}
