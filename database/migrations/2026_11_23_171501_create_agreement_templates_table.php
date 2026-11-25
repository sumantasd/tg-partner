<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('agreement_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_code', 50)->unique();
            $table->string('title', 255);
            $table->string('version', 20)->default('1.0');
            $table->longText('content');
            $table->string('status', 30)->default('draft'); // draft, active, archived
            $table->boolean('is_active')->default(false);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'status']);
        });

        $adminUser = DB::table('users')->where('role', 'admin')->first();

        // Baseline draft content for legal and admin review
        $draftContent = <<<'MARKDOWN'
# BUSINESS PARTNER AGREEMENT (DRAFT)

**Agreement Number:** {agreement_number}  
**Date of Agreement:** {effective_date}  
**Company:** Tech Googly Private Limited ("Tech Googly")  
**Partner Entity:** {company_name} ("Business Partner")  
**Authorized Representative:** {partner_name}  
**Partner Identification Code:** {partner_code}  

---

## 1. APPOINTMENT & ENGAGEMENT
Tech Googly hereby appoints the Business Partner as an independent authorized referral partner for its digital products, custom cloud software, and IT services, subject to the terms and covenants contained herein.

## 2. LEAD REFERRAL & PROTECTION RULES
2.1. The Business Partner shall submit prospective business leads via the authorized Tech Googly Partner Portal.
2.2. A lead is formally registered once submitted and verified against the 90-day duplicate cooling policy.
2.3. The Partner shall retain exclusive referral ownership for an active registered lead for the duration of its sales lifecycle, subject to satisfactory customer contact and SLA adherence.

## 3. COMMERCIAL COMMISSION POLICY
3.1. Standard commercial remuneration shall follow the active platform commission rate: **{commission_rate} ({commission_type})**.
3.2. Commission shall be generated and credited to the Partner's ledger only upon actual verification of cleared customer payments by Tech Googly Finance.
3.3. Under no circumstances shall commission be disbursed on unverified, cancelled, or refunded customer payments.

## 4. SERVICE LEVEL AGREEMENT & ETHICAL CONDUCT
4.1. The Partner agrees to represent Tech Googly's service capabilities truthfully and accurately.
4.2. The Partner shall not misrepresent pricing, project turnaround times, or commit to warranties not expressly authorized by Tech Googly in writing.

## 5. CONFIDENTIALITY & PROPRIETARY RIGHTS
All client data, project architecture specifications, technical designs, pricing sheets, and customer contact lists shall be treated as Strictly Confidential. The Partner shall not disclose any proprietary materials during or after the termination of this agreement.

## 6. DURATION & TERMINATION
6.1. This agreement takes effect on {effective_date} and shall remain in force unless terminated by either party with thirty (30) days' written notice.
6.2. Tech Googly reserves the right to immediately suspend or terminate any Partner engaging in fraudulent lead submission, code tampering, or unethical practices.

---
*Note: This agreement is a versioned draft template for Tech Googly legal administration and partner onboarding compliance.*
MARKDOWN;

        DB::table('agreement_templates')->insert([
            'template_code' => 'TPL-AGR-V1',
            'title' => 'Standard Tech Googly Business Partner Agreement',
            'version' => '1.0',
            'content' => $draftContent,
            'status' => 'active',
            'is_active' => true,
            'effective_from' => '2026-01-01',
            'effective_until' => null,
            'created_by' => $adminUser ? $adminUser->id : null,
            'notes' => 'Baseline agreement template version 1.0 created for Partner Onboarding and Legal Review.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agreement_templates');
    }
};
