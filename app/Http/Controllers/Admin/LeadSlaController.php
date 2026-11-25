<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\LeadSlaEscalation;
use App\Models\User;
use App\Services\LeadSlaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeadSlaController extends Controller
{
    public function __construct(
        protected LeadSlaService $slaService
    ) {}

    /**
     * SLA Performance Dashboard.
     */
    public function index(Request $request): View
    {
        $filters = [
            'staff_id' => $request->get('staff_id'),
            'status' => $request->get('status'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
        ];

        $metrics = $this->slaService->getDashboardMetrics($filters);
        $staffMembers = User::where('role', UserRole::TeamMember)->orderBy('name')->get();
        $recentEscalations = LeadSlaEscalation::with(['lead', 'assignedTo'])
            ->latest('breached_at')
            ->take(10)
            ->get();

        return view('admin.sla.index', compact('metrics', 'staffMembers', 'filters', 'recentEscalations'));
    }

    /**
     * Show SLA settings form.
     */
    public function settings(): View
    {
        $config = $this->slaService->getConfig();

        return view('admin.sla.settings', compact('config'));
    }

    /**
     * Update SLA settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sla_enabled' => ['required', 'boolean'],
            'sla_first_contact_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_followup_hours' => ['required', 'integer', 'min:1', 'max:336'],
            'sla_reassigned_first_contact_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'sla_calculation_mode' => ['required', 'in:calendar_hours,business_hours'],
            'sla_business_start_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'sla_business_end_time' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'sla_working_days' => ['required', 'array', 'min:1'],
            'sla_working_days.*' => ['integer', 'between:1,7'],
            'sla_due_soon_threshold_hours' => ['required', 'integer', 'min:1', 'max:48'],
        ]);

        $this->slaService->updateConfig($validated, $request->user());

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::LeadSlaSettingsUpdated,
            module: \App\Enums\AuditLogModule::Settings,
            description: "Lead SLA configuration settings updated by Admin {$request->user()->name}.",
            metadata: $validated,
            actor: $request->user()
        );

        return redirect()->route('admin.lead-sla.settings')
            ->with('success', 'Lead SLA parameters updated successfully.');
    }

    /**
     * List all SLA Escalation records.
     */
    public function escalations(Request $request): View
    {
        $query = LeadSlaEscalation::with(['lead', 'assignedTo', 'resolvedBy']);

        if ($request->filled('type')) {
            $query->where('escalation_type', $request->type);
        }

        if ($request->filled('staff_id')) {
            $query->where('assigned_to', $request->staff_id);
        }

        $escalations = $query->latest('breached_at')->paginate(20)->withQueryString();
        $staffMembers = User::where('role', UserRole::TeamMember)->orderBy('name')->get();

        return view('admin.sla.escalations', compact('escalations', 'staffMembers'));
    }
}
