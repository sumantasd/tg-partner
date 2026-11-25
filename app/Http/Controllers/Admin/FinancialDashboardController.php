<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionStatus;
use App\Enums\CustomerPaymentStatus;
use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Partner;
use App\Models\PartnerKyc;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinancialDashboardController extends Controller
{
    /**
     * Admin Overview & Financial Executive Dashboard.
     */
    public function index(): View
    {
        // 1. Partners & Team
        $totalPartners = Partner::count();
        $activePartners = Partner::whereHas('user', fn($q) => $q->where('status', 'active'))->count();
        $totalTeamMembers = User::where('role', UserRole::TeamMember)->count();

        // 2. Leads & Conversion
        $totalLeads = Lead::count();
        $newLeads = Lead::where('status', LeadStatus::Submitted)->count();
        $wonLeads = Lead::where('status', LeadStatus::Won)->count();
        $lostLeads = Lead::whereIn('status', [LeadStatus::Lost, LeadStatus::Rejected])->count();
        $inDiscussionLeads = Lead::whereIn('status', [
            LeadStatus::Contacted,
            LeadStatus::InDiscussion,
            LeadStatus::QuotationSent,
            LeadStatus::Negotiating,
        ])->count();
        $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100, 1) : 0.0;

        // 3. Customers & Projects
        $totalCustomers = Customer::count();
        $totalProjects = Project::count();
        $activeProjects = Project::whereIn('status', [ProjectStatus::InProgress, ProjectStatus::Approved])->count();
        $totalProjectValue = (float) Project::sum('final_deal_value');

        // 4. Financials & Payments
        $totalPayments = CustomerPayment::count();
        $pendingPaymentsCount = CustomerPayment::where('status', CustomerPaymentStatus::Recorded)->count();
        $pendingPaymentsAmount = (float) CustomerPayment::where('status', CustomerPaymentStatus::Recorded)->sum('amount');
        $verifiedPaymentsAmount = (float) CustomerPayment::where('status', CustomerPaymentStatus::Verified)->sum('amount');
        $rejectedPaymentsCount = CustomerPayment::where('status', CustomerPaymentStatus::Rejected)->count();

        // 5. Commissions & Payouts
        $totalCommissionGenerated = (float) Commission::where('status', '!=', CommissionStatus::Cancelled)->sum('amount');
        $pendingCommission = (float) Commission::where('status', CommissionStatus::Pending)->sum('amount');
        $approvedCommission = (float) Commission::whereIn('status', [CommissionStatus::Approved, CommissionStatus::Payable])->sum('amount');
        $paidCommission = (float) Commission::where('status', CommissionStatus::Paid)->sum('amount');

        // 6. Withdrawal & KYC Compliance Queues
        $pendingWithdrawalsCount = WithdrawalRequest::where('status', 'pending')->count();
        $pendingWithdrawalsAmount = (float) WithdrawalRequest::where('status', 'pending')->sum('amount');
        $pendingKycCount = PartnerKyc::where('kyc_status', 'pending_review')->count();

        // 7. Recent Feeds
        $recentLeads = Lead::with(['partner.user', 'assignedTo'])
            ->latest()
            ->take(6)
            ->get();

        $recentPayments = CustomerPayment::with(['customer', 'project', 'partner.user'])
            ->latest('payment_date')
            ->take(6)
            ->get();

        $pendingQueue = CustomerPayment::with(['customer', 'project', 'partner.user'])
            ->where('status', CustomerPaymentStatus::Recorded)
            ->latest('payment_date')
            ->take(6)
            ->get();

        $recentCommissions = Commission::with(['partner.user', 'project'])
            ->where('status', '!=', CommissionStatus::Cancelled)
            ->latest()
            ->take(6)
            ->get();

        $recentCustomers = Customer::with(['firstPartner.user', 'projects'])
            ->latest()
            ->take(6)
            ->get();

        $pendingWithdrawalList = WithdrawalRequest::with('partner.user')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        $pendingKycList = PartnerKyc::with('partner.user')
            ->where('kyc_status', 'pending_review')
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = LeadActivity::with(['lead', 'user'])
            ->latest()
            ->take(6)
            ->get();

        // 8. Service-wise Lead Distribution (Real Aggregation)
        $serviceDistribution = Lead::select('service_required', DB::raw('count(*) as count'))
            ->groupBy('service_required')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($item) use ($totalLeads) {
                $count = (int) ($item->getAttribute('count') ?? 0);
                return [
                    'service' => $item->service_required ?: 'General Consultation',
                    'count'   => $count,
                    'percent' => $totalLeads > 0 ? round(($count / $totalLeads) * 100, 1) : 0,
                ];
            });

        // 9. Top Partner Performance (Real Aggregation)
        $topPartners = Partner::with('user')
            ->withCount(['leads', 'leads as won_leads_count' => fn($q) => $q->where('status', LeadStatus::Won)])
            ->orderByDesc('won_leads_count')
            ->orderByDesc('leads_count')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalPartners',
            'activePartners',
            'totalTeamMembers',
            'totalLeads',
            'newLeads',
            'wonLeads',
            'lostLeads',
            'inDiscussionLeads',
            'conversionRate',
            'totalCustomers',
            'totalProjects',
            'activeProjects',
            'totalProjectValue',
            'totalPayments',
            'pendingPaymentsCount',
            'pendingPaymentsAmount',
            'verifiedPaymentsAmount',
            'rejectedPaymentsCount',
            'totalCommissionGenerated',
            'pendingCommission',
            'approvedCommission',
            'paidCommission',
            'pendingWithdrawalsCount',
            'pendingWithdrawalsAmount',
            'pendingKycCount',
            'recentLeads',
            'recentPayments',
            'pendingQueue',
            'recentCommissions',
            'recentCustomers',
            'pendingWithdrawalList',
            'pendingKycList',
            'recentActivities',
            'serviceDistribution',
            'topPartners'
        ));
    }
}
