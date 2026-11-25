<?php

namespace App\Http\Controllers\Partner;

use App\Enums\LeadStatus;
use App\Enums\ProjectStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Project;
use App\Services\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        protected WithdrawalService $withdrawalService
    ) {}

    /**
     * Display the Partner dashboard overview.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        // 1. Lead Pipeline Metrics
        $leadsCount = Lead::where('partner_id', $partner->id)->count();
        $newLeadsCount = Lead::where('partner_id', $partner->id)->where('status', LeadStatus::Submitted)->count();
        $inPipelineCount = Lead::where('partner_id', $partner->id)->whereIn('status', [
            LeadStatus::Assigned,
            LeadStatus::Contacted,
            LeadStatus::InDiscussion,
            LeadStatus::QuotationSent,
            LeadStatus::Negotiating,
        ])->count();
        $wonCount = Lead::where('partner_id', $partner->id)->where('status', LeadStatus::Won)->count();
        $lostCount = Lead::where('partner_id', $partner->id)->whereIn('status', [LeadStatus::Lost, LeadStatus::Rejected])->count();
        $conversionRate = $leadsCount > 0 ? round(($wonCount / $leadsCount) * 100, 1) : 0.0;

        // 2. Customers & Active Projects
        $customersCount = Customer::where('first_partner_id', $partner->id)->count();
        $activeProjectsCount = Project::where('partner_id', $partner->id)
            ->whereIn('status', [ProjectStatus::InProgress, ProjectStatus::Approved])
            ->count();

        // 3. Authoritative Financial & Balance Summary
        $balanceSummary = $this->withdrawalService->getPartnerBalanceSummary($partner);
        $hasPayoutDetails = $partner->hasValidPayoutDetails();
        $kyc = $partner->kyc;

        // 4. Recently Submitted Leads
        $recentLeads = Lead::where('partner_id', $partner->id)
            ->with(['assignedTo'])
            ->orderByDesc('created_at')
            ->take(5)
            ->get();

        return view('partner.workspace', compact(
            'partner',
            'leadsCount',
            'newLeadsCount',
            'inPipelineCount',
            'wonCount',
            'lostCount',
            'conversionRate',
            'customersCount',
            'activeProjectsCount',
            'balanceSummary',
            'hasPayoutDetails',
            'kyc',
            'recentLeads'
        ));
    }
}
