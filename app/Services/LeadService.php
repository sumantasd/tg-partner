<?php

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadInterest;
use App\Enums\LeadOwnershipStatus;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadOwnershipHistory;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\SystemSetting;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class LeadService
{
    public function __construct(
        protected ?LeadSlaService $slaService = null
    ) {
        $this->slaService = $slaService ?? app(LeadSlaService::class);
    }

    /**
     * Normalize customer phone number for consistent lookup and duplicate comparison.
     */
    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // If phone starts with country code 91 and has > 10 digits, take the last 10 digits
        if (strlen($digits) > 10 && str_starts_with($digits, '91')) {
            return substr($digits, -10);
        }

        // If phone starts with leading 0 and has 11 digits, strip leading 0
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Normalize customer email address.
     */
    public function normalizeEmail(?string $email): ?string
    {
        return $email ? strtolower(trim($email)) : null;
    }

    /**
     * Detect duplicate leads within the specified cooling period (default from system setting or 90 days).
     *
     * @return array{is_duplicate: bool, matched_lead: ?Lead, same_partner: bool}
     */
    public function detectDuplicate(string $phone, ?string $email, ?int $currentPartnerId = null, ?int $coolingDays = null): array
    {
        $coolingDays = $coolingDays ?? (int) SystemSetting::get('lead_ownership_validity_days', '90');
        $normalizedPhone = $this->normalizePhone($phone);
        $normalizedEmail = $this->normalizeEmail($email);
        $coolingSince = now()->subDays($coolingDays);

        // Fetch candidates within the cooling period in chronological referral order
        $candidates = Lead::with('partner.user')
            ->where(function ($query) use ($coolingSince) {
                $query->where('created_at', '>=', $coolingSince)
                    ->orWhere(function ($q) {
                        $q->where('ownership_status', LeadOwnershipStatus::Protected)
                            ->where(function ($sub) {
                                $sub->whereNull('ownership_expires_at')
                                    ->orWhere('ownership_expires_at', '>', now());
                            });
                    });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($candidates as $candidate) {
            $candidatePhone = $this->normalizePhone($candidate->customer_phone);
            $candidateEmail = $this->normalizeEmail($candidate->customer_email);

            $phoneMatches = ($normalizedPhone !== '' && $candidatePhone === $normalizedPhone);
            $emailMatches = (! empty($normalizedEmail) && ! empty($candidateEmail) && $candidateEmail === $normalizedEmail);

            if ($phoneMatches || $emailMatches) {
                return [
                    'is_duplicate' => true,
                    'matched_lead' => $candidate,
                    'same_partner' => ($currentPartnerId !== null && $candidate->partner_id === $currentPartnerId),
                ];
            }
        }

        return [
            'is_duplicate' => false,
            'matched_lead' => null,
            'same_partner' => false,
        ];
    }

    /**
     * Generate unique sequential lead number (e.g., LED-2026-0001).
     */
    public function generateLeadNumber(): string
    {
        $year = date('Y');
        $prefix = "LED-{$year}-";

        $latest = Lead::withTrashed()
            ->where('lead_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(lead_number, 10) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        if ($latest && preg_match('/^LED-\d{4}-(\d+)$/', $latest->lead_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Submit a new customer lead from a partner.
     */
    public function createLead(Partner $partner, array $data, ?User $actor = null, bool $enforceProtection = false): Lead
    {
        return DB::transaction(function () use ($partner, $data, $actor, $enforceProtection) {
            $leadNumber = $this->generateLeadNumber();
            $duplicateCheck = $this->detectDuplicate(
                $data['customer_phone'],
                $data['customer_email'] ?? null,
                $partner->id
            );

            if ($enforceProtection && $duplicateCheck['is_duplicate']) {
                if (! $duplicateCheck['same_partner']) {
                    throw new DomainException('This customer lead already exists in the Tech Googly Partner Program and cannot be submitted again at this time.');
                } else {
                    throw new DomainException("You have already submitted this customer lead (#{$duplicateCheck['matched_lead']->lead_number}).");
                }
            }

            $validityDays = (int) SystemSetting::get('lead_ownership_validity_days', '90');
            $expiresAt = now()->addDays($validityDays);

            $lead = Lead::create([
                'lead_number'               => $leadNumber,
                'partner_id'                => $partner->id,
                'original_partner_id'       => $partner->id,
                'customer_name'             => trim($data['customer_name']),
                'customer_company'          => ! empty($data['customer_company']) ? trim($data['customer_company']) : null,
                'customer_phone'            => trim($data['customer_phone']),
                'customer_email'            => $this->normalizeEmail($data['customer_email'] ?? null),
                'city'                      => ! empty($data['city']) ? trim($data['city']) : null,
                'service_required'          => trim($data['service_required']),
                'project_description'       => trim($data['project_description']),
                'estimated_value'           => ! empty($data['estimated_value']) ? (float) $data['estimated_value'] : null,
                'currency'                  => $data['currency'] ?? 'INR',
                'status'                    => LeadStatus::Submitted,
                'ownership_status'          => LeadOwnershipStatus::Protected,
                'ownership_started_at'      => now(),
                'submitted_at'              => now(),
                'ownership_expires_at'      => $expiresAt,
                'is_duplicate_flag'         => $duplicateCheck['is_duplicate'],
                'duplicate_matched_lead_id' => $duplicateCheck['matched_lead']?->id,
            ]);

            $actorUser = $actor ?? $partner->user;

            // Initial ownership audit record
            LeadOwnershipHistory::create([
                'lead_id'             => $lead->id,
                'previous_partner_id' => null,
                'new_partner_id'      => $partner->id,
                'from_status'         => null,
                'to_status'           => LeadOwnershipStatus::Protected,
                'action'              => 'initial_assignment',
                'reason'              => 'Initial partner lead referral.',
                'valid_until_before'  => null,
                'valid_until_after'   => $expiresAt,
                'performed_by'        => $actorUser->id,
            ]);

            // 1. Initial canonical timeline activity (Partner-visible)
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $actorUser->id,
                'activity_type' => LeadActivityType::LeadSubmitted,
                'to_status' => LeadStatus::Submitted->value,
                'summary' => "Lead submitted by {$partner->company_name}",
                'details' => 'Requested service: ' . $lead->service_required,
                'is_internal' => false,
                'created_at' => now(),
            ]);

            // 2. Duplicate detection internal notes (Admin-only timeline)
            if ($duplicateCheck['is_duplicate']) {
                $matched = $duplicateCheck['matched_lead'];
                if ($duplicateCheck['same_partner']) {
                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $actorUser->id,
                        'activity_type' => LeadActivityType::Note,
                        'summary' => "Duplicate Warning: Same partner submitted matching lead (#{$matched->lead_number})",
                        'details' => "Customer phone/email matches an existing lead submitted within the 90-day cooling period.",
                        'is_internal' => true,
                        'created_at' => now(),
                    ]);
                } else {
                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $actorUser->id,
                        'activity_type' => LeadActivityType::Note,
                        'summary' => "Ownership Conflict: Lead matches referral by {$matched->partner->company_name} (#{$matched->lead_number})",
                        'details' => "Original referral attribution is preserved. Admin review required to resolve conflict.",
                        'is_internal' => true,
                        'created_at' => now(),
                    ]);
                }
            }

            // Record meaningful activity for partner
            if ($partner->user) {
                app(\App\Services\InactivityService::class)->recordActivity($partner->user, 'lead_submission');
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadCreated,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lead->lead_number} ({$lead->customer_name}) submitted by Partner {$partner->company_name}.",
                subject: $lead,
                newValues: [
                    'lead_number' => $lead->lead_number,
                    'contact_name' => $lead->customer_name,
                    'company_name' => $lead->customer_company,
                    'phone' => $lead->customer_phone,
                    'status' => $lead->status->value,
                ],
                actor: $actorUser
            );

            return $lead;
        });
    }

    /**
     * Create a new Lead directly by Admin.
     * Supports optional Partner attribution, optional Team Member assignment,
     * duplicate detection within 90-day cooling period, and audit timeline tracking.
     */
    public function createAdminLead(array $data, User $admin): Lead
    {
        return DB::transaction(function () use ($data, $admin) {
            $leadNumber = $this->generateLeadNumber();
            $partnerId = !empty($data['partner_id']) ? (int) $data['partner_id'] : null;
            $teamMemberId = !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null;

            if ($partnerId !== null) {
                Partner::findOrFail($partnerId);
            }
            if ($teamMemberId !== null) {
                User::where('role', UserRole::TeamMember)->findOrFail($teamMemberId);
            }

            $duplicateCheck = $this->detectDuplicate(
                $data['customer_phone'],
                $data['customer_email'] ?? null,
                $partnerId
            );

            $status = $teamMemberId !== null ? LeadStatus::Assigned : LeadStatus::Submitted;
            $validityDays = (int) SystemSetting::get('lead_ownership_validity_days', '90');
            $expiresAt = $partnerId !== null ? now()->addDays($validityDays) : null;
            $ownershipStatus = $partnerId !== null ? LeadOwnershipStatus::Protected : LeadOwnershipStatus::Released;

            $lead = Lead::create([
                'lead_number'               => $leadNumber,
                'partner_id'                => $partnerId,
                'original_partner_id'       => $partnerId,
                'assigned_to'               => $teamMemberId,
                'assigned_by'               => $teamMemberId !== null ? $admin->id : null,
                'assigned_at'               => $teamMemberId !== null ? now() : null,
                'created_by'                => $admin->id,
                'customer_name'             => trim($data['customer_name']),
                'customer_company'          => !empty($data['customer_company']) ? trim($data['customer_company']) : null,
                'customer_phone'            => trim($data['customer_phone']),
                'whatsapp_number'           => !empty($data['whatsapp_number']) ? trim($data['whatsapp_number']) : null,
                'customer_email'            => $this->normalizeEmail($data['customer_email'] ?? null),
                'city'                      => !empty($data['city']) ? trim($data['city']) : null,
                'state'                     => !empty($data['state']) ? trim($data['state']) : null,
                'service_required'          => trim($data['service_required']),
                'lead_source'               => !empty($data['lead_source']) ? trim($data['lead_source']) : 'Direct',
                'priority'                  => !empty($data['priority']) ? trim($data['priority']) : 'Medium',
                'project_description'       => !empty($data['project_description']) ? trim($data['project_description']) : 'Directly entered by Admin',
                'estimated_value'           => !empty($data['estimated_value']) ? (float) $data['estimated_value'] : null,
                'currency'                  => $data['currency'] ?? 'INR',
                'status'                    => $status,
                'ownership_status'          => $ownershipStatus,
                'ownership_started_at'      => $partnerId !== null ? now() : null,
                'submitted_at'              => now(),
                'ownership_expires_at'      => $expiresAt,
                'next_followup_date'        => !empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
                'is_duplicate_flag'         => $duplicateCheck['is_duplicate'],
                'duplicate_matched_lead_id' => $duplicateCheck['matched_lead']?->id,
            ]);

            if ($partnerId !== null) {
                LeadOwnershipHistory::create([
                    'lead_id'             => $lead->id,
                    'previous_partner_id' => null,
                    'new_partner_id'      => $partnerId,
                    'from_status'         => null,
                    'to_status'           => LeadOwnershipStatus::Protected,
                    'action'              => 'initial_assignment',
                    'reason'              => 'Direct creation by Admin with partner attribution.',
                    'valid_until_before'  => null,
                    'valid_until_after'   => $expiresAt,
                    'performed_by'        => $admin->id,
                ]);
            }

            // 1. Initial creation timeline activity
            $sourceText = $lead->lead_source ? " via {$lead->lead_source}" : '';
            LeadActivity::create([
                'lead_id'       => $lead->id,
                'user_id'       => $admin->id,
                'activity_type' => LeadActivityType::LeadSubmitted,
                'to_status'     => $status->value,
                'summary'       => "Lead created by Admin ({$admin->name}){$sourceText}",
                'details'       => 'Service: ' . $lead->service_required . ($partnerId ? " (Attributed to Partner ID #{$partnerId})" : ''),
                'is_internal'   => false,
                'created_at'    => now(),
            ]);

            // 2. If team member assigned upon creation, log assignment activity and initiate SLA
            if ($teamMemberId !== null) {
                $assignedStaff = User::find($teamMemberId);
                LeadActivity::create([
                    'lead_id'       => $lead->id,
                    'user_id'       => $admin->id,
                    'activity_type' => LeadActivityType::LeadAssigned,
                    'to_status'     => LeadStatus::Assigned->value,
                    'summary'       => "Assigned to {$assignedStaff->name}",
                    'details'       => "Directly assigned upon creation by {$admin->name}.",
                    'is_internal'   => false,
                    'created_at'    => now(),
                ]);

                $this->slaService->onLeadAssigned($lead, $assignedStaff, $admin, false);
            }

            // 3. Duplicate detection audit note
            if ($duplicateCheck['is_duplicate']) {
                $matched = $duplicateCheck['matched_lead'];
                LeadActivity::create([
                    'lead_id'       => $lead->id,
                    'user_id'       => $admin->id,
                    'activity_type' => LeadActivityType::Note,
                    'summary'       => "Duplicate Detected: Matches Lead #{$matched->lead_number} ({$matched->customer_name})",
                    'details'       => "Customer phone/email matches an existing lead submitted within the 90-day cooling period.",
                    'is_internal'   => true,
                    'created_at'    => now(),
                ]);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadCreated,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lead->lead_number} ({$lead->customer_name}) created by Admin {$admin->name}.",
                subject: $lead,
                newValues: [
                    'lead_number' => $lead->lead_number,
                    'contact_name' => $lead->customer_name,
                    'company_name' => $lead->customer_company,
                    'status' => $lead->status->value,
                ],
                actor: $admin
            );

            return $lead;
        });
    }

    /**
     * Assign or reassign lead to an internal team member.
     */
    public function assignLead(Lead $lead, User $teamMember, User $admin, ?string $note = null): Lead
    {
        return DB::transaction(function () use ($lead, $teamMember, $admin, $note) {
            if (! $admin->isAdmin()) {
                throw new DomainException('Only an authorized Admin can assign leads.');
            }

            if (! $teamMember->isTeamMember()) {
                throw new DomainException('Leads can only be assigned to internal Team Members.');
            }

            $isReassignment = ($lead->assigned_to !== null);
            $previousAssignee = $lead->assignedTo;
            $fromStatus = $lead->status->value;

            $lead->update([
                'assigned_to' => $teamMember->id,
                'assigned_by' => $admin->id,
                'assigned_at' => now(),
                'status' => in_array($lead->status, [LeadStatus::Submitted, LeadStatus::Assigned]) ? LeadStatus::Assigned : $lead->status,
            ]);

            $activityType = $isReassignment ? LeadActivityType::LeadReassigned : LeadActivityType::LeadAssigned;
            $summary = $isReassignment
                ? "Lead reassigned to {$teamMember->name} by {$admin->name}"
                : "Lead assigned to {$teamMember->name} by {$admin->name}";

            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $admin->id,
                'activity_type' => $activityType,
                'from_status' => $fromStatus,
                'to_status' => $lead->status->value,
                'summary' => $summary,
                'details' => $isReassignment ? "Reassigned from " . ($previousAssignee ? $previousAssignee->name : 'None') : null,
                'is_internal' => false,
                'created_at' => now(),
            ]);

            // If private admin instruction/note is provided, log as internal note
            if (! empty($note)) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $admin->id,
                    'activity_type' => LeadActivityType::AdminComment,
                    'summary' => "Admin Assignment Note: {$admin->name}",
                    'details' => trim($note),
                    'is_internal' => true,
                    'created_at' => now(),
                ]);
            }

            // Hook into SLA management for assignment or reassignment
            $this->slaService->onLeadAssigned($lead, $teamMember, $admin, $isReassignment);

            app(\App\Services\AuditLogService::class)->record(
                action: $isReassignment ? \App\Enums\AuditLogAction::LeadReassigned : \App\Enums\AuditLogAction::LeadAssigned,
                module: \App\Enums\AuditLogModule::Lead,
                description: $summary,
                subject: $lead,
                oldValues: ['assigned_to' => $previousAssignee?->id, 'assignee_name' => $previousAssignee?->name],
                newValues: ['assigned_to' => $teamMember->id, 'assignee_name' => $teamMember->name],
                actor: $admin
            );

            return $lead;
        });
    }

    /**
     * Record a telecalling or CRM activity on the canonical timeline.
     */
    public function recordActivity(Lead $lead, User $actor, array $data): LeadActivity
    {
        return DB::transaction(function () use ($lead, $actor, $data) {
            // Authorization: Team member can only log activity on assigned lead; Admin can log on any lead
            if ($actor->isTeamMember() && $lead->assigned_to !== $actor->id) {
                throw new DomainException('Team members can only log activities for their assigned leads.');
            }

            if ($actor->isPartner()) {
                throw new DomainException('Partners cannot log internal sales activities.');
            }

            $activityType = LeadActivityType::from($data['activity_type']);
            $isInternal = (bool) ($data['is_internal'] ?? false);

            // Set default visibility based on activity type if not explicitly set
            if (! isset($data['is_internal'])) {
                $isInternal = in_array($activityType, [
                    LeadActivityType::Note,
                    LeadActivityType::AdminComment,
                ]);
            }

            $activity = LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'activity_type' => $activityType,
                'summary' => trim($data['summary']),
                'details' => ! empty($data['details']) ? trim($data['details']) : null,
                'is_internal' => $isInternal,
                'call_duration_seconds' => ! empty($data['call_duration_seconds']) ? (int) $data['call_duration_seconds'] : null,
                'customer_interest' => ! empty($data['customer_interest']) ? $data['customer_interest'] : null,
                'next_followup_date' => ! empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
                'created_at' => now(),
            ]);

            $leadUpdates = [];
            if (! empty($data['next_followup_date'])) {
                $leadUpdates['next_followup_date'] = $data['next_followup_date'];
            }
            if (! empty($data['customer_interest'])) {
                $leadUpdates['interest_level'] = $data['customer_interest'];
            }
            if (! empty($data['quotation_value'])) {
                $leadUpdates['quotation_value'] = (float) $data['quotation_value'];
            }
            if (! empty($leadUpdates)) {
                $lead->update($leadUpdates);
            }

            // Trigger SLA tracking for customer contact activity
            $lead->refresh();
            $this->slaService->onActivityLogged($lead, $activity, $actor);

            // Record meaningful activity for actor
            app(\App\Services\InactivityService::class)->recordActivity($actor, 'customer_interaction');

            return $activity;
        });
    }

    /**
     * Transition lead status with strict validation, timeline recording, and milestone handling.
     */
    public function updateStatus(Lead $lead, LeadStatus $newStatus, User $actor, ?string $reason = null, ?string $summary = null): Lead
    {
        return DB::transaction(function () use ($lead, $newStatus, $actor, $reason, $summary) {
            // Authorization
            if ($actor->isTeamMember() && $lead->assigned_to !== $actor->id) {
                throw new DomainException('Team members can only update status for their assigned leads.');
            }

            if ($actor->isPartner()) {
                throw new DomainException('Partners cannot manually modify lead workflow status.');
            }

            $oldStatus = $lead->status;

            if ($oldStatus === $newStatus) {
                return $lead;
            }

            // Validate status transitions
            $this->validateStatusTransition($oldStatus, $newStatus);

            $updates = ['status' => $newStatus];

            if ($newStatus === LeadStatus::Lost) {
                if (empty($reason)) {
                    throw new DomainException('A loss reason is mandatory when marking a lead as Lost.');
                }
                $updates['loss_reason'] = trim($reason);
            }

            if ($newStatus === LeadStatus::Rejected) {
                if (empty($reason)) {
                    throw new DomainException('A rejection reason is mandatory when rejecting a lead.');
                }
                $updates['rejection_reason'] = trim($reason);
            }

            $lead->update($updates);

            // Record status change in canonical timeline
            LeadActivity::create([
                'lead_id' => $lead->id,
                'user_id' => $actor->id,
                'activity_type' => LeadActivityType::StatusChange,
                'from_status' => $oldStatus->value,
                'to_status' => $newStatus->value,
                'summary' => $summary ?? "Status changed from {$oldStatus->label()} to {$newStatus->label()}",
                'details' => $reason,
                'is_internal' => false, // Status milestones are partner-visible
                'created_at' => now(),
            ]);

            // If final terminal state, mark SLA exempt
            if (in_array($newStatus, [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected], true)) {
                $this->slaService->onLeadClosed($lead);
            }

            // Record meaningful activity for actor
            app(\App\Services\InactivityService::class)->recordActivity($actor, 'lead_update');

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadStatusUpdated,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lead->lead_number} status updated from {$oldStatus->label()} to {$newStatus->label()} by {$actor->name}.",
                subject: $lead,
                oldValues: ['status' => $oldStatus->value],
                newValues: ['status' => $newStatus->value],
                metadata: ['reason' => $reason],
                actor: $actor
            );

            return $lead;
        });
    }

    /**
     * Validate allowable state transitions in the sales pipeline.
     */
    protected function validateStatusTransition(LeadStatus $from, LeadStatus $to): void
    {
        // Terminal states cannot be altered
        if ($from === LeadStatus::Won) {
            throw new DomainException('Won leads have reached deal completion and cannot be transitioned back.');
        }

        $allowed = match ($from) {
            LeadStatus::Submitted => [LeadStatus::Assigned, LeadStatus::Contacted, LeadStatus::Rejected, LeadStatus::Won],
            LeadStatus::Assigned => [LeadStatus::Contacted, LeadStatus::InDiscussion, LeadStatus::QuotationSent, LeadStatus::Negotiating, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected],
            LeadStatus::Contacted => [LeadStatus::InDiscussion, LeadStatus::QuotationSent, LeadStatus::Negotiating, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected],
            LeadStatus::InDiscussion => [LeadStatus::QuotationSent, LeadStatus::Negotiating, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected],
            LeadStatus::QuotationSent => [LeadStatus::Negotiating, LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected],
            LeadStatus::Negotiating => [LeadStatus::Won, LeadStatus::Lost, LeadStatus::Rejected],
            LeadStatus::Lost => [LeadStatus::Contacted, LeadStatus::InDiscussion], // Re-opening cold lead
            LeadStatus::Rejected => [], // Rejection is terminal
        };

        if (! in_array($to, $allowed, true)) {
            throw new DomainException("Illegal transition: cannot move lead from {$from->label()} to {$to->label()}.");
        }
    }

    /**
     * Admin duplicate conflict resolution.
     */
    public function resolveDuplicate(Lead $lead, User $admin, string $action, ?string $notes = null): Lead
    {
        return DB::transaction(function () use ($lead, $admin, $action, $notes) {
            if (! $admin->isAdmin()) {
                throw new DomainException('Only administrators can resolve duplicate lead conflicts.');
            }

            if ($action === 'dismiss') {
                $lead->is_duplicate_flag = false;
                $lead->save();

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $admin->id,
                    'activity_type' => LeadActivityType::AdminComment,
                    'summary' => 'Duplicate conflict dismissed by Admin',
                    'details' => $notes ?? 'Admin verified lead as genuine and distinct.',
                    'is_internal' => true,
                    'created_at' => now(),
                ]);
            } elseif ($action === 'reject') {
                $lead->status = LeadStatus::Rejected;
                $lead->rejection_reason = 'Rejected as duplicate of Lead #' . ($lead->duplicateMatchedLead ? $lead->duplicateMatchedLead->lead_number : 'original') . '. ' . ($notes ?? '');
                $lead->save();

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $admin->id,
                    'activity_type' => LeadActivityType::StatusChange,
                    'from_status' => $lead->getOriginal('status'),
                    'to_status' => LeadStatus::Rejected->value,
                    'summary' => 'Rejected as duplicate by Admin',
                    'details' => $notes ?? 'Duplicate lead marked rejected.',
                    'is_internal' => true,
                    'created_at' => now(),
                ]);
            } elseif ($action === 'transfer_attribution') {
                $originalPartner = $lead->duplicateMatchedLead?->partner;
                if ($originalPartner) {
                    $previousPartnerName = $lead->partner->company_name ?? 'Previous Partner';
                    $lead->partner_id = $originalPartner->id;
                    $lead->is_duplicate_flag = false;
                    $lead->save();

                    LeadActivity::create([
                        'lead_id' => $lead->id,
                        'user_id' => $admin->id,
                        'activity_type' => LeadActivityType::AdminComment,
                        'summary' => "Attribution transferred to original partner ({$originalPartner->company_name})",
                        'details' => "Reassigned attribution from {$previousPartnerName} to {$originalPartner->company_name} following 90-day protection policy.",
                        'is_internal' => true,
                        'created_at' => now(),
                    ]);
                }
            } elseif ($action === 'approve_as_new') {
                $lead->is_duplicate_flag = false;
                $lead->save();

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'user_id' => $admin->id,
                    'activity_type' => LeadActivityType::AdminComment,
                    'summary' => 'Lead approved as distinct new opportunity',
                    'details' => $notes ?? 'Admin granted attribution exception to the current partner.',
                    'is_internal' => true,
                    'created_at' => now(),
                ]);
            } else {
                throw new InvalidArgumentException("Unknown duplicate resolution action: {$action}");
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadDuplicateResolved,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Duplicate flag for Lead #{$lead->lead_number} resolved with action '{$action}' by Admin {$admin->name}.",
                subject: $lead,
                metadata: ['action' => $action, 'notes' => $notes],
                actor: $admin
            );

            return $lead;
        });
    }

    /**
     * Reassign lead ownership to another partner (Admin only).
     */
    public function reassignOwnership(Lead $lead, Partner $newPartner, string $reason, User $admin): Lead
    {
        return DB::transaction(function () use ($lead, $newPartner, $reason, $admin) {
            if (! $admin->isAdmin()) {
                throw new DomainException('Only administrators can reassign lead ownership.');
            }

            $reason = trim($reason);
            if (empty($reason)) {
                throw new InvalidArgumentException('A mandatory reason is required to reassign lead ownership.');
            }

            // Lock row to prevent concurrent reassignment race condition
            $lockedLead = Lead::where('id', $lead->id)->lockForUpdate()->firstOrFail();

            if ($lockedLead->partner_id === $newPartner->id) {
                throw new DomainException("Lead is already assigned to {$newPartner->company_name}.");
            }

            $previousPartnerId = $lockedLead->partner_id;
            $previousPartner = $lockedLead->partner;
            $fromStatus = $lockedLead->ownership_status ?? LeadOwnershipStatus::Protected;
            $validUntilBefore = $lockedLead->ownership_expires_at;

            $validityDays = (int) SystemSetting::get('lead_ownership_validity_days', '90');
            $newValidUntil = now()->addDays($validityDays);

            $lockedLead->partner_id = $newPartner->id;
            $lockedLead->ownership_status = LeadOwnershipStatus::Reassigned;
            $lockedLead->ownership_change_reason = $reason;
            $lockedLead->ownership_expires_at = $newValidUntil;
            $lockedLead->save();

            // Permanent audit log
            LeadOwnershipHistory::create([
                'lead_id'             => $lockedLead->id,
                'previous_partner_id' => $previousPartnerId,
                'new_partner_id'      => $newPartner->id,
                'from_status'         => $fromStatus,
                'to_status'           => LeadOwnershipStatus::Reassigned,
                'action'              => 'reassigned',
                'reason'              => $reason,
                'valid_until_before'  => $validUntilBefore,
                'valid_until_after'   => $newValidUntil,
                'performed_by'        => $admin->id,
            ]);

            // Timeline activity
            $prevName = $previousPartner ? $previousPartner->company_name : 'Unassigned';
            LeadActivity::create([
                'lead_id'       => $lockedLead->id,
                'user_id'       => $admin->id,
                'activity_type' => LeadActivityType::OwnershipReassigned,
                'summary'       => "Ownership Reassigned: Transferred from {$prevName} to {$newPartner->company_name}",
                'details'       => "Reason: {$reason}. Valid until: " . $newValidUntil->format('d M Y'),
                'is_internal'   => true,
                'created_at'    => now(),
            ]);

            // Notify new partner
            Notification::create([
                'user_id'    => $newPartner->user_id,
                'title'      => 'Lead Ownership Assigned',
                'message'    => "Lead #{$lockedLead->lead_number} has been assigned to your account by Admin.",
                'type'       => 'success',
                'link'       => route('partner.leads.show', $lockedLead),
                'created_at' => now(),
            ]);

            // Notify previous partner if one existed
            if ($previousPartner && $previousPartner->user_id) {
                Notification::create([
                    'user_id'    => $previousPartner->user_id,
                    'title'      => 'Lead Ownership Reassigned',
                    'message'    => "Ownership of Lead #{$lockedLead->lead_number} was reassigned by Admin.",
                    'type'       => 'warning',
                    'link'       => route('partner.leads.index'),
                    'created_at' => now(),
                ]);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadOwnershipChanged,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lockedLead->lead_number} ownership reassigned from {$prevName} to {$newPartner->company_name} by Admin {$admin->name}.",
                subject: $lockedLead,
                oldValues: ['partner_id' => $previousPartnerId, 'partner_name' => $prevName],
                newValues: ['partner_id' => $newPartner->id, 'partner_name' => $newPartner->company_name],
                metadata: ['reason' => $reason, 'valid_until' => $newValidUntil->toIso8601String()],
                actor: $admin
            );

            return $lockedLead;
        });
    }

    /**
     * Extend lead ownership validity (Admin only).
     */
    public function extendOwnership(Lead $lead, int $days, string $reason, User $admin): Lead
    {
        return DB::transaction(function () use ($lead, $days, $reason, $admin) {
            if (! $admin->isAdmin()) {
                throw new DomainException('Only administrators can extend lead ownership.');
            }

            if ($days <= 0) {
                throw new InvalidArgumentException('Extension days must be greater than zero.');
            }

            $reason = trim($reason);
            if (empty($reason)) {
                throw new InvalidArgumentException('A mandatory reason is required to extend lead ownership.');
            }

            $lockedLead = Lead::where('id', $lead->id)->lockForUpdate()->firstOrFail();

            $baseDate = ($lockedLead->ownership_expires_at && $lockedLead->ownership_expires_at->isFuture())
                ? $lockedLead->ownership_expires_at
                : now();

            $validUntilBefore = $lockedLead->ownership_expires_at;
            $newValidUntil = $baseDate->copy()->addDays($days);

            $fromStatus = $lockedLead->ownership_status ?? LeadOwnershipStatus::Protected;
            $toStatus = ($fromStatus === LeadOwnershipStatus::Expired) ? LeadOwnershipStatus::Protected : $fromStatus;

            $lockedLead->ownership_expires_at = $newValidUntil;
            $lockedLead->ownership_status = $toStatus;
            $lockedLead->save();

            LeadOwnershipHistory::create([
                'lead_id'             => $lockedLead->id,
                'previous_partner_id' => $lockedLead->partner_id,
                'new_partner_id'      => $lockedLead->partner_id,
                'from_status'         => $fromStatus,
                'to_status'           => $toStatus,
                'action'              => 'extended',
                'reason'              => $reason,
                'valid_until_before'  => $validUntilBefore,
                'valid_until_after'   => $newValidUntil,
                'performed_by'        => $admin->id,
            ]);

            LeadActivity::create([
                'lead_id'       => $lockedLead->id,
                'user_id'       => $admin->id,
                'activity_type' => LeadActivityType::OwnershipExtended,
                'summary'       => "Ownership Extended by {$days} days",
                'details'       => "Reason: {$reason}. New expiry: " . $newValidUntil->format('d M Y'),
                'is_internal'   => true,
                'created_at'    => now(),
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadOwnershipExtended,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lockedLead->lead_number} ownership extended by {$days} days by Admin {$admin->name}.",
                subject: $lockedLead,
                oldValues: ['valid_until' => $validUntilBefore?->toIso8601String()],
                newValues: ['valid_until' => $newValidUntil->toIso8601String()],
                metadata: ['days' => $days, 'reason' => $reason],
                actor: $admin
            );

            return $lockedLead;
        });
    }

    /**
     * Release lead ownership into unassigned pool (Admin only).
     */
    public function releaseOwnership(Lead $lead, string $reason, User $admin): Lead
    {
        return DB::transaction(function () use ($lead, $reason, $admin) {
            if (! $admin->isAdmin()) {
                throw new DomainException('Only administrators can release lead ownership.');
            }

            $reason = trim($reason);
            if (empty($reason)) {
                throw new InvalidArgumentException('A mandatory reason is required to release lead ownership.');
            }

            $lockedLead = Lead::where('id', $lead->id)->lockForUpdate()->firstOrFail();

            $previousPartnerId = $lockedLead->partner_id;
            $fromStatus = $lockedLead->ownership_status ?? LeadOwnershipStatus::Protected;
            $validUntilBefore = $lockedLead->ownership_expires_at;

            $lockedLead->ownership_status = LeadOwnershipStatus::Released;
            $lockedLead->ownership_change_reason = $reason;
            $lockedLead->save();

            LeadOwnershipHistory::create([
                'lead_id'             => $lockedLead->id,
                'previous_partner_id' => $previousPartnerId,
                'new_partner_id'      => $previousPartnerId,
                'from_status'         => $fromStatus,
                'to_status'           => LeadOwnershipStatus::Released,
                'action'              => 'released',
                'reason'              => $reason,
                'valid_until_before'  => $validUntilBefore,
                'valid_until_after'   => null,
                'performed_by'        => $admin->id,
            ]);

            LeadActivity::create([
                'lead_id'       => $lockedLead->id,
                'user_id'       => $admin->id,
                'activity_type' => LeadActivityType::OwnershipReleased,
                'summary'       => 'Lead Ownership Released by Admin',
                'details'       => "Reason: {$reason}",
                'is_internal'   => true,
                'created_at'    => now(),
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadOwnershipReleased,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Lead #{$lockedLead->lead_number} ownership released into pool by Admin {$admin->name}.",
                subject: $lockedLead,
                oldValues: ['ownership_status' => $fromStatus->value],
                newValues: ['ownership_status' => LeadOwnershipStatus::Released->value],
                metadata: ['reason' => $reason],
                actor: $admin
            );

            return $lockedLead;
        });
    }
}
