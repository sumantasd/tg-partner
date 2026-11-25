# TG Partner Program – Referral Partner Agreement & Official Letterhead Update Report

**Project:** TG Partner Program  
**Framework:** Laravel 12  
**Target Domain:** `partner.techgoogly.com`  
**Date:** 24 November 2026  
**Status:** **Completed & Verified**  

---

## 1. Executive Summary

In this phase, two major updates have been implemented together on the existing Batch 2 Agreement module without creating duplicate systems or modifying historical records:

1. **Simplified Referral Partner Agreement Content:**
   - Completely replaced the previous lengthy, complex agreement covenants with the simple, 9-clause **Referral Partner Agreement** specified in Part B.
   - Enforced the strict branding rule: **Company name is always "Tech Googly"** (zero occurrences of "Tech Googly Private Limited", "Tech Googly Pvt. Ltd.", or invented legal entities).
   - Embedded clauses for Purpose of Partnership, Commission Structure, Customer Payments & Verification, Refunds & Commission Adjustments, Commission Withdrawal, Partner Responsibilities, Confidentiality, Discontinuation, and Acceptance.

2. **Official Tech Googly Letterhead PDF Redesign:**
   - Extracted and matched the visual branding from the supplied reference document (`Tech Googly Appointment (1).pdf`).
   - Implemented running curved wave graphics across the top header (Purple `#6B3D95`, Pink `#EE3A81`, Green `#5FAA45`).
   - Positioned the official Tech Googly Logo with "Innovation For You" tagline on the upper-left.
   - Replicated the upper-right contact block with vertical cyan/blue divider and verified company details.
   - Added the thin branded horizontal accent bar (Purple left 60%, Pink right 40%).
   - Replicated running footer with purple dividing rule, 3 contact items with icons (Phone, Email, Ranaghat Office Address), and bottom curved wave graphics.
   - Resolved Dompdf pagination: documents now render at an exact, natural, and balanced **3 pages** (down from 13 pages in previous iterations) with zero blank pages and no clipped elements.

---

## 2. Existing Architecture Inspected

| Component | File Path | State Prior to Update | Action Taken |
| :--- | :--- | :--- | :--- |
| **Agreement Service** | `app/Services/PartnerAgreementService.php` | Contained verbose draft fallback with legacy corporate name; basic placeholder replacement. | Updated draft fallback with simplified 9 clauses; enhanced placeholder replacement (supports `{{...}}` and `{...}`); embedded letterhead Base64 assets; enforced "Tech Googly" naming. |
| **Agreement Blade Template** | `resources/views/pdf/agreement.blade.php` | Used generic corporate letterhead with legacy "Private Limited" signature heading. | Completely redesigned with exact wave ribbons, logo with tagline, upper-right contact block, branded dividers, 3 footer contact items, and "For Tech Googly" issuance seal. |
| **Agreement Template Model** | `app/Models/AgreementTemplate.php` | Standard placeholder rendering `{key}` only. | Upgraded `render()` to handle both `{{...}}` and `{...}` case-insensitively. |
| **Partner Agreement Model** | `app/Models/PartnerAgreement.php` | Casts and relationships for partner, template, policy, and status. | Preserved without destructive changes; immutable content and rate snapshot fields remain intact. |
| **Letterhead Settings** | `system_settings` table | Missing verified phone, email, and Ranaghat address keys. | Seeded verified values: `company_name`, `company_tagline`, `company_website`, `company_phone`, `company_email`, `company_address`. |
| **Active Template** | `agreement_templates` table | Template ID 1 (v1.0) active with legacy clauses. | Created and activated `TPL-REF-V2` (v2.0) with simplified 9-clause agreement; archived v1.0. |

---

## 3. Simplified Referral Partner Agreement Content (v2.0)

The active template (`TPL-REF-V2`, version 2.0) and the service fallback now strictly utilize the 9 clauses specified in Part B:

- **Preamble & Identity:** Identifies the agreement between **Tech Googly** (Office: 2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal - 741201) and the Referral Partner (`{{Partner_Full_Name}}`, `{{Partner_Code}}`, `{{Partner_Address}}`).
- **Clause 1 (Purpose of Partnership):** Defines referral scope; Partner introduces prospects; Tech Googly communicates, prepares quotations, sets pricing, and delivers; Partner cannot independently commit pricing.
- **Clause 2 (Commission Structure):** Establishes standard commission configured via Admin Panel, with project-specific overrides when recorded in the Partner Portal.
- **Clause 3 (Commission Generation and Customer Payments):** States commission is generated *only* when customer payment is cleared and verified by Tech Googly; calculated on eligible received amounts; proportionate across milestones/instalments; quotes/invoices do not generate payable commissions.
- **Clause 4 (Refunds and Commission Adjustments):** Protects financial integrity; reversed/refunded customer payments trigger proportionate commission deductions from available balance, future commissions, or return after reconciliation.
- **Clause 5 (Commission Withdrawal):** Withdrawal requests through Partner Portal subject to Tech Googly rules, KYC approval, and verified bank accounts.
- **Clause 6 (Partner Responsibilities):** Genuine referrals, permissioned customer data, professional representation, and prohibition of duplicate/misleading leads.
- **Clause 7 (Confidentiality):** Non-disclosure and misuse prevention for customer and pricing data.
- **Clause 8 (Partnership Discontinuation):** Termination by either party via written communication; reconciliation of verified payments and refund adjustment records.
- **Clause 9 (Agreement Acceptance):** Detailed partner acknowledgments before digital signature.

---

## 4. Exact Company Branding Compliance

A strict validation audit was performed across all templates, services, views, and generated PDFs:

```
[VERIFIED] Company Legal Name: "Tech Googly"
[VERIFIED] Zero occurrences of: "Tech Googly Private Limited"
[VERIFIED] Zero occurrences of: "Tech Googly Pvt. Ltd."
[VERIFIED] Zero occurrences of: "Tech Googly Pvt Ltd"
```

The company name is strictly **Tech Googly** in the running header, the agreement preamble, the body clauses, the Commercial Schedule box, and the authorized issuance signatory box.

---

## 5. Dynamic Commission Snapshot & Placeholders

The system automatically populates dynamic placeholders from the saved agreement and policy snapshots without hardcoding:

| Placeholder (Supported Formats) | Value Source | Example Rendered Output |
| :--- | :--- | :--- |
| `{{Agreement_Number}}` / `{agreement_number}` | `partner_agreements.agreement_number` | `TGPA-2026-000004` |
| `{{Agreement_Date}}` / `{effective_date}` | `partner_agreements.effective_date` | `24 Nov 2026` |
| `{{Partner_Full_Name}}` / `{partner_name}` | `partners.user.name` | `Sunil Kumar (Partner)` |
| `{{Partner_Code}}` / `{partner_code}` | `partners.partner_code` | `TGP-2026-QA01` |
| `{{Partner_Address}}` / `{partner_address}` | Filtered address components | `2 No. Park Street Road, Ranaghat, Nadia, West Bengal - 741201` |
| `{{Standard_Commission_Rate}}` / `{commission_rate}` | `commission_rate_snapshot` | `10.00%` (or fixed amount `₹3,500.00`) |
| `{{Commission_Type}}` / `{commission_type}` | `commission_type_snapshot` | `Percentage` or `Fixed` |
| `{{Acceptance_Timestamp}}` | `accepted_at` (IST) | `24 Nov 2026, 01:38:58 PM IST` |
| `{{Partner_IP_Address}}` | `accepted_ip` | `192.168.1.105` |

### Commercial Schedule Box in PDF
```
+----------------------------------------------------------------------------------------------------+
| COMMERCIAL SCHEDULE & COMMISSION REMUNERATION                                                      |
| Standard Commission Rate: 10.00%               Commission Type:      Percentage                    |
| Commission Policy:        Standard Global      Effective Date (IST): 24 Nov 2026                   |
|                           Commission Policy v1                                                     |
| * Note: Standard commission applies unless a different project-specific commission rate or fixed  |
|   remuneration is specifically configured for a project in the Partner Portal. Commission is       |
|   payable solely on cleared, verified customer payments actually received by Tech Googly.         |
+----------------------------------------------------------------------------------------------------+
```

---

## 6. Official Letterhead Redesign Details

Assets extracted directly from the reference PDF (`Tech Googly Appointment (1).pdf`) and rendered losslessly at 300 DPI:

1. **Top Header Wave Ribbon:**
   - High-resolution composite transparent PNG (`public/images/brand/letterhead_header_waves.png`).
   - Placed at the very top edge (`left: -16mm; right: -16mm; width: 100%`) spanning edge-to-edge.
   - Contains the flowing purple arch (`#6B3D95`), vibrant pink sub-curve (`#EE3A81`), and green accent (`#5FAA45`).

2. **Header Identity Block:**
   - **Logo:** `public/images/brand/tech-googly-letterhead-logo.png` containing Tech Googly 4-color pencil crown and tagline "Innovation For You".
   - **Contact Details:** Upper-right table with vertical divider (`border-left: 2px solid #0072b8`):
     - `Phone: +91 6295710238`
     - `Web: www.techgoogly.com`
     - `ADD: 2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal - 741201`
   - **Horizontal Branded Divider:** 2px high bar beneath header (60% `#6B3D95`, 40% `#EE3A81`).

3. **Running Footer:**
   - Fixed at `bottom: -32mm; left: -16mm; right: -16mm;`.
   - Top dividing rule: 1.5px solid `#6B3D95`.
   - 3 contact items with brand-colored icons:
     - `[Phone Icon]` `+91 6295710238`
     - `[Email Icon]` `info@techgoogly.com`
     - `[Location Icon]` `2 No. Park Street Road, Near Surendra Cinema Hall, Ranaghat, Nadia, West Bengal. - 741201`
   - Bottom curved wave ribbon (`public/images/brand/letterhead_footer_waves.png`) spanning edge-to-edge.
   - Dynamic canvas page numbering: "Page X of Y" positioned at `(485, 818)`.

---

## 7. Visual QA & Pagination Findings

Visual inspections were performed on sample generated PDFs (`test_pending.pdf`, `test_accepted.pdf`):

| Page | Content Rendered | Visual Findings |
| :---: | :--- | :--- |
| **Page 1** | Letterhead Header, Document Title, Metadata Table, Preamble, Clauses 1–3, Letterhead Footer. | Clean alignment; no margin clipping; address formatted without stray commas; wave ribbons sharp. |
| **Page 2** | Letterhead Header, Clauses 4–8 (Refunds, Withdrawals, Responsibilities, Confidentiality, Discontinuation), Letterhead Footer. | Clean paragraph spacing; bullet indents aligned; headings in brand purple `#6B3D95`. |
| **Page 3** | Letterhead Header, Clause 9 (all 6 acceptance bullets), Commercial Schedule Box, Signatory Blocks (Tech Googly Issuance Seal + Partner Execution Stamp), Letterhead Footer. | Complete Clause 9 grouped cleanly without split bullets; balanced signatures; zero page overflow. |

**Total Page Count:** Exactly **3 Pages** (Zero empty or single-line pages).

---

## 8. Preview and Download Verification

Both routes were tested and verified:
1. `GET /partner/agreement/preview` returns `Content-Disposition: inline; filename="Tech_Googly_Partner_Agreement_TGPA-XXXX-XXXXXX.pdf"` with `Content-Type: application/pdf`.
2. `GET /partner/agreement/download` returns `Content-Disposition: attachment; filename="Tech_Googly_Partner_Agreement_TGPA-XXXX-XXXXXX.pdf"` with `Content-Type: application/pdf`.
3. Admin endpoints `GET /admin/agreements/{agreement}/preview` and `GET /admin/agreements/{agreement}/download` return identical binary content.
4. Partner ownership authorization checks prevent partners from accessing other partners' agreements.

---

## 9. Historical Agreement Preservation

- Historical agreement records in `partner_agreements` retain their existing `content_snapshot`, `pdf_path`, `template_version`, and `accepted_at` timestamps.
- Historical agreements are **never** regenerated or overwritten when a new template version is published.
- The new v2.0 template applies exclusively to newly issued agreements or explicitly reissued agreements.

---

## 10. Automated Test Results

The test suite was executed across all unit and feature tests:

- **Batch 2 Agreement Feature Tests (`Batch2PartnerAgreementTest.php`):** **25 passed, 145 assertions**
- **Full Application Test Suite (`php artisan test`):** **221 passed, 1,222 assertions** (0 failures, 0 errors)
- **Frontend Assets Build (`npm run build`):** Built clean in 1.12s (`manifest.json`, `app.css`, `app.js`).
- **Database Status (`php artisan migrate:status`):** All 36 migrations in `Ran` status; zero destructive database operations performed.

---

## 11. Files Created and Modified

### Created Files
- `public/images/brand/letterhead_header_waves.png` – 300 DPI composite top wave ribbon.
- `public/images/brand/letterhead_footer_waves.png` – 300 DPI composite bottom wave ribbon.
- `public/images/brand/tech-googly-letterhead-logo.png` – High-resolution logo with "Innovation For You" tagline.
- `public/images/brand/icon_phone.png` – Clean 64x64 phone icon.
- `public/images/brand/icon_email.png` – Clean 64x48 envelope icon.
- `public/images/brand/icon_location.png` – Clean 48x64 location map pin icon.
- `scratch/seed_v2_template.php` – Seeder activating template `TPL-REF-V2` (v2.0).
- `scratch/set_company_settings.php` – Seeder setting verified company contact details.
- `agreement_content_and_letterhead_update_report.md` – This comprehensive update report.

### Modified Files
- `app/Services/PartnerAgreementService.php`:
  - Enforced "Tech Googly" naming (removed all "Private Limited" references).
  - Replaced draft fallback with simplified 9-clause Referral Partner Agreement.
  - Upgraded `replacePlaceholders()` to support `{{...}}` and `{...}` case-insensitively.
  - Implemented Base64 asset loading for header waves, footer waves, logo, and footer contact icons.
  - Enhanced address formatting to prevent rogue commas.
  - Added clean page break grouping for Clause 9.
- `resources/views/pdf/agreement.blade.php`:
  - Full redesign matching `Tech Googly Appointment (1).pdf` letterhead proportions.
  - Added running header waves, logo with tagline, upper-right contact block, and branded accent bar.
  - Added running footer purple line, 3 contact items with icons, and running footer waves.
  - Updated title to "REFERRAL PARTNER AGREEMENT".
  - Updated signatory block to strictly reference "Tech Googly".
  - Added dynamic fixed/percentage handling in Commercial Schedule box.
- `app/Models/AgreementTemplate.php`:
  - Upgraded `render()` method to support `{{...}}` and `{...}` placeholder replacements.
- `tests/Feature/Batch2PartnerAgreementTest.php`:
  - Added 8 dedicated feature tests covering simplified content, omission of legacy clauses, strict "Tech Googly" branding, dynamic commission rates, fixed commission details, refund terms, letterhead contact details, and historical agreement preservation.

---

## 12. Conclusion & Ready for Review

The Referral Partner Agreement update and official letterhead redesign are complete, verified visually and through automated tests, and ready for deployment. In strict adherence to project instructions, **Batch 3 has not been started**.
