<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CommissionType;
use App\Enums\FixedCommissionFrequency;
use App\Http\Controllers\Controller;
use App\Models\CommissionPolicy;
use App\Services\CommissionService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CommissionPolicyController extends Controller
{
    public function __construct(
        protected CommissionService $commissionService
    ) {}

    /**
     * Display listing of commission policies with active default highlighted.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = CommissionPolicy::with(['creator', 'updater', 'parentPolicy', 'revisions'])
            ->latest('id');

        if ($status && in_array($status, ['active', 'inactive', 'superseded'])) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('policy_code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $policies = $query->paginate(15)->withQueryString();
        $activeDefault = CommissionPolicy::getActiveDefault();

        $metrics = [
            'total_policies' => CommissionPolicy::count(),
            'active_policies' => CommissionPolicy::where('status', 'active')->count(),
            'default_rate' => $this->commissionService->getDefaultCommissionRate(),
        ];

        return view('admin.settings.commission-policies.index', compact('policies', 'activeDefault', 'metrics', 'status', 'search'));
    }

    /**
     * Show form to create a new commission policy.
     */
    public function create(): View
    {
        return view('admin.settings.commission-policies.create');
    }

    /**
     * Store newly created commission policy.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'policy_code'       => ['required', 'string', 'max:50', 'unique:commission_policies,policy_code'],
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'commission_type'   => ['required', Rule::enum(CommissionType::class)],
            'commission_value'  => ['required', 'numeric', 'min:0'],
            'fixed_frequency'   => ['nullable', Rule::enum(FixedCommissionFrequency::class)],
            'effective_from'    => ['required', 'date'],
            'effective_until'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status'            => ['required', 'in:active,inactive'],
            'is_default'        => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['commission_type'] === CommissionType::Percentage->value && $validated['commission_value'] > 100) {
            return back()->withInput()->withErrors(['commission_value' => 'Percentage commission cannot exceed 100%.']);
        }

        if ($validated['commission_type'] === CommissionType::Fixed->value && empty($validated['fixed_frequency'])) {
            return back()->withInput()->withErrors(['fixed_frequency' => 'Fixed commission frequency must be explicitly defined (e.g. per verified payment or per project).']);
        }

        try {
            $policy = $this->commissionService->createPolicy($validated, $request->user()->id);

            return redirect()->route('admin.settings.commission-policies.show', $policy)
                ->with('success', "Commission Policy #{$policy->policy_code} successfully created.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['is_default' => $e->getMessage()]);
        }
    }

    /**
     * Show policy details, revision chain, and audit timeline.
     */
    public function show(CommissionPolicy $commissionPolicy): View
    {
        $commissionPolicy->load(['creator', 'updater', 'parentPolicy.creator', 'revisions.creator']);

        return view('admin.settings.commission-policies.show', [
            'policy' => $commissionPolicy,
        ]);
    }

    /**
     * Show form to create a new revision version of an existing policy.
     */
    public function createRevision(CommissionPolicy $commissionPolicy): View
    {
        return view('admin.settings.commission-policies.create-revision', [
            'parent' => $commissionPolicy,
        ]);
    }

    /**
     * Store new revision version.
     */
    public function storeRevision(Request $request, CommissionPolicy $commissionPolicy): RedirectResponse
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'description'       => ['nullable', 'string', 'max:1000'],
            'commission_type'   => ['required', Rule::enum(CommissionType::class)],
            'commission_value'  => ['required', 'numeric', 'min:0'],
            'fixed_frequency'   => ['nullable', Rule::enum(FixedCommissionFrequency::class)],
            'effective_from'    => ['required', 'date'],
            'effective_until'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'is_default'        => ['nullable', 'boolean'],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['commission_type'] === CommissionType::Percentage->value && $validated['commission_value'] > 100) {
            return back()->withInput()->withErrors(['commission_value' => 'Percentage commission cannot exceed 100%.']);
        }

        try {
            $newRevision = $this->commissionService->createPolicyRevision($commissionPolicy, $validated, $request->user()->id);

            return redirect()->route('admin.settings.commission-policies.show', $newRevision)
                ->with('success', "New revision version {$newRevision->version} (#{$newRevision->policy_code}) successfully created. Prior version marked superseded.");
        } catch (DomainException $e) {
            return back()->withInput()->withErrors(['effective_from' => $e->getMessage()]);
        }
    }

    /**
     * Toggle policy status (Active / Inactive).
     */
    public function toggleStatus(CommissionPolicy $commissionPolicy, Request $request): RedirectResponse
    {
        $oldStatus = $commissionPolicy->status;

        if ($commissionPolicy->status === 'active') {
            if ($commissionPolicy->is_default) {
                return back()->with('error', 'Cannot deactivate the active global default policy without designating or activating a replacement default policy.');
            }
            $commissionPolicy->update([
                'status' => 'inactive',
                'updated_by' => $request->user()->id,
            ]);
            $msg = "Policy #{$commissionPolicy->policy_code} has been deactivated.";
            $newStatus = 'inactive';
        } else {
            $commissionPolicy->update([
                'status' => 'active',
                'updated_by' => $request->user()->id,
            ]);
            $msg = "Policy #{$commissionPolicy->policy_code} is now active.";
            $newStatus = 'active';
        }

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::CommissionPolicyToggled,
            module: \App\Enums\AuditLogModule::Settings,
            description: "Commission Policy #{$commissionPolicy->policy_code} status changed from {$oldStatus} to {$newStatus} by Admin.",
            subject: $commissionPolicy,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus],
            actor: $request->user()
        );

        return back()->with('success', $msg);
    }
}
