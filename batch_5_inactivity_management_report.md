# TG Partner Program – Batch 5 Implementation Report
## Partner & Staff Inactivity Management System

**Project:** TG Partner Program  
**Framework:** Laravel 12  
**Target Domain:** `partner.techgoogly.com`  
**Local URL:** `http://127.0.0.1:8000`  
**Status:** Completed & Fully Verified  
**Full Test Suite:** 278 Tests Passed (1,469 Assertions)  

---

## Executive Summary

Batch 5 introduces a comprehensive, automated, and secure **Partner & Staff Inactivity Management System** into the TG Partner Program. The system accurately tracks user engagement across business-critical events without counting background requests or page views, evaluates customizable inactivity thresholds (30, 60, and 90 days), delivers non-intrusive warning banners, issues deduplicated notifications, and equips administrators with an inactivity dashboard and audit-logged operational controls.

All changes strictly follow zero-destructive database principles (`migrate:fresh` or `db:wipe` were never used). Account approval status, KYC status, and financial records were fully preserved.

---

## Key Deliverables & Architecture

### 1. Activity Tracking Architecture
Tracked timestamps added to the `users` table:
- `last_login_at` & `last_logout_at`: Captured during authentication lifecycle (`LoginController`).
- `last_meaningful_activity_at`: Updated upon genuine business events:
  - Lead submission (`LeadService::createLead`)
  - Lead updates & status transitions (`LeadService::updateStatus`)
  - Customer interactions (calls, meetings, WhatsApp, notes in `LeadService::recordActivity`)
  - Digital Partner Agreement acceptance (`PartnerAgreementService::acceptAgreement`)
  - Lead ownership disputes (`LeadDisputeService::fileDispute`)
  - Commission withdrawal requests (`WithdrawalService::requestWithdrawal`)
- `last_lead_submission_at`: Explicitly tracks lead creation.
- `last_lead_update_at`: Explicitly tracks CRM pipeline progression.
- `last_customer_interaction_at`: Explicitly tracks direct prospect communications.
- **Safety Rule:** Passive GET requests, page refreshes, and polling endpoints do **not** touch or reset meaningful activity timestamps.

### 2. Inactivity Status & Threshold Configuration
- **Enum `InactivityStatus`:**
  - `Active`: Normal active engagement.
  - `AtRisk`: Inactivity >= 30 days (warning threshold).
  - `Inactive`: Inactivity >= 60 days (alert threshold).
  - `Critical`: Inactivity >= 90 days (critical attention threshold).
  - `Suspended`: Account suspended by Admin.
- **Independence Guarantee:** `inactivity_status` is decoupled from `kyc_status` and `users.status`. KYC status is never overwritten or mutated by inactivity.
- **System Settings:** Configurable separately for Partners and Staff:
  - `inactivity_partner_warning_days` (default 30)
  - `inactivity_partner_alert_days` (default 60)
  - `inactivity_partner_critical_days` (default 90)
  - `inactivity_staff_warning_days` (default 30)
  - `inactivity_staff_alert_days` (default 60)
  - `inactivity_staff_critical_days` (default 90)

### 3. Automated Scanning & Deduplication
- **Artisan Command:** `php artisan inactivity:check-thresholds` (`app/Console/Commands/CheckUserInactivityCommand.php`) registered to run daily.
- **Deduplicated Notifications:**
  - 30-Day Warning: Dispatched once to the user when entering `AtRisk`. Recorded in `inactivity_warning_sent_at`.
  - 60-Day Alert: Dispatched once to user and all administrators. Recorded in `inactivity_alert_sent_at`.
  - 90-Day Critical: Dispatched once to user and all administrators. Recorded in `inactivity_critical_sent_at`.
  - Subsequent scheduled cron runs verify these timestamps and will **never** generate repetitive spam notifications.

### 4. Admin Inactivity Dashboard (`/admin/inactivity`)
- Separate **Partners** and **Staff** management tabs.
- KPI metric cards:
  - Total Active Accounts
  - Accounts At Risk (30+ days)
  - Inactive Accounts (60+ days)
  - Critical Inactivity (90+ days)
  - Suspended Accounts
- Search and filter by name, email, inactivity status, and review flags.
- **Partner Metrics:** Days inactive, leads submitted, won leads, and lifetime revenue.
- **Staff Metrics:** Days inactive, leads assigned, follow-ups due today, and total completed activities.
- Direct quick actions for manual warnings, flagging/unflagging for review, suspension, reactivation, and audit history.

### 5. Administrative Controls & Safety Rules
- **Explicit Safety Rule:** Accounts are **NEVER** automatically suspended by cron jobs or automated threshold evaluations.
- **Mandatory Reason:** Account suspension requires an explicit Admin POST action with a mandatory written justification.
- **Reactivation Control:** Reactivation is strictly an explicit Admin action with a mandatory justification; accounts are never automatically reactivated.
- **Audit Logging:** Every administrative action (`warning_sent`, `marked_for_review`, `unmarked_review`, `suspended`, `reactivated`) is permanently recorded in `user_inactivity_histories` with admin ID, previous/new status, inactivity days, timestamp, and reason.

### 6. User Experience & Banners
- Inactive Partners (`partner.workspace`) and Staff (`team.workspace`) entering `AtRisk` or `Critical` status see an amber or red alert banner detailing their days of inactivity and guiding them to resume platform interactions.
- Banners are strictly scoped to the authenticated user and never disclose other users' data.

---

## Verification & Testing Summary

### Batch 5 Automated Feature Tests (`tests/Feature/Batch5InactivityManagementTest.php`)
All 16 test cases passed:
1. `test_01_login_timestamp_is_recorded`: Verifies login sets `last_login_at` and `last_meaningful_activity_at`.
2. `test_02_meaningful_activity_tracking`: Verifies lead submissions and CRM interactions update activity timestamps.
3. `test_03_background_requests_do_not_reset_activity`: Verifies GET browsing/polling does not reset activity.
4. `test_04_30_day_warning_threshold`: Verifies evaluation and dispatch of 30-day warning.
5. `test_05_60_day_inactivity_alert_threshold`: Verifies 60-day alert dispatched to partner and admin.
6. `test_06_90_day_critical_threshold`: Verifies 90-day critical flag without auto-suspending.
7. `test_07_duplicate_notification_prevention`: Verifies second scan does not resend notifications.
8. `test_08_partner_and_staff_separation`: Verifies independent thresholds for Partner and Staff.
9. `test_09_admin_suspension_with_reason`: Verifies Admin suspension sets suspended status and logs history without wiping KYC.
10. `test_10_admin_reactivation_with_reason`: Verifies Admin reactivation restores active status and logs history.
11. `test_11_mandatory_reason_validation`: Verifies empty reason is rejected with 422.
12. `test_12_suspended_user_access_restriction`: Verifies suspended user cannot access workspaces.
13. `test_13_active_users_login_seamlessly`: Verifies active partners and staff can authenticate cleanly.
14. `test_14_artisan_command_execution`: Verifies `inactivity:check-thresholds` command executes cleanly.
15. `test_15_admin_inactivity_dashboard_renders`: Verifies `/admin/inactivity` UI, filters, tabs, and history render without error.
16. `test_16_user_dashboards_display_inactivity_warning`: Verifies warning banner displays on partner and staff workspaces.

### Full Regression Test Suite
- **Executed Command:** `php artisan test`
- **Result:** **278 passed, 0 failed** (1,469 assertions) across all features (Admin Portal, Agreements, Lead Protection, Lead SLA, CRM Workspace, Financial Security, and Inactivity Management).
- **Frontend Assets:** `npm run build` compiled 61 modules successfully (`app.css` 124.51 kB, `app.js` 61.24 kB).

---

## Strict Adherence to Constraints
- **Batch 5 ONLY:** Work is strictly contained within Batch 5 requirements. Batch 6 has NOT been started.
- **Zero Destruction:** Database migrated cleanly via incremental migrations (`add_inactivity_fields_to_users_table`, `create_user_inactivity_histories_table`).
- **Data Integrity:** No existing records, KYC verification, or agreement records were altered or lost.
