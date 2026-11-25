<?php

namespace Tests\Feature;

use App\Enums\AgreementStatus;
use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\CustomerPaymentStatus;
use App\Enums\CustomerPaymentType;
use App\Enums\KycStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentMethod;
use App\Enums\PayoutStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\WithdrawalStatus;
use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Lead;
use App\Models\LeadDispute;
use App\Models\Partner;
use App\Models\PartnerAgreement;
use App\Models\PartnerKyc;
use App\Models\Payout;
use App\Models\Project;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Services\GlobalSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Batch7GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $partnerUserA;
    protected Partner $partnerA;
    protected User $partnerUserB;
    protected Partner $partnerB;
    protected User $staffUserA;
    protected User $staffUserB;
    protected GlobalSearchService $searchService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::create([
            'name' => 'Super Administrator',
            'email' => 'admin@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        $this->partnerUserA = User::create([
            'name' => 'Aarav Patel',
            'email' => 'aarav@pateldigital.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partnerA = Partner::create([
            'user_id' => $this->partnerUserA->id,
            'partner_code' => 'TGP-2026-ARAV',
            'company_name' => 'Patel Digital Solutions',
            'phone' => '9876500001',
            'partner_type' => 'referral',
            'default_commission_rate' => 10.00,
            'kyc_status' => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->partnerUserB = User::create([
            'name' => 'Rajesh Gupta',
            'email' => 'rajesh@guptaconsulting.in',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $this->partnerB = Partner::create([
            'user_id' => $this->partnerUserB->id,
            'partner_code' => 'TGP-2026-RAJE',
            'company_name' => 'Gupta Consulting Services',
            'phone' => '9876500002',
            'partner_type' => 'referral',
            'default_commission_rate' => 12.00,
            'kyc_status' => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
            'approved_at' => now(),
            'approved_by' => $this->adminUser->id,
        ]);

        $this->staffUserA = User::create([
            'name' => 'Rohan Sharma',
            'email' => 'rohan@techgoogly.com',
            'phone' => '9811122233',
            'employee_id' => 'EMP-1001',
            'designation' => 'Senior Telecaller',
            'department' => 'Inside Sales',
            'password' => Hash::make('secret123'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->staffUserB = User::create([
            'name' => 'Pooja Verma',
            'email' => 'pooja@techgoogly.com',
            'phone' => '9811144455',
            'employee_id' => 'EMP-1002',
            'designation' => 'Sales Executive',
            'department' => 'Inside Sales',
            'password' => Hash::make('secret123'),
            'role' => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        $this->searchService = app(GlobalSearchService::class);

        // In SQLite testing, register standard LIKE escape handler so \% and \_ match literals
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlite') {
            $pdo = \Illuminate\Support\Facades\DB::connection()->getPdo();
            $pdo->sqliteCreateFunction('like', function ($pattern, $value) {
                $tokens = preg_split('/(\\\\(?:%|_|\\\\)|%|_)/', (string) $pattern, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                $regex = '';
                foreach ($tokens as $token) {
                    if ($token === '%') {
                        $regex .= '.*';
                    } elseif ($token === '_') {
                        $regex .= '.';
                    } elseif ($token === '\\%') {
                        $regex .= '%';
                    } elseif ($token === '\\_') {
                        $regex .= '_';
                    } elseif ($token === '\\\\') {
                        $regex .= '\\\\';
                    } else {
                        $regex .= preg_quote($token, '/');
                    }
                }
                return preg_match('/^' . $regex . '$/is', (string) $value) ? 1 : 0;
            }, 2);
        }
    }

    protected function makeLead(array $attributes = []): Lead
    {
        return Lead::create(array_merge([
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Sample Customer',
            'customer_phone' => '9800000000',
            'service_required' => 'Custom ERP Development',
            'project_description' => 'Detailed requirement for ERP implementation.',
            'status' => LeadStatus::Submitted,
        ], $attributes));
    }

    protected function makeProject(array $attributes = []): Project
    {
        return Project::create(array_merge([
            'partner_id' => $this->partnerA->id,
            'project_code' => 'PRJ-' . uniqid(),
            'client_name' => 'Default Client',
            'project_title' => 'Default Project Title',
            'project_description' => 'Default Project Description for Testing.',
            'final_deal_value' => 100000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ], $attributes));
    }

    /**
     * 1. Minimum character validation and empty query handling.
     */
    public function test_01_empty_query_and_minimum_character_validation(): void
    {
        // Suggest endpoint with empty query
        $resEmpty = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => '']));
        $resEmpty->assertOk();
        $resEmpty->assertJson(['total_results' => 0, 'categories' => []]);

        // Suggest endpoint with single char (less than 2)
        $resSingle = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'a']));
        $resSingle->assertOk();
        $resSingle->assertJson(['total_results' => 0, 'categories' => []]);

        // Full search page with single char renders blank prompt
        $resPage = $this->actingAs($this->adminUser)->get(route('search.index', ['q' => 'a']));
        $resPage->assertOk();
        $resPage->assertSee('Search Tech Googly Database');
    }

    /**
     * 2. Admin can search across every supported module.
     */
    public function test_02_admin_search_across_supported_modules(): void
    {
        // Setup records across modules with distinct identifier 'Phoenix'
        $lead = $this->makeLead([
            'lead_number' => 'LD-PHOENIX-01',
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Phoenix Logistics Pvt Ltd',
            'customer_phone' => '9800000001',
            'customer_email' => 'contact@phoenixlogistics.com',
            'status' => LeadStatus::Submitted,
            'estimated_value' => 100000.00,
        ]);

        $customer = Customer::create([
            'customer_code' => 'CUS-PHOENIX-01',
            'name' => 'Phoenix Retail Ltd',
            'email' => 'admin@phoenixretail.in',
            'phone' => '9800000002',
            'first_partner_id' => $this->partnerA->id,
            'lead_id' => $lead->id,
            'status' => 'active',
            'created_by' => $this->adminUser->id,
        ]);

        $project = $this->makeProject([
            'partner_id' => $this->partnerA->id,
            'customer_id' => $customer->id,
            'project_code' => 'PRJ-PHOENIX-01',
            'client_name' => $customer->name,
            'project_title' => 'Phoenix Cloud ERP Migration',
            'project_description' => 'Phoenix Cloud ERP Migration description.',
            'final_deal_value' => 250000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ]);

        $payment = CustomerPayment::create([
            'payment_number' => 'CPAY-PHOENIX-01',
            'project_id' => $project->id,
            'customer_id' => $customer->id,
            'partner_id' => $this->partnerA->id,
            'payment_type' => CustomerPaymentType::Milestone,
            'amount' => 50000.00,
            'currency' => 'INR',
            'payment_method' => PaymentMethod::BankTransfer,
            'transaction_reference' => 'TXN-PHOENIX-UTR01',
            'payment_date' => now()->toDateString(),
            'recorded_by' => $this->adminUser->id,
            'status' => CustomerPaymentStatus::Verified,
        ]);

        $commission = Commission::create([
            'commission_number' => 'COM-PHOENIX-01',
            'project_id' => $project->id,
            'partner_id' => $this->partnerA->id,
            'customer_payment_id' => $payment->id,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 50000.00,
            'rate_applied' => 10.00,
            'amount' => 5000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Approved,
        ]);

        $withdrawal = WithdrawalRequest::create([
            'request_number' => 'WDR-PHOENIX-01',
            'partner_id' => $this->partnerA->id,
            'amount' => 5000.00,
            'currency' => 'INR',
            'payout_method' => 'bank_transfer',
            'status' => WithdrawalStatus::Approved,
            'requested_at' => now(),
            'recipient_snapshot' => ['bank_name' => 'HDFC Bank', 'account_number' => '1234'],
        ]);

        $payout = Payout::create([
            'payout_number' => 'PAY-PHOENIX-01',
            'partner_id' => $this->partnerA->id,
            'withdrawal_request_id' => $withdrawal->id,
            'amount' => 5000.00,
            'currency' => 'INR',
            'payment_method' => 'bank_transfer',
            'transaction_reference' => 'UTR-PHOENIX-999',
            'payment_status' => PayoutStatus::Completed,
            'payment_date' => now()->toDateString(),
            'processed_by' => $this->adminUser->id,
            'recipient_snapshot' => ['bank_name' => 'HDFC Bank'],
        ]);

        $agreement = PartnerAgreement::create([
            'agreement_number' => 'AGR-PHOENIX-01',
            'partner_id' => $this->partnerA->id,
            'version' => 1,
            'issue_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'status' => AgreementStatus::PendingAcceptance,
            'pdf_path' => 'agreements/AGR-PHOENIX-01.pdf',
            'content_snapshot' => 'Agreement content snapshot for testing Phoenix.',
            'created_by' => $this->adminUser->id,
        ]);

        $auditLog = AuditLog::create([
            'actor_user_id' => $this->adminUser->id,
            'actor_name_snapshot' => 'Super Administrator',
            'actor_role_snapshot' => 'admin',
            'action' => AuditLogAction::ProjectCreated->value,
            'module' => AuditLogModule::Project->value,
            'description' => 'Project Phoenix created successfully.',
        ]);

        // Search for 'Phoenix' as Admin
        $response = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'Phoenix']));
        $response->assertOk();

        $data = $response->json();
        $this->assertGreaterThanOrEqual(7, $data['total_results']);

        $categoryKeys = collect($data['categories'])->pluck('key')->all();
        $this->assertContains('leads', $categoryKeys);
        $this->assertContains('customers', $categoryKeys);
        $this->assertContains('projects', $categoryKeys);
        $this->assertContains('payments', $categoryKeys);
        $this->assertContains('commissions', $categoryKeys);
        $this->assertContains('withdrawals', $categoryKeys);
        $this->assertContains('payouts', $categoryKeys);
        $this->assertContains('agreements', $categoryKeys);
        $this->assertContains('audit_logs', $categoryKeys);

        // Verify detail URLs for Admin point to admin routes
        $leadItem = collect($data['categories'])->firstWhere('key', 'leads')['items'][0];
        $this->assertEquals(route('admin.leads.show', $lead), $leadItem['url']);

        $projectItem = collect($data['categories'])->firstWhere('key', 'projects')['items'][0];
        $this->assertEquals(route('admin.projects.show', $project), $projectItem['url']);
    }

    /**
     * 3. Partner searches own records only and cannot discover another Partner's data.
     */
    public function test_03_partner_searches_own_records_only_and_cannot_discover_other_partners_data(): void
    {
        // Lead A for Partner A
        $leadA = $this->makeLead([
            'lead_number' => 'LD-AAA-01',
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Alpha Horizon Tech',
            'customer_phone' => '9811100001',
            'status' => LeadStatus::Submitted,
        ]);

        // Lead B for Partner B
        $leadB = $this->makeLead([
            'lead_number' => 'LD-BBB-01',
            'partner_id' => $this->partnerB->id,
            'customer_name' => 'Alpha Zenith Corp',
            'customer_phone' => '9811100002',
            'status' => LeadStatus::Submitted,
        ]);

        // Partner A searches for 'Alpha'
        $responseA = $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'Alpha']));
        $responseA->assertOk();

        $dataA = $responseA->json();
        $this->assertEquals(1, $dataA['total_results']);

        $leadsFound = collect($dataA['categories'])->firstWhere('key', 'leads')['items'];
        $this->assertCount(1, $leadsFound);
        $this->assertEquals('Alpha Horizon Tech', $leadsFound[0]['title']);
        $this->assertEquals(route('partner.leads.show', $leadA), $leadsFound[0]['url']);

        // Partner A searches for Partner B's specific customer or code
        $responseSecret = $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'Zenith']));
        $responseSecret->assertOk();
        $this->assertEquals(0, $responseSecret->json('total_results'));

        $responsePartnerCode = $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'TGP-2026-RAJE']));
        $responsePartnerCode->assertOk();
        $this->assertEquals(0, $responsePartnerCode->json('total_results'));
    }

    /**
     * 4. Partner cannot discover Admin, Staff, KYC reviews, or Audit logs.
     */
    public function test_04_partner_cannot_discover_admin_staff_kyc_reviews_or_audit_logs(): void
    {
        // Create an audit log and KYC record
        AuditLog::create([
            'action' => AuditLogAction::AccountSuspended->value,
            'module' => AuditLogModule::Partner->value,
            'description' => 'Confidential investigation audit record for Omega Corp.',
        ]);

        PartnerKyc::create([
            'partner_id' => $this->partnerB->id,
            'legal_name' => 'Omega Technologies Pvt Ltd',
            'kyc_status' => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
            'pan_number' => 'ABCDE1234F',
            'registered_address' => '123 Tech Park',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400001',
            'bank_account_holder_name' => 'Omega Tech',
            'bank_name' => 'HDFC Bank',
            'bank_account_number' => '1234567890',
            'bank_ifsc_code' => 'HDFC0001234',
        ]);

        // Partner searches for 'Omega' or 'Rohan' (Staff name)
        $resAudit = $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'Omega']));
        $resAudit->assertOk();
        $this->assertEquals(0, $resAudit->json('total_results'));

        $resStaff = $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'Rohan']));
        $resStaff->assertOk();
        $this->assertEquals(0, $resStaff->json('total_results'));
    }

    /**
     * 5. Staff sees assigned leads and authorized customers only.
     */
    public function test_05_staff_sees_assigned_leads_and_authorized_customers_only(): void
    {
        // Lead assigned to Staff A
        $leadAssigned = $this->makeLead([
            'lead_number' => 'LD-STAFF-01',
            'partner_id' => $this->partnerA->id,
            'assigned_to' => $this->staffUserA->id,
            'customer_name' => 'Apex Engineering Works',
            'customer_phone' => '9822200001',
            'status' => LeadStatus::Assigned,
        ]);

        // Lead assigned to Staff B
        $leadUnassigned = $this->makeLead([
            'lead_number' => 'LD-STAFF-02',
            'partner_id' => $this->partnerA->id,
            'assigned_to' => $this->staffUserB->id,
            'customer_name' => 'Apex Global Solutions',
            'customer_phone' => '9822200002',
            'status' => LeadStatus::Assigned,
        ]);

        // Staff A searches for 'Apex'
        $response = $this->actingAs($this->staffUserA)->getJson(route('search.suggest', ['q' => 'Apex']));
        $response->assertOk();

        $data = $response->json();
        $this->assertEquals(1, $data['total_results']);

        $leads = collect($data['categories'])->firstWhere('key', 'leads')['items'];
        $this->assertCount(1, $leads);
        $this->assertEquals('Apex Engineering Works', $leads[0]['title']);
        $this->assertEquals(route('team.leads.show', $leadAssigned), $leads[0]['url']);
    }

    /**
     * 6. Staff cannot discover financial records, commissions, withdrawals, or audit logs.
     */
    public function test_06_staff_cannot_discover_financial_or_kyc_records(): void
    {
        $project = $this->makeProject([
            'partner_id' => $this->partnerA->id,
            'project_code' => 'PRJ-FIN-01',
            'client_name' => 'Fintech Corp',
            'project_title' => 'Fintech Billing Engine',
            'project_description' => 'Fintech Billing Engine description.',
            'final_deal_value' => 300000.00,
            'currency' => 'INR',
            'status' => ProjectStatus::InProgress,
            'agreed_commission_type' => CommissionType::Percentage,
            'agreed_commission_rate' => 10.00,
            'start_date' => now()->toDateString(),
        ]);

        $commission = Commission::create([
            'commission_number' => 'COM-FINTECH-99',
            'project_id' => $project->id,
            'partner_id' => $this->partnerA->id,
            'commission_type' => CommissionType::Percentage,
            'calculation_basis' => 50000.00,
            'rate_applied' => 10.00,
            'amount' => 5000.00,
            'currency' => 'INR',
            'status' => CommissionStatus::Approved,
        ]);

        // Staff A searches for 'Fintech'
        $response = $this->actingAs($this->staffUserA)->getJson(route('search.suggest', ['q' => 'Fintech']));
        $response->assertOk();
        $this->assertEquals(0, $response->json('total_results'));
    }

    /**
     * 7. Partial name, phone, email, and reference code searches.
     */
    public function test_07_partial_name_phone_email_and_reference_code_searches(): void
    {
        $lead = $this->makeLead([
            'lead_number' => 'LD-2026-SEARCH88',
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Siddharth Malhotra',
            'customer_phone' => '9876543210',
            'customer_email' => 'siddharth.m@example.com',
            'status' => LeadStatus::Submitted,
        ]);

        // Partial name
        $resName = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'Malhotra']));
        $resName->assertOk();
        $this->assertGreaterThanOrEqual(1, $resName->json('total_results'));

        // Partial phone
        $resPhone = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => '5432']));
        $resPhone->assertOk();
        $this->assertGreaterThanOrEqual(1, $resPhone->json('total_results'));

        // Partial email
        $resEmail = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'siddharth.m']));
        $resEmail->assertOk();
        $this->assertGreaterThanOrEqual(1, $resEmail->json('total_results'));

        // Exact reference code
        $resCode = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'LD-2026-SEARCH88']));
        $resCode->assertOk();
        $this->assertGreaterThanOrEqual(1, $resCode->json('total_results'));
    }

    /**
     * 8. SQL injection and LIKE wildcard escaping.
     */
    public function test_08_sql_injection_and_like_wildcard_escaping(): void
    {
        // Create records with special characters
        $this->makeLead([
            'lead_number' => 'LD-DISCOUNT-10',
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Special_10%_Promo_Deal',
            'status' => LeadStatus::Submitted,
        ]);

        $this->makeLead([
            'lead_number' => 'LD-NORMAL-01',
            'partner_id' => $this->partnerA->id,
            'customer_name' => 'Special Other Customer',
            'status' => LeadStatus::Submitted,
        ]);

        // Searching '10%' should only match the literal 10%
        $resPercent = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => '10%']));
        $resPercent->assertOk();
        $this->assertEquals(1, $resPercent->json('total_results'));

        // SQL injection payload should not break query or dump unauthorized records
        $sqlPayload = "' OR '1'='1";
        $resSql = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => $sqlPayload]));
        $resSql->assertOk();
        $this->assertEquals(0, $resSql->json('total_results'));
    }

    /**
     * 9. Sensitive data exclusion (passwords, tokens, bank accounts never in payload).
     */
    public function test_09_sensitive_data_exclusion(): void
    {
        $superSecretToken = 'super_secret_auth_token_xyz_999';
        $sensitiveBankAccount = '999888777666555';

        $sensitiveUser = User::create([
            'name' => 'Secret Partner User',
            'email' => 'secret_partner@techgoogly.com',
            'password' => Hash::make('secret123'),
            'role' => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);

        $partner = Partner::create([
            'user_id' => $sensitiveUser->id,
            'partner_code' => 'TGP-SENSITIVE',
            'company_name' => 'Confidential Corp',
            'phone' => '9876599999',
            'bank_account_number' => $sensitiveBankAccount,
            'kyc_status' => KycStatus::Approved,
        ]);

        $response = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'Confidential']));
        $response->assertOk();

        $content = $response->getContent();
        // Secrets and bank account numbers must NEVER be present in JSON payload
        $this->assertStringNotContainsString($sensitiveBankAccount, $content);
        $this->assertStringNotContainsString('password', strtolower($content));
        $this->assertStringNotContainsString('password_hash', $content);
    }

    /**
     * 10. Result caps and full search pagination.
     */
    public function test_10_result_caps_and_full_search_pagination(): void
    {
        // Create 8 leads with identical prefix
        for ($i = 1; $i <= 8; $i++) {
            $this->makeLead([
                'lead_number' => "LD-CAPS-{$i}",
                'partner_id' => $this->partnerA->id,
                'customer_name' => "Caps Lead {$i}",
                'status' => LeadStatus::Submitted,
            ]);
        }

        // Live suggest caps at 5 items per category
        $resSuggest = $this->actingAs($this->adminUser)->getJson(route('search.suggest', ['q' => 'Caps Lead']));
        $resSuggest->assertOk();
        $leadCategory = collect($resSuggest->json('categories'))->firstWhere('key', 'leads');
        $this->assertNotNull($leadCategory);
        $this->assertCount(5, $leadCategory['items']); // Capped at 5!

        // Full search page displays all 8 items
        $resPage = $this->actingAs($this->adminUser)->get(route('search.index', ['q' => 'Caps Lead']));
        $resPage->assertOk();
        $resPage->assertSee('Showing 1 to 8 of 8 results');
        $resPage->assertSee('Caps Lead 8');
    }

    /**
     * 11. Recent searches tracking and isolation.
     */
    public function test_11_recent_searches_tracking_and_isolation(): void
    {
        // Partner A searches for 'Aarav'
        $this->actingAs($this->partnerUserA)->getJson(route('search.suggest', ['q' => 'Aarav']));
        $this->assertEquals(['Aarav'], session('recent_searches'));

        // Clear recent searches
        $resClear = $this->actingAs($this->partnerUserA)->postJson(route('search.recent.clear'));
        $resClear->assertOk();
        $this->assertNull(session('recent_searches'));
    }

    /**
     * 12. Unauthenticated user redirected to login.
     */
    public function test_12_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('search.index'))->assertRedirect(route('login'));
        $this->getJson(route('search.suggest', ['q' => 'test']))->assertUnauthorized();
    }
}
