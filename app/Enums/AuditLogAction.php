<?php

namespace App\Enums;

enum AuditLogAction: string
{
    // Authentication
    case LoginSuccess = 'login_success';
    case LoginFailed = 'login_failed';
    case Logout = 'logout';
    case PasswordChanged = 'password_changed';
    case AccountSuspended = 'account_suspended';
    case AccountReactivated = 'account_reactivated';

    // Partner Management
    case PartnerCreated = 'partner_created';
    case PartnerApproved = 'partner_approved';
    case PartnerRejected = 'partner_rejected';
    case PartnerUpdated = 'partner_updated';
    case PartnerStatusChanged = 'partner_status_changed';
    case KycSubmitted = 'kyc_submitted';
    case KycReviewStarted = 'kyc_review_started';
    case KycApproved = 'kyc_approved';
    case KycRejected = 'kyc_rejected';
    case KycCorrectionRequested = 'kyc_correction_requested';
    case BankVerified = 'bank_verified';
    case BankRejected = 'bank_rejected';

    // Lead Management
    case LeadCreated = 'lead_created';
    case LeadAssigned = 'lead_assigned';
    case LeadReassigned = 'lead_reassigned';
    case LeadStatusUpdated = 'lead_status_updated';
    case LeadOwnershipChanged = 'lead_ownership_changed';
    case LeadOwnershipExtended = 'lead_ownership_extended';
    case LeadOwnershipReleased = 'lead_ownership_released';
    case LeadDisputeFiled = 'lead_dispute_filed';
    case LeadDisputeResolved = 'lead_dispute_resolved';
    case LeadDisputeRejected = 'lead_dispute_rejected';
    case LeadDuplicateResolved = 'lead_duplicate_resolved';

    // Team Management
    case TeamMemberCreated = 'team_member_created';
    case TeamMemberUpdated = 'team_member_updated';
    case TeamMemberStatusToggled = 'team_member_status_toggled';
    case TeamMemberPasswordReset = 'team_member_password_reset';

    // Customer & Project
    case CustomerCreated = 'customer_created';
    case CustomerUpdated = 'customer_updated';
    case LeadConvertedToCustomer = 'lead_converted_to_customer';
    case ProjectCreated = 'project_created';
    case ProjectUpdated = 'project_updated';
    case ProjectStatusChanged = 'project_status_changed';

    // Financial
    case CustomerPaymentRecorded = 'customer_payment_recorded';
    case CustomerPaymentVerified = 'customer_payment_verified';
    case CustomerPaymentRejected = 'customer_payment_rejected';
    case CustomerPaymentReversed = 'customer_payment_reversed';
    case PaymentScheduleGenerated = 'payment_schedule_generated';
    case PaymentScheduleUpdated = 'payment_schedule_updated';
    case PaymentSchedulePaused = 'payment_schedule_paused';
    case PaymentScheduleResumed = 'payment_schedule_resumed';
    case PaymentScheduleCancelled = 'payment_schedule_cancelled';
    case CommissionGenerated = 'commission_generated';
    case CommissionAdjusted = 'commission_adjusted';
    case WithdrawalRequested = 'withdrawal_requested';
    case WithdrawalCancelled = 'withdrawal_cancelled';
    case WithdrawalApproved = 'withdrawal_approved';
    case WithdrawalProcessed = 'withdrawal_processed';
    case WithdrawalRejected = 'withdrawal_rejected';
    case PayoutCompleted = 'payout_completed';

    // Agreement
    case AgreementGenerated = 'agreement_generated';
    case AgreementIssued = 'agreement_issued';
    case AgreementAccepted = 'agreement_accepted';
    case AgreementRevoked = 'agreement_revoked';
    case AgreementReissued = 'agreement_reissued';
    case AgreementTemplateCreated = 'agreement_template_created';
    case AgreementTemplateUpdated = 'agreement_template_updated';
    case AgreementTemplateActivated = 'agreement_template_activated';

    // Inactivity
    case InactivityWarningSent = 'inactivity_warning_sent';
    case InactivityReviewFlagged = 'inactivity_review_flagged';
    case InactivityReviewUnflagged = 'inactivity_review_unflagged';
    case InactivitySuspended = 'inactivity_suspended';
    case InactivityReactivated = 'inactivity_reactivated';
    case InactivitySettingsUpdated = 'inactivity_settings_updated';

    // Settings
    case CommissionSettingsUpdated = 'commission_settings_updated';
    case CommissionPolicyCreated = 'commission_policy_created';
    case CommissionPolicyRevised = 'commission_policy_revised';
    case CommissionPolicyToggled = 'commission_policy_toggled';
    case CommissionPolicyUpdated = 'commission_policy_updated';
    case LeadSlaSettingsUpdated = 'lead_sla_settings_updated';

    // System Backup & Cleanup
    case BackupCreated = 'backup_created';
    case BackupVerified = 'backup_verified';
    case BackupDeleted = 'backup_deleted';
    case BackupDownloaded = 'backup_downloaded';
    case RestoreInitiated = 'restore_initiated';
    case RestoreCompleted = 'restore_completed';
    case RestoreFailed = 'restore_failed';
    case CleanupPreviewed = 'cleanup_previewed';
    case CleanupExecuted = 'cleanup_executed';
    case CleanupFailed = 'cleanup_failed';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }

    /**
     * Resolve a readable label for an action (AuditLogAction enum, string, or legacy value) safely.
     */
    public static function labelFor(mixed $action): string
    {
        if ($action instanceof self) {
            return $action->label();
        }

        if (is_string($action)) {
            $enum = self::tryFrom($action);
            if ($enum) {
                return $enum->label();
            }

            return ucwords(str_replace(['_', '-'], ' ', $action));
        }

        return 'Unknown Action';
    }
}
