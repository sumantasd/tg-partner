<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectPaymentSchedule;
use App\Services\PaymentScheduleService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class PaymentScheduleController extends Controller
{
    /**
     * Record a payment against a specific monthly schedule.
     */
    public function storePayment(
        Request $request,
        ProjectPaymentSchedule $schedule,
        PaymentScheduleService $service
    ): RedirectResponse {
        $validated = $request->validate([
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'payment_date'          => ['required', 'date'],
            'payment_method'        => ['required', Rule::enum(PaymentMethod::class)],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'notes'                 => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $service->recordPaymentForSchedule($schedule, $validated, $request->user()->id);

            return redirect()->route('admin.projects.show', $schedule->project_id)
                ->with('success', "Payment #{$payment->payment_number} of ₹" . number_format($payment->amount, 2) . " recorded for {$schedule->billing_period}. Payment is pending verification.");
        } catch (DomainException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause a monthly schedule.
     */
    public function pause(
        Request $request,
        ProjectPaymentSchedule $schedule,
        PaymentScheduleService $service
    ): RedirectResponse {
        $service->pauseSchedule($schedule, $request->user()->id);

        return redirect()->route('admin.projects.show', $schedule->project_id)
            ->with('success', "Monthly schedule for {$schedule->billing_period} paused.");
    }

    /**
     * Resume a paused monthly schedule.
     */
    public function resume(
        Request $request,
        ProjectPaymentSchedule $schedule,
        PaymentScheduleService $service
    ): RedirectResponse {
        $service->resumeSchedule($schedule, $request->user()->id);

        return redirect()->route('admin.projects.show', $schedule->project_id)
            ->with('success', "Monthly schedule for {$schedule->billing_period} resumed.");
    }

    /**
     * Cancel a monthly schedule with mandatory reason.
     */
    public function cancel(
        Request $request,
        ProjectPaymentSchedule $schedule,
        PaymentScheduleService $service
    ): RedirectResponse {
        $request->validate([
            'cancellation_reason' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $service->cancelSchedule($schedule, $request->input('cancellation_reason'), $request->user()->id);

            return redirect()->route('admin.projects.show', $schedule->project_id)
                ->with('success', "Monthly schedule for {$schedule->billing_period} cancelled.");
        } catch (DomainException|InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Pause all future pending schedules for a project.
     */
    public function pauseProject(
        Request $request,
        Project $project,
        PaymentScheduleService $service
    ): RedirectResponse {
        $service->pauseProjectSchedules($project, $request->user()->id);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "All pending schedules for Project #{$project->project_code} paused.");
    }

    /**
     * Resume all paused schedules for a project.
     */
    public function resumeProject(
        Request $request,
        Project $project,
        PaymentScheduleService $service
    ): RedirectResponse {
        $service->resumeProjectSchedules($project, $request->user()->id);

        return redirect()->route('admin.projects.show', $project)
            ->with('success', "All paused schedules for Project #{$project->project_code} resumed.");
    }
}
