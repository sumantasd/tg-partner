<?php

namespace App\Services;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Partner;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Generate unique sequential customer code (e.g. CUS-2026-0001).
     */
    public function generateCustomerCode(): string
    {
        $year = date('Y');
        $prefix = "CUS-{$year}-";

        $latest = Customer::withTrashed()
            ->where('customer_code', 'like', "{$prefix}%")
            ->orderByRaw('CAST(SUBSTRING(customer_code, 10) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        if ($latest && preg_match('/^CUS-\d{4}-(\d+)$/', $latest->customer_code, $matches)) {
            $nextSeq = (int) $matches[1] + 1;
        } else {
            $nextSeq = 1;
        }

        return sprintf('%s%04d', $prefix, $nextSeq);
    }

    /**
     * Create a Customer directly by Admin without requiring a prior Won Lead.
     * Optionally attributes referral to a Partner, or keeps null if direct.
     * Note: Customer creation never prematurely generates commission.
     */
    public function createCustomer(array $customerData, User $creator): Customer
    {
        return DB::transaction(function () use ($customerData, $creator) {
            $partnerId = !empty($customerData['first_partner_id']) ? (int) $customerData['first_partner_id'] : null;

            if ($partnerId !== null) {
                // Ensure partner exists
                Partner::findOrFail($partnerId);
            }

            $customerCode = $this->generateCustomerCode();

            $customer = Customer::create([
                'customer_code'    => $customerCode,
                'name'             => trim($customerData['name']),
                'company_name'     => !empty($customerData['company_name']) ? trim($customerData['company_name']) : null,
                'phone'            => trim($customerData['phone']),
                'whatsapp_number'  => !empty($customerData['whatsapp_number']) ? trim($customerData['whatsapp_number']) : null,
                'email'            => !empty($customerData['email']) ? trim($customerData['email']) : null,
                'address_line1'    => !empty($customerData['address_line1']) ? trim($customerData['address_line1']) : null,
                'city'             => !empty($customerData['city']) ? trim($customerData['city']) : null,
                'state'            => !empty($customerData['state']) ? trim($customerData['state']) : null,
                'postal_code'      => !empty($customerData['postal_code']) ? trim($customerData['postal_code']) : null,
                'country'          => !empty($customerData['country']) ? trim($customerData['country']) : 'India',
                'first_partner_id' => $partnerId,
                'lead_id'          => null, // Direct customer creation
                'created_by'       => $creator->id,
                'status'           => $customerData['status'] ?? 'active',
                'notes'            => !empty($customerData['notes']) ? trim($customerData['notes']) : null,
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::CustomerCreated,
                module: \App\Enums\AuditLogModule::Customer,
                description: "Customer #{$customer->customer_code} ({$customer->name}) created directly by Admin {$creator->name}.",
                subject: $customer,
                newValues: [
                    'customer_code' => $customer->customer_code,
                    'name' => $customer->name,
                    'company_name' => $customer->company_name,
                    'phone' => $customer->phone,
                    'first_partner_id' => $customer->first_partner_id,
                ],
                actor: $creator
            );

            return $customer;
        });
    }

    /**
     * Update customer profile details.
     * Authoritative referral partner attribution is permanently protected.
     */
    public function updateCustomer(Customer $customer, array $customerData): Customer
    {
        $oldAttributes = $customer->getAttributes();

        $customer->update([
            'name'            => trim($customerData['name']),
            'company_name'    => !empty($customerData['company_name']) ? trim($customerData['company_name']) : null,
            'phone'           => trim($customerData['phone']),
            'whatsapp_number' => !empty($customerData['whatsapp_number']) ? trim($customerData['whatsapp_number']) : null,
            'email'           => !empty($customerData['email']) ? trim($customerData['email']) : null,
            'address_line1'   => !empty($customerData['address_line1']) ? trim($customerData['address_line1']) : null,
            'city'            => !empty($customerData['city']) ? trim($customerData['city']) : null,
            'state'           => !empty($customerData['state']) ? trim($customerData['state']) : null,
            'postal_code'     => !empty($customerData['postal_code']) ? trim($customerData['postal_code']) : null,
            'country'         => !empty($customerData['country']) ? trim($customerData['country']) : 'India',
            'status'          => $customerData['status'] ?? $customer->status,
            'notes'           => !empty($customerData['notes']) ? trim($customerData['notes']) : null,
        ]);

        app(\App\Services\AuditLogService::class)->recordModelUpdate(
            model: $customer,
            oldAttributes: $oldAttributes,
            action: \App\Enums\AuditLogAction::CustomerUpdated,
            module: \App\Enums\AuditLogModule::Customer,
            description: "Customer #{$customer->customer_code} ({$customer->name}) updated."
        );

        return $customer;
    }

    /**
     * Convert an eligible Won Lead into a full Customer.
     * Guaranteed atomic rollback and audit trail.
     */
    public function convertLeadToCustomer(Lead $lead, array $customerData, User $admin): Customer
    {
        return DB::transaction(function () use ($lead, $customerData, $admin) {
            // Lock lead to prevent concurrent conversion attempts
            $lockedLead = Lead::where('id', $lead->id)->lockForUpdate()->firstOrFail();

            if ($lockedLead->status !== LeadStatus::Won) {
                throw new DomainException("Cannot convert lead: Lead #{$lockedLead->lead_number} must be in Won status (current: {$lockedLead->status->label()}).");
            }

            if ($lockedLead->converted_customer_id !== null) {
                throw new DomainException("Cannot convert lead: Lead #{$lockedLead->lead_number} has already been converted to Customer ID #{$lockedLead->converted_customer_id}.");
            }

            $customerCode = $this->generateCustomerCode();

            $customer = Customer::create([
                'customer_code'    => $customerCode,
                'name'             => trim($customerData['name'] ?? $lockedLead->customer_name),
                'company_name'     => trim($customerData['company_name'] ?? $lockedLead->customer_company),
                'phone'            => trim($customerData['phone'] ?? $lockedLead->customer_phone),
                'whatsapp_number'  => trim($customerData['whatsapp_number'] ?? null),
                'email'            => trim($customerData['email'] ?? $lockedLead->customer_email),
                'address_line1'    => trim($customerData['address_line1'] ?? null),
                'city'             => trim($customerData['city'] ?? $lockedLead->city),
                'state'            => trim($customerData['state'] ?? null),
                'postal_code'      => trim($customerData['postal_code'] ?? null),
                'country'          => trim($customerData['country'] ?? 'India'),
                'first_partner_id' => $lockedLead->partner_id, // Authoritative partner attribution preserved
                'lead_id'          => $lockedLead->id,
                'created_by'       => $admin->id,
                'status'           => 'active',
                'notes'            => trim($customerData['notes'] ?? "Converted from Won Lead #{$lockedLead->lead_number}"),
            ]);

            // Update lead conversion state
            $lockedLead->update([
                'converted_customer_id' => $customer->id,
                'converted_at'          => now(),
            ]);

            // Record conversion in canonical lead activity timeline
            LeadActivity::create([
                'lead_id'       => $lockedLead->id,
                'user_id'       => $admin->id,
                'activity_type' => LeadActivityType::StatusChange,
                'summary'       => "Converted to Customer #{$customer->customer_code}",
                'details'       => "Customer record created for '{$customer->name}'. Original referral partner attribution preserved.",
                'is_internal'   => false, // Partner is entitled to see that their referral was converted
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LeadConvertedToCustomer,
                module: \App\Enums\AuditLogModule::Customer,
                description: "Lead #{$lockedLead->lead_number} successfully converted to Customer #{$customer->customer_code} ({$customer->name}) by Admin {$admin->name}.",
                subject: $customer,
                metadata: ['lead_id' => $lockedLead->id, 'lead_number' => $lockedLead->lead_number],
                actor: $admin
            );

            return $customer;
        });
    }
}
