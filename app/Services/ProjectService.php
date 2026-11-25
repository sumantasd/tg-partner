<?php

namespace App\Services;

use App\Enums\CommissionType;
use App\Enums\FixedCommissionFrequency;
use App\Enums\ProjectPaymentType;
use App\Enums\ProjectStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectService
{
    /**
     * Generate unique sequential project code (e.g. PRJ-2026-0001).
     */
    public function generateProjectCode(): string
    {
        $year = date('Y');
        $prefix = "PRJ-{$year}-";

        $latest = Project::withTrashed()
            ->where('project_code', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(project_code, 10) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        if ($latest && preg_match('/^PRJ-\d{4}-(\d+)$/', $latest->project_code, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Create a new Project for a Customer.
     */
    public function createProject(Customer $customer, array $data, ?User $actor = null): Project
    {
        return DB::transaction(function () use ($customer, $data, $actor) {
            $projectCode = $this->generateProjectCode();

            $partnerId = $data['partner_id'] ?? $customer->first_partner_id;
            $leadId = $data['lead_id'] ?? $customer->lead_id;

            $status = isset($data['status'])
                ? ($data['status'] instanceof ProjectStatus ? $data['status'] : ProjectStatus::from($data['status']))
                : ProjectStatus::InProgress;

            $paymentType = isset($data['payment_type'])
                ? ($data['payment_type'] instanceof ProjectPaymentType ? $data['payment_type'] : ProjectPaymentType::from($data['payment_type']))
                : ProjectPaymentType::Single;

            $commissionModel = $data['commission_model'] ?? 'global_default';
            $partner = $partnerId ? Partner::find($partnerId) : null;

            if ($commissionModel === 'global_default') {
                $resolved = app(\App\Services\CommissionService::class)->resolveDefaultPolicyForPartner($partner);
                $commissionType = $resolved['commission_type'];
                $agreedRate = array_key_exists('agreed_commission_rate', $data) && $data['agreed_commission_rate'] !== null
                    ? (float) $data['agreed_commission_rate']
                    : null;
                $policyId = $data['commission_policy_id'] ?? $resolved['policy_id'];
                $customType = null;
                $customRate = null;
                $customFreq = null;
                $notes = null;
            } else {
                $commissionType = isset($data['custom_commission_type'])
                    ? ($data['custom_commission_type'] instanceof CommissionType ? $data['custom_commission_type'] : CommissionType::from($data['custom_commission_type']))
                    : (isset($data['agreed_commission_type']) ? ($data['agreed_commission_type'] instanceof CommissionType ? $data['agreed_commission_type'] : CommissionType::from($data['agreed_commission_type'])) : CommissionType::Percentage);
                $customType = $commissionType;
                $customRate = isset($data['custom_commission_rate']) ? (float) $data['custom_commission_rate'] : null;
                $customFreq = !empty($data['custom_fixed_frequency'])
                    ? ($data['custom_fixed_frequency'] instanceof FixedCommissionFrequency ? $data['custom_fixed_frequency'] : FixedCommissionFrequency::from($data['custom_fixed_frequency']))
                    : null;
                $agreedRate = $customRate;
                $policyId = $data['commission_policy_id'] ?? null;
                $notes = $data['commission_notes'] ?? null;
            }

            $project = Project::create([
                'partner_id'               => $partnerId,
                'customer_id'              => $customer->id,
                'lead_id'                  => $leadId,
                'assigned_to'              => $data['assigned_to'] ?? null,
                'project_code'             => $projectCode,
                'client_name'              => $customer->name,
                'client_company'           => $customer->company_name,
                'client_email'             => $customer->email,
                'client_phone'             => $customer->phone,
                'project_title'            => trim($data['project_title']),
                'service_category'         => trim($data['service_category'] ?? 'General'),
                'project_description'      => trim($data['project_description'] ?? 'Project created for customer.'),
                'estimated_budget'         => $data['estimated_budget'] ?? null,
                'final_deal_value'         => $data['final_deal_value'] ?? 0.00,
                'payment_type'             => $paymentType,
                'monthly_amount'           => $data['monthly_amount'] ?? null,
                'duration_months'          => $data['duration_months'] ?? null,
                'billing_start_date'       => $data['billing_start_date'] ?? null,
                'monthly_due_day'          => $data['monthly_due_day'] ?? null,
                'first_payment_due_date'   => $data['first_payment_due_date'] ?? null,
                'grace_period_days'        => $data['grace_period_days'] ?? 0,
                'currency'                 => $data['currency'] ?? 'INR',
                'status'                   => $status,
                'agreed_commission_type'   => $commissionType,
                'agreed_commission_rate'   => $agreedRate,
                'commission_model'         => $commissionModel,
                'custom_commission_type'   => $customType,
                'custom_commission_rate'   => $customRate,
                'custom_fixed_frequency'   => $customFreq,
                'commission_effective_date'=> $data['commission_effective_date'] ?? now()->toDateString(),
                'commission_status'        => $data['commission_status'] ?? 'active',
                'commission_policy_id'     => $policyId,
                'commission_notes'         => $notes,
                'start_date'               => $data['start_date'] ?? now()->toDateString(),
                'expected_completion_date' => $data['expected_completion_date'] ?? null,
                'submitted_at'             => now(),
            ]);

            if ($leadId) {
                Lead::where('id', $leadId)->update([
                    'converted_project_id' => $project->id,
                ]);
            }

            // If monthly payment, automatically generate schedule
            if ($project->payment_type === ProjectPaymentType::Monthly) {
                app(PaymentScheduleService::class)->generateSchedule($project, $actor ? $actor->id : 1);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::ProjectCreated,
                module: \App\Enums\AuditLogModule::Project,
                description: "Project #{$project->project_code} ({$project->project_title}) created for Customer '{$customer->name}'.",
                subject: $project,
                metadata: [
                    'project_code' => $project->project_code,
                    'customer_id' => $customer->id,
                    'partner_id' => $partnerId,
                    'payment_type' => $project->payment_type->value,
                    'final_deal_value' => $project->final_deal_value,
                    'agreed_commission_type' => $project->agreed_commission_type?->value,
                    'agreed_commission_rate' => $project->agreed_commission_rate,
                    'status' => $project->status?->value,
                ],
                actor: $actor
            );

            return $project;
        });
    }
}
