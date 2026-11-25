<?php

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadSlaEscalationType;
use App\Enums\LeadSlaStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSlaEscalation;
use App\Models\Notification;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LeadSlaService
{
    public const TIMEZONE = 'Asia/Kolkata';

    /**
     * Get all SLA configuration values with defaults.
     *
     * @return array{
     *     sla_enabled: bool,
     *     sla_first_contact_hours: int,
     *     sla_followup_hours: int,
     *     sla_reassigned_first_contact_hours: int,
     *     sla_calculation_mode: string,
     *     sla_business_start_time: string,
     *     sla_business_end_time: string,
     *     sla_working_days: array<int>,
     *     sla_due_soon_threshold_hours: int
     * }
     */
    public function getConfig(): array
    {
        $enabled = SystemSetting::get('sla_enabled', 'true');
        $firstContactHours = (int) SystemSetting::get('sla_first_contact_hours', '24');
        $followupHours = (int) SystemSetting::get('sla_followup_hours', '48');
        $reassignedHours = (int) SystemSetting::get('sla_reassigned_first_contact_hours', '12');
        $calcMode = SystemSetting::get('sla_calculation_mode', 'calendar_hours');
        $startTime = SystemSetting::get('sla_business_start_time', '09:30');
        $endTime = SystemSetting::get('sla_business_end_time', '18:30');
        $workingDaysJson = SystemSetting::get('sla_working_days', '[1,2,3,4,5,6]');
        $dueSoonHours = (int) SystemSetting::get('sla_due_soon_threshold_hours', '4');

        $workingDays = json_decode($workingDaysJson, true);
        if (!is_array($workingDays) || empty($workingDays)) {
            $workingDays = [1, 2, 3, 4, 5, 6];
        }

        return [
            'sla_enabled' => in_array(strtolower((string) $enabled), ['true', '1', 'yes'], true),
            'sla_first_contact_hours' => $firstContactHours > 0 ? $firstContactHours : 24,
            'sla_followup_hours' => $followupHours > 0 ? $followupHours : 48,
            'sla_reassigned_first_contact_hours' => $reassignedHours > 0 ? $reassignedHours : 12,
            'sla_calculation_mode' => in_array($calcMode, ['calendar_hours', 'business_hours'], true) ? $calcMode : 'calendar_hours',
            'sla_business_start_time' => $startTime ?: '09:30',
            'sla_business_end_time' => $endTime ?: '18:30',
            'sla_working_days' => array_map('intval', $workingDays),
            'sla_due_soon_threshold_hours' => $dueSoonHours > 0 ? $dueSoonHours : 4,
        ];
    }

    /**
     * Update SLA configuration settings.
     */
    public function updateConfig(array $data, User $admin): void
    {
        if (isset($data['sla_enabled'])) {
            SystemSetting::set('sla_enabled', $data['sla_enabled'] ? 'true' : 'false', $admin->id);
        }

        if (isset($data['sla_first_contact_hours'])) {
            SystemSetting::set('sla_first_contact_hours', (string) max(1, (int) $data['sla_first_contact_hours']), $admin->id);
        }

        if (isset($data['sla_followup_hours'])) {
            SystemSetting::set('sla_followup_hours', (string) max(1, (int) $data['sla_followup_hours']), $admin->id);
        }

        if (isset($data['sla_reassigned_first_contact_hours'])) {
            SystemSetting::set('sla_reassigned_first_contact_hours', (string) max(1, (int) $data['sla_reassigned_first_contact_hours']), $admin->id);
        }

        if (isset($data['sla_calculation_mode'])) {
            $mode = in_array($data['sla_calculation_mode'], ['business_hours', 'calendar_hours'], true) ? $data['sla_calculation_mode'] : 'calendar_hours';
            SystemSetting::set('sla_calculation_mode', $mode, $admin->id);
        }

        if (isset($data['sla_business_start_time'])) {
            SystemSetting::set('sla_business_start_time', $data['sla_business_start_time'], $admin->id);
        }

        if (isset($data['sla_business_end_time'])) {
            SystemSetting::set('sla_business_end_time', $data['sla_business_end_time'], $admin->id);
        }

        if (isset($data['sla_working_days'])) {
            $days = is_array($data['sla_working_days']) ? $data['sla_working_days'] : [1, 2, 3, 4, 5, 6];
            SystemSetting::set('sla_working_days', json_encode(array_values(array_map('intval', $days))), $admin->id);
        }

        if (isset($data['sla_due_soon_threshold_hours'])) {
            SystemSetting::set('sla_due_soon_threshold_hours', (string) max(1, (int) $data['sla_due_soon_threshold_hours']), $admin->id);
        }
    }

    /**
     * Calculate target deadline given a start time and target hours.
     */
    public function calculateDeadline(CarbonInterface $startTime, int $hours, ?string $mode = null): Carbon
    {
        $config = $this->getConfig();
        $calcMode = $mode ?? $config['sla_calculation_mode'];
        $start = Carbon::parse($startTime)->setTimezone(self::TIMEZONE);

        if ($calcMode === 'calendar_hours') {
            return $start->copy()->addHours($hours);
        }

        // Business hours calculation
        $workingDays = $config['sla_working_days']; // e.g. [1,2,3,4,5,6] (Carbon 1=Mon, 7=Sun)
        [$startHour, $startMinute] = array_map('intval', explode(':', $config['sla_business_start_time']));
        [$endHour, $endMinute] = array_map('intval', explode(':', $config['sla_business_end_time']));

        $current = $start->copy();
        $remainingMinutes = $hours * 60;

        while ($remainingMinutes > 0) {
            // Check if current day is a working day
            $isoDay = $current->isoWeekday(); // 1=Mon, 7=Sun
            $isWorkingDay = in_array($isoDay, $workingDays, true);

            $dayStart = $current->copy()->setTime($startHour, $startMinute, 0);
            $dayEnd = $current->copy()->setTime($endHour, $endMinute, 0);

            if (! $isWorkingDay) {
                // Move to next day start
                $current->addDay()->setTime($startHour, $startMinute, 0);
                continue;
            }

            if ($current->lessThan($dayStart)) {
                $current = $dayStart->copy();
            }

            if ($current->greaterThanOrEqualTo($dayEnd)) {
                $current->addDay()->setTime($startHour, $startMinute, 0);
                continue;
            }

            // Available business minutes remaining today
            $availableMinutesToday = $current->diffInMinutes($dayEnd, false);

            if ($remainingMinutes <= $availableMinutesToday) {
                $current->addMinutes($remainingMinutes);
                $remainingMinutes = 0;
            } else {
                $remainingMinutes -= $availableMinutesToday;
                $current->addDay()->setTime($startHour, $startMinute, 0);
            }
        }

        return $current;
    }

    /**
     * Calculate first contact deadline for an assigned lead.
     */
    public function calculateFirstContactDeadline(CarbonInterface $assignedAt, bool $isReassignment = false): Carbon
    {
        $config = $this->getConfig();
        $hours = $isReassignment ? $config['sla_reassigned_first_contact_hours'] : $config['sla_first_contact_hours'];

        return $this->calculateDeadline($assignedAt, $hours);
    }

    /**
     * Calculate follow-up deadline.
     */
    public function calculateFollowupDeadline(CarbonInterface $contactedAt, ?string $nextFollowupDate = null): Carbon
    {
        $config = $this->getConfig();

        if (! empty($nextFollowupDate)) {
            // When an explicit follow-up date is scheduled, it is due by end of business (18:30 IST) on that date
            [$endHour, $endMinute] = array_map('intval', explode(':', $config['sla_business_end_time']));
            return Carbon::parse($nextFollowupDate, self::TIMEZONE)->setTime($endHour, $endMinute, 0);
        }

        return $this->calculateDeadline($contactedAt, $config['sla_followup_hours']);
    }

    /**
     * Hook when a lead is assigned or reassigned to a team member.
     */
    public function onLeadAssigned(Lead $lead, User $teamMember, User $admin, bool $isReassignment = false): void
    {
        $config = $this->getConfig();
        if (! $config['sla_enabled']) {
            $lead->update([
                'overall_sla_status' => LeadSlaStatus::Exempt,
                'first_contact_sla_status' => LeadSlaStatus::Exempt,
            ]);
            return;
        }

        $assignedAt = now()->setTimezone(self::TIMEZONE);
        $deadline = $this->calculateFirstContactDeadline($assignedAt, $isReassignment);

        $updates = [
            'first_contact_due_at' => $deadline,
            'first_contact_sla_status' => LeadSlaStatus::OnTrack,
            'overall_sla_status' => LeadSlaStatus::OnTrack,
        ];

        // If previously contacted, preserve contacted state
        if ($lead->first_contacted_at !== null) {
            $updates['first_contact_sla_status'] = LeadSlaStatus::Met;
        }

        $lead->update($updates);
    }

    /**
     * Check if an activity type represents a genuine direct customer contact.
     * Only genuine contact activities satisfy First Contact SLA.
     */
    public function isGenuineCustomerContact(LeadActivityType $activityType): bool
    {
        return in_array($activityType, [
            LeadActivityType::Call,
            LeadActivityType::WhatsApp,
            LeadActivityType::Email,
            LeadActivityType::Meeting,
        ], true);
    }

    /**
     * Hook when staff or admin logs an activity.
     */
    public function onActivityLogged(Lead $lead, LeadActivity $activity, User $actor): void
    {
        $config = $this->getConfig();
        if (! $config['sla_enabled'] || $lead->overall_sla_status === LeadSlaStatus::Exempt) {
            return;
        }

        $now = now()->setTimezone(self::TIMEZONE);
        $isGenuine = $this->isGenuineCustomerContact($activity->activity_type);

        if (! $isGenuine) {
            // Notes, internal comments, status updates do not satisfy first contact SLA.
            return;
        }

        $updates = [];

        // 1. First Contact SLA evaluation
        if ($lead->first_contacted_at === null) {
            $updates['first_contacted_at'] = $now;

            $isMet = true;
            if ($lead->first_contact_due_at && $now->greaterThan($lead->first_contact_due_at)) {
                $isMet = false;
            }

            $updates['first_contact_sla_status'] = $isMet ? LeadSlaStatus::Met : LeadSlaStatus::Overdue;

            // Record timeline activity for First Contact Met
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'activity_type' => LeadActivityType::FirstContactMet,
                'summary' => "First Contact SLA " . ($isMet ? "Satisfied (Met)" : "Satisfied (Overdue)"),
                'details' => "First customer contact established via {$activity->activity_type->label()} on " . $now->format('d M Y, h:i A') . " IST.",
                'is_internal' => true,
                'created_at' => now(),
            ]);
        }

        // 2. Follow-up SLA tracking
        $updates['last_contacted_at'] = $now;
        $updates['followup_due_at'] = $this->calculateFollowupDeadline($now, $activity->next_followup_date ?: $lead->next_followup_date?->format('Y-m-d'));
        $updates['followup_sla_status'] = LeadSlaStatus::OnTrack;

        // Reset missed follow-up counter on genuine contact
        $updates['missed_followups_count'] = 0;

        // 3. Compute overall SLA status
        $firstStatus = $updates['first_contact_sla_status'] ?? $lead->first_contact_sla_status;
        if ($firstStatus === LeadSlaStatus::Overdue) {
            $updates['overall_sla_status'] = LeadSlaStatus::Overdue;
        } else {
            $updates['overall_sla_status'] = LeadSlaStatus::OnTrack;
        }

        $lead->update($updates);
    }

    /**
     * Mark SLA exempt when lead enters final state (Won, Lost, Rejected).
     */
    public function onLeadClosed(Lead $lead): void
    {
        $lead->update([
            'overall_sla_status' => LeadSlaStatus::Exempt,
        ]);
    }

    /**
     * Refresh SLA status based on elapsed time for a lead.
     */
    public function refreshLeadSla(Lead $lead): Lead
    {
        $config = $this->getConfig();
        if (! $config['sla_enabled'] || $lead->overall_sla_status === LeadSlaStatus::Exempt) {
            return $lead;
        }

        if (in_array($lead->status, [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected], true)) {
            $lead->update(['overall_sla_status' => LeadSlaStatus::Exempt]);
            return $lead;
        }

        $now = now()->setTimezone(self::TIMEZONE);
        $dueSoonHours = $config['sla_due_soon_threshold_hours'];
        $updates = [];

        // Check First Contact SLA
        if ($lead->first_contacted_at === null && $lead->first_contact_due_at !== null) {
            if ($now->greaterThan($lead->first_contact_due_at)) {
                $updates['first_contact_sla_status'] = LeadSlaStatus::Overdue;
            } elseif ($now->copy()->addHours($dueSoonHours)->greaterThanOrEqualTo($lead->first_contact_due_at)) {
                $updates['first_contact_sla_status'] = LeadSlaStatus::DueSoon;
            } else {
                $updates['first_contact_sla_status'] = LeadSlaStatus::OnTrack;
            }
        }

        // Check Follow-up SLA
        if ($lead->followup_due_at !== null) {
            if ($now->greaterThan($lead->followup_due_at)) {
                $updates['followup_sla_status'] = LeadSlaStatus::Overdue;
            } elseif ($now->copy()->addHours($dueSoonHours)->greaterThanOrEqualTo($lead->followup_due_at)) {
                $updates['followup_sla_status'] = LeadSlaStatus::DueSoon;
            } else {
                $updates['followup_sla_status'] = LeadSlaStatus::OnTrack;
            }
        }

        // Determine Overall SLA Status
        $firstStatus = $updates['first_contact_sla_status'] ?? $lead->first_contact_sla_status;
        $followupStatus = $updates['followup_sla_status'] ?? $lead->followup_sla_status;

        if ($firstStatus === LeadSlaStatus::Overdue || $followupStatus === LeadSlaStatus::Overdue) {
            $updates['overall_sla_status'] = LeadSlaStatus::Overdue;
        } elseif ($firstStatus === LeadSlaStatus::DueSoon || $followupStatus === LeadSlaStatus::DueSoon) {
            $updates['overall_sla_status'] = LeadSlaStatus::DueSoon;
        } elseif ($firstStatus === LeadSlaStatus::Met && ($followupStatus === LeadSlaStatus::OnTrack || $followupStatus === null)) {
            $updates['overall_sla_status'] = LeadSlaStatus::OnTrack;
        } else {
            $updates['overall_sla_status'] = LeadSlaStatus::OnTrack;
        }

        $lead->update($updates);

        return $lead;
    }

    /**
     * Check active leads for SLA breaches, record escalations, and notify Admins.
     * Deduplicates escalations so repeated runs do not spam duplicate notifications.
     *
     * @return array{first_contact_breaches: int, followup_breaches: int, repeated_missed: int}
     */
    public function checkAndEscalateBreaches(): array
    {
        $config = $this->getConfig();
        if (! $config['sla_enabled']) {
            return ['first_contact_breaches' => 0, 'followup_breaches' => 0, 'repeated_missed' => 0];
        }

        $now = now()->setTimezone(self::TIMEZONE);
        $firstContactBreaches = 0;
        $followupBreaches = 0;
        $repeatedMissed = 0;

        // Fetch active assigned leads
        $activeLeads = Lead::with(['assignedTo', 'partner'])
            ->whereNotNull('assigned_to')
            ->whereNotIn('status', [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected])
            ->where(function ($q) {
                $q->whereNull('overall_sla_status')
                    ->orWhere('overall_sla_status', '!=', LeadSlaStatus::Exempt);
            })
            ->get();

        $adminUsers = User::where('role', UserRole::Admin)->get();

        foreach ($activeLeads as $lead) {
            $this->refreshLeadSla($lead);

            // 1. Check First Contact Breach
            if ($lead->first_contacted_at === null && $lead->first_contact_due_at && $now->greaterThan($lead->first_contact_due_at)) {
                // Deduplicate: check if already escalated for first_contact_breach
                $alreadyEscalated = LeadSlaEscalation::where('lead_id', $lead->id)
                    ->where('escalation_type', LeadSlaEscalationType::FirstContactBreach)
                    ->exists();

                if (! $alreadyEscalated) {
                    LeadSlaEscalation::create([
                        'lead_id' => $lead->id,
                        'assigned_to' => $lead->assigned_to,
                        'escalation_type' => LeadSlaEscalationType::FirstContactBreach,
                        'severity' => 'critical',
                        'due_at' => $lead->first_contact_due_at,
                        'breached_at' => $now,
                        'details' => "First contact target of {$lead->first_contact_due_at->format('d M Y, h:i A')} IST missed by " . ($lead->assignedTo ? $lead->assignedTo->name : 'Staff') . ".",
                        'notification_sent' => true,
                    ]);

                    // Timeline activity
                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $lead->assigned_to,
                        'activity_type' => LeadActivityType::SlaBreached,
                        'summary' => "SLA Breach: First Contact Overdue",
                        'details' => "Assigned team member did not make genuine contact before deadline {$lead->first_contact_due_at->format('d M Y, h:i A')} IST.",
                        'is_internal' => true,
                        'created_at' => now(),
                    ]);

                    // Admin In-App Notifications
                    foreach ($adminUsers as $admin) {
                        Notification::send(
                            $admin->id,
                            "SLA Breach: Lead #{$lead->lead_number}",
                            "First contact overdue for Lead #{$lead->lead_number} assigned to " . ($lead->assignedTo ? $lead->assignedTo->name : 'Staff') . ".",
                            'warning',
                            route('admin.leads.show', $lead->id)
                        );
                    }

                    $lead->update([
                        'first_contact_sla_status' => LeadSlaStatus::Overdue,
                        'overall_sla_status' => LeadSlaStatus::Overdue,
                        'last_escalated_at' => $now,
                    ]);

                    $firstContactBreaches++;
                }
            }

            // 2. Check Follow-up Breach
            if ($lead->followup_due_at && $now->greaterThan($lead->followup_due_at)) {
                // Deduplicate per followup_due_at timestamp
                $alreadyEscalatedFollowup = LeadSlaEscalation::where('lead_id', $lead->id)
                    ->where('escalation_type', LeadSlaEscalationType::FollowupBreach)
                    ->where('due_at', $lead->followup_due_at)
                    ->exists();

                if (! $alreadyEscalatedFollowup) {
                    $newMissedCount = $lead->missed_followups_count + 1;

                    LeadSlaEscalation::create([
                        'lead_id' => $lead->id,
                        'assigned_to' => $lead->assigned_to,
                        'escalation_type' => LeadSlaEscalationType::FollowupBreach,
                        'severity' => 'warning',
                        'due_at' => $lead->followup_due_at,
                        'breached_at' => $now,
                        'details' => "Follow-up due at {$lead->followup_due_at->format('d M Y, h:i A')} IST missed.",
                        'notification_sent' => true,
                    ]);

                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $lead->assigned_to,
                        'activity_type' => LeadActivityType::SlaBreached,
                        'summary' => "SLA Breach: Follow-up Overdue",
                        'details' => "Scheduled follow-up due at {$lead->followup_due_at->format('d M Y, h:i A')} IST was missed.",
                        'is_internal' => true,
                        'created_at' => now(),
                    ]);

                    // Check for repeated missed follow-ups (2 or more)
                    if ($newMissedCount >= 2) {
                        $alreadyEscalatedRepeated = LeadSlaEscalation::where('lead_id', $lead->id)
                            ->where('escalation_type', LeadSlaEscalationType::RepeatedMissedFollowup)
                            ->where('created_at', '>=', now()->subHours(24))
                            ->exists();

                        if (! $alreadyEscalatedRepeated) {
                            LeadSlaEscalation::create([
                                'lead_id' => $lead->id,
                                'assigned_to' => $lead->assigned_to,
                                'escalation_type' => LeadSlaEscalationType::RepeatedMissedFollowup,
                                'severity' => 'critical',
                                'due_at' => $lead->followup_due_at,
                                'breached_at' => $now,
                                'details' => "Staff has repeatedly missed follow-ups ({$newMissedCount} missed follow-ups).",
                                'notification_sent' => true,
                            ]);

                            LeadActivity::create([
                                'lead_id' => $lead->id,
                                'user_id' => $lead->assigned_to,
                                'activity_type' => LeadActivityType::SlaEscalated,
                                'summary' => "Critical Escalation: Repeated Missed Follow-ups",
                                'details' => "{$newMissedCount} consecutive follow-ups missed. Escalated to Admin.",
                                'is_internal' => true,
                                'created_at' => now(),
                            ]);

                            $repeatedMissed++;
                        }
                    }

                    // Admin Notifications
                    foreach ($adminUsers as $admin) {
                        Notification::send(
                            $admin->id,
                            "Follow-up Overdue: Lead #{$lead->lead_number}",
                            "Follow-up overdue for Lead #{$lead->lead_number} assigned to " . ($lead->assignedTo ? $lead->assignedTo->name : 'Staff') . " ({$newMissedCount} missed).",
                            'warning',
                            route('admin.leads.show', $lead->id)
                        );
                    }

                    $lead->update([
                        'followup_sla_status' => LeadSlaStatus::Overdue,
                        'overall_sla_status' => LeadSlaStatus::Overdue,
                        'missed_followups_count' => $newMissedCount,
                        'last_escalated_at' => $now,
                    ]);

                    $followupBreaches++;
                }
            }
        }

        return [
            'first_contact_breaches' => $firstContactBreaches,
            'followup_breaches' => $followupBreaches,
            'repeated_missed' => $repeatedMissed,
        ];
    }

    /**
     * Compute SLA dashboard KPIs and staff-wise performance breakdown.
     */
    public function getDashboardMetrics(array $filters = []): array
    {
        $now = now()->setTimezone(self::TIMEZONE);

        $query = Lead::with(['assignedTo', 'partner'])
            ->whereNotNull('assigned_to');

        if (! empty($filters['staff_id'])) {
            $query->where('assigned_to', $filters['staff_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('overall_sla_status', $filters['status']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from'], self::TIMEZONE)->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to'], self::TIMEZONE)->endOfDay());
        }

        $allLeads = $query->get();

        // Refresh statuses in memory for accurate counts
        foreach ($allLeads as $lead) {
            $this->refreshLeadSla($lead);
        }

        $activeLeads = $allLeads->filter(fn (Lead $l) => $l->isUnderActiveSla());

        $totalUnderSla = $activeLeads->count();
        $onTrackCount = $activeLeads->where('overall_sla_status', LeadSlaStatus::OnTrack)->count();
        $dueSoonCount = $activeLeads->where('overall_sla_status', LeadSlaStatus::DueSoon)->count();
        $overdueFirstContactCount = $activeLeads->where('first_contact_sla_status', LeadSlaStatus::Overdue)->count();
        $overdueFollowupCount = $activeLeads->where('followup_sla_status', LeadSlaStatus::Overdue)->count();
        $totalOverdueCount = $activeLeads->where('overall_sla_status', LeadSlaStatus::Overdue)->count();

        $complianceRate = $totalUnderSla > 0
            ? round((($onTrackCount + $dueSoonCount) / $totalUnderSla) * 100, 1)
            : 100.0;

        // Staff-wise Breakdown
        $staffMembers = User::where('role', UserRole::TeamMember)
            ->when(! empty($filters['staff_id']), fn ($q) => $q->where('id', $filters['staff_id']))
            ->get();

        $staffPerformance = $staffMembers->map(function (User $staff) use ($allLeads) {
            $staffLeads = $allLeads->where('assigned_to', $staff->id);
            $staffActive = $staffLeads->filter(fn (Lead $l) => $l->isUnderActiveSla());

            $assignedCount = $staffActive->count();
            $firstMet = $staffLeads->where('first_contact_sla_status', LeadSlaStatus::Met)->count();
            $firstOverdue = $staffActive->where('first_contact_sla_status', LeadSlaStatus::Overdue)->count();
            $followupOverdue = $staffActive->where('followup_sla_status', LeadSlaStatus::Overdue)->count();
            $dueSoon = $staffActive->where('overall_sla_status', LeadSlaStatus::DueSoon)->count();
            $onTrack = $staffActive->where('overall_sla_status', LeadSlaStatus::OnTrack)->count();

            $totalEscalations = LeadSlaEscalation::where('assigned_to', $staff->id)->count();

            $staffCompliance = $assignedCount > 0
                ? round((($onTrack + $dueSoon) / $assignedCount) * 100, 1)
                : 100.0;

            return [
                'staff' => $staff,
                'active_leads' => $assignedCount,
                'first_contact_met' => $firstMet,
                'first_contact_overdue' => $firstOverdue,
                'followup_overdue' => $followupOverdue,
                'due_soon' => $dueSoon,
                'total_escalations' => $totalEscalations,
                'compliance_rate' => $staffCompliance,
            ];
        });

        return [
            'total_under_sla' => $totalUnderSla,
            'on_track_count' => $onTrackCount,
            'due_soon_count' => $dueSoonCount,
            'overdue_first_contact_count' => $overdueFirstContactCount,
            'overdue_followup_count' => $overdueFollowupCount,
            'total_overdue_count' => $totalOverdueCount,
            'compliance_rate' => $complianceRate,
            'staff_performance' => $staffPerformance,
            'leads' => $allLeads,
        ];
    }
}
