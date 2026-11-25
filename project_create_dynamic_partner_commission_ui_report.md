# Project Create & Edit Form — Dynamic Partner Commission & Policy UI Implementation Report

**Project:** TG Partner Program  
**Framework:** Laravel 12 (PHP 8.4)  
**System URL:** `http://127.0.0.1:8000`  
**Timezone:** `Asia/Kolkata`  
**Currency:** `INR (₹)`  
**Company:** Tech Googly  

---

## 1. Executive Summary

The Project Create and Project Edit screens (`/admin/projects/create` and `/admin/projects/{project}/edit`) have been enhanced with an intelligent, partner-aware **Dynamic Commission Terms & Policy Selector**. 

Previously, administrators had to manually determine a referral partner's applicable commission terms, risking human error or desynchronization with approved partner agreement policies. The enhanced architecture automatically retrieves and displays the selected partner's authoritative commission rate, simplifies the commission terms model into **exactly two explicit choices**, provides instantaneous client-side UI feedback with asynchronous server verification, and enforces strict server-side validation and financial ledger integrity.

All 390 test cases across the entire application suite—including 13 dedicated feature test scenarios in `ProjectCreateDynamicPartnerCommissionTest`—passed with 0 failures (2,045 assertions).

---

## 2. Key Architecture & Functional Deliverables

### A. Dynamic Partner Selection & Commission Rate Auto-Loading
1. **Instantaneous UI Preview via Preloaded Dictionary:**
   - On initial page load of both Create and Edit project forms, a dictionary of active partner commission structures and the platform global default policy is passed from the controller (`$partnerCommissions`, `$globalCommission`).
   - Changing the **Referral Partner** dropdown instantly updates the **Partner Commission Info Card** without network latency.
2. **Asynchronous Verification Endpoint (`GET /admin/projects/partner-commission`):**
   - Route registered before wildcard project routes to prevent matching collision:
     ```php
     Route::get('/projects/partner-commission', [AdminProjectController::class, 'resolvePartnerCommission'])
         ->name('projects.partner-commission');
     ```
   - Features animated loading spinner and graceful error fallback.
   - When no partner is selected (or "Platform Direct" is chosen), the preview cleanly reverts to the platform default (e.g. `Partner Commission: 10.00%`, `Source: Global Default Policy`).
3. **Hierarchical Resolution Precedence:**
   - **Tier 1:** Accepted Partner Agreement (`$partner->activeAgreement`) linked to an active `CommissionPolicy` or agreement rate snapshot. (Source: `Partner Assigned Policy`, Badge: Green pill).
   - **Tier 2:** Active platform-wide global default policy (`is_default = true`, `status = active`, e.g. `POL-DEF-V1`). (Source: `Global Default Policy`, Badge: Slate/Purple pill).

### B. Two-Option Commission Terms Model
The form presents **exactly two distinct radio options**, eliminating UI clutter and confusion:

```
[●] Use Default Commission Policy
    Applies partner-assigned agreement policy or platform default

[○] Custom Project Override
    Set bespoke percentage or fixed amount terms for this project
```

1. **Option 1: Use Default Commission Policy (`global_default`):**
   - Hides all custom override input fields (`custom_commission_type`, `custom_commission_rate`, `custom_fixed_frequency`, `commission_notes`).
   - Displays a locked **Effective Commission Summary Card** showing:
     - **Effective Commission:** E.g., `12.00%` or `₹5,000.00`
     - **Commission Type:** E.g., `Percentage` (Calculated on transaction value)
     - **Policy Source & Code:** E.g., `Partner Assigned Policy (POL-SPEC-V1)` or `Global Default Policy (POL-DEF-V1)`
   - Prevents accidental tampering or editing of authoritative policy terms directly from the project form.
2. **Option 2: Custom Project Override (`custom`):**
   - Dynamically reveals the custom project terms box (`custom_commission_fields_box`).
   - Fields:
     - **Custom Commission Type:** Percentage Based (%) vs Fixed Amount (INR).
     - **Custom Commission Rate / Amount:** Dynamic label and placeholder switching between `%` and `₹`.
     - **Fixed Commission Frequency:** Conditionally displayed **only** when Fixed Amount is selected (`per_payment`, `per_project`, `per_milestone`); hidden when Percentage is selected.
     - **Commission Effective Date:** Defaults to today (`Asia/Kolkata`).
     - **Commission Notes / Rationale:** For audit logging (e.g., "Negotiated 12% override for strategic referral").

---

## 3. Financial Integrity & Backend Validation

1. **Authoritative Server-Side Resolution:**
   - In `global_default` mode, server-side controllers (`ProjectController@store` and `ProjectController@update`) re-resolve the partner's applicable policy directly via `CommissionService::resolveDefaultPolicyForPartner()`.
   - Any frontend tampering (e.g. injecting high rates while in default mode) is strictly ignored.
2. **Custom Mode Strict Validation:**
   - When `commission_model === 'custom'` and type is percentage, `custom_commission_rate` is validated not to exceed 100.00%.
   - `custom_fixed_frequency` is automatically cleaned to `null` for percentage models.
   - For fixed commission, omitting `custom_fixed_frequency` fails validation with a clear message.
3. **Policy ID Integrity:**
   - Inactive or archived policy references submitted in form requests are rejected using `Rule::exists('commission_policies', 'id')->where('status', 'active')`.
4. **Historical Commission Immutability:**
   - Previously awarded commissions (`commissions` table) remain completely untouched when project terms are changed.
   - Future customer payment verifications on that project automatically adopt the updated terms.

---

## 4. Modified & Created Files

| File | Status | Description |
|---|---|---|
| `app/Services/CommissionService.php` | Modified | Added `resolveDefaultPolicyForPartner(?Partner $partner, ?string $effectiveDate)` and updated `resolveCommissionPolicyForProject()`. |
| `app/Services/ProjectService.php` | Modified | Updated `createProject()` to resolve and apply authoritative partner policy for `global_default` without overriding dynamic updates. Added missing `Partner` model import. |
| `routes/web.php` | Modified | Registered `GET /admin/projects/partner-commission` before `/admin/projects/{project}`. |
| `app/Http/Controllers/Admin/ProjectController.php` | Modified | Added `resolvePartnerCommission()` AJAX endpoint; updated `create()`, `store()`, `edit()`, and `update()` with partner preload data, model selector, and server validation. |
| `resources/views/admin/projects/create.blade.php` | Modified | Implemented partner info card, 2-option model selector, summary card, custom override container, and client JavaScript. |
| `resources/views/admin/projects/edit.blade.php` | Modified | Mirrored the exact dynamic partner card, 2-option model selector, summary card, custom fields, and JavaScript in edit view. |
| `tests/Feature/ProjectCreateDynamicPartnerCommissionTest.php` | Created | Comprehensive feature test suite with 13 tests covering all 12 user scenarios plus authorization checks. |

---

## 5. Verification & Test Execution Results

### Dedicated Test Suite: `ProjectCreateDynamicPartnerCommissionTest`
```
PASS  Tests\Feature\ProjectCreateDynamicPartnerCommissionTest
✓ ajax endpoint returns applicable commission details (0.83s)
✓ ajax endpoint returns global default when no partner id (0.04s)
✓ partner specific agreement policy takes precedence (0.07s)
✓ partner agreement snapshot rate is used (0.04s)
✓ default mode enforces authoritative resolution ignoring frontend tampering (0.04s)
✓ custom percentage override saves correctly and nulls frequency (0.04s)
✓ custom percentage override rejects rate over 100 (0.04s)
✓ custom fixed amount override saves correctly (0.04s)
✓ custom fixed amount override requires frequency (0.04s)
✓ inactive commission policy is rejected (0.04s)
✓ project edit updates policy on partner change (0.05s)
✓ historical commissions remain unchanged when terms change (0.08s)
✓ non admins cannot access ajax endpoint (0.04s)

Tests:    13 passed (60 assertions)
Duration: 1.62s
```

### Key Test File Regressions Run Together
```
PASS  Tests\Feature\Batch1TimezoneAndCommissionPolicyTest (13 passed)
PASS  Tests\Feature\Batch6CentralizedAuditLogTest (25 passed)
PASS  Tests\Feature\Batch8DynamicCommissionTest (22 passed)
PASS  Tests\Feature\Batch81FinalCommissionIntegrityTest (26 passed)
PASS  Tests\Feature\CustomerProjectPaymentAndCommissionTest (15 passed)
PASS  Tests\Feature\ProjectCreateDynamicPartnerCommissionTest (13 passed)

Tests:    114 passed (543 assertions)
Duration: 8.95s
```

### Full Application Test Suite Run
```
Tests:    390 passed (2045 assertions)
Duration: 74.74s
```

### Production Asset Compilation
```
> vite build
✓ 61 modules transformed.
public/build/manifest.json              0.33 kB │ gzip:  0.17 kB
public/build/assets/app-o1RAVk7R.css  133.69 kB │ gzip: 21.14 kB
public/build/assets/app-DJGfjmoA.js    61.24 kB │ gzip: 22.97 kB
✓ built in 821ms
```

---

## 6. Conclusion & Constraints Adherence

- **Strict Scope:** Only the Partner selection and Commission Policy & Terms sections were modified.
- **Batch 9:** Strictly **NOT** started.
- **Database Safety:** Zero destructive commands executed (`migrate:fresh` and `db:wipe` avoided; local records intact).
- **Timezone & Currency:** Preserved `Asia/Kolkata` and `INR (₹)`.
- **Branding:** Exactly preserved "Tech Googly".
