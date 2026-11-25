# TG Partner Program – Batch 6 Implementation Report
## Centralized Audit Log System

**Project:** TG Partner Program  
**Framework:** Laravel 12  
**Target Domain:** `partner.techgoogly.com`  
**Local URL:** `http://127.0.0.1:8000`  
**Timezone:** `Asia/Kolkata` (IST)  
**Currency:** INR (₹)  
**Company Branding:** Tech Googly ("Innovation For You")  
**Status:** Completed & Fully Verified  
**Dedicated Batch 6 Tests:** 16 Passed (86 Assertions)  
**Full Test Suite:** 294 Tests Passed (1,555 Assertions)  

---

## 1. Executive Summary

Batch 6 introduces an enterprise-grade, append-only, immutable **Centralized Audit Log System** to the TG Partner Program. The system captures critical administrative, operational, authentication, and financial transactions across the platform while strictly sanitizing sensitive data (passwords, tokens, PAN, Aadhaar, and bank account numbers).

The architecture preserves all existing domain-specific histories (lead ownership, lead disputes, partner agreements, partner KYC histories, withdrawal histories, user inactivity logs) while providing administrators with a unified, searchable, filterable, and IST-formatted audit dashboard at `/admin/audit-logs`.

All database migrations were executed incrementally (`2026_11_24_000008_create_audit_logs_table.php`). Zero destructive operations were performed, preserving all test and production-like records.

---

## 2. Database Schema & Architecture

### Table: `audit_logs`
Applied via incremental migration `database/migrations/2026_11_24_000008_create_audit_logs_table.php`:

| Column | Type | Index | Description |
|---|---|---|---|
| `id` | `BIGINT UNSIGNED` | Primary Key | Auto-increment unique event identifier |
| `actor_user_id` | `BIGINT UNSIGNED NULL` | Foreign Key (NullOnDelete) | Reference to `users.id` (null for automated system/cron tasks) |
| `actor_name_snapshot` | `VARCHAR(150) NULL` | — | Snapshot of actor's name at the moment the event occurred |
| `actor_role_snapshot` | `VARCHAR(50) NULL` | — | Snapshot of actor's role (`admin`, `partner`, `team_member`, `system`) |
| `action` | `VARCHAR(100)` | Indexed | Machine-readable action code (from `AuditLogAction` enum) |
| `module` | `VARCHAR(50)` | Indexed | Functional subsystem (from `AuditLogModule` enum) |
| `subject_type` | `VARCHAR(150) NULL` | Composite Index | Polymorphic model class (`Lead`, `Partner`, `CustomerPayment`, etc.) |
| `subject_id` | `BIGINT UNSIGNED NULL` | Composite Index | Polymorphic model primary key ID |
| `description` | `TEXT` | Full text / like | Human-readable narrative describing the action |
| `old_values` | `JSON NULL` | — | Sanitized before-state attributes (only fields that changed) |
| `new_values` | `JSON NULL` | — | Sanitized after-state attributes |
| `metadata` | `JSON NULL` | — | Supplementary contextual data (reasons, IP, flags) |
| `ip_address` | `VARCHAR(45) NULL` | — | Client IPv4 or IPv6 network address |
| `user_agent` | `TEXT NULL` | — | Client device / browser user-agent string |
| `created_at` | `TIMESTAMP` | Indexed | Timestamp in UTC, displayed as formatted `Asia/Kolkata` IST |

*Note: Audit logs are strictly immutable and append-only. The table intentionally omits `updated_at`, and the Eloquent model defines `const UPDATED_AT = null;`.*

---

## 3. Events Covered Across Platform Modules

| Module (`AuditLogModule`) | Actions Logged (`AuditLogAction`) | Trigger Location |
|---|---|---|
| **Authentication & Access (`auth`)** | `login_success`, `login_failed`, `logout`, `password_changed`, `account_suspended`, `account_reactivated` | `app/Http/Controllers/Auth/LoginController.php`, `app/Http/Controllers/Admin/TeamMemberController.php` |
| **Partner Management (`partner`)** | `partner_created`, `partner_approved`, `partner_rejected`, `partner_updated`, `partner_status_changed`, `kyc_submitted`, `kyc_review_started`, `kyc_approved`, `kyc_rejected`, `kyc_correction_requested`, `bank_verified`, `bank_rejected` | `app/Http/Controllers/Admin/PartnerController.php`, `app/Services/KycService.php` |
| **Lead Management (`lead`)** | `lead_created`, `lead_assigned`, `lead_reassigned`, `lead_status_updated`, `lead_ownership_changed`, `lead_ownership_extended`, `lead_ownership_released`, `lead_dispute_filed`, `lead_dispute_resolved`, `lead_dispute_rejected`, `lead_duplicate_resolved` | `app/Services/LeadService.php`, `app/Services/LeadDisputeService.php` |
| **Staff & Team (`team`)** | `team_member_created`, `team_member_updated`, `team_member_status_toggled`, `team_member_password_reset` | `app/Http/Controllers/Admin/TeamMemberController.php` |
| **Customer Operations (`customer`)** | `customer_created`, `customer_updated`, `lead_converted_to_customer` | `app/Services/CustomerService.php` |
| **Financial & Payouts (`financial`)** | `customer_payment_recorded`, `customer_payment_verified`, `customer_payment_rejected`, `customer_payment_reversed`, `commission_generated`, `withdrawal_requested`, `withdrawal_cancelled`, `withdrawal_approved`, `withdrawal_processed`, `withdrawal_rejected`, `payout_completed` | `app/Http/Controllers/Admin/PaymentController.php`, `app/Services/CommissionService.php`, `app/Services/WithdrawalService.php` |
| **Agreements & Legal (`agreement`)** | `agreement_generated`, `agreement_accepted`, `agreement_revoked`, `agreement_reissued` | `app/Services/PartnerAgreementService.php` |
| **Inactivity Monitoring (`inactivity`)** | `inactivity_warning_sent`, `inactivity_review_flagged`, `inactivity_review_unflagged`, `account_suspended`, `account_reactivated`, `inactivity_settings_updated` | `app/Services/InactivityService.php`, `app/Http/Controllers/Admin/InactivityController.php` |
| **System Settings (`settings`)** | `commission_settings_updated`, `commission_policy_created`, `commission_policy_revised`, `lead_sla_settings_updated` | `app/Http/Controllers/Admin/CommissionSettingController.php`, `app/Services/CommissionService.php`, `app/Http/Controllers/Admin/LeadSlaController.php` |

---

## 4. Security & Sanitization Architecture

### Data Redaction & Masking Rules (`AuditLogService::sanitizeData`)
1. **Strictly Redacted (`[REDACTED]`):**
   - `password`, `password_confirmation`, `current_password`, `new_password`, `password_hash`
   - `remember_token`, `secret`, `api_secret`, `token`, `access_token`, `api_key`
   - `otp`, `pin`, `cvv`, `private_key`
2. **Masked Identifiers (Preserves Last 4 Chars):**
   - Bank Account Numbers (`••••••••••7891`)
   - PAN Numbers (`••••••234F`)
   - Aadhaar Numbers (`••••••••9012`)
   - Bank Routing Codes / IFSC
   - Tax IDs / GSTIN
3. **Failed Authentication Protections:**
   - Plaintext passwords or attempted credentials are **never** captured in `description`, `new_values`, or `metadata`. Only normalized email, IP address, and failure reason are logged.
4. **Append-Only Enforcement:**
   - No `edit`, `update`, or `destroy` routes exist for audit logs. Normal Admin users cannot modify or purge audit entries.
5. **Role-Based Access Control:**
   - Audit logs are accessible strictly to authenticated users with `UserRole::Admin`.
   - Partner and Sales Staff accounts are forbidden (HTTP 403 Forbidden).
   - Unauthenticated visitors are redirected to the login page.
6. **Transaction Atomicity:**
   - Audit logs logged during transactional workflows roll back atomically if the database transaction aborts, preventing orphaned audit records.

---

## 5. Existing History Integration

Existing domain-specific history tables continue to operate seamlessly alongside the centralized audit system:
- `lead_ownership_histories`: Maintains partner ownership tracking and validity periods.
- `lead_disputes`: Maintains dispute evidence files, review statuses, and resolution reasons.
- `partner_kyc_histories`: Maintains immutable snapshots of partner identity and bank document versions.
- `partner_agreements`: Maintains legally binding electronic acceptance timestamps, IPs, and PDF snapshots.
- `user_inactivity_histories`: Tracks engagement alerts and warning dispatches.
- `project_status_histories`: Tracks project milestone progression.

---

## 6. Admin Audit Log Dashboard & UI

Located at `/admin/audit-logs`:
- **Real-Time KPI Cards:** Total Audit Events, Today's Events (IST), Admin Actions, Partner Actions, Staff CRM Actions.
- **Multidimensional Filters:**
  - Date Range in IST (`from_date`, `to_date`)
  - Module (`auth`, `partner`, `lead`, `financial`, etc.)
  - Role (`admin`, `partner`, `team_member`, `system`)
  - Specific Actor
  - Subject Model Type
  - Text Search across descriptions, subject IDs, and actor names
- **Interactive Inspection Modal:**
  - Fast client-side Alpine.js modal inspecting event details without reloading.
  - Side-by-side color-coded before/after diff viewer (Old state in soft rose, New state in soft emerald).
  - Contextual metadata formatted in clean JSON.
- **Navigation Integration:**
  - "Central Audit Logs" link added to the main Admin sidebar under "Intelligence & Settings" in `resources/views/layouts/sidebar-content.blade.php`.

---

## 7. Verification & Automated Test Results

### Dedicated Batch 6 Test Suite (`tests/Feature/Batch6CentralizedAuditLogTest.php`)
All 16 test cases passed:
1. `test_01_successful_login_is_logged`: Verifies login event records actor snapshot and email.
2. `test_02_failed_login_is_logged_without_password_leakage`: Verifies failed login logs without leaking password.
3. `test_03_logout_is_logged`: Verifies user logout is recorded.
4. `test_04_lead_submission_is_logged`: Verifies partner lead submission creates audit record.
5. `test_05_lead_assignment_and_status_changes_are_logged`: Verifies lead assignment and pipeline transitions.
6. `test_06_partner_creation_and_approval_are_logged`: Verifies partner creation and approval.
7. `test_07_partner_kyc_events_are_logged`: Verifies KYC review, KYC approval, and bank verification.
8. `test_08_financial_payment_and_commission_events_are_logged`: Verifies payment verification and commission generation.
9. `test_09_withdrawal_and_payout_events_are_logged`: Verifies withdrawal request and payout audit records.
10. `test_10_before_after_values_and_diffs_captured_correctly`: Verifies diffing only records modified fields.
11. `test_11_sensitive_data_sanitization_and_redaction`: Verifies redaction of passwords and masking of PAN/Aadhaar/Bank.
12. `test_12_admin_only_access_and_staff_partner_denial`: Verifies 403 Forbidden for Staff and Partners.
13. `test_13_append_only_behavior_no_edit_or_delete_routes`: Verifies absence of edit/delete routes.
14. `test_14_filtering_and_pagination`: Verifies filtering by module, role, and text search.
15. `test_15_ist_timestamp_display`: Verifies timestamps are rendered in `Asia/Kolkata` IST.
16. `test_16_duplicate_prevention_and_transaction_rollback_safety`: Verifies rollback safety inside transactions.

### Full Application Regression Suite
- **Command:** `php artisan test`
- **Result:** **294 passed, 0 failed** (1,555 assertions across 21 test suites).
- **Duration:** 168.93s.

### Frontend Asset Compilation
- **Command:** `npm run build`
- **Result:** Vite v7.3.8 compiled 61 modules cleanly (`app-BMxrtK3R.css` 126.12 kB, `app-DJGfjmoA.js` 61.24 kB).

---

## 8. Files Created & Modified

### Created:
1. `database/migrations/2026_11_24_000008_create_audit_logs_table.php`
2. `app/Enums/AuditLogModule.php`
3. `app/Enums/AuditLogAction.php`
4. `app/Models/AuditLog.php`
5. `app/Services/AuditLogService.php`
6. `app/Http/Controllers/Admin/AuditLogController.php`
7. `resources/views/admin/audit-logs/index.blade.php`
8. `resources/views/admin/audit-logs/show.blade.php`
9. `tests/Feature/Batch6CentralizedAuditLogTest.php`
10. `batch_6_centralized_audit_log_report.md`

### Modified:
1. `routes/web.php` (registered admin audit log routes)
2. `resources/views/layouts/sidebar-content.blade.php` (added Central Audit Logs sidebar link)
3. `app/Http/Controllers/Auth/LoginController.php` (login, failed login, rate limit, logout hooks)
4. `app/Http/Controllers/Admin/TeamMemberController.php` (staff creation, update, toggle, password reset hooks)
5. `app/Http/Controllers/Admin/PartnerController.php` (partner creation, update, approve, reject, suspend, activate hooks)
6. `app/Services/LeadService.php` (lead creation, assignment, status update, duplicate resolve, ownership hooks)
7. `app/Services/LeadDisputeService.php` (dispute filed, resolved, rejected hooks)
8. `app/Services/KycService.php` (KYC submitted, review started, approved, bank verified, rejected hooks)
9. `app/Services/CustomerService.php` (customer created, updated, lead converted hooks)
10. `app/Services/CommissionService.php` (policy created, revised, payment verified, commission generated, rejected, reversed hooks)
11. `app/Services/WithdrawalService.php` (withdrawal requested, approved, processing, rejected, payout completed hooks)
12. `app/Services/InactivityService.php` (warning sent, review flagged, unflagged, suspended, reactivated hooks)
13. `app/Http/Controllers/Admin/PaymentController.php` (payment recorded hook)
14. `app/Http/Controllers/Admin/InactivityController.php` (inactivity settings updated hook)
15. `app/Http/Controllers/Admin/LeadSlaController.php` (SLA settings updated hook)
16. `app/Http/Controllers/Admin/CommissionSettingController.php` (commission settings updated hook)

---

## 9. Next Steps

> [!IMPORTANT]
> **STOP Condition Honored:** Batch 6 is fully complete and verified. Work has stopped. Batch 7 (Global Search) has **NOT** been started and awaits review and approval.
