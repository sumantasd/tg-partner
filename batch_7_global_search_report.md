# Batch 7: Centralized Global Search System – Implementation & Verification Report

**Project:** TG Partner Program  
**Framework:** Laravel 12 (PHP 8.4)  
**Timezone:** `Asia/Kolkata` (IST)  
**Currency:** `INR (₹)`  
**Company Legal Branding:** Tech Googly  
**Date:** November 24, 2026  
**Status:** Completed & Fully Verified (321 / 321 Tests Passing)

---

## 1. Executive Summary

Batch 7 introduces a high-performance, role-aware **Centralized Global Search System** for the TG Partner Program. The system empowers authenticated users (**Admin**, **Partner**, and **Staff / Team Members**) to quickly find records across leads, customers, projects, financial transactions, agreements, and audit logs while strictly enforcing privacy boundaries and query-level security.

### Core Capabilities Delivered:
1. **Interactive Global Search Palette (Modal):** Activated instantly via `Ctrl + K` (Windows/Linux) or `Cmd + K` (macOS), top-bar search input, or navigation item. Features debounced live search (300ms, min 2 chars), grouped result cards, arrow-key navigation, Enter selection, and responsive layout for mobile and desktop.
2. **Dedicated Full Search Results View (`/search`):** Comprehensive search interface with active search query, category filtering tabs with record counts, date range filters (`date_from`, `date_to`), status filtering, and paginated card displays (15 records per page, preserving query strings).
3. **Strict Query-Level Role Scoping:**
   - **Admin:** Unrestricted visibility across all 11 modules (Partners, Staff, Leads, Customers, Projects, Customer Payments, Commissions, Withdrawal Requests, Payouts, Agreements, KYC Reviews, Lead Disputes, Audit Logs).
   - **Partner:** Strictly restricted to own submitted leads (`partner_id`), converted customers (`first_partner_id`), linked projects, commissions, withdrawals, agreements, and disputes. Never has access to other partners, staff, admin settings, KYC review dossiers, or audit logs.
   - **Staff / Team Members:** Strictly restricted to assigned leads (`assigned_to`) and customers converted from assigned leads. Zero access to partner bank accounts, KYC dossiers, commissions, payouts, or audit logs.
4. **Data Privacy & Injection Hardening:** Explicit field selection avoids leaking sensitive credentials (passwords, OTPs, session tokens, full PAN/Aadhaar numbers, bank account numbers). Search queries are parameterized and wildcard-escaped against SQL injection.
5. **Session-Isolated Recent Searches:** Recent searches are tracked safely in session storage (`recent_searches`) with an instant clear endpoint (`POST /search/recent/clear`), guaranteeing zero database overhead and complete isolation between users.
6. **Zero Destructive Database Changes:** Executed with zero schema wipes, preserving all existing local database records.

---

## 2. Files Created and Modified

### New Components Created:
- `app/Services/GlobalSearchService.php`: Core business logic for query sanitization (`escapeLike`), module resolution, role-based database scoping, result formatting, live suggestion capping, and full-page paginated filtering.
- `app/Http/Controllers/GlobalSearchController.php`: Controller handling live suggestions (`GET /search/suggest`), full search page (`GET /search`), and recent search clearing (`POST /search/recent/clear`).
- `resources/views/layouts/global-search-modal.blade.php`: Modal search palette component supporting keyboard shortcuts, live AJAX debouncing, recent searches, keyboard navigation, and responsive states.
- `resources/views/search/index.blade.php`: Full search results view with category tabs, status filters, date range filters, pagination, and empty/no-results states.
- `tests/Feature/Batch7GlobalSearchTest.php`: 12 automated feature test methods testing all role scenarios, partial searches, SQL injection prevention, sensitive data exclusion, pagination, and session isolation.

### Existing Files Modified:
- `routes/web.php`: Added authenticated global search routes with route names `search.suggest`, `search.index`, and `search.recent.clear`.
- `resources/views/layouts/app.blade.php`: Added top navigation search input with `Ctrl + K` badge, mobile search icon trigger, and inclusion of the global search modal palette.
- `resources/views/layouts/sidebar-content.blade.php`: Added Global Search shortcut link in sidebar navigation for all authenticated roles.

---

## 3. Searchable Modules & Role Permissions Matrix

The global search applies query-level filtering directly in the database rather than post-query filtering in memory:

| Module | Searchable Fields | Admin Access | Partner Access | Staff Access | Destination Route / Detail URL |
| :--- | :--- | :---: | :---: | :---: | :--- |
| **Leads** | `lead_number`, `customer_name`, `customer_company`, `customer_phone`, `customer_email`, `city` | Full access | Own leads only (`partner_id`) | Assigned leads only (`assigned_to`) | `admin.leads.show` / `partner.leads.show` / `team.leads.show` |
| **Customers** | `customer_code`, `name`, `company_name`, `phone`, `email` | Full access | Own customers only (`first_partner_id`) | Customers linked to assigned leads | `admin.customers.show` / `partner.customers.show` / `team.leads.show` |
| **Projects** | `project_code`, `project_title`, `client_name`, `client_company` | Full access | Own projects only (`partner_id`) | **Blocked** | `admin.projects.show` / `partner.projects.show` |
| **Customer Payments** | `payment_number`, `transaction_reference`, `notes` | Full access | Linked to own projects | **Blocked** | `admin.customer-payments.show` |
| **Commissions** | `commission_number`, `notes` | Full access | Own commissions only (`partner_id`) | **Blocked** | `admin.commissions.show` / `partner.commissions.show` |
| **Withdrawal Requests** | `request_number`, `payout_notes` | Full access | Own requests only (`partner_id`) | **Blocked** | `admin.withdrawals.show` / `partner.withdrawals.show` |
| **Payouts** | `payout_number`, `transaction_reference`, `notes` | Full access | Linked to own withdrawals | **Blocked** | `admin.payouts.index` |
| **Agreements** | `agreement_number`, `template_version` | Full access | Own agreements only (`partner_id`) | **Blocked** | `admin.agreements.show` / `partner.agreements.show` |
| **KYC Records** | `legal_name`, `pan_number` (metadata only) | Full access (review) | **Blocked** (no review list) | **Blocked** | `admin.kyc.show` |
| **Lead Disputes** | `dispute_number`, `reason` | Full access | Own disputes only (`partner_id`) | **Blocked** | `admin.leads.disputes.show` / `partner.leads.disputes.show` |
| **Audit Logs** | `action`, `module`, `description`, `actor_name_snapshot` | Full access | **Blocked** | **Blocked** | `admin.audit-logs.show` |
| **Partners** | `partner_code`, `company_name`, `phone` | Full access | **Blocked** (no partner list) | **Blocked** | `admin.partners.show` |
| **Staff / Users** | `name`, `email`, `employee_id`, `designation` | Full access | **Blocked** (no staff directory) | **Blocked** | `admin.team-members.show` |

---

## 4. Security & Data Protection Architecture

### A. IDOR & Query-Level Scoping
All queries enforce authorization at the database layer using Eloquent/QueryBuilder:
- **Partners:** All queries enforce `where('partner_id', $partner->id)` or `where('first_partner_id', $partner->id)`. A partner searching for another partner's lead number, customer code, or company name receives zero results.
- **Staff:** All queries enforce `where('assigned_to', $user->id)`. Staff members cannot view leads assigned to colleagues.
- **Non-Searchable Entities:** Partners and Staff cannot access routes, suggest categories, or full search queries for KYC verification logs, audit logs, financial payout vouchers, or administrative user lists.

### B. Sensitive Data Exclusion
To comply with security requirements, the following attributes are **strictly excluded** from search payloads:
- Passwords and password hashes (`password`, `remember_token`).
- One-Time Passwords and tokens (`otp`, `auth_token`, `reset_token`).
- Bank details: Full bank account numbers and routing codes are never queried or returned; only unencrypted last-4 digits or bank names are shown where necessary.
- KYC dossiers: Private identity proof document files (`pan_document_path`, `aadhaar_front_path`, etc.) are never queried.

### C. Wildcard and SQL Injection Protection
- The service incorporates `GlobalSearchService::escapeLike($query)`, which sanitizes `\`, `%`, and `_` characters to prevent wildcard manipulation or performance degradation attacks.
- PDO prepared statements parameterize all query inputs, preventing SQL injection attempts (verified with `' OR '1'='1` injection payloads).

---

## 5. UI / UX Design & Workflow

### 1. Global Search Palette (Modal)
- **Shortcut Trigger:** `Ctrl + K` (Windows/Linux) or `Cmd + K` (macOS) triggers the modal from anywhere in the authenticated application.
- **Keyboard Navigation:**
  - `ArrowDown` / `ArrowUp`: Highlights search result items sequentially.
  - `Enter`: Navigates directly to the highlighted item's detail URL.
  - `Escape`: Instantly closes the search palette.
- **Debounced Live Suggestions:** Searches after 300ms debounce once 2 or more characters are entered.
- **Empty & Recent Searches State:** Displays up to 5 recently executed searches stored in session with a 1-click "Clear" button.
- **Category Grouping & Result Capping:** Live suggestions group results into clear visual sections (Leads, Customers, Projects, etc.) capped at 5 results per group, with a bottom action pill: `View all results for "[query]" →`.

### 2. Full Search Results Page (`/search`)
- Accessible by pressing Enter on the search bar or clicking "View all results".
- Displays query input, total matching records, and dynamic filter tabs for all searchable modules with active counts.
- Supports module category filtering (`module=leads`), status filtering, and date range filters (`date_from`, `date_to`).
- Renders detailed record cards (code badge, title, subtitle, status tag, metadata badges, and direct action link).
- Paginated at 15 items per page (`appends(request()->query())` ensures filter state persists across page navigation).

---

## 6. Verification and Automated Testing

### A. Dedicated Batch 7 Test Suite
Command: `php artisan test tests/Feature/Batch7GlobalSearchTest.php`  
Result: **12 / 12 passed (72 assertions, 1.73s)**

```
PASS  Tests\Feature\Batch7GlobalSearchTest
✓ 01 empty query and minimum character validation
✓ 02 admin search across supported modules
✓ 03 partner searches own records only and cannot discover other partners data
✓ 04 partner cannot discover admin staff kyc reviews or audit logs
✓ 05 staff sees assigned leads and authorized customers only
✓ 06 staff cannot discover financial or kyc records
✓ 07 partial name phone email and reference code searches
✓ 08 sql injection and like wildcard escaping
✓ 09 sensitive data exclusion
✓ 10 result caps and full search pagination
✓ 11 recent searches tracking and isolation
✓ 12 unauthenticated user redirected to login
```

### B. Full Regression Test Suite
Command: `php artisan test`  
Result: **321 / 321 passed (1,749 assertions, 150.13s)**  
All test suites across Authentication, Role Security, Admin Portals, Staff Dashboards, Partner Workspaces, Batches 1 to 6.2, and Batch 7 passed cleanly with 0 failures and 0 errors.

### C. Vite Production Build
Command: `npm run build`  
Result: **Passed (909ms)**
- `public/build/assets/app-CsrbOyym.css`: 129.35 kB (gzip: 20.68 kB)
- `public/build/assets/app-DJGfjmoA.js`: 61.24 kB (gzip: 22.97 kB)

---

## 7. Operational Notes & Constraints Compliance

1. **Destructive Commands:** Zero destructive database commands (`migrate:fresh`, `db:wipe`) were executed.
2. **Branding Compliance:** Company name is preserved as **Tech Googly** throughout all views and components.
3. **Timezone & Currency:** Application timezone strictly adheres to `Asia/Kolkata` (IST) and currency formatting `INR (₹)`.
4. **Scope Constraint:** Batch 7 is strictly completed. **Batch 8 has NOT been started.**
