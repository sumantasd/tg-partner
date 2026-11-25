<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\PayoutStatus;
use App\Enums\WithdrawalStatus;
use App\Models\Commission;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\WithdrawalRequest;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PayoutService
{
    /**
     * Create a payout with server-side amount calculation, row-level locking, and transaction safety.
     *
     * @param int $partnerId
     * @param array<int> $commissionIds
     * @param array{payment_method: string, transaction_reference?: string|null, payment_date?: string|null, notes?: string|null, receipt_path?: string|null, expected_amount?: float|null, withdrawal_request_id?: int|null} $payoutData
     * @param int $adminUserId
     * @param bool $markCompletedImmediately
     * @return Payout
     */
    public function createPayout(
        int $partnerId,
        array $commissionIds,
        array $payoutData,
        int $adminUserId,
        bool $markCompletedImmediately = true
    ): Payout {
        if (empty($commissionIds)) {
            throw new InvalidArgumentException('At least one commission must be selected for payout.');
        }

        return DB::transaction(function () use (
            $partnerId,
            $commissionIds,
            $payoutData,
            $adminUserId,
            $markCompletedImmediately
        ) {
            // 1. Pessimistic row locking on candidate commissions
            $commissions = Commission::whereIn('id', $commissionIds)
                ->lockForUpdate()
                ->get();

            if ($commissions->count() !== count(array_unique($commissionIds))) {
                throw new DomainException('One or more selected commissions could not be found.');
            }

            // 2. Validate every selected commission
            foreach ($commissions as $commission) {
                if ($commission->partner_id !== $partnerId) {
                    throw new DomainException("Commission #{$commission->commission_number} does not belong to partner ID {$partnerId}.");
                }

                if (! in_array($commission->status, [CommissionStatus::Payable, CommissionStatus::InWithdrawal], true)) {
                    throw new DomainException("Commission #{$commission->commission_number} is not in Payable or InWithdrawal status (current: {$commission->status->label()}).");
                }

                if ($commission->payout_id !== null) {
                    throw new DomainException("Commission #{$commission->commission_number} is already linked to Payout #{$commission->payout_id}.");
                }
            }

            // 3. Server-side authoritative total amount calculation
            $serverCalculatedAmount = round($commissions->sum(fn ($c) => (float) $c->amount), 2);

            if (isset($payoutData['expected_amount'])) {
                $expected = round((float) $payoutData['expected_amount'], 2);
                if (abs($serverCalculatedAmount - $expected) > 0.001) {
                    throw new DomainException("Payout amount mismatch: expected {$expected}, calculated {$serverCalculatedAmount}.");
                }
            }

            // 4. Fetch partner and freeze minimal encrypted historical snapshot
            $partner = Partner::findOrFail($partnerId);

            $recipientSnapshot = [
                'partner_code'      => $partner->partner_code,
                'company_name'      => $partner->company_name,
                'bank_name'         => $partner->bank_name,
                'bank_account_name' => $partner->bank_account_name,
                'masked_account'    => $partner->masked_account_number,
                'bank_routing_code' => $partner->bank_routing_code,
                'payout_method'     => $payoutData['payment_method'] ?? $partner->payout_method,
                'tax_id'            => $partner->tax_id,
            ];

            $initialStatus = $markCompletedImmediately ? PayoutStatus::Completed : PayoutStatus::Initiated;

            // 5. Create immutable payout record
            $payoutNumber = 'PAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $payout = Payout::create([
                'payout_number'          => $payoutNumber,
                'partner_id'             => $partner->id,
                'withdrawal_request_id'  => $payoutData['withdrawal_request_id'] ?? null,
                'amount'                 => $serverCalculatedAmount,
                'currency'               => 'INR',
                'payment_method'         => $payoutData['payment_method'] ?? $partner->payout_method,
                'transaction_reference'  => $payoutData['transaction_reference'] ?? null,
                'payment_status'         => $initialStatus,
                'payment_date'           => $payoutData['payment_date'] ?? now()->toDateString(),
                'processed_by'           => $adminUserId,
                'recipient_snapshot'     => $recipientSnapshot,
                'receipt_path'           => $payoutData['receipt_path'] ?? null,
                'notes'                  => $payoutData['notes'] ?? null,
            ]);

            // 6. Link commissions atomically
            $targetCommissionStatus = $markCompletedImmediately ? CommissionStatus::Paid : CommissionStatus::Payable;
            $paidAt = $markCompletedImmediately ? now() : null;

            Commission::whereIn('id', $commissions->pluck('id'))->update([
                'payout_id' => $payout->id,
                'status'    => $targetCommissionStatus,
                'paid_at'   => $paidAt,
            ]);

            // 7. Update associated withdrawal request if present
            if (! empty($payoutData['withdrawal_request_id'])) {
                WithdrawalRequest::where('id', $payoutData['withdrawal_request_id'])->update([
                    'status'      => $markCompletedImmediately ? WithdrawalStatus::Settled : WithdrawalStatus::Approved,
                    'settled_at'  => $markCompletedImmediately ? now() : null,
                ]);
            }

            Log::info('Payout created', [
                'payout_id'     => $payout->id,
                'payout_number' => $payout->payout_number,
                'partner_id'    => $partnerId,
                'amount'        => $serverCalculatedAmount,
                'status'        => $payout->payment_status->value,
                'admin_id'      => $adminUserId,
            ]);

            return $payout;
        });
    }

    /**
     * Mark an initiated or processing payout as completed.
     */
    public function completePayout(Payout $payout, string $transactionReference, ?string $receiptPath = null): Payout
    {
        return DB::transaction(function () use ($payout, $transactionReference, $receiptPath) {
            $lockedPayout = Payout::where('id', $payout->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayout->payment_status === PayoutStatus::Completed) {
                return $lockedPayout;
            }

            if ($lockedPayout->isFailedOrCancelled()) {
                throw new DomainException("Cannot complete a {$lockedPayout->payment_status->value} payout.");
            }

            $lockedPayout->update([
                'payment_status'        => PayoutStatus::Completed,
                'transaction_reference' => $transactionReference,
                'receipt_path'          => $receiptPath ?? $lockedPayout->receipt_path,
                'payment_date'          => now()->toDateString(),
            ]);

            Commission::where('payout_id', $lockedPayout->id)->update([
                'status'  => CommissionStatus::Paid,
                'paid_at' => now(),
            ]);

            if ($lockedPayout->withdrawal_request_id) {
                WithdrawalRequest::where('id', $lockedPayout->withdrawal_request_id)->update([
                    'status'     => WithdrawalStatus::Settled,
                    'settled_at' => now(),
                ]);
            }

            Log::info('Payout marked as completed', [
                'payout_id'             => $lockedPayout->id,
                'payout_number'         => $lockedPayout->payout_number,
                'transaction_reference' => $transactionReference,
            ]);

            return $lockedPayout;
        });
    }

    /**
     * Release commissions when a payout fails or is cancelled.
     * Completed payouts are sealed and can never be released.
     */
    public function releasePayout(Payout $payout, PayoutStatus $newStatus, string $reason): Payout
    {
        if (! $newStatus->isReleased()) {
            throw new InvalidArgumentException("Release target status must be Failed or Cancelled.");
        }

        return DB::transaction(function () use ($payout, $newStatus, $reason) {
            $lockedPayout = Payout::where('id', $payout->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayout->payment_status === PayoutStatus::Completed) {
                throw new DomainException('Completed payouts are permanently sealed and cannot be cancelled or released.');
            }

            $updatedNotes = trim(($lockedPayout->notes ?? '') . "\n[" . now()->toDateTimeString() . "] Status changed to {$newStatus->value}: {$reason}");

            $lockedPayout->update([
                'payment_status' => $newStatus,
                'notes'          => $updatedNotes,
            ]);

            // Release all linked commissions back to Payable status
            Commission::where('payout_id', $lockedPayout->id)->update([
                'payout_id'             => null,
                'withdrawal_request_id' => null,
                'status'                => CommissionStatus::Payable,
                'paid_at'               => null,
            ]);

            if ($lockedPayout->withdrawal_request_id) {
                WithdrawalRequest::where('id', $lockedPayout->withdrawal_request_id)->update([
                    'status'           => WithdrawalStatus::Rejected,
                    'rejection_reason' => $reason,
                ]);
            }

            Log::warning('Payout released/cancelled', [
                'payout_id'     => $lockedPayout->id,
                'payout_number' => $lockedPayout->payout_number,
                'new_status'    => $newStatus->value,
                'reason'        => $reason,
            ]);

            return $lockedPayout;
        });
    }
}
