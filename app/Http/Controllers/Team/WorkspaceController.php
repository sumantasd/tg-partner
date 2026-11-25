<?php

namespace App\Http\Controllers\Team;

use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadSlaStatus;
use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\LeadSlaService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WorkspaceController extends Controller
{
    public function __construct(
        protected LeadSlaService $slaService
    ) {}

    /**
     * Team Member protected workspace home & telecalling CRM dashboard.
     * All metrics, activities, and follow-ups are strictly scoped to the authenticated staff member.
     */
    public function index(Request $request): View
    {
        $teamUser = $request->user();
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $endOfWeek = now()->endOfWeek()->toDateString();

        // Refresh SLA for active leads assigned to this user
        $myActiveLeads = Lead::where('assigned_to', $teamUser->id)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->get();
        foreach ($myActiveLeads as $lead) {
            $this->slaService->refreshLeadSla($lead);
        }

        // 1. Scoped Lead Counts & Personal KPIs
        $totalAssigned = Lead::where('assigned_to', $teamUser->id)->count();
        $wonCount = Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::Won)->count();
        $lostCount = Lead::where('assigned_to', $teamUser->id)->whereIn('status', [LeadStatus::Lost, LeadStatus::Rejected])->count();
        $inProgressCount = Lead::where('assigned_to', $teamUser->id)->whereIn('status', [
            LeadStatus::Contacted,
            LeadStatus::InDiscussion,
            LeadStatus::QuotationSent,
            LeadStatus::Negotiating,
        ])->count();

        $todayFollowupsCount = Lead::where('assigned_to', $teamUser->id)
            ->whereDate('next_followup_date', $today)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $overdueFollowupsCount = Lead::where('assigned_to', $teamUser->id)
            ->where(function ($q) use ($today) {
                $q->whereDate('next_followup_date', '<', $today)
                    ->orWhere('followup_sla_status', LeadSlaStatus::Overdue);
            })
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $pendingFirstContactCount = Lead::where('assigned_to', $teamUser->id)
            ->whereNull('first_contacted_at')
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $overdueFirstContactCount = Lead::where('assigned_to', $teamUser->id)
            ->where('first_contact_sla_status', LeadSlaStatus::Overdue)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $totalSlaBreachedCount = Lead::where('assigned_to', $teamUser->id)
            ->where('overall_sla_status', LeadSlaStatus::Overdue)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $hotLeadsCount = Lead::where('assigned_to', $teamUser->id)
            ->where('interest_level', LeadInterest::Hot)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->count();

        $conversionRate = $totalAssigned > 0 ? round(($wonCount / $totalAssigned) * 100, 1) : 0.0;

        $stats = [
            'total_assigned'          => $totalAssigned,
            'today_followup'          => $todayFollowupsCount,
            'overdue_followup'        => $overdueFollowupsCount,
            'pending_first_contact'   => $pendingFirstContactCount,
            'overdue_first_contact'   => $overdueFirstContactCount,
            'total_sla_breached'      => $totalSlaBreachedCount,
            'in_progress'             => $inProgressCount,
            'hot_leads'               => $hotLeadsCount,
            'won'                     => $wonCount,
            'lost'                    => $lostCount,
            'conversion_rate'         => $conversionRate,
        ];

        // 2. Stage Breakdown
        $stages = [
            'assigned'       => Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::Assigned)->count(),
            'contacted'      => Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::Contacted)->count(),
            'in_discussion'  => Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::InDiscussion)->count(),
            'quotation_sent' => Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::QuotationSent)->count(),
            'negotiating'    => Lead::where('assigned_to', $teamUser->id)->where('status', LeadStatus::Negotiating)->count(),
            'won'            => $wonCount,
            'lost'           => $lostCount,
        ];

        // 3. Priority Leads: Overdue SLA first, then due today/soon
        $todayLeads = Lead::where('assigned_to', $teamUser->id)
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->where(function ($q) use ($today) {
                $q->whereDate('next_followup_date', '<=', $today)
                    ->orWhere('overall_sla_status', LeadSlaStatus::Overdue)
                    ->orWhere('overall_sla_status', LeadSlaStatus::DueSoon)
                    ->orWhereNull('first_contacted_at');
            })
            ->orderByRaw("CASE 
                WHEN overall_sla_status = 'overdue' THEN 0 
                WHEN overall_sla_status = 'due_soon' THEN 1 
                WHEN next_followup_date < ? THEN 2 
                ELSE 3 END", [$today])
            ->orderBy('next_followup_date', 'asc')
            ->take(6)
            ->get();

        // 4. Follow-up Calendar / Schedule Breakdown
        $calendarSchedule = [
            'overdue'   => $overdueFollowupsCount,
            'today'     => $todayFollowupsCount,
            'tomorrow'  => Lead::where('assigned_to', $teamUser->id)
                ->whereDate('next_followup_date', $tomorrow)
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
                ->count(),
            'this_week' => Lead::where('assigned_to', $teamUser->id)
                ->whereDate('next_followup_date', '>', $tomorrow)
                ->whereDate('next_followup_date', '<=', $endOfWeek)
                ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
                ->count(),
        ];

        // 5. Recent Telecalling Activities (Latest 6 activities on this staff member's assigned leads)
        $recentActivities = LeadActivity::whereHas('lead', function ($q) use ($teamUser) {
                $q->where('assigned_to', $teamUser->id);
            })
            ->with(['lead', 'user'])
            ->latest()
            ->take(6)
            ->get();

        return view('team.workspace', compact(
            'teamUser',
            'stats',
            'stages',
            'todayLeads',
            'calendarSchedule',
            'recentActivities'
        ));
    }
}
