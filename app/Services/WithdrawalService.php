<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\PayoutStatus;
use App\Enums\UserRole;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Notification;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\User;
use App\Models\WithdrawalRequest;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WithdrawalService
{
    /**
     * Minimum allowable withdrawal in INR.
     */
    public const MINIMUM_WITHDRAWAL_AMOUNT = 500.00;

    /**
     * Compute authoritative partner balance summary in INR based on actual DB records.
     *
     * @return array{
     *     total_earned: float,
     *     total_paid: float,
     *     pending_commission: float,
     *     available_balance: float,
     *     reserved_in_pending: float,
     *     total_withdrawn: float
     * }
     */
    public function getPartnerBalanceSummary(Partner $partner): array
    {
        // 1. Total Earned: all non-cancelled commissions
        $totalEarned = (float) Commission::where('partner_id', $partner->id)
            ->where('status', '!=', CommissionStatus::Cancelled)
            ->sum('amount');

        // 2. Total Paid: commissions in Paid status
        $totalPaid = (float) Commission::where('partner_id', $partner->id)
            ->where('status', CommissionStatus::Paid)
            ->sum('amount');

        // 3. Pending Commission: unverified client payments / pending review
        $pendingCommission = (float) Commission::where('partner_id', $partner->id)
            ->where('status', CommissionStatus::Pending)
            ->sum('amount');

        // 4. Approved Commission: all approved/payable commissions
        $approvedCommission = (float) Commission::where('partner_id', $partner->id)
            ->whereIn('status', [CommissionStatus::Payable, CommissionStatus::Approved])
            ->sum('amount');

        // 5. Available Withdrawal Balance: Payable/Approved commissions NOT currently linked to a withdrawal or payout
        $availableBalance = (float) Commission::where('partner_id', $partner->id)
            ->whereIn('status', [CommissionStatus::Payable, CommissionStatus::Approved])
            ->whereNull('withdrawal_request_id')
            ->whereNull('payout_id')
            ->sum('amount');

        // 6. Amount Reserved in Pending / Active Withdrawals
        $reservedInPending = (float) WithdrawalRequest::where('partner_id', $partner->id)
            ->whereIn('status', [
                WithdrawalStatus::Pending,
                WithdrawalStatus::Approved,
                WithdrawalStatus::Processing,
            ])
            ->sum('amount');

        // 7. Total Withdrawn: completed payouts amount
        $totalWithdrawn = (float) Payout::where('partner_id', $partner->id)
            ->where('payment_status', PayoutStatus::Completed)
            ->sum('amount');

        // If no payout records yet, fallback to total commissions marked Paid
        if ($totalWithdrawn <= 0 && $totalPaid > 0) {
            $totalWithdrawn = $totalPaid;
        }

        // 8. Recovery adjustments sum (recovery debit entries)
        $recoveryAdjustments = (float) Commission::where('partner_id', $partner->id)
            ->where('adjustment_type', 'recovery')
            ->sum('amount');

        $finalWithdrawable = max(0.00, $availableBalance);

        return [
            'total_earned'               => round($totalEarned, 2),
            'total_paid'                 => round($totalPaid, 2),
            'paid_commission'            => round($totalPaid, 2),
            'approved_commission'        => round($approvedCommission, 2),
            'pending_commission'         => round($pendingCommission, 2),
            'available_balance'          => round($availableBalance, 2),
            'available_commission'       => round($availableBalance, 2),
            'reserved_in_pending'        => round($reservedInPending, 2),
            'pending_withdrawal'         => round($reservedInPending, 2),
            'total_withdrawn'            => round($totalWithdrawn, 2),
            'recovery_adjustments'       => round($recoveryAdjustments, 2),
            'final_withdrawable_balance' => round($finalWithdrawable, 2),
            'is_kyc_approved'            => $partner->isKycApproved(),
            'is_bank_verified'           => $partner->isBankVerified(),
            'is_fully_verified'          => $partner->isFullyKycVerified(),
        ];
    }

    /**
     * Retrieve all commissions eligible for immediate withdrawal reservation.
     *
     * @return Collection<int, Commission>
     */
    public function getEligibleCommissions(Partner $partner): Collection
    {
        return Commission::where('partner_id', $partner->id)
            ->whereIn('status', [CommissionStatus::Payable, CommissionStatus::Approved])
            ->whereNull('withdrawal_request_id')
            ->whereNull('payout_id')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Partner requests commission withdrawal.
     * Atomic, concurrency-safe with pessimistic row locking.
     *
     * @param Partner $partner
     * @param float $amount
     * @param string $payoutMethod 'bank_transfer' or 'upi'
     * @param string|null $partnerNotes
     * @param array<int>|null $selectedCommissionIds Optional specific commissions to withdraw
     * @return WithdrawalRequest
     */
    public function requestWithdrawal(
        Partner $partner,
        float $amount,
        string $payoutMethod = 'bank_transfer',
        ?string $partnerNotes = null,
        ?array $selectedCommissionIds = null
    ): WithdrawalRequest {
        $amount = round($amount, 2);

        if ($amount < self::MINIMUM_WITHDRAWAL_AMOUNT) {
            throw new InvalidArgumentException(
                'The minimum withdrawal amount is ₹' . number_format(self::MINIMUM_WITHDRAWAL_AMOUNT, 2) . '.'
            );
        }

        if (! in_array($payoutMethod, ['bank_transfer', 'upi'], true)) {
            throw new InvalidArgumentException("Unsupported payout method '{$payoutMethod}'. Choose 'bank_transfer' or 'upi'.");
        }

        // Financial Compliance Gate: KYC & Bank Verification
        if (! $partner->isKycApproved()) {
            throw new DomainException('Your KYC verification is pending. Please wait for Admin approval before requesting a withdrawal.');
        }

        if (! $partner->isBankVerified()) {
            throw new DomainException('Your bank account verification is pending. Withdrawal requests will be enabled after verification.');
        }

        // Validate payout details configuration
        if (! $partner->hasValidPayoutDetails($payoutMethod)) {
            $methodLabel = $payoutMethod === 'upi' ? 'UPI details' : 'bank account details';
            throw new DomainException("Please configure your complete {$methodLabel} before requesting a withdrawal.");
        }

        return DB::transaction(function () use (
            $partner,
            $amount,
            $payoutMethod,
            $partnerNotes,
            $selectedCommissionIds
        ) {
            // 1. Lock all eligible commissions for this partner to prevent concurrent double-spend
            $eligibleCommissions = Commission::where('partner_id', $partner->id)
                ->whereIn('status', [CommissionStatus::Payable, CommissionStatus::Approved])
                ->whereNull('withdrawal_request_id')
                ->whereNull('payout_id')
                ->lockForUpdate()
                ->orderBy('id', 'asc')
                ->get();

            $availableBalance = round($eligibleCommissions->sum(fn ($c) => (float) $c->amount), 2);

            if ($amount > $availableBalance) {
                throw new DomainException(
                    "Requested amount of ₹" . number_format($amount, 2) .
                    " exceeds your available withdrawal balance of ₹" . number_format($availableBalance, 2) . "."
                );
            }

            // 2. Determine which commissions to reserve
            $commissionsToReserve = new Collection();

            if (! empty($selectedCommissionIds)) {
                $uniqueIds = array_unique($selectedCommissionIds);
                $commissionsToReserve = $eligibleCommissions->whereIn('id', $uniqueIds);

                if ($commissionsToReserve->count() !== count($uniqueIds)) {
                    throw new DomainException('One or more selected commissions are no longer available for withdrawal.');
                }

                $selectedSum = round($commissionsToReserve->sum(fn ($c) => (float) $c->amount), 2);
                if (abs($selectedSum - $amount) > 0.01) {
                    throw new DomainException(
                        "The sum of selected commissions (₹" . number_format($selectedSum, 2) .
                        ") does not match the requested amount (₹" . number_format($amount, 2) . ")."
                    );
                }
            } else {
                // FIFO allocation: pick oldest commissions until requested amount is covered
                $runningTotal = 0.0;
                foreach ($eligibleCommissions as $comm) {
                    $commissionsToReserve->push($comm);
                    $runningTotal += (float) $comm->amount;
                    if ($runningTotal >= $amount - 0.001) {
                        break;
                    }
                }

                if ($runningTotal < $amount - 0.001) {
                    throw new DomainException('Insufficient eligible commissions to cover the requested amount.');
                }
            }

            // 3. Freeze immutable recipient snapshot
            $recipientSnapshot = [
                'payout_method'     => $payoutMethod,
                'account_holder'    => $partner->bank_account_name,
                'bank_name'         => $partner->bank_name,
                'masked_account'    => $partner->masked_account_number,
                'account_number'    => $partner->bank_account_number,
                'ifsc_code'         => $partner->ifsc_code,
                'upi_id'            => $partner->upi_id,
                'partner_code'      => $partner->partner_code,
                'company_name'      => $partner->company_name,
            ];

            // 4. Generate unique request number
            $requestNumber = 'WTH-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // 5. Create WithdrawalRequest
            $withdrawal = WithdrawalRequest::create([
                'request_number'     => $requestNumber,
                'partner_id'         => $partner->id,
                'amount'             => $amount,
                'currency'           => 'INR',
                'payout_method'      => $payoutMethod,
                'partner_notes'      => $partnerNotes,
                'recipient_snapshot' => $recipientSnapshot,
                'status'             => WithdrawalStatus::Pending,
                'admin_notes'        => null,
                'rejection_reason'   => null,
                'reviewed_by'        => null,
                'reviewed_at'        => null,
                'settled_at'         => null,
            ]);

            // 6. Atomically reserve commissions
            Commission::whereIn('id', $commissionsToReserve->pluck('id'))->update([
                'withdrawal_request_id' => $withdrawal->id,
                'status'                => CommissionStatus::InWithdrawal,
            ]);

            // 7. Dispatch notifications
            Notification::send(
                $partner->user_id,
                'Withdrawal Request Submitted',
                "Your withdrawal request #{$withdrawal->request_number} for ₹" . number_format($amount, 2) . " has been submitted for Admin review.",
                'info',
                route('partner.withdrawals.show', $withdrawal)
            );

            // Notify all admins
            $admins = User::where('role', UserRole::Admin)->get();
            foreach ($admins as $admin) {
                Notification::send(
                    $admin->id,
                    'New Partner Withdrawal Request',
                    "Partner {$partner->company_name} ({$partner->partner_code}) requested withdrawal of ₹" . number_format($amount, 2) . ".",
                    'info',
                    route('admin.payouts.show', $withdrawal)
                );
            }

            Log::info('Withdrawal request created', [
                'request_id'     => $withdrawal->id,
                'request_number' => $withdrawal->request_number,
                'partner_id'     => $partner->id,
                'amount'         => $amount,
                'payout_method'  => $payoutMethod,
            ]);

            // Record meaningful activity for partner
            if ($partner->user) {
                app(\App\Services\InactivityService::class)->recordActivity($partner->user, 'withdrawal_requested');
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::WithdrawalRequested,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Withdrawal request #{$withdrawal->request_number} for ₹" . number_format($amount, 2) . " submitted by Partner {$partner->company_name}.",
                subject: $withdrawal,
                newValues: ['amount' => $amount, 'payout_method' => $payoutMethod],
                actor: $partner->user
            );

            return $withdrawal;
        });
    }

    /**
     * Partner cancels their own Pending withdrawal request.
     * Atomically releases all reserved commissions back to Payable status.
     */
    public function cancelWithdrawal(
        WithdrawalRequest $withdrawal,
        Partner $partner,
        ?string $reason = null
    ): WithdrawalRequest {
        if ($withdrawal->partner_id !== $partner->id) {
            throw new DomainException('Unauthorized. You can only cancel your own withdrawal requests.');
        }

        if (! $withdrawal->canBeCancelledByPartner()) {
            throw new DomainException(
                "Only pending withdrawal requests can be cancelled. Current status: {$withdrawal->status->label()}."
            );
        }

        return DB::transaction(function () use ($withdrawal, $partner, $reason) {
            $lockedWithdrawal = WithdrawalRequest::where('id', $withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedWithdrawal->canBeCancelledByPartner()) {
                throw new DomainException('This request is no longer in pending status and cannot be cancelled.');
            }

            // Release all linked commissions
            Commission::where('withdrawal_request_id', $lockedWithdrawal->id)->update([
                'withdrawal_request_id' => null,
                'status'                => CommissionStatus::Payable,
            ]);

            $cancelReason = $reason ? trim($reason) : 'Cancelled by partner';

            $lockedWithdrawal->update([
                'status'           => WithdrawalStatus::Cancelled,
                'rejection_reason' => $cancelReason,
            ]);

            Log::info('Withdrawal request cancelled by partner', [
                'request_id'     => $lockedWithdrawal->id,
                'request_number' => $lockedWithdrawal->request_number,
                'partner_id'     => $partner->id,
                'reason'         => $cancelReason,
            ]);

            Notification::send(
                $partner->user_id,
                'Withdrawal Request Cancelled',
                "Your withdrawal request #{$lockedWithdrawal->request_number} has been cancelled and your balance has been released.",
                'warning',
                route('partner.withdrawals.show', $lockedWithdrawal)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::WithdrawalCancelled,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Withdrawal request #{$lockedWithdrawal->request_number} cancelled by Partner {$partner->company_name}.",
                subject: $lockedWithdrawal,
                metadata: ['reason' => $cancelReason],
                actor: $partner->user
            );

            return $lockedWithdrawal;
        });
    }

    /**
     * Admin approves a pending withdrawal request.
     */
    public function approveWithdrawal(
        WithdrawalRequest $withdrawal,
        int $adminUserId,
        ?string $adminNotes = null
    ): WithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminUserId, $adminNotes) {
            $locked = WithdrawalRequest::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== WithdrawalStatus::Pending) {
                throw new DomainException("Only pending withdrawal requests can be approved (current: {$locked->status->label()}).");
            }

            $notes = $adminNotes ? trim($adminNotes) : $locked->admin_notes;

            $locked->update([
                'status'      => WithdrawalStatus::Approved,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'admin_notes' => $notes,
            ]);

            Log::info('Withdrawal request approved by admin', [
                'request_id'     => $locked->id,
                'request_number' => $locked->request_number,
                'admin_id'       => $adminUserId,
                'amount'         => $locked->amount,
            ]);

            Notification::send(
                $locked->partner->user_id,
                'Withdrawal Request Approved',
                "Your withdrawal request #{$locked->request_number} for ₹" . number_format($locked->amount, 2) . " has been approved and queued for processing.",
                'success',
                route('partner.withdrawals.show', $locked)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::WithdrawalApproved,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Withdrawal request #{$locked->request_number} for ₹" . number_format($locked->amount, 2) . " approved by Admin.",
                subject: $locked,
                oldValues: ['status' => 'pending'],
                newValues: ['status' => 'approved'],
                actor: User::find($adminUserId)
            );

            return $locked;
        });
    }

    /**
     * Admin marks an approved request as Processing (dispatched to bank).
     */
    public function markProcessing(
        WithdrawalRequest $withdrawal,
        int $adminUserId,
        ?string $adminNotes = null
    ): WithdrawalRequest {
        return DB::transaction(function () use ($withdrawal, $adminUserId, $adminNotes) {
            $locked = WithdrawalRequest::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if (! in_array($locked->status, [WithdrawalStatus::Pending, WithdrawalStatus::Approved], true)) {
                throw new DomainException("Withdrawal request cannot be marked processing from status: {$locked->status->label()}.");
            }

            $notes = $adminNotes ? trim($adminNotes) : $locked->admin_notes;

            $locked->update([
                'status'      => WithdrawalStatus::Processing,
                'reviewed_by' => $adminUserId,
                'admin_notes' => $notes,
            ]);

            Log::info('Withdrawal request marked processing by admin', [
                'request_id'     => $locked->id,
                'request_number' => $locked->request_number,
                'admin_id'       => $adminUserId,
            ]);

            Notification::send(
                $locked->partner->user_id,
                'Payout Processing',
                "Payout for withdrawal request #{$locked->request_number} is now being processed by our finance team.",
                'info',
                route('partner.withdrawals.show', $locked)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::WithdrawalProcessed,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Withdrawal request #{$locked->request_number} marked as Processing by Admin.",
                subject: $locked,
                oldValues: ['status' => 'approved'],
                newValues: ['status' => 'processing'],
                actor: User::find($adminUserId)
            );

            return $locked;
        });
    }

    /**
     * Admin rejects a withdrawal request with mandatory reason.
     * Releases all reserved commissions back to Payable status.
     */
    public function rejectWithdrawal(
        WithdrawalRequest $withdrawal,
        string $reason,
        int $adminUserId,
        ?string $adminNotes = null
    ): WithdrawalRequest {
        $reason = trim($reason);
        if ($reason === '') {
            throw new InvalidArgumentException('A rejection reason is mandatory when rejecting a withdrawal request.');
        }

        return DB::transaction(function () use ($withdrawal, $reason, $adminUserId, $adminNotes) {
            $locked = WithdrawalRequest::where('id', $withdrawal->id)->lockForUpdate()->firstOrFail();

            if ($locked->isPaid()) {
                throw new DomainException('Cannot reject an already settled/paid withdrawal request.');
            }

            // 1. Release reserved commissions back to Payable status
            Commission::where('withdrawal_request_id', $locked->id)->update([
                'withdrawal_request_id' => null,
                'status'                => CommissionStatus::Payable,
            ]);

            // 2. If a linked Payout exists that is not completed, cancel it
            if ($locked->payout && ! $locked->payout->isCompleted()) {
                $locked->payout->update([
                    'payment_status' => PayoutStatus::Cancelled,
                    'notes'          => trim(($locked->payout->notes ?? '') . "\nCancelled due to withdrawal rejection: {$reason}"),
                ]);
            }

            $notes = $adminNotes ? trim($adminNotes) : $locked->admin_notes;

            $locked->update([
                'status'           => WithdrawalStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_by'      => $adminUserId,
                'reviewed_at'      => now(),
                'admin_notes'      => $notes,
            ]);

            Log::warning('Withdrawal request rejected by admin', [
                'request_id'     => $locked->id,
                'request_number' => $locked->request_number,
                'admin_id'       => $adminUserId,
                'reason'         => $reason,
            ]);

            Notification::send(
                $locked->partner->user_id,
                'Withdrawal Request Rejected',
                "Your withdrawal request #{$locked->request_number} was rejected: {$reason}. Your commission balance has been restored.",
                'error',
                route('partner.withdrawals.show', $locked)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::WithdrawalRejected,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Withdrawal request #{$locked->request_number} rejected by Admin. Reason: {$reason}",
                subject: $locked,
                metadata: ['reason' => $reason],
                actor: User::find($adminUserId)
            );

            return $locked;
        });
    }

    /**
     * Admin completes payment / marks payout as Paid.
     * Requires UTR / transaction reference, payment date.
     * Updates linked commissions to Paid and seals the record.
     */
    public function completePayout(
        WithdrawalRequest $withdrawal,
        string $transactionReference,
        string $paymentDate,
        int $adminUserId,
        ?string $receiptPath = null,
        ?string $adminNotes = null
    ): Payout {
        $transactionReference = trim($transactionReference);
        if ($transactionReference === '') {
            throw new InvalidArgumentException('UTR / Transaction reference is required to mark a payout as Paid.');
        }

        return DB::transaction(function () use (
            $withdrawal,
            $transactionReference,
            $paymentDate,
            $adminUserId,
            $receiptPath,
            $adminNotes
        ) {
            $lockedWithdrawal = WithdrawalRequest::where('id', $withdrawal->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedWithdrawal->isPaid()) {
                throw new DomainException('This withdrawal request has already been paid and settled.');
            }

            if ($lockedWithdrawal->isRejected() || $lockedWithdrawal->isCancelled()) {
                throw new DomainException("Cannot complete a {$lockedWithdrawal->status->value} withdrawal request.");
            }

            // 1. Lock linked commissions
            $commissions = Commission::where('withdrawal_request_id', $lockedWithdrawal->id)
                ->lockForUpdate()
                ->get();

            // 2. Create or update Payout record
            $payout = $lockedWithdrawal->payout;

            if (! $payout) {
                $payoutNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

                $payout = Payout::create([
                    'payout_number'          => $payoutNumber,
                    'partner_id'             => $lockedWithdrawal->partner_id,
                    'withdrawal_request_id'  => $lockedWithdrawal->id,
                    'amount'                 => $lockedWithdrawal->amount,
                    'currency'               => $lockedWithdrawal->currency ?? 'INR',
                    'payment_method'         => $lockedWithdrawal->payout_method,
                    'transaction_reference'  => $transactionReference,
                    'payment_status'         => PayoutStatus::Completed,
                    'payment_date'           => $paymentDate,
                    'processed_by'           => $adminUserId,
                    'recipient_snapshot'     => $lockedWithdrawal->recipient_snapshot,
                    'receipt_path'           => $receiptPath,
                    'notes'                  => $adminNotes,
                ]);
            } else {
                $payout->update([
                    'transaction_reference' => $transactionReference,
                    'payment_status'        => PayoutStatus::Completed,
                    'payment_date'          => $paymentDate,
                    'receipt_path'          => $receiptPath ?? $payout->receipt_path,
                    'processed_by'          => $adminUserId,
                    'notes'                 => $adminNotes ?? $payout->notes,
                ]);
            }

            // 3. Mark all linked commissions as Paid permanently
            Commission::where('withdrawal_request_id', $lockedWithdrawal->id)->update([
                'payout_id' => $payout->id,
                'status'    => CommissionStatus::Paid,
                'paid_at'   => now(),
            ]);

            // 4. Update withdrawal request to Paid / Settled
            $combinedNotes = $adminNotes ? trim($adminNotes) : $lockedWithdrawal->admin_notes;

            $lockedWithdrawal->update([
                'status'      => WithdrawalStatus::Paid,
                'settled_at'  => now(),
                'reviewed_by' => $adminUserId,
                'admin_notes' => $combinedNotes,
            ]);

            // 5. Notify partner of completed disbursement
            Notification::send(
                $lockedWithdrawal->partner->user_id,
                'Payout Completed',
                "Your payout of ₹" . number_format($lockedWithdrawal->amount, 2) . " has been sent! UTR Reference: {$transactionReference}.",
                'success',
                route('partner.withdrawals.show', $lockedWithdrawal)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::PayoutCompleted,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Payout #{$payout->payout_number} completed for Withdrawal #{$lockedWithdrawal->request_number}. UTR: {$transactionReference}",
                subject: $payout,
                metadata: [
                    'amount' => $payout->amount,
                    'transaction_reference' => $transactionReference,
                    'payment_date' => $paymentDate,
                ],
                actor: User::find($adminUserId)
            );

            return $payout;
        });
    }
}
