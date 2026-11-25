<?php

namespace App\Services;

use App\Enums\InactivityStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserInactivityHistory;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InactivityService
{
    /**
     * Get inactivity configuration thresholds.
     *
     * @return array{
     *     enabled: bool,
     *     partner_warning_days: int,
     *     partner_alert_days: int,
     *     partner_critical_days: int,
     *     staff_warning_days: int,
     *     staff_alert_days: int,
     *     staff_critical_days: int
     * }
     */
    public function getConfig(): array
    {
        $enabled = SystemSetting::get('inactivity_enabled', 'true');

        return [
            'enabled' => in_array(strtolower((string) $enabled), ['true', '1', 'yes'], true),
            'partner_warning_days' => max(1, (int) SystemSetting::get('inactivity_partner_warning_days', '30')),
            'partner_alert_days' => max(1, (int) SystemSetting::get('inactivity_partner_alert_days', '60')),
            'partner_critical_days' => max(1, (int) SystemSetting::get('inactivity_partner_critical_days', '90')),
            'staff_warning_days' => max(1, (int) SystemSetting::get('inactivity_staff_warning_days', '30')),
            'staff_alert_days' => max(1, (int) SystemSetting::get('inactivity_staff_alert_days', '60')),
            'staff_critical_days' => max(1, (int) SystemSetting::get('inactivity_staff_critical_days', '90')),
        ];
    }

    /**
     * Update inactivity thresholds in system settings.
     */
    public function updateConfig(array $data, User $admin): void
    {
        if (isset($data['inactivity_enabled'])) {
            SystemSetting::set('inactivity_enabled', $data['inactivity_enabled'] ? 'true' : 'false', $admin->id);
        }

        if (isset($data['partner_warning_days'])) {
            SystemSetting::set('inactivity_partner_warning_days', (string) max(1, (int) $data['partner_warning_days']), $admin->id);
        }

        if (isset($data['partner_alert_days'])) {
            SystemSetting::set('inactivity_partner_alert_days', (string) max(1, (int) $data['partner_alert_days']), $admin->id);
        }

        if (isset($data['partner_critical_days'])) {
            SystemSetting::set('inactivity_partner_critical_days', (string) max(1, (int) $data['partner_critical_days']), $admin->id);
        }

        if (isset($data['staff_warning_days'])) {
            SystemSetting::set('inactivity_staff_warning_days', (string) max(1, (int) $data['staff_warning_days']), $admin->id);
        }

        if (isset($data['staff_alert_days'])) {
            SystemSetting::set('inactivity_staff_alert_days', (string) max(1, (int) $data['staff_alert_days']), $admin->id);
        }

        if (isset($data['staff_critical_days'])) {
            SystemSetting::set('inactivity_staff_critical_days', (string) max(1, (int) $data['staff_critical_days']), $admin->id);
        }
    }

    /**
     * Record a genuine meaningful action performed by a user.
     * Page views, polling, and background requests do NOT call this method.
     */
    public function recordActivity(User $user, string $actionType, ?array $metadata = null): void
    {
        $now = now();
        $updates = [
            'last_meaningful_activity_at' => $now,
            'inactivity_warning_sent_at' => null,
            'inactivity_alert_sent_at' => null,
            'inactivity_critical_sent_at' => null,
        ];

        if ($actionType === 'login') {
            $updates['last_login_at'] = $now;
        } elseif ($actionType === 'lead_submission') {
            $updates['last_lead_submission_at'] = $now;
        } elseif ($actionType === 'customer_interaction') {
            $updates['last_customer_interaction_at'] = $now;
            $updates['last_lead_update_at'] = $now;
        } elseif ($actionType === 'lead_update') {
            $updates['last_lead_update_at'] = $now;
        }

        if ($user->status !== UserStatus::Suspended) {
            $updates['inactivity_status'] = InactivityStatus::Active;
        }

        $user->update($updates);
    }

    /**
     * Record logout timestamp.
     */
    public function recordLogout(User $user): void
    {
        $user->update([
            'last_logout_at' => now(),
        ]);
    }

    /**
     * Evaluate and calculate inactivity status based on elapsed days.
     */
    public function evaluateInactivityStatus(User $user): InactivityStatus
    {
        if ($user->status === UserStatus::Suspended) {
            return InactivityStatus::Suspended;
        }

        $config = $this->getConfig();
        if (! $config['enabled']) {
            return InactivityStatus::Active;
        }

        $isStaff = ($user->role === UserRole::TeamMember);
        $warningDays = $isStaff ? $config['staff_warning_days'] : $config['partner_warning_days'];
        $alertDays = $isStaff ? $config['staff_alert_days'] : $config['partner_alert_days'];
        $criticalDays = $isStaff ? $config['staff_critical_days'] : $config['partner_critical_days'];

        $days = $user->getInactivityDays();

        if ($days >= $criticalDays) {
            return InactivityStatus::Critical;
        }

        if ($days >= $alertDays) {
            return InactivityStatus::Inactive;
        }

        if ($days >= $warningDays) {
            return InactivityStatus::AtRisk;
        }

        return InactivityStatus::Active;
    }

    /**
     * Process inactivity evaluations across all non-admin users,
     * update statuses, and dispatch deduplicated threshold notifications.
     *
     * @return array{
     *     evaluated: int,
     *     warnings_sent: int,
     *     alerts_sent: int,
     *     critical_sent: int
     * }
     */
    public function processInactivityEvaluations(): array
    {
        $config = $this->getConfig();
        if (! $config['enabled']) {
            return ['evaluated' => 0, 'warnings_sent' => 0, 'alerts_sent' => 0, 'critical_sent' => 0];
        }

        $users = User::whereIn('role', [UserRole::Partner, UserRole::TeamMember])
            ->where('status', '!=', UserStatus::Suspended)
            ->get();

        $adminUsers = User::where('role', UserRole::Admin)->get();

        $evaluated = 0;
        $warningsSent = 0;
        $alertsSent = 0;
        $criticalSent = 0;

        foreach ($users as $user) {
            $evaluated++;
            $status = $this->evaluateInactivityStatus($user);
            $days = $user->getInactivityDays();

            // Update status if changed
            if ($user->inactivity_status !== $status) {
                $user->inactivity_status = $status;
                $user->save();
            }

            $route = $user->role === UserRole::Partner ? 'partner.workspace' : 'team.workspace';

            // 1. Threshold 30 Days: Warning (At Risk)
            if ($status === InactivityStatus::AtRisk && $user->inactivity_warning_sent_at === null) {
                Notification::send(
                    $user->id,
                    'Account Inactivity Warning',
                    "Your account has been inactive for {$days} days. Please log in and interact with leads to maintain active partner/staff status.",
                    'warning',
                    route($route)
                );

                $user->update(['inactivity_warning_sent_at' => now()]);

                UserInactivityHistory::create([
                    'user_id' => $user->id,
                    'action' => 'warning_sent',
                    'previous_status' => InactivityStatus::Active->value,
                    'new_status' => InactivityStatus::AtRisk->value,
                    'inactivity_days' => $days,
                    'reason' => "Account crossed {$days}-day inactivity warning threshold.",
                    'created_at' => now(),
                ]);

                $warningsSent++;
            }

            // 2. Threshold 60 Days: Inactivity Alert
            if ($status === InactivityStatus::Inactive && $user->inactivity_alert_sent_at === null) {
                Notification::send(
                    $user->id,
                    'Account Inactivity Alert (60 Days)',
                    "Your account has reached {$days} days of inactivity. Please resume platform activities to avoid critical inactivity flags.",
                    'warning',
                    route($route)
                );

                foreach ($adminUsers as $admin) {
                    Notification::send(
                        $admin->id,
                        "Inactivity Alert: {$user->name}",
                        "{$user->role->label()} {$user->name} has been inactive for {$days} days.",
                        'warning',
                        route('admin.inactivity.index')
                    );
                }

                $user->update(['inactivity_alert_sent_at' => now()]);

                UserInactivityHistory::create([
                    'user_id' => $user->id,
                    'action' => 'alert_sent',
                    'previous_status' => InactivityStatus::AtRisk->value,
                    'new_status' => InactivityStatus::Inactive->value,
                    'inactivity_days' => $days,
                    'reason' => "Account crossed {$days}-day inactivity alert threshold.",
                    'created_at' => now(),
                ]);

                $alertsSent++;
            }

            // 3. Threshold 90 Days: Critical Inactivity Notice
            if ($status === InactivityStatus::Critical && $user->inactivity_critical_sent_at === null) {
                Notification::send(
                    $user->id,
                    'Critical Inactivity Notice (90 Days)',
                    "Your account has reached {$days} days without meaningful activity and is now flagged as critical. Please contact your account manager.",
                    'danger',
                    route($route)
                );

                foreach ($adminUsers as $admin) {
                    Notification::send(
                        $admin->id,
                        "Critical Inactivity Alert: {$user->name}",
                        "{$user->role->label()} {$user->name} has reached {$days} days of critical inactivity. Administrative review recommended.",
                        'danger',
                        route('admin.inactivity.index')
                    );
                }

                $user->update(['inactivity_critical_sent_at' => now()]);

                UserInactivityHistory::create([
                    'user_id' => $user->id,
                    'action' => 'critical_alert_sent',
                    'previous_status' => InactivityStatus::Inactive->value,
                    'new_status' => InactivityStatus::Critical->value,
                    'inactivity_days' => $days,
                    'reason' => "Account crossed {$days}-day critical inactivity threshold.",
                    'created_at' => now(),
                ]);

                $criticalSent++;
            }
        }

        return [
            'evaluated' => $evaluated,
            'warnings_sent' => $warningsSent,
            'alerts_sent' => $alertsSent,
            'critical_sent' => $criticalSent,
        ];
    }

    /**
     * Send a manual warning from Admin to user.
     */
    public function sendManualWarning(User $user, User $admin, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A reason is mandatory when dispatching an inactivity warning.');
        }

        $route = $user->role === UserRole::Partner ? 'partner.workspace' : 'team.workspace';

        Notification::send(
            $user->id,
            'Official Inactivity Warning from Administrator',
            $reason,
            'warning',
            route($route)
        );

        UserInactivityHistory::create([
            'user_id' => $user->id,
            'action' => 'manual_warning_sent',
            'previous_status' => $user->inactivity_status->value,
            'new_status' => $user->inactivity_status->value,
            'inactivity_days' => $user->getInactivityDays(),
            'reason' => $reason,
            'performed_by' => $admin->id,
            'created_at' => now(),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::InactivityWarningSent,
            module: \App\Enums\AuditLogModule::Inactivity,
            description: "Manual inactivity warning dispatched to {$user->name} ({$user->email}). Reason: {$reason}",
            subject: $user,
            metadata: ['reason' => $reason, 'inactivity_days' => $user->getInactivityDays()],
            actor: $admin
        );
    }

    /**
     * Mark a user for internal administrative review.
     */
    public function flagForReview(User $user, User $admin, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A mandatory reason is required to flag an account for review.');
        }

        $user->update([
            'inactivity_flagged_for_review' => true,
            'inactivity_flagged_at' => now(),
            'inactivity_flagged_by' => $admin->id,
            'inactivity_review_reason' => $reason,
        ]);

        UserInactivityHistory::create([
            'user_id' => $user->id,
            'action' => 'marked_for_review',
            'previous_status' => $user->inactivity_status->value,
            'new_status' => $user->inactivity_status->value,
            'inactivity_days' => $user->getInactivityDays(),
            'reason' => $reason,
            'performed_by' => $admin->id,
            'created_at' => now(),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::InactivityReviewFlagged,
            module: \App\Enums\AuditLogModule::Inactivity,
            description: "Account {$user->name} ({$user->email}) flagged for inactivity review by Admin. Reason: {$reason}",
            subject: $user,
            metadata: ['reason' => $reason],
            actor: $admin
        );
    }

    /**
     * Clear administrative review flag.
     */
    public function unflagReview(User $user, User $admin, ?string $reason = null): void
    {
        $user->update([
            'inactivity_flagged_for_review' => false,
            'inactivity_flagged_at' => null,
            'inactivity_flagged_by' => null,
            'inactivity_review_reason' => null,
        ]);

        UserInactivityHistory::create([
            'user_id' => $user->id,
            'action' => 'unmarked_review',
            'previous_status' => $user->inactivity_status->value,
            'new_status' => $user->inactivity_status->value,
            'inactivity_days' => $user->getInactivityDays(),
            'reason' => $reason ?: 'Administrative review completed.',
            'performed_by' => $admin->id,
            'created_at' => now(),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::InactivityReviewUnflagged,
            module: \App\Enums\AuditLogModule::Inactivity,
            description: "Inactivity review cleared for {$user->name} ({$user->email}) by Admin.",
            subject: $user,
            metadata: ['reason' => $reason],
            actor: $admin
        );
    }

    /**
     * Explicit Admin account suspension with mandatory reason.
     * Accounts are NEVER suspended automatically by cron.
     */
    public function suspendUser(User $user, User $admin, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A mandatory reason is required to suspend an account.');
        }

        $previousStatus = $user->inactivity_status->value;

        DB::transaction(function () use ($user, $admin, $reason, $previousStatus) {
            $user->update([
                'status' => UserStatus::Suspended,
                'inactivity_status' => InactivityStatus::Suspended,
            ]);

            UserInactivityHistory::create([
                'user_id' => $user->id,
                'action' => 'suspended',
                'previous_status' => $previousStatus,
                'new_status' => InactivityStatus::Suspended->value,
                'inactivity_days' => $user->getInactivityDays(),
                'reason' => $reason,
                'performed_by' => $admin->id,
                'created_at' => now(),
            ]);

            Notification::send(
                $user->id,
                'Account Suspended',
                "Your account has been suspended by an administrator. Reason: {$reason}",
                'danger'
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AccountSuspended,
                module: \App\Enums\AuditLogModule::Inactivity,
                description: "Account {$user->name} ({$user->email}) suspended due to inactivity by Admin. Reason: {$reason}",
                subject: $user,
                oldValues: ['status' => UserStatus::Active->value, 'inactivity_status' => $previousStatus],
                newValues: ['status' => UserStatus::Suspended->value, 'inactivity_status' => InactivityStatus::Suspended->value],
                metadata: ['reason' => $reason],
                actor: $admin
            );
        });
    }

    /**
     * Explicit Admin account reactivation with mandatory reason.
     * Accounts are NEVER automatically reactivated.
     */
    public function reactivateUser(User $user, User $admin, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A mandatory reason is required to reactivate an account.');
        }

        DB::transaction(function () use ($user, $admin, $reason) {
            $user->update([
                'status' => UserStatus::Active,
                'inactivity_status' => InactivityStatus::Active,
                'last_meaningful_activity_at' => now(),
                'inactivity_warning_sent_at' => null,
                'inactivity_alert_sent_at' => null,
                'inactivity_critical_sent_at' => null,
                'inactivity_flagged_for_review' => false,
            ]);

            UserInactivityHistory::create([
                'user_id' => $user->id,
                'action' => 'reactivated',
                'previous_status' => InactivityStatus::Suspended->value,
                'new_status' => InactivityStatus::Active->value,
                'inactivity_days' => 0,
                'reason' => $reason,
                'performed_by' => $admin->id,
                'created_at' => now(),
            ]);

            Notification::send(
                $user->id,
                'Account Reactivated',
                "Your account has been reactivated by an administrator. You can now access your dashboard.",
                'info'
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::AccountReactivated,
                module: \App\Enums\AuditLogModule::Inactivity,
                description: "Account {$user->name} ({$user->email}) reactivated by Admin. Reason: {$reason}",
                subject: $user,
                oldValues: ['status' => UserStatus::Suspended->value, 'inactivity_status' => InactivityStatus::Suspended->value],
                newValues: ['status' => UserStatus::Active->value, 'inactivity_status' => InactivityStatus::Active->value],
                metadata: ['reason' => $reason],
                actor: $admin
            );
        });
    }

    /**
     * Compute Partner Inactivity Dashboard metrics and paginated records.
     */
    public function getPartnerDashboardData(array $filters = []): array
    {
        $query = User::with(['partner.leads'])
            ->where('role', UserRole::Partner);

        if (! empty($filters['status'])) {
            $query->where('inactivity_status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('partner', function ($pq) use ($search) {
                        $pq->where('company_name', 'like', "%{$search}%")
                            ->orWhere('partner_code', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['flagged_only'])) {
            $query->where('inactivity_flagged_for_review', true);
        }

        // Freshly evaluate active partners
        $allPartners = User::where('role', UserRole::Partner)->get();
        foreach ($allPartners as $p) {
            $evaluated = $this->evaluateInactivityStatus($p);
            if ($p->inactivity_status !== $evaluated) {
                $p->inactivity_status = $evaluated;
                $p->save();
            }
        }

        $counts = [
            'total' => $allPartners->count(),
            'active' => $allPartners->where('inactivity_status', InactivityStatus::Active)->count(),
            'at_risk' => $allPartners->where('inactivity_status', InactivityStatus::AtRisk)->count(),
            'inactive' => $allPartners->where('inactivity_status', InactivityStatus::Inactive)->count(),
            'critical' => $allPartners->where('inactivity_status', InactivityStatus::Critical)->count(),
            'suspended' => $allPartners->where('inactivity_status', InactivityStatus::Suspended)->count(),
            'flagged' => $allPartners->where('inactivity_flagged_for_review', true)->count(),
        ];

        $partners = $query->latest('last_meaningful_activity_at')->paginate(15)->withQueryString();

        return [
            'counts' => $counts,
            'partners' => $partners,
        ];
    }

    /**
     * Compute Staff Inactivity Dashboard metrics and paginated records.
     */
    public function getStaffDashboardData(array $filters = []): array
    {
        $query = User::with(['assignedLeads', 'loggedLeadActivities'])
            ->where('role', UserRole::TeamMember);

        if (! empty($filters['status'])) {
            $query->where('inactivity_status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['flagged_only'])) {
            $query->where('inactivity_flagged_for_review', true);
        }

        $allStaff = User::where('role', UserRole::TeamMember)->get();
        foreach ($allStaff as $s) {
            $evaluated = $this->evaluateInactivityStatus($s);
            if ($s->inactivity_status !== $evaluated) {
                $s->inactivity_status = $evaluated;
                $s->save();
            }
        }

        $counts = [
            'total' => $allStaff->count(),
            'active' => $allStaff->where('inactivity_status', InactivityStatus::Active)->count(),
            'at_risk' => $allStaff->where('inactivity_status', InactivityStatus::AtRisk)->count(),
            'inactive' => $allStaff->where('inactivity_status', InactivityStatus::Inactive)->count(),
            'critical' => $allStaff->where('inactivity_status', InactivityStatus::Critical)->count(),
            'suspended' => $allStaff->where('inactivity_status', InactivityStatus::Suspended)->count(),
            'flagged' => $allStaff->where('inactivity_flagged_for_review', true)->count(),
        ];

        $staff = $query->latest('last_meaningful_activity_at')->paginate(15)->withQueryString();

        return [
            'counts' => $counts,
            'staff' => $staff,
        ];
    }
}
