# TG Partner Program — Batch 8 Implementation Report
## Monthly-wise and Project-wise Dynamic Commission System

**Project:** TG Partner Program  
**Framework:** Laravel 12 (PHP 8.4)  
**Timezone:** Asia/Kolkata (IST)  
**Currency:** INR (₹)  
**Branding Rule:** Strictly "Tech Googly"  
**Completion Date:** 2026-11-24  

---

## 1. Executive Summary

Batch 8 implements a high-precision, transactionally safe, auditable **Monthly-wise and Project-wise Dynamic Commission System** for the TG Partner Program. The architecture guarantees historical snapshot immutability, project-level policy overrides over global policies, recurring monthly commission generation, one-time fixed project frequency guards, robust payment reversals with partner recovery debits, and zero cross-partner data leakage.

All 22 dedicated test scenarios in `Batch8DynamicCommissionTest` pass with 100% success (92 assertions). The full system regression suite of 343 tests across all 8 batches passes with zero errors and zero failures (1,842 assertions).

---

## 2. Files Created and Modified

### 2.1 Database & Migrations
- [`database/migrations/2026_11_24_000009_add_dynamic_commission_fields_to_tables.php`](file:///c:/Projects/TG-Partner-Program/database/migrations/2026_11_24_000009_add_dynamic_commission_fields_to_tables.php):
  - **`projects` table**: Added `commission_model` (`global_default` / `custom`, indexed), `custom_commission_type` (`percentage` / `fixed`), `custom_commission_rate`, `custom_fixed_frequency` (`per_payment` / `per_project` / `per_milestone`), `commission_effective_date`, `commission_status` (`active` / `inactive`), `commission_policy_id` (foreign key to `commission_policies`), and `commission_notes`.
  - **`customer_payments` table**: Added `billing_month` (e.g. `'2026-11'`, indexed) and `billing_period` (e.g. `'November 2026'`).
  - **`commissions` table**: Added `policy_source` (`global_policy` / `project_override`, indexed), `commission_policy_id` (nullable FK), `policy_version`, `policy_code`, `commission_frequency`, `billing_month` (indexed), `billing_period`, `effective_policy_date`, `calculated_at`, `parent_commission_id` (self-referencing FK for adjustments), and `adjustment_type` (`reversal` / `recovery` / `adjustment`). Safe indexed rollback in `down()`.

### 2.2 Models & Enums
- [`app/Enums/CustomerPaymentType.php`](file:///c:/Projects/TG-Partner-Program/app/Enums/CustomerPaymentType.php): Added `Monthly = 'monthly'` alongside `Advance`, `Milestone`, and `Full`.
- [`app/Enums/AuditLogAction.php`](file:///c:/Projects/TG-Partner-Program/app/Enums/AuditLogAction.php): Added `case CommissionPolicyUpdated = 'commission_policy_updated'`.
- [`app/Models/Project.php`](file:///c:/Projects/TG-Partner-Program/app/Models/Project.php): Added dynamic commission fillable fields, casts (`commission_effective_date` => `date`, `custom_commission_type` => `CommissionType`, `custom_fixed_frequency` => `FixedCommissionFrequency`), `commissionPolicy()` relation, and `hasCustomCommissionOverride()` backwards-compatible helper.
- [`app/Models/CustomerPayment.php`](file:///c:/Projects/TG-Partner-Program/app/Models/CustomerPayment.php): Added `billing_month` and `billing_period` to `$fillable`.
- [`app/Models/Commission.php`](file:///c:/Projects/TG-Partner-Program/app/Models/Commission.php): Added dynamic snapshot fields to `$fillable`, date/decimal casts, `commissionPolicy()` relation, `parentCommission()` self-relation, and `adjustments()` relation.

### 2.3 Services & Business Logic
- [`app/Services/CommissionService.php`](file:///c:/Projects/TG-Partner-Program/app/Services/CommissionService.php):
  - `resolveCommissionPolicyForProject(Project $project, ?string $effectiveDate = null)`: Resolves active project override vs. global default policy (`POL-DEF-V1`), ensuring strict precedence and fallback safety.
  - `configureProjectCommission(Project $project, array $data, int $adminUserId)`: Transactionally updates project commission settings and records comprehensive audit diffs.
  - `verifyPaymentAndGenerateCommission(CustomerPayment $payment, int $adminUserId)`: Row-level pessimistic locking (`lockForUpdate`), duplicate prevention via unique constraints, historical snapshot population, monthly payment support, and `per_project` frequency one-time guard.
  - `reverseVerifiedPayment(CustomerPayment $payment, string $reason, int $adminUserId)`: Distinguishes unpaid commission cancellation from already-paid commission recovery, creating auditable recovery debit entries (`amount = -$commission->amount`, `adjustment_type = 'recovery'`).
  - `adjustCommissionForPartialRefund(CustomerPayment $payment, float $refundAmount, string $reason, int $adminUserId)`: Proportional commission recalculation or recovery debit creation for partial customer refunds.
- [`app/Services/ProjectService.php`](file:///c:/Projects/TG-Partner-Program/app/Services/ProjectService.php): Updated `createProject()` to accept dynamic commission terms.

### 2.4 Controllers & Routes
- [`app/Http/Controllers/Admin/ProjectController.php`](file:///c:/Projects/TG-Partner-Program/app/Http/Controllers/Admin/ProjectController.php): Updated `create`, `store`, `edit`, `update`, and `show` to configure, audit, and display dynamic commission terms and policy history.
- [`app/Http/Controllers/Admin/PaymentController.php`](file:///c:/Projects/TG-Partner-Program/app/Http/Controllers/Admin/PaymentController.php): Updated `store`, `update`, and added `partialRefund()` action.
- [`routes/web.php`](file:///c:/Projects/TG-Partner-Program/routes/web.php): Added `POST /admin/payments/{payment}/partial-refund` (`payments.partial-refund`).
- [`app/Http/Controllers/Admin/CommissionController.php`](file:///c:/Projects/TG-Partner-Program/app/Http/Controllers/Admin/CommissionController.php): Implemented multi-dimensional filtering (project, partner, customer, billing month, status, type, date range) and summary tabs (`ledger`, `project_summary`, `partner_summary`, `adjustments`).
- [`app/Http/Controllers/Partner/CommissionController.php`](file:///c:/Projects/TG-Partner-Program/app/Http/Controllers/Partner/CommissionController.php): Added `billing_month` filter and loaded snapshot relations, strictly scoped to authenticated partner.

### 2.5 Blade Views
- [`resources/views/admin/projects/create.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/admin/projects/create.blade.php): Added Commission Policy & Terms radio options, custom type, custom rate/fixed amount, frequency, effective date, status, notes.
- [`resources/views/admin/projects/edit.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/admin/projects/edit.blade.php): Added Commission Policy & Terms fields pre-filled with existing project values.
- [`resources/views/admin/projects/show.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/admin/projects/show.blade.php): Added Dynamic Commission Terms & Policy Hierarchy card and updated commissions table with Billing Period & Policy Source.
- [`resources/views/admin/commissions/index.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/admin/commissions/index.blade.php): Added tabbed views (`ledger`, `project_summary`, `partner_summary`, `adjustments`), multi-filter form, and updated table columns.
- [`resources/views/admin/commissions/show.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/admin/commissions/show.blade.php): Updated with policy source, billing period, calculation snapshot, and adjustments.
- [`resources/views/partner/commissions/index.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/partner/commissions/index.blade.php): Added billing month filter, project/customer reference, eligible payment basis, and rate/type.
- [`resources/views/partner/commissions/show.blade.php`](file:///c:/Projects/TG-Partner-Program/resources/views/partner/commissions/show.blade.php): Updated with payment period, customer reference, and statement notes.

### 2.6 Test Suites
- [`tests/Feature/Batch8DynamicCommissionTest.php`](file:///c:/Projects/TG-Partner-Program/tests/Feature/Batch8DynamicCommissionTest.php): 22 targeted test scenarios verifying all Batch 8 requirements.

---

## 3. Architecture & Functional Details

### 3.1 Commission Policy Hierarchy & Precedence
1. **Tier 1: Active Custom Project Override**
   - If a project is configured with `commission_model = 'custom'`, `commission_status = 'active'`, and `commission_effective_date <= payment_date`, the project-level terms strictly govern calculation.
   - Project overrides can be either a customized percentage (e.g. 15%) or a fixed rupee amount (e.g. ₹5,000).
   - Can optionally link to a specific `CommissionPolicy` record for formal governance tracking.
2. **Tier 2: Global Active Commission Policy Fallback**
   - When a project uses `commission_model = 'global_default'`, the system dynamically queries the active global default policy (`is_default = true`, `status = 'active'`) valid for the payment date (e.g. `POL-DEF-V1` at 10.00%).
   - Global policy revisions (e.g., updating default policy to 12%) immediately apply to new payments under global default projects without mutating existing project overrides.
3. **Tier 3: Platform Baseline Fallback**
   - In the event of no matching database policy, the system falls back to `system_settings.default_commission_rate` (10.00%).

### 3.2 Fixed Commission Frequency Behavior
The system supports three distinct fixed commission frequencies:
- `per_payment`: The fixed amount (e.g. ₹2,000) is awarded on every verified payment under the project.
- `per_project`: The fixed amount (e.g. ₹5,000) is awarded strictly ONCE across the entire project lifecycle. When subsequent payments are verified, the system recognizes the existing `per_project` commission, marks the payment as verified, links it, and returns the existing commission without double-crediting.
- `per_milestone`: Fixed commission awarded on each verified milestone payment.

### 3.3 Monthly Recurring Payments & Commission Generation
- Recurring client billing is represented via `payment_type = 'monthly'` with tracked `billing_month` (e.g. `'2026-01'`) and `billing_period` (e.g. `'January 2026'`).
- Each verified monthly payment generates a separate, distinct commission record linked to that exact payment and period.
- For a 12-month retainer, 12 distinct verified payments yield 12 distinct commission entries in the ledger.
- Payments in `recorded` or `rejected` state generate zero commission.

### 3.4 Historical Snapshot Integrity & Immutability
Every generated `Commission` record permanently freezes the calculation snapshot at the moment of verification:
- `policy_source`: `'project_override'` or `'global_policy'`
- `commission_policy_id`: Foreign key to policy (if applicable)
- `policy_version`: Version number at calculation time
- `policy_code`: Policy identifier at calculation time
- `commission_frequency`: Applied frequency
- `billing_month`: Target billing month
- `billing_period`: Readable period string
- `effective_policy_date`: Effective validity date of the applied policy
- `calculated_at`: Timestamp of computation

Subsequent modifications to global policies or project commission settings **never alter past commission rows**.

### 3.5 Reversals, Refunds & Recovery Mechanism
- **Unpaid Commission Reversal**: When a verified customer payment is reversed and its linked commission is unpaid (`Payable`), the commission status is set to `Cancelled`.
- **Already-Paid Commission Recovery**: When a payment is reversed but the commission was already paid out (`Paid`), the paid record represents immutable financial history and is never deleted. The system automatically inserts an auditable recovery debit entry:
  - `amount`: `-$original_amount`
  - `status`: `Payable` (reduces available partner balance)
  - `adjustment_type`: `'recovery'`
  - `parent_commission_id`: Points to the original commission
- **Partial Customer Refunds**: Proportional commission adjustment or recovery entry creation via `adjustCommissionForPartialRefund()`.

### 3.6 Concurrency Protection & Duplicate Prevention
- Database row locking: `CustomerPayment::where('id', $id)->lockForUpdate()->firstOrFail()`.
- Unique database constraint on `commissions.customer_payment_id` prevents duplicate commissions at the engine level.
- Re-verifying an already processed payment safely throws `DomainException`, preventing concurrent duplicates.

### 3.7 Centralized Audit Log Integration
All commission and project policy events are recorded in the centralized `audit_logs` table inside atomic database transactions:
- `AuditLogAction::CommissionPolicyUpdated`: Logged when project commission terms are modified.
- `AuditLogAction::CommissionGenerated`: Logged when commission is computed upon payment verification.
- `AuditLogAction::CustomerPaymentReversed`: Logged when payment is reversed.
- `AuditLogAction::CommissionAdjusted`: Logged for recoveries and partial refund adjustments.

---

## 4. Test Results

### 4.1 Dedicated Batch 8 Test Suite (`Batch8DynamicCommissionTest`)
```
PASS  Tests\Feature\Batch8DynamicCommissionTest
✓ 01 global percentage fallback                                   0.05s  
✓ 02 project specific percentage override                         0.04s  
✓ 03 project specific fixed commission                            0.04s  
✓ 04 per payment fixed commission                                 0.05s  
✓ 05 per project fixed commission generated only once             0.05s  
✓ 06 per milestone fixed commission                               0.04s  
✓ 07 monthly payment generates separate commission                0.06s  
✓ 08 twelve monthly payments generate twelve eligible commissions 0.10s  
✓ 09 pending payment generates no commission                      0.04s  
✓ 10 rejected payment generates no commission                     0.03s  
✓ 11 repeated payment verification does not duplicate commission  0.06s  
✓ 12 concurrent generation does not duplicate commission          0.04s  
✓ 13 global policy changes do not alter old commissions           0.05s  
✓ 14 project rate changes do not alter old commissions            0.05s  
✓ 15 correct policy version snapshot                              0.04s  
✓ 16 payment refund reverses only corresponding commission        0.05s  
✓ 17 unpaid commission adjustment                                 0.05s  
✓ 18 already paid commission recovery                             0.04s  
✓ 19 partner cannot view another partners commissions             0.09s  
✓ 20 staff cannot access financial records                        0.04s  
✓ 21 audit log entries are generated correctly                    0.04s  
✓ 22 transaction rollback on commission failure                   0.04s  

Tests:    22 passed (92 assertions)
Duration: 2.91s
```

### 4.2 Full Application Regression Test Suite
```
PASS  Tests\Unit\ExampleTest (1 test)
PASS  Tests\Feature\AdminPortalCompletionAndCrudTest (15 tests)
PASS  Tests\Feature\AdminTeamMemberManagementTest (11 tests)
PASS  Tests\Feature\AuthenticationAndRoleSecurityTest (9 tests)
PASS  Tests\Feature\Batch1TimezoneAndCommissionPolicyTest (13 tests)
PASS  Tests\Feature\Batch2AgreementModuleTest (16 tests)
PASS  Tests\Feature\Batch3LeadProtectionTest (22 tests)
PASS  Tests\Feature\Batch4LeadSlaTest (22 tests)
PASS  Tests\Feature\Batch5InactivityManagementTest (22 tests)
PASS  Tests\Feature\Batch6CentralizedAuditLogTest (25 tests)
PASS  Tests\Feature\Batch6FinancialAuditAtomicIntegrityTest (12 tests)
PASS  Tests\Feature\Batch7GlobalSearchTest (24 tests)
PASS  Tests\Feature\Batch8DynamicCommissionTest (22 tests)
PASS  Tests\Feature\CustomerProjectPaymentAndCommissionTest (15 tests)
PASS  Tests\Feature\DatabaseArchitectureTest (5 tests)
PASS  Tests\Feature\LeadManagementAndActivityTrackingTest (19 tests)
PASS  Tests\Feature\PartnerPortalCompletionAndKycTest (17 tests)
PASS  Tests\Feature\SecurityAndFinancialIntegrityAuditTest (19 tests)
PASS  Tests\Feature\StaffDashboardAndCrmAuditTest (21 tests)
PASS  Tests\Feature\WithdrawalAndCommissionLedgerTest (18 tests)

Tests:    343 passed (1,842 assertions)
Duration: 71.68s
```

### 4.3 Production Asset Compilation (`npm run build`)
```
> vite build
vite v7.3.8 building client environment for production...
transforming...
✓ 61 modules transformed.
rendering chunks...
computing gzip size...
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-CO5TcbBu.css  129.46 kB │ gzip: 20.70 kB
public/build/assets/app-DJGfjmoA.js    61.24 kB │ gzip: 22.97 kB
✓ built in 700ms
```

---

## 5. Operational Limitations, Constraints & Safety Guarantees

1. **Transaction Safety**: All payment verifications, commission calculations, and reversals run inside atomic database transactions (`DB::transaction`). If any step (including mandatory audit logging) fails, the entire transaction rolls back cleanly.
2. **Double Payout Prevention**: Unique constraint on `commissions.customer_payment_id` prevents duplicate records. In-flight race conditions are blocked via `lockForUpdate()`.
3. **Immutability of Settled Ledgers**: Commission records that are already `Paid` cannot be updated or deleted. Reversals create explicit debit recovery entries (`adjustment_type = 'recovery'`).
4. **Timezone & Currency Consistency**: All dates and calculations are strictly bound to `Asia/Kolkata` (IST) and `INR (₹)`.
5. **No Destructive Database Commands**: No `migrate:fresh` or `db:wipe` was run. All table extensions were achieved via incremental, index-safe migrations.
6. **Scope Boundary**: Batch 8 is complete. In accordance with user directives, Batch 9 has NOT been started.
