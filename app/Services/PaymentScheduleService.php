<?php

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentScheduleStatus;
use App\Enums\ProjectPaymentType;
use App\Models\CustomerPayment;
use App\Models\Project;
use App\Models\ProjectPaymentSchedule;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentScheduleService
{
    /**
     * Generate monthly payment schedules for a monthly project.
     * Idempotent: Never duplicates an existing billing month schedule.
     *
     * @return Collection<int, ProjectPaymentSchedule>
     */
    public function generateSchedule(Project $project, int $adminUserId): Collection
    {
        return DB::transaction(function () use ($project, $adminUserId) {
            $lockedProject = Project::where('id', $project->id)->lockForUpdate()->firstOrFail();

            if ($lockedProject->payment_type !== ProjectPaymentType::Monthly) {
                return new Collection();
            }

            $duration = (int) ($lockedProject->duration_months ?: 12);
            $monthlyAmount = (float) ($lockedProject->monthly_amount ?: ($lockedProject->final_deal_value ? $lockedProject->final_deal_value / $duration : 0));

            if ($monthlyAmount <= 0) {
                throw new DomainException("Cannot generate monthly schedules without a valid monthly payment amount.");
            }

            $startDate = $lockedProject->billing_start_date ? Carbon::parse($lockedProject->billing_start_date) : Carbon::now()->startOfMonth();
            $dueDay = (int) ($lockedProject->monthly_due_day ?: $startDate->day);
            $graceDays = (int) ($lockedProject->grace_period_days ?: 0);
            $firstDueDate = $lockedProject->first_payment_due_date ? Carbon::parse($lockedProject->first_payment_due_date) : null;

            $schedules = new Collection();
            $createdCount = 0;

            for ($i = 1; $i <= $duration; $i++) {
                $monthCarbon = $startDate->copy()->addMonthsNoOverflow($i - 1);
                $billingMonth = $monthCarbon->format('Y-m');
                $billingPeriod = $monthCarbon->format('F Y');

                // Determine due date for this month
                if ($i === 1 && $firstDueDate) {
                    $dueDate = $firstDueDate->copy();
                } else {
                    $day = min($dueDay, $monthCarbon->daysInMonth);
                    $dueDate = $monthCarbon->copy()->day($day);
                }

                // Check for existing schedule to guarantee idempotency
                $existing = ProjectPaymentSchedule::where('project_id', $lockedProject->id)
                    ->where('billing_month', $billingMonth)
                    ->first();

                if ($existing) {
                    $schedules->push($existing);
                    continue;
                }

                // Initial status determination
                $now = Carbon::now()->startOfDay();
                $dueWithGrace = $dueDate->copy()->addDays($graceDays);
                if ($now->gt($dueWithGrace)) {
                    $status = PaymentScheduleStatus::Overdue;
                } elseif ($now->gte($dueDate)) {
                    $status = PaymentScheduleStatus::Due;
                } else {
                    $status = PaymentScheduleStatus::Upcoming;
                }

                $schedule = ProjectPaymentSchedule::create([
                    'project_id'          => $lockedProject->id,
                    'customer_id'         => $lockedProject->customer_id,
                    'sequence_number'     => $i,
                    'billing_month'       => $billingMonth,
                    'billing_period'      => $billingPeriod,
                    'due_date'            => $dueDate->toDateString(),
                    'expected_amount'     => $monthlyAmount,
                    'received_amount'     => 0.00,
                    'outstanding_amount'  => $monthlyAmount,
                    'status'              => $status,
                    'customer_payment_id' => null,
                    'is_paused'           => false,
                ]);

                $schedules->push($schedule);
                $createdCount++;
            }

            if ($createdCount > 0) {
                app(AuditLogService::class)->record(
                    action: AuditLogAction::PaymentScheduleGenerated,
                    module: AuditLogModule::Financial,
                    description: "Generated {$createdCount} monthly payment schedules for Project #{$lockedProject->project_code}.",
                    subject: $lockedProject,
                    metadata: ['duration' => $duration, 'monthly_amount' => $monthlyAmount],
                    actor: User::find($adminUserId)
                );
            }

            return $schedules;
        });
    }

    /**
     * Record a customer payment against a specific monthly payment schedule.
     */
    public function recordPaymentForSchedule(ProjectPaymentSchedule $schedule, array $data, int $adminUserId): CustomerPayment
    {
        return DB::transaction(function () use ($schedule, $data, $adminUserId) {
            $lockedSchedule = ProjectPaymentSchedule::where('id', $schedule->id)->lockForUpdate()->firstOrFail();
            $project = $lockedSchedule->project;

            $amount = (float) $data['amount'];
            if ($amount <= 0) {
                throw new InvalidArgumentException("Payment amount must be greater than zero.");
            }

            $paymentNumber = 'CPAY-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            $payment = CustomerPayment::create([
                'payment_number'        => $paymentNumber,
                'project_id'            => $project->id,
                'customer_id'           => $lockedSchedule->customer_id,
                'partner_id'            => $project->partner_id,
                'payment_schedule_id'   => $lockedSchedule->id,
                'payment_type'          => CustomerPaymentType::Monthly,
                'billing_month'         => $lockedSchedule->billing_month,
                'billing_period'        => $lockedSchedule->billing_period,
                'amount'                => $amount,
                'currency'              => $data['currency'] ?? ($project->currency ?? 'INR'),
                'payment_method'        => $data['payment_method'] ?? PaymentMethod::BankTransfer,
                'transaction_reference' => $data['transaction_reference'] ?? ('TXN-' . strtoupper(bin2hex(random_bytes(4)))),
                'payment_date'          => $data['payment_date'] ?? now()->toDateString(),
                'proof_attachment_path' => $data['proof_attachment_path'] ?? null,
                'recorded_by'           => $adminUserId,
                'status'                => CustomerPaymentStatus::Recorded,
                'commission_generated'  => false,
                'notes'                 => $data['notes'] ?? "Recorded for {$lockedSchedule->billing_period}",
            ]);

            // If no verified payment yet, indicate paid pending verification
            if ((float) $lockedSchedule->received_amount <= 0) {
                $lockedSchedule->update([
                    'status' => PaymentScheduleStatus::PaidPendingVerification,
                    'customer_payment_id' => $payment->id,
                ]);
            }

            app(AuditLogService::class)->record(
                action: AuditLogAction::CustomerPaymentRecorded,
                module: AuditLogModule::Financial,
                description: "Customer Payment #{$payment->payment_number} of ₹" . number_format($amount, 2) . " recorded for {$lockedSchedule->billing_period}.",
                subject: $payment,
                metadata: ['schedule_id' => $lockedSchedule->id, 'amount' => $amount],
                actor: User::find($adminUserId)
            );

            return $payment;
        });
    }

    /**
     * Recalculate schedule amounts and status based on actual payments.
     */
    public function syncScheduleStatus(ProjectPaymentSchedule $schedule): void
    {
        if ($schedule->status === PaymentScheduleStatus::Cancelled) {
            return;
        }

        $verifiedAmount = (float) CustomerPayment::where('project_id', $schedule->project_id)
            ->where(function ($q) use ($schedule) {
                $q->where('payment_schedule_id', $schedule->id)
                  ->orWhere('billing_month', $schedule->billing_month);
            })
            ->where('status', CustomerPaymentStatus::Verified)
            ->sum('amount');

        $hasPending = CustomerPayment::where('project_id', $schedule->project_id)
            ->where(function ($q) use ($schedule) {
                $q->where('payment_schedule_id', $schedule->id)
                  ->orWhere('billing_month', $schedule->billing_month);
            })
            ->where('status', CustomerPaymentStatus::Recorded)
            ->exists();

        $schedule->received_amount = $verifiedAmount;
        $schedule->outstanding_amount = max(0, (float) $schedule->expected_amount - $verifiedAmount);

        if ($schedule->is_paused) {
            // Retain paused status
        } elseif ($schedule->outstanding_amount <= 0) {
            $schedule->status = PaymentScheduleStatus::Verified;
        } elseif ($verifiedAmount > 0) {
            $schedule->status = PaymentScheduleStatus::PartiallyPaid;
        } else {
            if ($hasPending) {
                $schedule->status = PaymentScheduleStatus::PaidPendingVerification;
            } elseif ($schedule->isOverdue()) {
                $schedule->status = PaymentScheduleStatus::Overdue;
            } elseif (Carbon::now()->startOfDay()->gte($schedule->due_date)) {
                $schedule->status = PaymentScheduleStatus::Due;
            } else {
                $schedule->status = PaymentScheduleStatus::Upcoming;
            }
        }

        $schedule->save();
    }

    /**
     * Pause a monthly schedule entry.
     */
    public function pauseSchedule(ProjectPaymentSchedule $schedule, int $adminUserId): ProjectPaymentSchedule
    {
        return DB::transaction(function () use ($schedule, $adminUserId) {
            $schedule->update([
                'is_paused' => true,
                'paused_at' => now(),
            ]);

            app(AuditLogService::class)->record(
                action: AuditLogAction::PaymentSchedulePaused,
                module: AuditLogModule::Financial,
                description: "Monthly schedule for {$schedule->billing_period} paused by Admin.",
                subject: $schedule,
                actor: User::find($adminUserId)
            );

            return $schedule;
        });
    }

    /**
     * Resume a paused monthly schedule entry.
     */
    public function resumeSchedule(ProjectPaymentSchedule $schedule, int $adminUserId): ProjectPaymentSchedule
    {
        return DB::transaction(function () use ($schedule, $adminUserId) {
            $schedule->update([
                'is_paused' => false,
                'paused_at' => null,
            ]);

            $this->syncScheduleStatus($schedule);

            app(AuditLogService::class)->record(
                action: AuditLogAction::PaymentScheduleResumed,
                module: AuditLogModule::Financial,
                description: "Monthly schedule for {$schedule->billing_period} resumed by Admin.",
                subject: $schedule,
                actor: User::find($adminUserId)
            );

            return $schedule;
        });
    }

    /**
     * Cancel a future schedule entry with a mandatory reason.
     */
    public function cancelSchedule(ProjectPaymentSchedule $schedule, string $reason, int $adminUserId): ProjectPaymentSchedule
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException("A cancellation reason is required.");
        }

        if ((float) $schedule->received_amount > 0) {
            throw new DomainException("Cannot cancel schedule for {$schedule->billing_period}: Verified payments exist. Reversal required first.");
        }

        return DB::transaction(function () use ($schedule, $reason, $adminUserId) {
            $schedule->update([
                'status'              => PaymentScheduleStatus::Cancelled,
                'cancelled_at'        => now(),
                'cancellation_reason' => trim($reason),
            ]);

            app(AuditLogService::class)->record(
                action: AuditLogAction::PaymentScheduleCancelled,
                module: AuditLogModule::Financial,
                description: "Monthly schedule for {$schedule->billing_period} cancelled. Reason: {$reason}",
                subject: $schedule,
                metadata: ['reason' => $reason],
                actor: User::find($adminUserId)
            );

            return $schedule;
        });
    }

    /**
     * Pause all future active schedules for a project.
     */
    public function pauseProjectSchedules(Project $project, int $adminUserId): void
    {
        DB::transaction(function () use ($project, $adminUserId) {
            $project->update([
                'schedules_paused'    => true,
                'schedules_paused_at' => now(),
            ]);

            ProjectPaymentSchedule::where('project_id', $project->id)
                ->where('status', '!=', PaymentScheduleStatus::Verified)
                ->where('status', '!=', PaymentScheduleStatus::Cancelled)
                ->update([
                    'is_paused' => true,
                    'paused_at' => now(),
                ]);

            app(AuditLogService::class)->record(
                action: AuditLogAction::PaymentSchedulePaused,
                module: AuditLogModule::Financial,
                description: "All pending payment schedules for Project #{$project->project_code} paused by Admin.",
                subject: $project,
                actor: User::find($adminUserId)
            );
        });
    }

    /**
     * Resume all paused schedules for a project.
     */
    public function resumeProjectSchedules(Project $project, int $adminUserId): void
    {
        DB::transaction(function () use ($project, $adminUserId) {
            $project->update([
                'schedules_paused'    => false,
                'schedules_paused_at' => null,
            ]);

            $schedules = ProjectPaymentSchedule::where('project_id', $project->id)
                ->where('is_paused', true)
                ->get();

            foreach ($schedules as $schedule) {
                $schedule->update([
                    'is_paused' => false,
                    'paused_at' => null,
                ]);
                $this->syncScheduleStatus($schedule);
            }

            app(AuditLogService::class)->record(
                action: AuditLogAction::PaymentScheduleResumed,
                module: AuditLogModule::Financial,
                description: "All paused payment schedules for Project #{$project->project_code} resumed by Admin.",
                subject: $project,
                actor: User::find($adminUserId)
            );
        });
    }

    /**
     * Batch update overdue schedules past their grace periods.
     */
    public function updateOverdueSchedules(?Project $project = null): int
    {
        $query = ProjectPaymentSchedule::whereNotIn('status', [
            PaymentScheduleStatus::Verified,
            PaymentScheduleStatus::Cancelled,
            PaymentScheduleStatus::Overdue,
        ]);

        if ($project) {
            $query->where('project_id', $project->id);
        }

        $schedules = $query->with('project')->get();
        $count = 0;

        foreach ($schedules as $schedule) {
            if ($schedule->isOverdue()) {
                $schedule->update(['status' => PaymentScheduleStatus::Overdue]);
                $count++;
            }
        }

        return $count;
    }
}
