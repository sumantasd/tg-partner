# TG PARTNER PROGRAM — DEMO DATA BACKUP & CLEANUP MODULE REPORT

**Project:** TG Partner Program  
**Framework:** Laravel 12.71 / PHP 8.4.15 / Vite 7.3.8  
**Deployment Target:** `partner.techgoogly.com`  
**Timezone:** Asia/Kolkata (IST)  
**Base Currency:** INR (₹)  
**Deliverable Document:** `demo_data_backup_cleanup_report.md`  
**Date:** 2026-11-25  

---

## EXECUTIVE SUMMARY

A dedicated, enterprise-grade, **Admin-only Demo Data Backup & Cleanup module** has been designed, implemented, and fully verified for the TG Partner Program. The module gives administrators complete control to:
1. Generate complete, AES-256 encrypted backups of database records and uploaded private documents.
2. Manage, view metadata for, verify, and stream downloads of existing backup archives.
3. Perform controlled restorations with mandatory SHA-256 integrity validation and an automated **safety pre-restore backup** before touching live data.
4. Preview exact record counts across **18 demo data categories** targeted for deletion versus **5 production categories strictly preserved**.
5. Execute dependency-aware, transaction-safe demo data purges while preserving the designated Admin account, database schema, migrations, system settings, commission policies, and legal templates.

### Status and Quality Checks
- **PHPStan Static Analysis:** Level 5 (`php vendor/bin/phpstan analyse app --level=5`) — **0 Errors across 132 files**.
- **Security Audit:** `composer audit` — **0 Vulnerabilities / 0 Advisories**.
- **Automated Test Suite:** `php artisan test` — **403 Passed, 2,115 Assertions, 0 Failures** (13 new comprehensive backup/cleanup feature tests + 390 existing regression tests).
- **Vite Production Assets:** `npm run build` — **Built in 2.54s (Manifest, CSS, JS verified)**.
- **Data Safety Compliance:** **Zero demo data altered or deleted during development**. Real database records in local MariaDB remain 100% intact.

---

## 1. ARCHITECTURE & WORKFLOW

```
                                Admin Control Workspace
                                           │
                    ┌──────────────────────┴──────────────────────┐
                    ▼                                             ▼
            [ BACKUP SUBSYSTEM ]                          [ CLEANUP SUBSYSTEM ]
                    │                                             │
      ┌─────────────┴─────────────┐                 ┌─────────────┴─────────────┐
      ▼                           ▼                 ▼                           ▼
 Pure PHP SQL Dump       Collect Storage Docs   Preview 18 Purge Cats     Safety Prerequisites:
(MariaDB & SQLite)      (KYC, Agreements, etc.)  vs 5 Preserved Cats      1. Verified Backup Exists
      │                           │                 │                     2. Confirmation Phrase
      └─────────────┬─────────────┘                 │                     3. Admin Password Check
                    ▼                               │                     4. Concurrency Mutex Lock
          Generate Manifest.json                    ▼                               │
                    │                       Dependency-Aware DB Purge               │
                    ▼                       (Child tables -> Parent tables)         │
         ZipArchive + AES-256                       │                               ▼
         Stored in Private Disk                     ▼                     Physical File Cleanup
                    │                       Preserve 1 Admin User        (Preserve Dir & .gitignore)
                    ▼                       & Production Configs                    │
         Immediate Verification                     │                               ▼
         (SHA-256 + Integrity)                      └───────────────┬───────────────┘
                    │                                               ▼
                    └──────────────────────────────────────►  Centralized Audit Log
                                                              (AuditLogService)
```

### Module Separation & Privilege Isolation
- **Strict Role Authorization:** All routes and controller endpoints are protected by `auth` and `role.admin` (`EnsureUserIsAdmin`) middleware. Non-admin users (Partners and Staff) are immediately denied access with HTTP 403 Forbidden.
- **Concurrency Mutex Lock:** Operations acquire an atomic cache lock (`Cache::lock('demo_backup_cleanup_operation', 180-300)`). Concurrent backups, restores, or cleanups are blocked with an immediate domain exception.
- **Audit Logging:** Every operation (Backup Created, Verified, Downloaded, Deleted, Restore Initiated, Restore Completed, Restore Failed, Cleanup Executed, Cleanup Failed) is recorded in `audit_logs` via `AuditLogService` under `AuditLogModule::SystemBackup`.

---

## 2. DATABASE TABLES COVERED & PURGE ORDER

### Dependency-Aware Deletion Order (Topological Sort)
To respect foreign-key constraints and avoid database deadlocks or orphaned foreign references, deletions are executed inside a database transaction (`DB::transaction`) with temporary foreign key suppression (`SET FOREIGN_KEY_CHECKS = 0; ... SET FOREIGN_KEY_CHECKS = 1;` in MySQL/MariaDB and `PRAGMA foreign_keys = OFF; ... PRAGMA foreign_keys = ON;` in SQLite) in the following dependency-aware sequence:

| Step | Table Name | Description | Nature of Data |
| :---: | :--- | :--- | :--- |
| **1** | `support_ticket_replies` | Replies to support tickets | Demo testing communication |
| **2** | `support_tickets` | Support ticket inquiries | Demo partner support requests |
| **3** | `lead_disputes` | Duplicate lead ownership disputes | Testing disputes between partners |
| **4** | `lead_activities` | Notes, call logs, timeline touchpoints | Demo CRM activity history |
| **5** | `lead_ownership_histories` | 90-day protection window ownership logs | Demo lead protection history |
| **6** | `lead_sla_escalations` | SLA breach warnings & escalation logs | Demo SLA triggers |
| **7** | `commissions` | Ledger records (Pending, Payable, Paid) | Demo commissions (bypasses model hook) |
| **8** | `payouts` | Banking & UPI disbursement records | Demo payout disbursements |
| **9** | `withdrawal_requests` | Partner withdrawal requests | Demo withdrawal reservations |
| **10** | `customer_payments` | Recorded, verified, reversed receipts | Demo customer transactions |
| **11** | `project_payment_schedules` | Monthly recurring billing schedules | Demo billing intervals |
| **12** | `project_status_histories` | Status transition tracking logs | Demo project history |
| **13** | `projects` | Customer projects & service agreements | Demo project master records |
| **14** | `customers` | Client accounts & profiles | Converted demo clients |
| **15** | `leads` | Inquiries & partner-submitted leads | Demo lead pipeline |
| **16** | `partner_kyc_histories` | KYC status change audit history | Demo KYC review logs |
| **17** | `partner_kycs` | Partner Aadhaar, PAN, bank passbook docs | Demo KYC submissions |
| **18** | `partner_agreements` | TGPA legal partner contracts | Demo signed/pending agreements |
| **19** | `user_inactivity_histories` | Inactivity warning & suspension logs | Demo inactivity tracking |
| **20** | `notifications` | In-app user notifications & alerts | Demo notifications |
| **21** | `audit_logs` | Demo audit logs | Non-admin & demo actor logs |
| **22** | `partners` | Partner master profiles & bank settings | Demo referral partners |
| **23** | `users` | Staff & partner users (except designated Admin) | All users where `id != $currentAdmin->id` |

### Production Assets Strictly Preserved (Never Deleted)
1. **Designated Super Admin Account:** Preserves exactly one designated administrator (`$currentAdmin->id`), ensuring seamless continued access.
2. **Database Schema & Migrations:** `migrations` table and all table structures are preserved. `migrate:fresh` or `db:wipe` are **strictly forbidden**.
3. **Production System Settings:** `system_settings` table (branding, lead SLA parameters, platform configuration).
4. **Commission Policies:** `commission_policies` table (standard global policy and active version lineage).
5. **Agreement Templates:** `agreement_templates` table (active master legal agreement templates).
6. **Demo Backups Ledger:** `demo_backups` table (preserves backup history and pre-restore safety snapshots).

---

## 3. STORAGE PATHS & FILESYSTEM HANDLING

### Storage Directory Layout

```
storage/app/
├── private/
│   ├── agreements/              <-- TGPA-*.pdf (Cleaned during purge; .gitignore preserved)
│   ├── private/
│   │   └── kyc_documents/       <-- Partner KYC documents (Cleaned during purge; directory kept)
│   ├── kyc_documents/           <-- Alternate KYC document path
│   └── backups/
│       └── demo/                <-- Encrypted backup zip archives (PRESERVED across purges)
└── public/                      <-- Public uploads (Branding assets preserved)
```

### Filesystem Cleanup Safeguards
- **Directory Preservation:** Cleanup empties files inside document directories but leaves the directories and `.gitignore`/`.gitkeep` files intact.
- **Zero Asset Deletion:** Public branding assets (`public/assets`, logos, stylesheets), system source code, and configuration files are never touched.
- **Backup Directory Protection:** Backups stored in `storage/app/private/backups/demo/` are explicitly excluded from file purges.

---

## 4. BACKUP SECURITY & INTEGRITY ARCHITECTURE

### Encryption & Storage Isolation
- **Storage Location:** Backups are stored on the `local` private disk (`storage/app/private/backups/demo/`), completely outside public web root access (`public/`).
- **AES-256 Archive Encryption:** Zip archives are encrypted using standard `ZipArchive::EM_AES_256` with a key deterministically derived from `hash('sha256', config('app.key') . '_demo_backup_salt_2026')` or an administrator-provided custom password.
- **SHA-256 Checksumming:** Each archive's cryptographic hash is calculated via `hash_file('sha256', $zipPath)` upon creation, stored in `demo_backups.sha256_checksum`, and re-verified on download, verification, and restore requests.
- **Portability:** Database dump engine operates via pure PDO/Eloquent queries supporting both **MariaDB / MySQL** (production/staging) and **SQLite** (unit and feature test environments) without relying on external system binaries (`mysqldump`).

---

## 5. RESTORATION STRATEGY & SAFETY PRE-BACKUP

Restoring data is a destructive operation. The restoration engine implements multiple defensive gates:

```
[ Admin Clicks Restore ]
          │
          ▼
[ Re-Authenticate Password & Type "RESTORE-CONFIRMED" ]
          │
          ▼
[ SHA-256 Checksum & ZipArchive Integrity Validation ]
          │
          ▼
[ AUTOMATIC PRE-RESTORE SAFETY BACKUP CREATED & VERIFIED ]
    (If safety backup fails -> RESTORE ABORTED IMMEDIATELY)
          │
          ▼
[ Application Enters Maintenance Mode (php artisan down) ]
          │
          ▼
[ Secure Extraction with Zip Slip Protection ]
          │
          ▼
[ Controlled Database & Storage File Restoration ]
          │
          ▼
[ Finally Block: Application Exits Maintenance Mode (php artisan up) ]
          │
          ▼
[ Audit Log: RestoreCompleted Recorded ]
```

### Zip Slip Protection
To prevent directory traversal attacks during extraction, every archive entry path is validated:
```php
for ($i = 0; $i < $zip->numFiles; $i++) {
    $filename = $zip->getNameIndex($i);
    if (str_contains($filename, '..') || str_starts_with($filename, '/') || str_starts_with($filename, '\\')) {
        throw new DomainException("Potential malicious entry detected in archive: {$filename}");
    }
}
```

---

## 6. CLEANUP IMPACT ASSESSMENT & PREVIEW

Before an administrator can purge demo data, the system renders an interactive impact assessment at `admin/demo-data/cleanup` with exact real-time database counts:

| Category | UI Scope Displayed | Purge Action |
| :--- | :--- | :---: |
| **Partner Accounts** | Registered demo partners, profiles, and banking credentials | **PURGED** |
| **Internal Staff Accounts** | Internal team members, sales consultants, assignees | **PURGED** |
| **Leads** | Partner-submitted and admin-created demo leads | **PURGED** |
| **Lead Activities** | Notes, call logs, timeline touchpoints | **PURGED** |
| **Lead Ownership Histories** | Historical 90-day protection and reassignment logs | **PURGED** |
| **Lead Disputes** | Disputes between partners over duplicate submissions | **PURGED** |
| **Customers** | Converted customers and client profiles | **PURGED** |
| **Projects** | Single and monthly service projects | **PURGED** |
| **Customer Payments** | Recorded, verified, and reversed customer receipts | **PURGED** |
| **Billing Schedules** | Monthly recurring invoices and billing period schedules | **PURGED** |
| **Commissions** | Pending, payable, paid, and recovery commission records | **PURGED** |
| **Withdrawal Requests** | Partner payout requests and reservations | **PURGED** |
| **Payouts** | Completed and processed banking/UPI disbursement records | **PURGED** |
| **KYC Records & Files** | Partner Aadhaar, PAN, and passbook submissions and files | **PURGED** |
| **Partner Agreements & PDFs** | Signed and pending TGPA legal agreement records and PDF files | **PURGED** |
| **In-App Notifications** | System alerts, task reminders, and status notifications | **PURGED** |
| **Demo Audit Logs** | Audit logs generated by demo users, staff, and automated routines | **PURGED** |
| **Other Dependent Records** | Support tickets, SLA escalations, inactivity logs, status histories | **PURGED** |
| **Designated Super Admin** | Exactly one administrator user preserved | **PRESERVED** |
| **Database Migrations** | Complete database schema and migrations table | **PRESERVED** |
| **System Settings** | Platform configuration, lead SLA rules, company settings | **PRESERVED** |
| **Commission Policies** | Standard commission policies and version history | **PRESERVED** |
| **Agreement Templates** | Official Tech Googly legal templates and clauses | **PRESERVED** |

### Cleanup Authorization Gates
1. **Verified Backup Prerequisite:** Cleanup button is completely disabled unless at least one verified backup exists (`DemoBackup::where('status', 'verified')->exists()`).
2. **Strict Confirmation Phrase:** Must type exactly `CONFIRM-CLEANUP-DEMO-DATA`.
3. **Password Re-Authentication:** Administrator's current login password is confirmed via `Hash::check()`.

---

## 7. AUTOMATED TEST SUITE EXECUTION

A dedicated test suite was authored in `tests/Feature/AdminDemoDataBackupAndCleanupTest.php` covering all functional and security aspects:

```text
$ php artisan test tests/Feature/AdminDemoDataBackupAndCleanupTest.php

   PASS  Tests\Feature\AdminDemoDataBackupAndCleanupTest
  ✓ unauthorized users cannot access demo data module                                    0.52s  
  ✓ admin can create demo data backup                                                    0.14s  
  ✓ checksum verification validates and catches corruption                               0.11s  
  ✓ backup download is secure and admin only                                             0.12s  
  ✓ backup deletion requires valid admin password                                        0.12s  
  ✓ restore workflow creates safety pre backup                                           0.26s  
  ✓ restore aborts on invalid phrase or password                                         0.13s  
  ✓ cleanup preview calculates accurate counts                                           0.04s  
  ✓ cleanup is blocked without verified backup                                           0.03s  
  ✓ cleanup preserves designated admin and purges demo data                              0.12s  
  ✓ concurrent operation lock prevents simultaneous execution                            0.02s  
  ✓ cleanup removes demo files while preserving directory structures                     0.13s  
  ✓ failed cleanup rolls back atomically                                                 0.14s  

  Tests:    13 passed (70 assertions)
  Duration: 2.06s
```

### Full Application Regression Suite Execution
```text
$ php artisan test

  Tests:    403 passed (2115 assertions)
  Duration: 48.58s
```

### Static Analysis & Asset Build
```text
$ php vendor/bin/phpstan analyse app --level=5
Note: Using configuration file C:\Projects\TG-Partner-Program\phpstan.neon.
 132/132 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100%
 [OK] No errors

$ composer audit
No security vulnerability advisories found.

$ npm run build
vite v7.3.8 building client environment for production...
✓ 61 modules transformed.
rendering chunks...
public/build/assets/app-t_FqwtRY.css  134.38 kB │ gzip: 21.22 kB
public/build/assets/app-DJGfjmoA.js    61.24 kB │ gzip: 22.97 kB
✓ built in 2.54s
```

---

## 8. REMAINING RISKS & OPERATIONAL RECOMMENDATIONS

| Risk Item | Severity | Mitigation Implemented | Operational Recommendation |
| :--- | :---: | :--- | :--- |
| **Large Storage Archives** | Low | Zip compression + selective file inclusion (skips temp files & backups). | When storage grows, periodically delete obsolete manual backups via the Admin UI. |
| **Long-Running Restorations on High Volume** | Low | Concurrency lock timeout configured to 300 seconds; maintenance mode active during run. | Perform restorations during off-peak hours on production. |
| **Admin Credential Compromise** | Medium | Password re-authentication required for deletion, restore, and cleanup. | Enforce strong passwords and 2FA for all administrator accounts. |
| **Accidental Cleanup of Staging Data** | Low | Strict confirmation phrase (`CONFIRM-CLEANUP-DEMO-DATA`) + verified backup requirement. | Maintain off-site automated backups as standard ops practice. |

---

## 9. CONCLUSION & AUDIT SIGN-OFF

The **Demo Data Backup & Cleanup Module** is fully operational, thoroughly tested, and ready for production deployment. All strict rules have been respected:
- Zero real records were deleted.
- No live database backup or restoration was executed.
- Destructive commands (`migrate:fresh`, `db:wipe`) were never run.
- Financial business logic and existing module code remain untouched.

As instructed, all automatic operations have stopped. The system is awaiting explicit administrator authorization before creating the first live backup or executing demo data cleanup.
