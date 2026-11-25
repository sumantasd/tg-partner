<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\InactivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InactivityController extends Controller
{
    public function __construct(
        protected InactivityService $inactivityService
    ) {}

    /**
     * Inactivity Management Dashboard (Partner and Staff tabs).
     */
    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'partners');
        if (! in_array($tab, ['partners', 'staff'], true)) {
            $tab = 'partners';
        }

        $filters = [
            'status' => $request->get('status'),
            'search' => $request->get('search'),
            'flagged_only' => $request->boolean('flagged_only'),
        ];

        if ($tab === 'staff') {
            $data = $this->inactivityService->getStaffDashboardData($filters);
        } else {
            $data = $this->inactivityService->getPartnerDashboardData($filters);
        }

        $config = $this->inactivityService->getConfig();

        return view('admin.inactivity.index', compact('tab', 'data', 'config', 'filters'));
    }

    /**
     * Show Inactivity Configuration Settings.
     */
    public function settings(): View
    {
        $config = $this->inactivityService->getConfig();

        return view('admin.inactivity.settings', compact('config'));
    }

    /**
     * Update Inactivity Configuration Settings.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inactivity_enabled' => ['required', 'boolean'],
            'partner_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
            'partner_alert_days' => ['required', 'integer', 'min:1', 'max:365'],
            'partner_critical_days' => ['required', 'integer', 'min:1', 'max:365'],
            'staff_warning_days' => ['required', 'integer', 'min:1', 'max:365'],
            'staff_alert_days' => ['required', 'integer', 'min:1', 'max:365'],
            'staff_critical_days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        // Validate logical order: warning < alert < critical
        if ($validated['partner_warning_days'] >= $validated['partner_alert_days'] || $validated['partner_alert_days'] >= $validated['partner_critical_days']) {
            return back()->withErrors(['partner_warning_days' => 'Partner thresholds must be sequential: Warning < Alert < Critical.'])->withInput();
        }

        if ($validated['staff_warning_days'] >= $validated['staff_alert_days'] || $validated['staff_alert_days'] >= $validated['staff_critical_days']) {
            return back()->withErrors(['staff_warning_days' => 'Staff thresholds must be sequential: Warning < Alert < Critical.'])->withInput();
        }

        $this->inactivityService->updateConfig($validated, $request->user());

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::InactivitySettingsUpdated,
            module: \App\Enums\AuditLogModule::Inactivity,
            description: "Inactivity policy thresholds updated by Admin {$request->user()->name}.",
            metadata: $validated,
            actor: $request->user()
        );

        return redirect()->route('admin.inactivity.settings')
            ->with('success', 'Inactivity policy thresholds updated successfully.');
    }

    /**
     * View Inactivity Audit History for a user.
     */
    public function history(User $user): View
    {
        $histories = $user->inactivityHistories()->with('performer')->paginate(20);

        return view('admin.inactivity.history', compact('user', 'histories'));
    }

    /**
     * Send manual warning notification to user.
     */
    public function sendWarning(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->inactivityService->sendManualWarning($user, $request->user(), $request->reason);

        return back()->with('success', "Inactivity warning successfully dispatched to {$user->name}.");
    }

    /**
     * Mark a user for internal administrative review.
     */
    public function flagForReview(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $this->inactivityService->flagForReview($user, $request->user(), $request->reason);

        return back()->with('success', "Account for {$user->name} has been flagged for administrative review.");
    }

    /**
     * Clear administrative review flag.
     */
    public function unflagReview(Request $request, User $user): RedirectResponse
    {
        $this->inactivityService->unflagReview($user, $request->user(), $request->input('reason'));

        return back()->with('success', "Administrative review flag cleared for {$user->name}.");
    }

    /**
     * Explicit Admin account suspension with mandatory reason.
     */
    public function suspend(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->inactivityService->suspendUser($user, $request->user(), $request->reason);

        return back()->with('success', "Account for {$user->name} has been suspended.");
    }

    /**
     * Explicit Admin account reactivation with mandatory reason.
     */
    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->inactivityService->reactivateUser($user, $request->user(), $request->reason);

        return back()->with('success', "Account for {$user->name} has been reactivated successfully.");
    }
}
