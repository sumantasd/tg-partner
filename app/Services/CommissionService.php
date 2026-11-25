<?php

namespace App\Services;

use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\FixedCommissionFrequency;
use App\Models\Commission;
use App\Models\CommissionPolicy;
use App\Models\CustomerPayment;
use App\Models\Partner;
use App\Models\Project;
use App\Models\SystemSetting;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CommissionService
{
    /**
     * Get the currently active global default commission policy.
     */
    public function getDefaultCommissionPolicy(): ?CommissionPolicy
    {
        return CommissionPolicy::getActiveDefault();
    }

    /**
     * Get the active system default commission percentage rate (e.g. 10.00).
     * Backwards-compatible: checks active CommissionPolicy first, falls back to SystemSetting.
     */
    public function getDefaultCommissionRate(): float
    {
        $policy = $this->getDefaultCommissionPolicy();
        if ($policy && $policy->commission_type === CommissionType::Percentage) {
            return round((float) $policy->commission_value, 2);
        }

        $rate = SystemSetting::get('default_commission_rate', '10.00');
        return round((float) $rate, 2);
    }

    /**
     * Update the system default commission percentage rate.
     * Validates 0 <= rate <= 100 and records the updating user and timestamp.
     * Synchronizes SystemSetting and creates/updates CommissionPolicy version.
     */
    public function updateDefaultCommissionRate(float $newRate, int $adminUserId): float
    {
        if ($newRate < 0.00 || $newRate > 100.00) {
            throw new InvalidArgumentException("Commission rate must be between 0% and 100%. Provided: {$newRate}%");
        }

        $rounded = round($newRate, 2);
        SystemSetting::set('default_commission_rate', number_format($rounded, 2, '.', ''), $adminUserId);

        // Keep versioned policy synchronized
        $activeDefault = CommissionPolicy::getActiveDefault();
        if ($activeDefault) {
            if ($activeDefault->commission_type === CommissionType::Percentage && (float) $activeDefault->commission_value === $rounded) {
                return $rounded;
            }

            // Create new revision version
            $this->createPolicyRevision($activeDefault, [
                'name' => "Global Default Policy (v" . ($activeDefault->version + 1) . ")",
                'commission_type' => CommissionType::Percentage,
                'commission_value' => $rounded,
                'effective_from' => now()->toDateString(),
                'is_default' => true,
                'description' => "Updated global default commission rate to {$rounded}%.",
            ], $adminUserId);
        }

        return $rounded;
    }

    /**
     * Create a brand-new Commission Policy.
     */
    public function createPolicy(array $data, int $adminUserId): CommissionPolicy
    {
        return DB::transaction(function () use ($data, $adminUserId) {
            $isDefault = (bool) ($data['is_default'] ?? false);
            $effectiveFrom = $data['effective_from'] ?? now()->toDateString();
            $effectiveUntil = !empty($data['effective_until']) ? $data['effective_until'] : null;

            if ($isDefault) {
                $this->assertNoOverlappingDefaultPolicies($effectiveFrom, $effectiveUntil);
            }

            $type = $data['commission_type'] instanceof CommissionType
                ? $data['commission_type']
                : CommissionType::from($data['commission_type']);

            $frequency = null;
            if ($type === CommissionType::Fixed && !empty($data['fixed_frequency'])) {
                $frequency = $data['fixed_frequency'] instanceof FixedCommissionFrequency
                    ? $data['fixed_frequency']
                    : FixedCommissionFrequency::from($data['fixed_frequency']);
            }

            $policyCode = $data['policy_code'] ?? ('POL-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3))));

            $policy = CommissionPolicy::create([
                'policy_code' => $policyCode,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'commission_type' => $type,
                'commission_value' => (float) $data['commission_value'],
                'fixed_frequency' => $frequency,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'status' => $data['status'] ?? 'active',
                'is_default' => $isDefault,
                'version' => 1,
                'parent_policy_id' => null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);

            if ($isDefault && $type === CommissionType::Percentage && $policy->status === 'active') {
                SystemSetting::set('default_commission_rate', number_format($policy->commission_value, 2, '.', ''), $adminUserId);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CommissionPolicyCreated,
                module: \App\Enums\AuditLogModule::Settings,
                description: "Commission policy '{$policy->name}' ({$policy->policy_code}) v{$policy->version} created by Admin.",
                subject: $policy,
                metadata: ['policy_code' => $policy->policy_code, 'rate' => $policy->commission_value],
                actor: \App\Models\User::find($adminUserId)
            );

            return $policy;
        });
    }

    /**
     * Create a new version/revision of an existing policy.
     */
    public function createPolicyRevision(CommissionPolicy $parent, array $data, int $adminUserId): CommissionPolicy
    {
        return DB::transaction(function () use ($parent, $data, $adminUserId) {
            $effectiveFrom = $data['effective_from'] ?? now()->toDateString();
            $effectiveUntil = !empty($data['effective_until']) ? $data['effective_until'] : null;
            $isDefault = isset($data['is_default']) ? (bool) $data['is_default'] : $parent->is_default;

            // Close parent policy validity prior to new revision effective start
            $yesterday = date('Y-m-d', strtotime($effectiveFrom . ' -1 day'));
            if (!$parent->effective_until || $parent->effective_until->toDateString() >= $effectiveFrom) {
                $parent->update([
                    'effective_until' => $yesterday,
                    'status' => 'superseded',
                    'updated_by' => $adminUserId,
                ]);
            }

            if ($isDefault) {
                $this->assertNoOverlappingDefaultPolicies($effectiveFrom, $effectiveUntil, $parent->id);
            }

            $nextVersion = $parent->version + 1;
            $policyCode = $data['policy_code'] ?? ($parent->policy_code . '-V' . $nextVersion);

            $type = isset($data['commission_type'])
                ? ($data['commission_type'] instanceof CommissionType ? $data['commission_type'] : CommissionType::from($data['commission_type']))
                : $parent->commission_type;

            $frequency = null;
            if ($type === CommissionType::Fixed) {
                $freqVal = $data['fixed_frequency'] ?? $parent->fixed_frequency;
                if ($freqVal) {
                    $frequency = $freqVal instanceof FixedCommissionFrequency ? $freqVal : FixedCommissionFrequency::from($freqVal);
                }
            }

            $newRevision = CommissionPolicy::create([
                'policy_code' => $policyCode,
                'name' => trim($data['name'] ?? $parent->name),
                'description' => $data['description'] ?? $parent->description,
                'commission_type' => $type,
                'commission_value' => isset($data['commission_value']) ? (float) $data['commission_value'] : $parent->commission_value,
                'fixed_frequency' => $frequency,
                'effective_from' => $effectiveFrom,
                'effective_until' => $effectiveUntil,
                'status' => $data['status'] ?? 'active',
                'is_default' => $isDefault,
                'version' => $nextVersion,
                'parent_policy_id' => $parent->id,
                'notes' => $data['notes'] ?? ("Revision version {$nextVersion} of {$parent->policy_code}"),
                'created_by' => $adminUserId,
                'updated_by' => $adminUserId,
            ]);

            if ($isDefault && $type === CommissionType::Percentage && $newRevision->status === 'active') {
                SystemSetting::set('default_commission_rate', number_format($newRevision->commission_value, 2, '.', ''), $adminUserId);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CommissionPolicyRevised,
                module: \App\Enums\AuditLogModule::Settings,
                description: "Commission policy '{$newRevision->name}' revised to v{$newRevision->version} by Admin.",
                subject: $newRevision,
                metadata: ['policy_code' => $newRevision->policy_code, 'rate' => $newRevision->commission_value, 'parent_id' => $parent->id],
                actor: \App\Models\User::find($adminUserId)
            );

            return $newRevision;
        });
    }

    /**
     * Guard: Prevent overlapping active global default commission policies.
     */
    protected function assertNoOverlappingDefaultPolicies(string $from, ?string $until, ?int $ignoreId = null): void
    {
        $query = CommissionPolicy::where('is_default', true)
            ->where('status', 'active');

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        $existing = $query->get();

        foreach ($existing as $policy) {
            $pStart = $policy->effective_from->toDateString();
            $pEnd = $policy->effective_until ? $policy->effective_until->toDateString() : '9999-12-31';
            $newEnd = $until ?? '9999-12-31';

            // Overlap condition: max(start1, start2) <= min(end1, end2)
            $overlapStart = max($from, $pStart);
            $overlapEnd = min($newEnd, $pEnd);

            if ($overlapStart <= $overlapEnd) {
                throw new DomainException("Cannot activate global default policy: Overlaps with existing active default policy #{$policy->policy_code} (valid from {$pStart} to " . ($policy->effective_until ? $pEnd : 'indefinite') . ").");
            }
        }
    }

    /**
     * Resolve the applicable commission policy for a project following strict hierarchy:
     * 1. Project-specific active custom override.
     * 2. Global active default commission policy fallback.
     *
     * @return array{
     *     source: string,
     *     commission_type: CommissionType,
     *     rate_applied: float,
     *     fixed_frequency: ?FixedCommissionFrequency,
     *     policy_id: ?int,
     *     policy_version: ?int,
     *     policy_code: ?string,
     *     effective_policy_date: string
     * }
     */
    /**
     * Resolve the applicable default commission policy for a specific partner:
     * 1. If partner has an active accepted agreement with a policy/rate snapshot, use it.
     * 2. Else if partner has an explicitly configured default_commission_rate that differs from global default, use it.
     * 3. Else fallback to the active global default commission policy.
     *
     * @return array{
     *     source: string,
     *     source_label: string,
     *     commission_type: CommissionType,
     *     commission_type_label: string,
     *     rate_applied: float,
     *     rate_formatted: string,
     *     fixed_frequency: ?FixedCommissionFrequency,
     *     policy_id: ?int,
     *     policy_version: ?int,
     *     policy_code: ?string,
     *     policy_name: ?string,
     *     effective_date: string
     * }
     */
    public function resolveDefaultPolicyForPartner(?Partner $partner, ?string $effectiveDate = null): array
    {
        $effectiveDate = $effectiveDate ?? now()->toDateString();

        // 1. Check Partner Active Accepted Agreement
        if ($partner) {
            $agreement = $partner->activeAgreement;
            if ($agreement) {
                // If agreement links to an active CommissionPolicy
                if ($agreement->commission_policy_id) {
                    $policy = $agreement->commissionPolicy;
                    if ($policy && $policy->status === 'active') {
                        $rate = (float) $policy->commission_value;
                        return [
                            'source' => 'partner_policy',
                            'source_label' => 'Partner Assigned Policy',
                            'commission_type' => $policy->commission_type,
                            'commission_type_label' => $policy->commission_type === CommissionType::Fixed ? 'Fixed Amount' : 'Percentage',
                            'rate_applied' => $rate,
                            'rate_formatted' => $policy->commission_type === CommissionType::Fixed ? '₹' . number_format($rate, 2) : number_format($rate, 2) . '%',
                            'fixed_frequency' => $policy->fixed_frequency,
                            'policy_id' => $policy->id,
                            'policy_version' => $policy->version,
                            'policy_code' => $policy->policy_code,
                            'policy_name' => $policy->name,
                            'effective_date' => $policy->effective_from?->toDateString() ?? $effectiveDate,
                        ];
                    }
                }

                // If agreement has rate snapshot
                if ($agreement->commission_rate_snapshot !== null && (float) $agreement->commission_rate_snapshot > 0) {
                    $rate = (float) $agreement->commission_rate_snapshot;
                    $type = $agreement->commission_type_snapshot instanceof CommissionType
                        ? $agreement->commission_type_snapshot
                        : ($agreement->commission_type_snapshot === 'fixed' ? CommissionType::Fixed : CommissionType::Percentage);

                    return [
                        'source' => 'partner_policy',
                        'source_label' => 'Partner Assigned Policy',
                        'commission_type' => $type,
                        'commission_type_label' => $type === CommissionType::Fixed ? 'Fixed Amount' : 'Percentage',
                        'rate_applied' => $rate,
                        'rate_formatted' => $type === CommissionType::Fixed ? '₹' . number_format($rate, 2) : number_format($rate, 2) . '%',
                        'fixed_frequency' => null,
                        'policy_id' => $agreement->commission_policy_id,
                        'policy_version' => $agreement->commission_policy_version ? (int) $agreement->commission_policy_version : null,
                        'policy_code' => $agreement->commissionPolicy?->policy_code,
                        'policy_name' => 'Partner Agreement Snapshot',
                        'effective_date' => $agreement->effective_date?->toDateString() ?? $effectiveDate,
                    ];
                }
            }
        }

        // 2. Fallback to Active Global Default Commission Policy
        $policy = CommissionPolicy::where('is_default', true)
            ->where('status', 'active')
            ->whereDate('effective_from', '<=', $effectiveDate)
            ->where(function ($q) use ($effectiveDate) {
                $q->whereNull('effective_until')
                  ->orWhereDate('effective_until', '>=', $effectiveDate);
            })
            ->latest('version')
            ->first();

        if ($policy) {
            $rate = (float) $policy->commission_value;
            return [
                'source' => 'global_policy',
                'source_label' => 'Global Default Policy',
                'commission_type' => $policy->commission_type,
                'commission_type_label' => $policy->commission_type === CommissionType::Fixed ? 'Fixed Amount' : 'Percentage',
                'rate_applied' => $rate,
                'rate_formatted' => $policy->commission_type === CommissionType::Fixed ? '₹' . number_format($rate, 2) : number_format($rate, 2) . '%',
                'fixed_frequency' => $policy->fixed_frequency,
                'policy_id' => $policy->id,
                'policy_version' => $policy->version,
                'policy_code' => $policy->policy_code,
                'policy_name' => $policy->name,
                'effective_date' => $policy->effective_from->toDateString(),
            ];
        }

        $defaultRate = $this->getDefaultCommissionRate();
        return [
            'source' => 'global_policy',
            'source_label' => 'Global Default Policy',
            'commission_type' => CommissionType::Percentage,
            'commission_type_label' => 'Percentage',
            'rate_applied' => (float) $defaultRate,
            'rate_formatted' => number_format($defaultRate, 2) . '%',
            'fixed_frequency' => null,
            'policy_id' => null,
            'policy_version' => 1,
            'policy_code' => 'POL-DEF-V1',
            'policy_name' => 'Standard Global Default Policy',
            'effective_date' => '2026-01-01',
        ];
    }

    /**
     * Resolve the applicable commission policy for a project following strict hierarchy:
     * 1. Project-specific active custom override.
     * 2. Partner-specific active policy (agreement snapshot or profile rate).
     * 3. Global active default commission policy fallback.
     *
     * @return array{
     *     source: string,
     *     commission_type: CommissionType,
     *     rate_applied: float,
     *     fixed_frequency: ?FixedCommissionFrequency,
     *     policy_id: ?int,
     *     policy_version: ?int,
     *     policy_code: ?string,
     *     effective_policy_date: string
     * }
     */
    public function resolveCommissionPolicyForProject(Project $project, ?string $effectiveDate = null): array
    {
        $effectiveDate = $effectiveDate ?? now()->toDateString();

        // 1. Check Project-specific Override
        if ($project->commission_model === 'custom' && $project->commission_status === 'active') {
            $startDate = $project->commission_effective_date?->toDateString();
            if (!$startDate || $startDate <= $effectiveDate) {
                $type = $project->custom_commission_type ?? $project->agreed_commission_type ?? CommissionType::Percentage;
                $rate = (float) ($project->custom_commission_rate ?? $project->agreed_commission_rate ?? 0.0);
                $freq = $project->custom_fixed_frequency;

                return [
                    'source' => 'project_override',
                    'commission_type' => $type,
                    'rate_applied' => $rate,
                    'fixed_frequency' => $type === CommissionType::Fixed ? $freq : null,
                    'policy_id' => $project->commission_policy_id,
                    'policy_version' => $project->commissionPolicy?->version,
                    'policy_code' => $project->commissionPolicy?->policy_code,
                    'effective_policy_date' => $startDate ?? $project->created_at?->toDateString() ?? $effectiveDate,
                ];
            }
        }

        // Backwards-compatibility check for existing projects created with custom agreed_commission_rate > 0
        if ($project->agreed_commission_rate !== null && (float) $project->agreed_commission_rate > 0 && $project->custom_commission_rate === null) {
            $type = $project->agreed_commission_type ?? CommissionType::Percentage;
            $rate = (float) $project->agreed_commission_rate;

            return [
                'source' => 'project_override',
                'commission_type' => $type,
                'rate_applied' => $rate,
                'fixed_frequency' => null,
                'policy_id' => null,
                'policy_version' => null,
                'policy_code' => null,
                'effective_policy_date' => $project->created_at?->toDateString() ?? $effectiveDate,
            ];
        }

        // 2. Resolve Partner Applicable Default Policy or Global Policy Fallback
        $partner = $project->partner ?? ($project->partner_id ? Partner::find($project->partner_id) : null);
        $partnerPolicy = $this->resolveDefaultPolicyForPartner($partner, $effectiveDate);

        return [
            'source' => $partnerPolicy['source'],
            'commission_type' => $partnerPolicy['commission_type'],
            'rate_applied' => $partnerPolicy['rate_applied'],
            'fixed_frequency' => $partnerPolicy['fixed_frequency'],
            'policy_id' => $partnerPolicy['policy_id'],
            'policy_version' => $partnerPolicy['policy_version'],
            'policy_code' => $partnerPolicy['policy_code'],
            'effective_policy_date' => $partnerPolicy['effective_date'] ?? $effectiveDate,
        ];
    }

    /**
     * Configure or update project-wise commission settings.
     */
    public function configureProjectCommission(Project $project, array $data, int $adminUserId): Project
    {
        return DB::transaction(function () use ($project, $data, $adminUserId) {
            $model = $data['commission_model'] ?? 'global_default';
            $type = !empty($data['custom_commission_type'])
                ? ($data['custom_commission_type'] instanceof CommissionType ? $data['custom_commission_type'] : CommissionType::from($data['custom_commission_type']))
                : null;
            $rate = isset($data['custom_commission_rate']) ? (float) $data['custom_commission_rate'] : null;
            $freq = !empty($data['custom_fixed_frequency'])
                ? ($data['custom_fixed_frequency'] instanceof FixedCommissionFrequency ? $data['custom_fixed_frequency'] : FixedCommissionFrequency::from($data['custom_fixed_frequency']))
                : null;
            $effectiveDate = !empty($data['commission_effective_date']) ? $data['commission_effective_date'] : now()->toDateString();
            $status = $data['commission_status'] ?? 'active';
            $policyId = !empty($data['commission_policy_id']) ? (int) $data['commission_policy_id'] : null;
            $notes = $data['commission_notes'] ?? null;

            $oldValues = [
                'commission_model' => $project->commission_model,
                'custom_commission_type' => $project->custom_commission_type?->value,
                'custom_commission_rate' => $project->custom_commission_rate,
                'custom_fixed_frequency' => $project->custom_fixed_frequency?->value,
                'commission_status' => $project->commission_status,
            ];

            $updateData = [
                'commission_model' => $model,
                'custom_commission_type' => $type,
                'custom_commission_rate' => $rate,
                'custom_fixed_frequency' => $freq,
                'commission_effective_date' => $effectiveDate,
                'commission_status' => $status,
                'commission_policy_id' => $policyId,
                'commission_notes' => $notes,
            ];

            if ($model === 'custom' && $type !== null) {
                $updateData['agreed_commission_type'] = $type;
                if ($rate !== null && $type === CommissionType::Percentage) {
                    $updateData['agreed_commission_rate'] = min(100.00, $rate);
                } elseif ($rate !== null && $type === CommissionType::Fixed && $rate <= 999.99) {
                    $updateData['agreed_commission_rate'] = $rate;
                }
            } elseif ($model === 'global_default') {
                $partner = $project->partner ?? ($project->partner_id ? Partner::find($project->partner_id) : null);
                $resolved = $this->resolveDefaultPolicyForPartner($partner);
                $updateData['agreed_commission_type'] = $resolved['commission_type'];
                $updateData['agreed_commission_rate'] = $resolved['rate_applied'];
                $updateData['commission_policy_id'] = $resolved['policy_id'];
                $updateData['custom_commission_type'] = null;
                $updateData['custom_commission_rate'] = null;
                $updateData['custom_fixed_frequency'] = null;
                $updateData['commission_notes'] = null;
            }

            $project->update($updateData);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CommissionPolicyUpdated,
                module: \App\Enums\AuditLogModule::Project,
                description: "Commission terms for Project #{$project->project_code} updated by Admin (Model: {$model}).",
                subject: $project,
                oldValues: $oldValues,
                newValues: [
                    'commission_model' => $model,
                    'custom_commission_type' => $type?->value,
                    'custom_commission_rate' => $rate,
                    'custom_fixed_frequency' => $freq?->value,
                    'commission_status' => $status,
                ],
                metadata: ['project_id' => $project->id, 'project_code' => $project->project_code],
                actor: \App\Models\User::find($adminUserId)
            );

            return $project;
        });
    }

    /**
     * Verify a Customer Payment and automatically generate the authoritative Commission.
     * Concurrency-safe, transactional, and idempotent.
     */
    public function verifyPaymentAndGenerateCommission(CustomerPayment $payment, int $adminUserId): ?Commission
    {
        return DB::transaction(function () use ($payment, $adminUserId) {
            // Lock payment row to prevent race conditions or double clicks
            $lockedPayment = CustomerPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status === CustomerPaymentStatus::Rejected) {
                throw new DomainException("Cannot generate commission: Payment #{$lockedPayment->payment_number} is rejected.");
            }

            // Check if commission already linked via unique FK or payment already processed
            if ($lockedPayment->commission_generated || Commission::where('customer_payment_id', $lockedPayment->id)->exists()) {
                throw new DomainException("Commission has already been generated for Payment #{$lockedPayment->payment_number}.");
            }

            if ($lockedPayment->amount <= 0) {
                throw new DomainException("Cannot verify payment with non-positive amount: {$lockedPayment->amount}");
            }

            $project = $lockedPayment->project;
            if (! $project) {
                throw new DomainException("Project associated with payment #{$lockedPayment->payment_number} was not found.");
            }

            // If payment was in recorded state, mark it as verified
            if ($lockedPayment->status !== CustomerPaymentStatus::Verified) {
                $lockedPayment->update([
                    'status'      => CustomerPaymentStatus::Verified,
                    'verified_by' => $adminUserId,
                    'verified_at' => now(),
                ]);
            }

            // Populate billing month / period if absent
            $paymentDate = $lockedPayment->payment_date ?: now();
            if (!$lockedPayment->billing_month) {
                $lockedPayment->billing_month = $paymentDate->format('Y-m');
                $lockedPayment->billing_period = $paymentDate->format('F Y');
                $lockedPayment->save();
            }

            // If no referred partner exists on the payment/project, verify payment without generating partner commission
            $partnerId = $lockedPayment->partner_id ?: $project->partner_id;
            if (! $partnerId) {
                $lockedPayment->update(['commission_generated' => false]);
                return null;
            }

            // Resolve dynamic commission policy hierarchy
            $policy = $this->resolveCommissionPolicyForProject($project, $lockedPayment->payment_date?->toDateString());

            // Check PER_PROJECT fixed frequency constraint: Awarded only once per project
            if ($policy['commission_type'] === CommissionType::Fixed && $policy['fixed_frequency'] === FixedCommissionFrequency::PerProject) {
                $existingProjectCommission = Commission::where('project_id', $project->id)
                    ->where('commission_frequency', FixedCommissionFrequency::PerProject->value)
                    ->where('status', '!=', CommissionStatus::Cancelled)
                    ->first();

                if ($existingProjectCommission) {
                    $note = trim(($lockedPayment->notes ?? '') . "\nPer-project fixed commission already awarded on Commission #{$existingProjectCommission->commission_number}.");
                    $lockedPayment->update([
                        'commission_generated' => false,
                        'notes' => $note,
                    ]);

                    app(\App\Services\AuditLogService::class)->record(
                        action: \App\Enums\AuditLogAction::CustomerPaymentVerified,
                        module: \App\Enums\AuditLogModule::Financial,
                        description: "Customer Payment #{$lockedPayment->payment_number} verified (Per-project commission previously awarded on #{$existingProjectCommission->commission_number}).",
                        subject: $lockedPayment,
                        oldValues: ['status' => CustomerPaymentStatus::Recorded->value],
                        newValues: ['status' => CustomerPaymentStatus::Verified->value],
                        metadata: ['amount' => $lockedPayment->amount, 'existing_commission_id' => $existingProjectCommission->id],
                        actor: \App\Models\User::find($adminUserId)
                    );

                    return $existingProjectCommission;
                }
            }

            // Calculate commission amount
            $calculationBasis = (float) $lockedPayment->amount;
            $rateApplied = (float) $policy['rate_applied'];

            $amount = match ($policy['commission_type']) {
                CommissionType::Percentage => round(($calculationBasis * $rateApplied) / 100, 2),
                CommissionType::Fixed => match ($policy['fixed_frequency']) {
                    FixedCommissionFrequency::PerPayment,
                    FixedCommissionFrequency::PerProject,
                    FixedCommissionFrequency::PerMilestone => round($rateApplied, 2),
                    default => round($project->final_deal_value > 0
                        ? $rateApplied * ($calculationBasis / (float) $project->final_deal_value)
                        : $rateApplied, 2),
                },
            };

            // Concurrency-safe milestone sequence per project
            $nextSequence = (int) (Commission::where('project_id', $project->id)->max('milestone_sequence') ?? 0) + 1;
            $commissionNumber = 'COM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            // Formulate descriptive milestone label including billing period/month
            $milestoneName = 'Customer Payment: ' . ($lockedPayment->payment_type?->label() ?? 'Payment');
            if ($lockedPayment->billing_period || $lockedPayment->billing_month) {
                $milestoneName .= ' (' . ($lockedPayment->billing_period ?? $lockedPayment->billing_month) . ')';
            }

            $commission = Commission::create([
                'commission_number'     => $commissionNumber,
                'project_id'            => $project->id,
                'partner_id'            => $partnerId,
                'customer_payment_id'   => $lockedPayment->id,
                'payout_id'             => null,
                'milestone_name'        => $milestoneName,
                'milestone_sequence'    => $nextSequence,
                'commission_type'       => $policy['commission_type'],
                'policy_source'         => $policy['source'],
                'commission_policy_id'  => $policy['policy_id'],
                'policy_version'        => $policy['policy_version'],
                'policy_code'           => $policy['policy_code'],
                'commission_frequency'  => $policy['fixed_frequency'],
                'billing_month'         => $lockedPayment->billing_month,
                'billing_period'        => $lockedPayment->billing_period,
                'effective_policy_date' => $policy['effective_policy_date'],
                'calculated_at'         => now(),
                'calculation_basis'     => $calculationBasis,
                'rate_applied'          => $rateApplied,
                'amount'                => $amount,
                'currency'              => $lockedPayment->currency ?? 'INR',
                'status'                => CommissionStatus::Payable,
                'approved_at'           => now(),
                'approved_by'           => $adminUserId,
                'payable_at'            => now(),
                'notes'                 => "Generated from verified customer payment #{$lockedPayment->payment_number} via {$policy['source']} (" . ($policy['commission_type'] === CommissionType::Percentage ? "{$rateApplied}% of " . number_format($calculationBasis, 2) : "₹" . number_format($rateApplied, 2) . " fixed") . ")",
            ]);

            $lockedPayment->update([
                'commission_generated' => true,
            ]);

            // Sync monthly payment schedule if applicable
            $schedule = $lockedPayment->paymentSchedule
                ?? \App\Models\ProjectPaymentSchedule::where('project_id', $project->id)
                    ->where('billing_month', $lockedPayment->billing_month)
                    ->first();

            if ($schedule) {
                if (! $lockedPayment->payment_schedule_id) {
                    $lockedPayment->payment_schedule_id = $schedule->id;
                    $lockedPayment->save();
                }
                app(PaymentScheduleService::class)->syncScheduleStatus($schedule);
            }

            Log::info('Payment verified and commission generated', [
                'payment_id'        => $lockedPayment->id,
                'payment_number'    => $lockedPayment->payment_number,
                'commission_id'     => $commission->id,
                'commission_number' => $commission->commission_number,
                'amount'            => $commission->amount,
                'rate_applied'      => $rateApplied,
                'policy_source'     => $policy['source'],
                'partner_id'        => $partnerId,
                'admin_id'          => $adminUserId,
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CustomerPaymentVerified,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Customer Payment #{$lockedPayment->payment_number} for ₹" . number_format($lockedPayment->amount, 2) . " verified by Admin.",
                subject: $lockedPayment,
                oldValues: ['status' => CustomerPaymentStatus::Recorded->value],
                newValues: ['status' => CustomerPaymentStatus::Verified->value],
                metadata: ['amount' => $lockedPayment->amount, 'commission_id' => $commission->id],
                actor: \App\Models\User::find($adminUserId)
            );

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CommissionGenerated,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Commission #{$commission->commission_number} of ₹" . number_format($commission->amount, 2) . " generated for Partner.",
                subject: $commission,
                newValues: ['amount' => $commission->amount, 'status' => $commission->status->value],
                metadata: [
                    'payment_id' => $lockedPayment->id,
                    'rate' => $rateApplied,
                    'policy_source' => $policy['source'],
                    'policy_version' => $policy['policy_version'],
                    'billing_month' => $lockedPayment->billing_month,
                ],
                actor: \App\Models\User::find($adminUserId)
            );

            return $commission;
        });
    }

    /**
     * Backward-compatible helper for existing Phase 1/2 tests.
     */
    public function generateFromCustomerPayment(CustomerPayment $payment, int $adminUserId): Commission
    {
        return $this->verifyPaymentAndGenerateCommission($payment, $adminUserId);
    }

    /**
     * Reject a Customer Payment with mandatory reason.
     * Generates ZERO commission.
     */
    public function rejectPayment(CustomerPayment $payment, string $reason, int $adminUserId): CustomerPayment
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException("A rejection reason is mandatory when rejecting a payment.");
        }

        return DB::transaction(function () use ($payment, $reason, $adminUserId) {
            $locked = CustomerPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === CustomerPaymentStatus::Verified && $locked->commission_generated) {
                throw new DomainException("Cannot directly reject an already verified payment with generated commission. Use reversal process instead.");
            }

            $locked->update([
                'status'               => CustomerPaymentStatus::Rejected,
                'rejection_reason'     => trim($reason),
                'verified_by'          => $adminUserId,
                'verified_at'          => now(),
                'commission_generated' => false,
            ]);

            Log::warning('Payment rejected by admin', [
                'payment_id'     => $locked->id,
                'payment_number' => $locked->payment_number,
                'admin_id'       => $adminUserId,
                'reason'         => $reason,
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CustomerPaymentRejected,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Customer Payment #{$locked->payment_number} rejected by Admin. Reason: {$reason}",
                subject: $locked,
                oldValues: ['status' => $payment->status->value],
                newValues: ['status' => CustomerPaymentStatus::Rejected->value],
                metadata: ['reason' => $reason],
                actor: \App\Models\User::find($adminUserId)
            );

            return $locked;
        });
    }

    /**
     * Reverse a verified Customer Payment and safely adjust the associated Commission.
     */
    public function reverseVerifiedPayment(CustomerPayment $payment, string $reason, int $adminUserId): CustomerPayment
    {
        if (trim($reason) === '') {
            throw new InvalidArgumentException("A reversal reason is mandatory.");
        }

        return DB::transaction(function () use ($payment, $reason, $adminUserId) {
            $lockedPayment = CustomerPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== CustomerPaymentStatus::Verified) {
                throw new DomainException("Only verified payments can be reversed.");
            }

            $linkedCommission = Commission::where('customer_payment_id', $lockedPayment->id)->first();

            if ($linkedCommission) {
                if ($linkedCommission->status === CommissionStatus::Paid) {
                    // Create an auditable recovery entry for already paid commission
                    $nextSeq = (int) (Commission::where('project_id', $lockedPayment->project_id)->max('milestone_sequence') ?? 0) + 1;
                    $recoveryNumber = 'COM-REC-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

                    $recoveryCommission = Commission::create([
                        'commission_number'     => $recoveryNumber,
                        'project_id'            => $lockedPayment->project_id,
                        'partner_id'            => $linkedCommission->partner_id,
                        'customer_payment_id'   => null,
                        'parent_commission_id'  => $linkedCommission->id,
                        'adjustment_type'       => 'recovery',
                        'milestone_name'        => "Recovery: Reversal of Payment #{$lockedPayment->payment_number}",
                        'milestone_sequence'    => $nextSeq,
                        'commission_type'       => $linkedCommission->commission_type,
                        'policy_source'         => $linkedCommission->policy_source,
                        'commission_policy_id'  => $linkedCommission->commission_policy_id,
                        'policy_version'        => $linkedCommission->policy_version,
                        'policy_code'           => $linkedCommission->policy_code,
                        'commission_frequency'  => $linkedCommission->commission_frequency,
                        'billing_month'         => $linkedCommission->billing_month,
                        'billing_period'        => $linkedCommission->billing_period,
                        'calculation_basis'     => -$linkedCommission->calculation_basis,
                        'rate_applied'          => $linkedCommission->rate_applied,
                        'amount'                => -$linkedCommission->amount,
                        'currency'              => $linkedCommission->currency ?? 'INR',
                        'status'                => CommissionStatus::Payable,
                        'approved_at'           => now(),
                        'approved_by'           => $adminUserId,
                        'payable_at'            => now(),
                        'notes'                 => "Recovery debit for already-paid Commission #{$linkedCommission->commission_number} due to Customer Payment #{$lockedPayment->payment_number} reversal. Reason: {$reason}",
                    ]);

                    $auditNote = trim(($linkedCommission->notes ?? '') . "\n[" . now()->toDateTimeString() . " by User #{$adminUserId}] Reversal Recovery #{$recoveryCommission->commission_number} created: {$reason}");
                    $linkedCommission->update([
                        'notes' => $auditNote,
                    ]);

                    app(\App\Services\AuditLogService::class)->record(
                        action: \App\Enums\AuditLogAction::CommissionAdjusted,
                        module: \App\Enums\AuditLogModule::Financial,
                        description: "Recovery entry #{$recoveryCommission->commission_number} (-₹" . number_format($linkedCommission->amount, 2) . ") created for already paid Commission #{$linkedCommission->commission_number}.",
                        subject: $recoveryCommission,
                        newValues: ['amount' => $recoveryCommission->amount, 'status' => $recoveryCommission->status->value],
                        metadata: ['parent_commission_id' => $linkedCommission->id, 'reason' => $reason],
                        actor: \App\Models\User::find($adminUserId)
                    );
                } else {
                    $oldStatus = $linkedCommission->status->value;
                    $auditNote = trim(($linkedCommission->notes ?? '') . "\n[" . now()->toDateTimeString() . " by User #{$adminUserId}] Reversal: {$reason}");
                    $linkedCommission->update([
                        'status' => CommissionStatus::Cancelled,
                        'notes'  => $auditNote,
                    ]);

                    app(\App\Services\AuditLogService::class)->record(
                        action: \App\Enums\AuditLogAction::CommissionAdjusted,
                        module: \App\Enums\AuditLogModule::Financial,
                        description: "Commission #{$linkedCommission->commission_number} status adjusted to Cancelled due to payment reversal. Reason: {$reason}",
                        subject: $linkedCommission,
                        oldValues: ['status' => $oldStatus],
                        newValues: ['status' => CommissionStatus::Cancelled->value],
                        metadata: ['reason' => $reason],
                        actor: \App\Models\User::find($adminUserId)
                    );
                }
            }

            $paymentAuditNote = trim(($lockedPayment->notes ?? '') . "\n[" . now()->toDateTimeString() . " by User #{$adminUserId}] Payment Reversed: {$reason}");

            $lockedPayment->update([
                'status'               => CustomerPaymentStatus::Rejected,
                'rejection_reason'     => "Payment Reversed: {$reason}",
                'commission_generated' => false,
                'notes'                => $paymentAuditNote,
            ]);

            // Sync monthly payment schedule if applicable
            $schedule = $lockedPayment->paymentSchedule
                ?? \App\Models\ProjectPaymentSchedule::where('project_id', $lockedPayment->project_id)
                    ->where('billing_month', $lockedPayment->billing_month)
                    ->first();

            if ($schedule) {
                app(PaymentScheduleService::class)->syncScheduleStatus($schedule);
            }

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CustomerPaymentReversed,
                module: \App\Enums\AuditLogModule::Financial,
                description: "Verified Customer Payment #{$lockedPayment->payment_number} reversed by Admin. Reason: {$reason}",
                subject: $lockedPayment,
                oldValues: ['status' => CustomerPaymentStatus::Verified->value],
                newValues: ['status' => CustomerPaymentStatus::Rejected->value],
                metadata: ['reason' => $reason],
                actor: \App\Models\User::find($adminUserId)
            );

            return $lockedPayment;
        });
    }

    /**
     * Proportional partial refund adjustment for a customer payment.
     */
    public function adjustCommissionForPartialRefund(
        CustomerPayment $payment,
        float $refundAmount,
        string $reason,
        int $adminUserId
    ): Commission {
        if ($refundAmount <= 0) {
            throw new InvalidArgumentException("Refund amount must be positive.");
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException("An adjustment reason is mandatory.");
        }

        return DB::transaction(function () use ($payment, $refundAmount, $reason, $adminUserId) {
            $lockedPayment = CustomerPayment::where('id', $payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== CustomerPaymentStatus::Verified) {
                throw new DomainException("Only verified payments can have commissions adjusted.");
            }

            if ($refundAmount > (float) $lockedPayment->amount) {
                throw new DomainException("Refund amount (₹{$refundAmount}) cannot exceed original payment amount (₹{$lockedPayment->amount}).");
            }

            $linkedCommission = Commission::where('customer_payment_id', $lockedPayment->id)->first();
            if (! $linkedCommission) {
                throw new DomainException("No commission found linked to Payment #{$lockedPayment->payment_number}.");
            }

            $ratio = $refundAmount / (float) $lockedPayment->amount;
            $reduction = round((float) $linkedCommission->amount * $ratio, 2);

            if ($linkedCommission->status === CommissionStatus::Paid) {
                // Already paid: create recovery debit entry
                $nextSeq = (int) (Commission::where('project_id', $lockedPayment->project_id)->max('milestone_sequence') ?? 0) + 1;
                $recoveryNumber = 'COM-ADJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

                $adjustment = Commission::create([
                    'commission_number'     => $recoveryNumber,
                    'project_id'            => $lockedPayment->project_id,
                    'partner_id'            => $linkedCommission->partner_id,
                    'customer_payment_id'   => null,
                    'parent_commission_id'  => $linkedCommission->id,
                    'adjustment_type'       => 'adjustment',
                    'milestone_name'        => "Partial Refund Recovery: Payment #{$lockedPayment->payment_number}",
                    'milestone_sequence'    => $nextSeq,
                    'commission_type'       => $linkedCommission->commission_type,
                    'policy_source'         => $linkedCommission->policy_source,
                    'commission_policy_id'  => $linkedCommission->commission_policy_id,
                    'policy_version'        => $linkedCommission->policy_version,
                    'policy_code'           => $linkedCommission->policy_code,
                    'commission_frequency'  => $linkedCommission->commission_frequency,
                    'billing_month'         => $linkedCommission->billing_month,
                    'billing_period'        => $linkedCommission->billing_period,
                    'calculation_basis'     => -$refundAmount,
                    'rate_applied'          => $linkedCommission->rate_applied,
                    'amount'                => -$reduction,
                    'currency'              => $linkedCommission->currency ?? 'INR',
                    'status'                => CommissionStatus::Payable,
                    'approved_at'           => now(),
                    'approved_by'           => $adminUserId,
                    'payable_at'            => now(),
                    'notes'                 => "Proportional partial refund adjustment (-₹" . number_format($reduction, 2) . ") for already paid Commission #{$linkedCommission->commission_number}. Reason: {$reason}",
                ]);

                $linkedCommission->update([
                    'notes' => trim(($linkedCommission->notes ?? '') . "\n[" . now()->toDateTimeString() . " by User #{$adminUserId}] Partial Refund Recovery #{$adjustment->commission_number} (-₹{$reduction}) created."),
                ]);

                app(\App\Services\AuditLogService::class)->record(
                    action: \App\Enums\AuditLogAction::CommissionAdjusted,
                    module: \App\Enums\AuditLogModule::Financial,
                    description: "Partial refund recovery #{$adjustment->commission_number} of -₹" . number_format($reduction, 2) . " created for Commission #{$linkedCommission->commission_number}.",
                    subject: $adjustment,
                    newValues: ['amount' => $adjustment->amount, 'status' => $adjustment->status->value],
                    metadata: ['parent_commission_id' => $linkedCommission->id, 'refund_amount' => $refundAmount, 'reason' => $reason],
                    actor: \App\Models\User::find($adminUserId)
                );

                return $adjustment;
            } else {
                // Unpaid: proportionally reduce payable balance
                $oldAmount = $linkedCommission->amount;
                $newAmount = max(0.00, round((float) $linkedCommission->amount - $reduction, 2));
                $newBasis = max(0.00, round((float) $linkedCommission->calculation_basis - $refundAmount, 2));

                $linkedCommission->update([
                    'amount'            => $newAmount,
                    'calculation_basis' => $newBasis,
                    'notes'             => trim(($linkedCommission->notes ?? '') . "\n[" . now()->toDateTimeString() . " by User #{$adminUserId}] Partial refund adjustment: -₹" . number_format($reduction, 2) . ". Reason: {$reason}"),
                ]);

                app(\App\Services\AuditLogService::class)->record(
                    action: \App\Enums\AuditLogAction::CommissionAdjusted,
                    module: \App\Enums\AuditLogModule::Financial,
                    description: "Commission #{$linkedCommission->commission_number} adjusted from ₹" . number_format($oldAmount, 2) . " to ₹" . number_format($newAmount, 2) . " due to partial refund of ₹" . number_format($refundAmount, 2) . ". Reason: {$reason}",
                    subject: $linkedCommission,
                    oldValues: ['amount' => $oldAmount],
                    newValues: ['amount' => $newAmount],
                    metadata: ['refund_amount' => $refundAmount, 'reason' => $reason],
                    actor: \App\Models\User::find($adminUserId)
                );

                return $linkedCommission;
            }
        });
    }

    /**
     * Create an authoritative commission entry for a project milestone.
     */
    public function createMilestoneCommission(
        Project $project,
        string $milestoneName,
        int $milestoneSequence,
        float $calculationBasis,
        float $rateApplied,
        CommissionType $commissionType,
        int $adminUserId,
        ?string $notes = null
    ): Commission {
        return DB::transaction(function () use (
            $project,
            $milestoneName,
            $milestoneSequence,
            $calculationBasis,
            $rateApplied,
            $commissionType,
            $notes
        ) {
            $exists = Commission::where('project_id', $project->id)
                ->where('milestone_sequence', $milestoneSequence)
                ->exists();

            if ($exists) {
                throw new DomainException("Milestone sequence {$milestoneSequence} already exists for Project #{$project->project_code}.");
            }

            $amount = match ($commissionType) {
                CommissionType::Percentage => round(($calculationBasis * $rateApplied) / 100, 2),
                CommissionType::Fixed => round($rateApplied, 2),
            };

            $commissionNumber = 'COM-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

            return Commission::create([
                'commission_number'  => $commissionNumber,
                'project_id'         => $project->id,
                'partner_id'         => $project->partner_id,
                'payout_id'          => null,
                'milestone_name'     => $milestoneName,
                'milestone_sequence' => $milestoneSequence,
                'commission_type'    => $commissionType,
                'calculation_basis'  => $calculationBasis,
                'rate_applied'       => $rateApplied,
                'amount'             => $amount,
                'currency'           => $project->currency ?? 'INR',
                'status'             => CommissionStatus::Pending,
                'approved_at'        => null,
                'approved_by'        => null,
                'payable_at'         => null,
                'notes'              => $notes,
            ]);
        });
    }

    /**
     * Approve a pending commission.
     */
    public function approveCommission(Commission $commission, int $adminUserId): Commission
    {
        if ($commission->status !== CommissionStatus::Pending) {
            throw new DomainException("Only pending commissions can be approved (current: {$commission->status->label()}).");
        }

        $commission->update([
            'status'      => CommissionStatus::Approved,
            'approved_at' => now(),
            'approved_by' => $adminUserId,
        ]);

        return $commission;
    }

    /**
     * Unlock commission to payable when owner confirms client payment received.
     */
    public function markPayable(Commission $commission): Commission
    {
        if (! in_array($commission->status, [CommissionStatus::Pending, CommissionStatus::Approved], true)) {
            throw new DomainException("Commission cannot be marked payable from current status: {$commission->status->label()}.");
        }

        $commission->update([
            'status'     => CommissionStatus::Payable,
            'payable_at' => now(),
        ]);

        return $commission;
    }

    /**
     * Cancel an unpaid commission.
     */
    public function cancelCommission(Commission $commission, string $reason): Commission
    {
        if ($commission->status === CommissionStatus::Paid) {
            throw new DomainException("Paid commissions are immutable and cannot be cancelled.");
        }

        if ($commission->payout_id !== null) {
            throw new DomainException("Cannot cancel a commission currently attached to a payout.");
        }

        $notes = trim(($commission->notes ?? '') . "\n[" . now()->toDateTimeString() . "] Cancelled: {$reason}");

        $oldStatus = $commission->status->value;

        $commission->update([
            'status' => CommissionStatus::Cancelled,
            'notes'  => $notes,
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::CommissionAdjusted,
            module: \App\Enums\AuditLogModule::Financial,
            description: "Commission #{$commission->commission_number} status adjusted to Cancelled. Reason: {$reason}",
            subject: $commission,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => CommissionStatus::Cancelled->value],
            metadata: ['reason' => $reason]
        );

        return $commission;
    }
}
