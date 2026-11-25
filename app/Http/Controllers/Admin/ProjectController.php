<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionType;
use App\Enums\FixedCommissionFrequency;
use App\Enums\ProjectPaymentType;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CommissionPolicy;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\PaymentScheduleService;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * List all projects across the platform with filtering and search.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status');
        $partnerId = $request->query('partner_id');
        $assignedTo = $request->query('assigned_to');

        $query = Project::with(['customer', 'partner.user', 'assignedTo', 'payments', 'commissions'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                  ->orWhere('project_title', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%")
                  ->orWhere('client_company', 'like', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($partnerId) {
            $query->where('partner_id', $partnerId);
        }

        if ($assignedTo) {
            $query->where('assigned_to', $assignedTo);
        }

        $projects = $query->paginate(15)->withQueryString();
        $partners = Partner::with('user')->orderBy('company_name')->get();
        $teamMembers = User::where('role', UserRole::TeamMember)->where('status', 'active')->orderBy('name')->get();

        $metrics = [
            'total_projects' => Project::count(),
            'in_progress'    => Project::where('status', ProjectStatus::InProgress)->count(),
            'completed'      => Project::where('status', ProjectStatus::Completed)->count(),
            'total_value'    => (float) Project::sum('final_deal_value'),
        ];

        return view('admin.projects.index', compact('projects', 'partners', 'teamMembers', 'search', 'status', 'partnerId', 'assignedTo', 'metrics'));
    }

    /**
     * Show form to create a project for a customer.
     */
    public function create(Request $request, CommissionService $commissionService): View
    {
        $customerId = $request->query('customer_id');
        $customer = $customerId ? Customer::with('firstPartner')->findOrFail($customerId) : null;
        $customers = Customer::orderBy('name')->get();
        $teamMembers = User::where('role', UserRole::TeamMember)->where('status', 'active')->orderBy('name')->get();
        $partners = Partner::with(['user', 'activeAgreement.commissionPolicy'])->orderBy('company_name')->get();
        $commissionPolicies = CommissionPolicy::where('status', 'active')->orderBy('name')->get();
        $defaultCommissionRate = $commissionService->getDefaultCommissionRate();

        $partnerCommissions = $partners->mapWithKeys(function ($p) use ($commissionService) {
            return [$p->id => $commissionService->resolveDefaultPolicyForPartner($p)];
        });
        $globalCommission = $commissionService->resolveDefaultPolicyForPartner(null);

        return view('admin.projects.create', compact('customer', 'customers', 'teamMembers', 'partners', 'commissionPolicies', 'defaultCommissionRate', 'partnerCommissions', 'globalCommission'));
    }

    /**
     * AJAX endpoint: Retrieve the applicable commission policy for a selected Partner.
     */
    public function resolvePartnerCommission(Request $request, CommissionService $commissionService): JsonResponse
    {
        $partnerId = $request->query('partner_id');
        $partner = $partnerId ? Partner::with('activeAgreement.commissionPolicy')->find($partnerId) : null;
        $resolved = $commissionService->resolveDefaultPolicyForPartner($partner);

        return response()->json([
            'status'                    => 'success',
            'partner_id'                => $partner?->id,
            'partner_name'              => $partner?->company_name,
            'has_partner'               => (bool) $partner,
            'commission_rate'           => $resolved['rate_applied'],
            'commission_rate_formatted' => $resolved['rate_formatted'],
            'commission_type'           => is_object($resolved['commission_type']) ? $resolved['commission_type']->value : (string) $resolved['commission_type'],
            'commission_type_label'     => $resolved['commission_type_label'],
            'source'                    => $resolved['source'],
            'source_label'              => $resolved['source_label'],
            'policy_id'                 => $resolved['policy_id'],
            'policy_code'               => $resolved['policy_code'],
            'policy_name'               => $resolved['policy_name'],
            'fixed_frequency'           => is_object($resolved['fixed_frequency']) ? $resolved['fixed_frequency']->value : $resolved['fixed_frequency'],
            'effective_date'            => $resolved['effective_date'],
        ]);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request, ProjectService $service, CommissionService $commissionService): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id'            => ['required', 'exists:customers,id'],
            'partner_id'             => ['nullable', 'exists:partners,id'],
            'assigned_to'            => ['nullable', 'exists:users,id'],
            'project_title'          => ['required', 'string', 'max:255'],
            'service_category'       => ['nullable', 'string', 'max:150'],
            'project_description'    => ['required', 'string'],
            'payment_type'           => ['nullable', Rule::enum(ProjectPaymentType::class)],
            'monthly_amount'         => ['nullable', 'numeric', 'min:0'],
            'duration_months'        => ['nullable', 'integer', 'min:1', 'max:120'],
            'billing_start_date'     => ['nullable', 'date'],
            'monthly_due_day'        => ['nullable', 'integer', 'between:1,31'],
            'first_payment_due_date' => ['nullable', 'date'],
            'grace_period_days'      => ['nullable', 'integer', 'min:0'],
            'estimated_budget'       => ['nullable', 'numeric', 'min:0'],
            'final_deal_value'       => ['nullable', 'numeric', 'min:0'],
            'commission_model'       => ['required', 'in:global_default,custom'],
            'custom_commission_type' => ['nullable', 'required_if:commission_model,custom', Rule::enum(CommissionType::class)],
            'custom_commission_rate' => ['nullable', 'required_if:commission_model,custom', 'numeric', 'min:0'],
            'custom_fixed_frequency' => ['nullable', 'required_if:custom_commission_type,fixed', Rule::enum(FixedCommissionFrequency::class)],
            'commission_effective_date' => ['nullable', 'date'],
            'commission_status'      => ['nullable', 'in:active,inactive'],
            'commission_policy_id'   => [
                'nullable',
                Rule::exists('commission_policies', 'id')->where(fn ($q) => $q->where('status', 'active')),
            ],
            'commission_notes'       => ['nullable', 'string', 'max:1000'],
            'status'                 => ['required', Rule::enum(ProjectStatus::class)],
            'start_date'             => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ], [
            'commission_policy_id.exists' => 'The selected commission policy is invalid or inactive.',
        ]);

        if ($validated['commission_model'] === 'custom') {
            if (($validated['custom_commission_type'] === 'percentage' || $validated['custom_commission_type'] === CommissionType::Percentage) && (float) $validated['custom_commission_rate'] > 100.00) {
                throw ValidationException::withMessages([
                    'custom_commission_rate' => 'Percentage commission rate cannot exceed 100%.',
                ]);
            }
            if ($validated['custom_commission_type'] === 'percentage' || $validated['custom_commission_type'] === CommissionType::Percentage) {
                $validated['custom_fixed_frequency'] = null;
            }
        } else {
            // Default mode: resolve authoritative rate and policy on server; do not trust arbitrary input
            $partner = !empty($validated['partner_id']) ? Partner::find($validated['partner_id']) : null;
            $defaultPolicy = $commissionService->resolveDefaultPolicyForPartner($partner);
            $validated['agreed_commission_type'] = $defaultPolicy['commission_type'];
            $validated['agreed_commission_rate'] = null;
            $validated['commission_policy_id'] = $defaultPolicy['policy_id'];
            $validated['custom_commission_type'] = null;
            $validated['custom_commission_rate'] = null;
            $validated['custom_fixed_frequency'] = null;
            $validated['commission_notes'] = null;
        }

        // Auto-calculate final_deal_value for monthly payment if needed
        $paymentType = $validated['payment_type'] ?? ProjectPaymentType::Single;
        if (($paymentType === 'monthly' || $paymentType === ProjectPaymentType::Monthly) && empty($validated['final_deal_value'])) {
            $validated['final_deal_value'] = (float) ($validated['monthly_amount'] ?? 0) * (int) ($validated['duration_months'] ?? 1);
        }
        $validated['final_deal_value'] = $validated['final_deal_value'] ?? 0.00;

        $customer = Customer::findOrFail($validated['customer_id']);
        $project = $service->createProject($customer, $validated, $request->user());

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Project #{$project->project_code} ({$project->project_title}) created successfully!");
    }

    /**
     * Display a single project with its payments, schedules and commission ledger.
     */
    public function show(Project $project, CommissionService $commissionService): View
    {
        $project->load([
            'customer',
            'partner.user',
            'lead',
            'assignedTo',
            'commissionPolicy',
            'payments.verifiedBy',
            'commissions.customerPayment',
            'commissions.parentCommission',
            'paymentSchedules.customerPayment',
            'paymentSchedules.payments',
        ]);

        $resolvedPolicy = $commissionService->resolveCommissionPolicyForProject($project);

        $policyHistory = AuditLog::where('subject_type', Project::class)
            ->where('subject_id', $project->id)
            ->whereIn('action', [
                \App\Enums\AuditLogAction::CommissionPolicyUpdated->value,
                \App\Enums\AuditLogAction::ProjectUpdated->value,
            ])
            ->latest()
            ->take(10)
            ->get();

        $schedules = $project->paymentSchedules;
        $totalExpectedSchedule = (float) $schedules->sum('expected_amount');
        $totalReceivedSchedule = (float) $schedules->sum('received_amount');
        $totalOutstandingSchedule = (float) $schedules->sum('outstanding_amount');
        $totalVerifiedPayments = $project->totalVerifiedPayments();
        $totalOutstandingProject = $project->totalOutstandingAmount();

        return view('admin.projects.show', compact(
            'project',
            'resolvedPolicy',
            'policyHistory',
            'schedules',
            'totalExpectedSchedule',
            'totalReceivedSchedule',
            'totalOutstandingSchedule',
            'totalVerifiedPayments',
            'totalOutstandingProject'
        ));
    }

    /**
     * Show form to edit an existing project.
     */
    public function edit(Project $project, CommissionService $commissionService): View
    {
        $project->load(['customer', 'partner.activeAgreement.commissionPolicy', 'commissionPolicy']);
        $teamMembers = User::where('role', UserRole::TeamMember)->where('status', 'active')->orderBy('name')->get();
        $partners = Partner::with(['user', 'activeAgreement.commissionPolicy'])->orderBy('company_name')->get();
        $commissionPolicies = CommissionPolicy::where('status', 'active')->orderBy('name')->get();
        $defaultCommissionRate = $commissionService->getDefaultCommissionRate();

        $partnerCommissions = $partners->mapWithKeys(function ($p) use ($commissionService) {
            return [$p->id => $commissionService->resolveDefaultPolicyForPartner($p)];
        });
        $globalCommission = $commissionService->resolveDefaultPolicyForPartner(null);
        $currentResolvedCommission = $commissionService->resolveCommissionPolicyForProject($project);

        return view('admin.projects.edit', compact('project', 'teamMembers', 'partners', 'commissionPolicies', 'defaultCommissionRate', 'partnerCommissions', 'globalCommission', 'currentResolvedCommission'));
    }

    /**
     * Update project details.
     */
    public function update(Request $request, Project $project, CommissionService $commissionService): RedirectResponse
    {
        $validated = $request->validate([
            'partner_id'               => ['nullable', 'exists:partners,id'],
            'assigned_to'              => ['nullable', 'exists:users,id'],
            'project_title'            => ['required', 'string', 'max:255'],
            'service_category'         => ['nullable', 'string', 'max:150'],
            'project_description'      => ['required', 'string'],
            'payment_type'             => ['nullable', Rule::enum(ProjectPaymentType::class)],
            'monthly_amount'           => ['nullable', 'numeric', 'min:0'],
            'duration_months'          => ['nullable', 'integer', 'min:1', 'max:120'],
            'billing_start_date'       => ['nullable', 'date'],
            'monthly_due_day'          => ['nullable', 'integer', 'between:1,31'],
            'first_payment_due_date'   => ['nullable', 'date'],
            'grace_period_days'        => ['nullable', 'integer', 'min:0'],
            'estimated_budget'         => ['nullable', 'numeric', 'min:0'],
            'final_deal_value'         => ['required', 'numeric', 'min:0'],
            'status'                   => ['required', Rule::enum(ProjectStatus::class)],
            'agreed_commission_rate'   => ['nullable', 'numeric', 'between:0,100'],
            'commission_model'         => ['sometimes', 'nullable', 'in:global_default,custom'],
            'custom_commission_type'   => ['nullable', 'required_if:commission_model,custom', Rule::enum(CommissionType::class)],
            'custom_commission_rate'   => ['nullable', 'required_if:commission_model,custom', 'numeric', 'min:0'],
            'custom_fixed_frequency'   => ['nullable', 'required_if:custom_commission_type,fixed', Rule::enum(FixedCommissionFrequency::class)],
            'commission_effective_date' => ['nullable', 'date'],
            'commission_status'        => ['nullable', 'in:active,inactive'],
            'commission_policy_id'     => [
                'nullable',
                Rule::exists('commission_policies', 'id')->where(fn ($q) => $q->where('status', 'active')),
            ],
            'commission_notes'         => ['nullable', 'string', 'max:1000'],
            'start_date'               => ['nullable', 'date'],
            'expected_completion_date' => ['nullable', 'date'],
        ], [
            'commission_policy_id.exists' => 'The selected commission policy is invalid or inactive.',
        ]);

        $model = $validated['commission_model'] ?? $project->commission_model ?? 'global_default';

        if ($model === 'custom' && $request->has('commission_model')) {
            if (($validated['custom_commission_type'] === 'percentage' || $validated['custom_commission_type'] === CommissionType::Percentage) && (float) $validated['custom_commission_rate'] > 100.00) {
                throw ValidationException::withMessages([
                    'custom_commission_rate' => 'Percentage commission rate cannot exceed 100%.',
                ]);
            }
            if ($validated['custom_commission_type'] === 'percentage' || $validated['custom_commission_type'] === CommissionType::Percentage) {
                $validated['custom_fixed_frequency'] = null;
            }
        }

        $oldAttributes = $project->getAttributes();
        $oldStatus = is_object($project->status) ? $project->status->value : (string) $project->status;
        $newStatus = is_object($validated['status']) ? $validated['status']->value : (string) $validated['status'];

        if (array_key_exists('partner_id', $validated)) {
            $project->partner_id = $validated['partner_id'];
        }

        // Configure via CommissionService for strict auditing and policy snapshotting
        if ($request->has('commission_model')) {
            $commissionService->configureProjectCommission($project, [
                'commission_model' => $model,
                'custom_commission_type' => $model === 'custom' ? ($validated['custom_commission_type'] ?? null) : null,
                'custom_commission_rate' => $model === 'custom' ? ($validated['custom_commission_rate'] ?? null) : null,
                'custom_fixed_frequency' => $model === 'custom' ? ($validated['custom_fixed_frequency'] ?? null) : null,
                'commission_effective_date' => $validated['commission_effective_date'] ?? null,
                'commission_status' => $validated['commission_status'] ?? 'active',
                'commission_policy_id' => $validated['commission_policy_id'] ?? null,
                'commission_notes' => $validated['commission_notes'] ?? null,
            ], $request->user()->id);
        }

        $updateData = [
            'assigned_to'              => $validated['assigned_to'] ?? null,
            'project_title'            => $validated['project_title'],
            'service_category'         => $validated['service_category'] ?? null,
            'project_description'      => $validated['project_description'],
            'estimated_budget'         => $validated['estimated_budget'] ?? null,
            'final_deal_value'         => $validated['final_deal_value'],
            'status'                   => $validated['status'],
            'start_date'               => $validated['start_date'] ?? null,
            'expected_completion_date' => $validated['expected_completion_date'] ?? null,
        ];

        if (array_key_exists('agreed_commission_rate', $validated)) {
            $updateData['agreed_commission_rate'] = $validated['agreed_commission_rate'];
        }
        if (array_key_exists('payment_type', $validated)) {
            $updateData['payment_type'] = $validated['payment_type'];
        }
        if (array_key_exists('monthly_amount', $validated)) {
            $updateData['monthly_amount'] = $validated['monthly_amount'];
        }
        if (array_key_exists('duration_months', $validated)) {
            $updateData['duration_months'] = $validated['duration_months'];
        }
        if (array_key_exists('billing_start_date', $validated)) {
            $updateData['billing_start_date'] = $validated['billing_start_date'];
        }
        if (array_key_exists('monthly_due_day', $validated)) {
            $updateData['monthly_due_day'] = $validated['monthly_due_day'];
        }
        if (array_key_exists('first_payment_due_date', $validated)) {
            $updateData['first_payment_due_date'] = $validated['first_payment_due_date'];
        }
        if (array_key_exists('grace_period_days', $validated)) {
            $updateData['grace_period_days'] = $validated['grace_period_days'];
        }

        $project->update($updateData);

        // If monthly payment and no schedules generated yet, generate them
        if ($project->payment_type === ProjectPaymentType::Monthly && $project->paymentSchedules()->count() === 0) {
            app(PaymentScheduleService::class)->generateSchedule($project, $request->user()->id);
        }

        $action = ($oldStatus !== $newStatus)
            ? \App\Enums\AuditLogAction::ProjectStatusChanged
            : \App\Enums\AuditLogAction::ProjectUpdated;

        app(\App\Services\AuditLogService::class)->recordModelUpdate(
            model: $project,
            oldAttributes: $oldAttributes,
            action: $action,
            module: \App\Enums\AuditLogModule::Project,
            description: "Project #{$project->project_code} details updated by Admin.",
            actor: $request->user()
        );

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "Project #{$project->project_code} updated successfully.");
    }
}
