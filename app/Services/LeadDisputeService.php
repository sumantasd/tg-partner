<?php

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadDisputeCategory;
use App\Enums\LeadDisputeStatus;
use App\Enums\LeadOwnershipStatus;
use App\Enums\UserRole;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadDispute;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\User;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

class LeadDisputeService
{
    public function __construct(
        protected LeadService $leadService
    ) {}

    /**
     * Generate sequential unique dispute number (e.g., DSP-2026-0001).
     */
    public function generateDisputeNumber(): string
    {
        $year = date('Y');
        $prefix = "DSP-{$year}-";

        $latest = LeadDispute::withTrashed()
            ->where('dispute_number', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(dispute_number, 10) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        if ($latest && preg_match('/^DSP-\d{4}-(\d+)$/', $latest->dispute_number, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Create a new Lead Ownership Dispute raised by a partner.
     */
    public function createDispute(Partner $partner, array $data, ?UploadedFile $evidence = null): LeadDispute
    {
        return DB::transaction(function () use ($partner, $data, $evidence) {
            $disputeNumber = $this->generateDisputeNumber();
            $leadId = !empty($data['lead_id']) ? (int) $data['lead_id'] : null;
            $lead = null;

            if ($leadId !== null) {
                $lead = Lead::find($leadId);
            }

            // If lead not explicitly passed but phone/email provided, attempt to locate matching active lead
            if ($lead === null && (!empty($data['customer_phone_claimed']) || !empty($data['customer_email_claimed']))) {
                $phone = !empty($data['customer_phone_claimed']) ? $this->leadService->normalizePhone($data['customer_phone_claimed']) : null;
                $email = !empty($data['customer_email_claimed']) ? $this->leadService->normalizeEmail($data['customer_email_claimed']) : null;

                $query = Lead::query();
                if ($phone) {
                    $query->where('customer_phone', 'like', "%{$phone}%");
                }
                if ($email) {
                    $query->orWhere('customer_email', $email);
                }
                $matchedLead = $query->latest()->first();
                if ($matchedLead) {
                    $lead = $matchedLead;
                    $leadId = $matchedLead->id;
                }
            }

            // Handle secure evidence upload in private storage
            $evidencePath = null;
            $evidenceFilename = null;
            if ($evidence instanceof UploadedFile) {
                $evidencePath = $evidence->store('disputes', 'local');
                $evidenceFilename = $evidence->getClientOriginalName();
            }

            $category = $data['dispute_category'] instanceof LeadDisputeCategory
                ? $data['dispute_category']
                : LeadDisputeCategory::from($data['dispute_category']);

            $dispute = LeadDispute::create([
                'dispute_number'         => $disputeNumber,
                'lead_id'                => $leadId,
                'partner_id'             => $partner->id,
                'dispute_category'       => $category,
                'status'                 => LeadDisputeStatus::Pending,
                'customer_phone_claimed' => !empty($data['customer_phone_claimed']) ? trim($data['customer_phone_claimed']) : null,
                'customer_email_claimed' => !empty($data['customer_email_claimed']) ? $this->leadService->normalizeEmail($data['customer_email_claimed']) : null,
                'explanation'            => trim($data['explanation']),
                'evidence_path'          => $evidencePath,
                'evidence_filename'      => $evidenceFilename,
            ]);

            // If lead is identified, mark ownership status as Disputed and log timeline activity
            if ($lead) {
                $lead->ownership_status = LeadOwnershipStatus::Disputed;
                $lead->save();

                LeadActivity::create([
                    'lead_id'       => $lead->id,
                    'user_id'       => $partner->user_id,
                    'activity_type' => LeadActivityType::DisputeRaised,
                    'summary'       => "Ownership Dispute Raised (#{$dispute->dispute_number})",
                    'details'       => "Partner {$partner->company_name} submitted dispute ({$category->label()}). Reason: " . trim($data['explanation']),
                    'is_internal'   => true,
                    'created_at'    => now(),
                ]);
            }

            // Notify Admin users about the new dispute
            $admins = User::where('role', UserRole::Admin)->get();
            foreach ($admins as $admin) {
                Notification::create([
                    'user_id'    => $admin->id,
                    'title'      => 'New Lead Ownership Dispute',
                    'message'    => "Partner {$partner->company_name} raised dispute #{$dispute->dispute_number}.",
                    'type'       => 'warning',
                    'link'       => route('admin.lead-disputes.show', $dispute),
                    'created_at' => now(),
                ]);
            }

            // Record meaningful activity for partner
            if ($partner->user) {
                app(\App\Services\InactivityService::class)->recordActivity($partner->user, 'dispute_raised');
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadDisputeFiled,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Ownership dispute #{$dispute->dispute_number} raised by Partner {$partner->company_name}.",
                subject: $dispute,
                metadata: ['lead_id' => $leadId, 'category' => $category->value],
                actor: $partner->user
            );

            return $dispute;
        });
    }

    /**
     * Update dispute status (e.g. Under Review).
     */
    public function updateStatus(LeadDispute $dispute, LeadDisputeStatus $status, User $admin): LeadDispute
    {
        if (!$admin->isAdmin()) {
            throw new DomainException('Only an authorized Admin can update dispute status.');
        }

        $dispute->status = $status;
        $dispute->save();

        if ($dispute->lead && $status === LeadDisputeStatus::UnderReview) {
            $dispute->lead->ownership_status = LeadOwnershipStatus::UnderReview;
            $dispute->lead->save();
        }

        return $dispute;
    }

    /**
     * Add internal administrative review notes to the dispute.
     */
    public function addAdminNotes(LeadDispute $dispute, string $notes, User $admin): LeadDispute
    {
        if (!$admin->isAdmin()) {
            throw new DomainException('Only an authorized Admin can add internal dispute notes.');
        }

        $existing = $dispute->admin_notes ? $dispute->admin_notes . "\n\n" : '';
        $timestamp = now()->timezone('Asia/Kolkata')->format('d M Y, h:i A');
        $dispute->admin_notes = $existing . "[{$timestamp} - {$admin->name}]: " . trim($notes);
        $dispute->save();

        return $dispute;
    }

    /**
     * Resolve a lead dispute with mandatory reason and optional ownership reassignment.
     */
    public function resolveDispute(LeadDispute $dispute, array $data, User $admin): LeadDispute
    {
        return DB::transaction(function () use ($dispute, $data, $admin) {
            if (!$admin->isAdmin()) {
                throw new DomainException('Only an authorized Admin can resolve lead disputes.');
            }

            $reason = trim($data['resolution_reason'] ?? '');
            if (empty($reason)) {
                throw new InvalidArgumentException('A mandatory resolution reason is required to resolve this dispute.');
            }

            $reassignToClaimant = !empty($data['reassign_to_claimant']);

            if ($reassignToClaimant) {
                if (!$dispute->lead) {
                    throw new DomainException('Cannot reassign ownership: No lead is linked to this dispute.');
                }

                // Reassign lead to the claimant partner
                $this->leadService->reassignOwnership(
                    $dispute->lead,
                    $dispute->partner,
                    "Dispute #{$dispute->dispute_number} resolved in claimant's favor: {$reason}",
                    $admin
                );
            } else {
                // Keep existing ownership and return lead status to protected
                if ($dispute->lead) {
                    $dispute->lead->ownership_status = LeadOwnershipStatus::Protected;
                    $dispute->lead->save();
                }
            }

            $dispute->update([
                'status'            => LeadDisputeStatus::Resolved,
                'resolution_reason' => $reason,
                'resolved_by'       => $admin->id,
                'resolved_at'       => now(),
            ]);

            if ($dispute->lead) {
                LeadActivity::create([
                    'lead_id'       => $dispute->lead->id,
                    'user_id'       => $admin->id,
                    'activity_type' => LeadActivityType::DisputeResolved,
                    'summary'       => "Ownership Dispute #{$dispute->dispute_number} Resolved",
                    'details'       => ($reassignToClaimant ? "Resolved with lead reassigned to claimant partner {$dispute->partner->company_name}. " : "Resolved with original attribution maintained. ") . "Decision: {$reason}",
                    'is_internal'   => true,
                    'created_at'    => now(),
                ]);
            }

            // Notify claimant partner
            Notification::create([
                'user_id'    => $dispute->partner->user_id,
                'title'      => 'Dispute Resolved',
                'message'    => "Your ownership dispute #{$dispute->dispute_number} has been resolved by Admin.",
                'type'       => 'success',
                'link'       => route('partner.disputes.show', $dispute),
                'created_at' => now(),
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadDisputeResolved,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Ownership dispute #{$dispute->dispute_number} resolved by Admin {$admin->name}. Reason: {$reason}",
                subject: $dispute,
                metadata: ['reason' => $reason, 'reassign_to_claimant' => $reassignToClaimant],
                actor: $admin
            );

            return $dispute;
        });
    }

    /**
     * Reject a lead dispute with mandatory reason.
     */
    public function rejectDispute(LeadDispute $dispute, string $reason, User $admin): LeadDispute
    {
        return DB::transaction(function () use ($dispute, $reason, $admin) {
            if (!$admin->isAdmin()) {
                throw new DomainException('Only an authorized Admin can reject lead disputes.');
            }

            $reason = trim($reason);
            if (empty($reason)) {
                throw new InvalidArgumentException('A mandatory rejection reason is required.');
            }

            $dispute->update([
                'status'            => LeadDisputeStatus::Rejected,
                'resolution_reason' => $reason,
                'resolved_by'       => $admin->id,
                'resolved_at'       => now(),
            ]);

            if ($dispute->lead) {
                // If lead was marked disputed/under_review, return to protected
                if (in_array($dispute->lead->ownership_status, [LeadOwnershipStatus::Disputed, LeadOwnershipStatus::UnderReview])) {
                    $dispute->lead->ownership_status = LeadOwnershipStatus::Protected;
                    $dispute->lead->save();
                }

                LeadActivity::create([
                    'lead_id'       => $dispute->lead->id,
                    'user_id'       => $admin->id,
                    'activity_type' => LeadActivityType::DisputeRejected,
                    'summary'       => "Ownership Dispute #{$dispute->dispute_number} Rejected",
                    'details'       => "Claim rejected by {$admin->name}. Reason: {$reason}",
                    'is_internal'   => true,
                    'created_at'    => now(),
                ]);
            }

            // Notify claimant partner
            Notification::create([
                'user_id'    => $dispute->partner->user_id,
                'title'      => 'Dispute Rejected',
                'message'    => "Your ownership dispute #{$dispute->dispute_number} has been reviewed and rejected.",
                'type'       => 'danger',
                'link'       => route('partner.disputes.show', $dispute),
                'created_at' => now(),
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadDisputeRejected,
                module: \App\Enums\AuditLogModule::Lead,
                description: "Ownership dispute #{$dispute->dispute_number} rejected by Admin {$admin->name}. Reason: {$reason}",
                subject: $dispute,
                metadata: ['reason' => $reason],
                actor: $admin
            );

            return $dispute;
        });
    }
}
