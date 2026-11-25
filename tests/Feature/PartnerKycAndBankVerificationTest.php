<?php

namespace Tests\Feature;

use App\Enums\BankVerificationStatus;
use App\Enums\CommissionStatus;
use App\Enums\CommissionType;
use App\Enums\KycStatus;
use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Partner;
use App\Models\PartnerKyc;
use App\Models\Project;
use App\Models\User;
use App\Services\WithdrawalService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerKycAndBankVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $teamUser;
    protected User $partnerUser1;
    protected Partner $partner1;
    protected User $partnerUser2;
    protected Partner $partner2;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Admin User
        $this->admin = User::factory()->create([
            'name'   => 'Admin User',
            'email'  => 'admin@techgoogly.com',
            'role'   => UserRole::Admin,
            'status' => UserStatus::Active,
        ]);

        // Team Member User
        $this->teamUser = User::factory()->create([
            'name'   => 'Team Staff',
            'email'  => 'staff@techgoogly.com',
            'role'   => UserRole::TeamMember,
            'status' => UserStatus::Active,
        ]);

        // Partner 1
        $this->partnerUser1 = User::factory()->create([
            'name'   => 'Partner One',
            'email'  => 'partner1@test.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner1 = Partner::create([
            'user_id'                  => $this->partnerUser1->id,
            'partner_code'             => 'TGP-KYC01',
            'company_name'             => 'Partner One Corp',
            'phone'                    => '9876543210',
            'bank_name'                => 'HDFC Bank',
            'bank_account_name'        => 'Partner One Corp',
            'bank_account_number'      => '123456789012',
            'bank_routing_code'        => 'HDFC0001234',
            'payout_method'            => 'bank_transfer',
            'kyc_status'               => KycStatus::NotSubmitted,
            'bank_verification_status' => BankVerificationStatus::NotSubmitted,
        ]);

        // Partner 2
        $this->partnerUser2 = User::factory()->create([
            'name'   => 'Partner Two',
            'email'  => 'partner2@test.com',
            'role'   => UserRole::Partner,
            'status' => UserStatus::Active,
        ]);
        $this->partner2 = Partner::create([
            'user_id'                  => $this->partnerUser2->id,
            'partner_code'             => 'TGP-KYC02',
            'company_name'             => 'Partner Two Corp',
            'phone'                    => '9876543211',
            'bank_name'                => 'ICICI Bank',
            'bank_account_name'        => 'Partner Two Corp',
            'bank_account_number'      => '987654321098',
            'bank_routing_code'        => 'ICIC0001234',
            'payout_method'            => 'bank_transfer',
            'kyc_status'               => KycStatus::NotSubmitted,
            'bank_verification_status' => BankVerificationStatus::NotSubmitted,
        ]);
    }

    /**
     * 1. Partner can submit KYC.
     */
    public function test_partner_can_submit_kyc(): void
    {
        $panPdf = UploadedFile::fake()->create('pan_card.pdf', 500, 'application/pdf');
        $idPdf = UploadedFile::fake()->create('aadhaar_card_front.pdf', 600, 'application/pdf');
        $idBackPdf = UploadedFile::fake()->create('aadhaar_card_back.pdf', 600, 'application/pdf');
        $bankPdf = UploadedFile::fake()->create('bank_passbook.pdf', 700, 'application/pdf');

        $response = $this->actingAs($this->partnerUser1)->post(route('partner.kyc.store'), [
            'legal_name'                    => 'Partner One Corp',
            'partner_type'                  => 'private_limited',
            'authorized_signatory_name'     => 'Director One',
            'business_registration_number'  => 'U72200WB2020PTC123456',
            'gstin'                         => '19ABCDE1234F1Z5',
            'dob_or_incorporation_date'     => '2020-01-15',
            'pan_number'                    => 'ABCDE1234F',
            'registered_address'            => '123 Business Boulevard, Suite 400',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700001',
            'country'                       => 'India',
            'identity_document_type'        => 'aadhaar',
            'id_contains_current_address'   => '1',
            'pan_document'                  => $panPdf,
            'identity_document'             => $idPdf,
            'identity_document_back'        => $idBackPdf,
            'bank_proof_document'           => $bankPdf,
            'bank_account_holder_name'      => 'Partner One Corp',
            'bank_name'                     => 'State Bank of India',
            'bank_account_number'           => '112233445566',
            'bank_account_number_confirmation' => '112233445566',
            'bank_ifsc_code'                => 'SBIN0001234',
            'upi_id'                        => 'partnerone@sbi',
            'preferred_payout_method'       => 'bank_transfer',
            'declaration_accepted'          => '1',
        ]);

        $response->assertRedirect(route('partner.kyc.show'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('partner_kycs', [
            'partner_id' => $this->partner1->id,
            'legal_name' => 'Partner One Corp',
            'authorized_signatory_name' => 'Director One',
            'business_registration_number' => 'U72200WB2020PTC123456',
            'declaration_accepted' => 1,
            'kyc_status' => KycStatus::Pending->value,
            'bank_verification_status' => BankVerificationStatus::Pending->value,
        ]);

        $this->partner1->refresh();
        $this->assertEquals(KycStatus::Pending, $this->partner1->kyc_status);
        $this->assertEquals(BankVerificationStatus::Pending, $this->partner1->bank_verification_status);

        $kyc = $this->partner1->kyc;
        $this->assertEquals('ABCDE••••F', $kyc->pan_number_masked);
        $this->assertNotNull($kyc->pan_document_path);
        $this->assertNotNull($kyc->identity_document_back_path);
        Storage::disk('local')->assertExists($kyc->pan_document_path);
        Storage::disk('local')->assertExists($kyc->identity_document_path);
        Storage::disk('local')->assertExists($kyc->identity_document_back_path);
        Storage::disk('local')->assertExists($kyc->bank_proof_document_path);
    }

    /**
     * 2. Partner cannot edit KYC after submission (strict submission lock).
     */
    public function test_partner_cannot_edit_kyc_after_submission_lock(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Original Partner Legal Name',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Original Address',
            'city'                        => 'Kolkata',
            'state'                       => 'West Bengal',
            'postal_code'                 => '700001',
            'country'                     => 'India',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Original Partner Legal Name',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        // Attempting to visit submission form redirects with warning
        $createResponse = $this->actingAs($this->partnerUser1)->get(route('partner.kyc.create'));
        $createResponse->assertRedirect(route('partner.kyc.show'));
        $createResponse->assertSessionHas('warning');

        // Attempting to POST new data is blocked
        $storeResponse = $this->actingAs($this->partnerUser1)->post(route('partner.kyc.store'), [
            'legal_name' => 'Attempted Tampered Name',
        ]);
        $storeResponse->assertRedirect(route('partner.kyc.show'));
        $storeResponse->assertSessionHasErrors('kyc');

        $kyc->refresh();
        $this->assertEquals('Original Partner Legal Name', $kyc->legal_name);
    }

    /**
     * 3. Partner cannot replace submitted documents while locked.
     */
    public function test_partner_cannot_replace_submitted_documents_while_locked(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Original Address',
            'city'                        => 'Kolkata',
            'state'                       => 'West Bengal',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/original_pan.pdf',
            'identity_document_path'      => 'private/kyc/original_id.pdf',
            'bank_proof_document_path'    => 'private/kyc/original_bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::UnderReview,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::UnderReview,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        $newDoc = UploadedFile::fake()->create('tampered_pan.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->partnerUser1)->post(route('partner.kyc.resubmit'), [
            'pan_document' => $newDoc,
        ]);

        // Resubmit without correction authorization redirects with error
        $response->assertRedirect(route('partner.kyc.show'));
        $response->assertSessionHasErrors('kyc');

        $kyc->refresh();
        $this->assertEquals('private/kyc/original_pan.pdf', $kyc->pan_document_path);
    }

    /**
     * 4. Partner cannot change bank details after submission.
     */
    public function test_partner_cannot_change_bank_details_after_submission(): void
    {
        $this->partner1->update([
            'kyc_status'               => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        $response = $this->actingAs($this->partnerUser1)->put(route('partner.payout-settings.update'), [
            'payout_method'       => 'bank_transfer',
            'bank_name'           => 'Unauthorized Bank Switch',
            'bank_account_name'   => 'Tampered Account Holder',
            'bank_account_number' => '999999999999',
            'bank_routing_code'   => 'SBIN0009999',
        ]);

        $response->assertRedirect(route('partner.payout-settings.edit'));
        $response->assertSessionHas('error');

        $this->partner1->refresh();
        $this->assertEquals('HDFC Bank', $this->partner1->bank_name);
        $this->assertEquals('123456789012', $this->partner1->bank_account_number);
    }

    /**
     * 5. Partner cannot independently reopen rejected KYC.
     */
    public function test_partner_cannot_independently_reopen_rejected_kyc(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Rejected,
            'bank_verification_status'    => BankVerificationStatus::Rejected,
            'rejection_reason'            => 'Fraudulent documents detected.',
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::Rejected,
            'bank_verification_status' => BankVerificationStatus::Rejected,
        ]);

        // Attempting to access correction form is redirected
        $correctionPageResponse = $this->actingAs($this->partnerUser1)->get(route('partner.kyc.correction'));
        $correctionPageResponse->assertRedirect(route('partner.kyc.show'));

        // Attempting to post correction resubmission fails
        $resubmitResponse = $this->actingAs($this->partnerUser1)->post(route('partner.kyc.resubmit'), [
            'legal_name' => 'Trying to reopen',
        ]);
        $resubmitResponse->assertRedirect(route('partner.kyc.show'));
        $resubmitResponse->assertSessionHasErrors('kyc');

        $kyc->refresh();
        $this->assertEquals(KycStatus::Rejected, $kyc->kyc_status);
    }

    /**
     * 6. Admin can approve KYC.
     */
    public function test_admin_can_approve_kyc(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.kyc.approve-kyc', $kyc), [
            'internal_admin_notes' => 'PAN verified via NSDL portal.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kyc->refresh();
        $this->assertEquals(KycStatus::Approved, $kyc->kyc_status);
        $this->assertEquals($this->admin->id, $kyc->kyc_approved_by);
        $this->assertNotNull($kyc->kyc_approved_at);
        $this->assertEquals('PAN verified via NSDL portal.', $kyc->internal_admin_notes);

        $this->partner1->refresh();
        $this->assertEquals(KycStatus::Approved, $this->partner1->kyc_status);
        $this->assertDatabaseHas('partner_kyc_histories', [
            'partner_kyc_id' => $kyc->id,
            'action'         => 'kyc_approved',
            'admin_id'       => $this->admin->id,
        ]);
    }

    /**
     * 7. Admin can reject with mandatory reason.
     */
    public function test_admin_can_reject_kyc_with_mandatory_reason(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        // Attempt rejection without reason fails validation
        $emptyResponse = $this->actingAs($this->admin)->post(route('admin.kyc.reject-kyc', $kyc), [
            'rejection_reason' => '',
        ]);
        $emptyResponse->assertSessionHasErrors('rejection_reason');

        // Rejection with mandatory reason succeeds
        $successResponse = $this->actingAs($this->admin)->post(route('admin.kyc.reject-kyc', $kyc), [
            'rejection_reason'     => 'The submitted PAN belongs to a blacklisted entity.',
            'internal_admin_notes' => 'Flagged by risk engine.',
        ]);

        $successResponse->assertRedirect();
        $successResponse->assertSessionHas('success');

        $kyc->refresh();
        $this->assertEquals(KycStatus::Rejected, $kyc->kyc_status);
        $this->assertEquals('The submitted PAN belongs to a blacklisted entity.', $kyc->rejection_reason);

        $this->partner1->refresh();
        $this->assertEquals(KycStatus::Rejected, $this->partner1->kyc_status);
    }

    /**
     * 8. Admin can request correction with mandatory reason.
     */
    public function test_admin_can_request_correction_with_mandatory_reason(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        // Attempt correction request without instructions fails validation
        $failResponse = $this->actingAs($this->admin)->post(route('admin.kyc.request-correction', $kyc), [
            'correction_reason' => '',
        ]);
        $failResponse->assertSessionHasErrors('correction_reason');

        // Correction request with valid reason and allowed fields succeeds
        $response = $this->actingAs($this->admin)->post(route('admin.kyc.request-correction', $kyc), [
            'correction_reason'    => 'Please upload a clear, legible photograph of your PAN card.',
            'allowed_fields'       => ['pan_document', 'pan_number'],
            'internal_admin_notes' => 'Corner was clipped on original scan.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $kyc->refresh();
        $this->assertEquals(KycStatus::CorrectionRequested, $kyc->kyc_status);
        $this->assertEquals('Please upload a clear, legible photograph of your PAN card.', $kyc->correction_instructions);
        $this->assertContains('pan_document', $kyc->allowed_correction_fields);
        $this->assertContains('pan_number', $kyc->allowed_correction_fields);

        $this->partner1->refresh();
        $this->assertEquals(KycStatus::CorrectionRequested, $this->partner1->kyc_status);
    }

    /**
     * 9. Partner can edit only authorized correction fields.
     */
    public function test_partner_can_edit_only_authorized_correction_fields(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Original Address',
            'city'                        => 'Kolkata',
            'state'                       => 'West Bengal',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/original_pan.pdf',
            'identity_document_path'      => 'private/kyc/original_id.pdf',
            'bank_proof_document_path'    => 'private/kyc/original_bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::CorrectionRequested,
            'bank_verification_status'    => BankVerificationStatus::CorrectionRequested,
            'correction_instructions'     => 'Update your PAN number only.',
            'correction_reason'           => 'Update your PAN number only.',
            'allowed_correction_fields'   => ['pan_number'],
            'correction_fields'           => ['pan_number'],
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::CorrectionRequested,
            'bank_verification_status' => BankVerificationStatus::CorrectionRequested,
        ]);

        // Partner attempts to update pan_number (allowed) and legal_name (unauthorized)
        $response = $this->actingAs($this->partnerUser1)->post(route('partner.kyc.resubmit'), [
            'pan_number' => 'XYZPA9999K',
            'legal_name' => 'Unauthorized Tampered Legal Name',
        ]);

        $response->assertRedirect(route('partner.kyc.show'));
        $response->assertSessionHas('success');

        $kyc->refresh();
        // pan_number is updated
        $this->assertEquals('XYZPA9999K', $kyc->pan_number);
        // legal_name remains unchanged because it was not in allowed_correction_fields
        $this->assertEquals('Partner One Corp', $kyc->legal_name);
        // Status resets to pending review
        $this->assertEquals(KycStatus::Pending, $kyc->kyc_status);
    }

    /**
     * 10. Previous KYC submission remains preserved in history snapshot.
     */
    public function test_previous_kyc_submission_remains_preserved_in_history_snapshot(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Snapshot Company v1',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address v1',
            'city'                        => 'Kolkata',
            'state'                       => 'West Bengal',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/v1_pan.pdf',
            'identity_document_path'      => 'private/kyc/v1_id.pdf',
            'bank_proof_document_path'    => 'private/kyc/v1_bank.pdf',
            'bank_account_holder_name'    => 'Snapshot Company v1',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::CorrectionRequested,
            'bank_verification_status'    => BankVerificationStatus::CorrectionRequested,
            'correction_instructions'     => 'Update legal name and city.',
            'allowed_correction_fields'   => ['legal_name', 'city'],
            'submitted_at'                => now(),
        ]);
        $this->partner1->update([
            'kyc_status' => KycStatus::CorrectionRequested,
            'bank_verification_status' => BankVerificationStatus::CorrectionRequested,
        ]);

        // Partner resubmits with new legal_name and city
        $this->actingAs($this->partnerUser1)->post(route('partner.kyc.resubmit'), [
            'legal_name' => 'Snapshot Company v2',
            'city'       => 'Mumbai',
        ]);

        // Historical snapshot must preserve v1 details
        $history = $kyc->histories()->orderBy('id', 'desc')->first();
        $this->assertNotNull($history);
        $this->assertEquals('resubmitted_correction', $history->action);
        $this->assertIsArray($history->snapshot_data);
        $this->assertEquals('Snapshot Company v1', $history->snapshot_data['legal_name']);
        $this->assertEquals('Kolkata', $history->snapshot_data['city']);
    }

    /**
     * 11. KYC approval alone is insufficient if bank verification is pending.
     */
    public function test_kyc_approval_alone_is_insufficient_if_bank_verification_is_pending(): void
    {
        $this->partner1->update([
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        $this->assertTrue($this->partner1->isKycApproved());
        $this->assertFalse($this->partner1->isBankVerified());
        $this->assertFalse($this->partner1->isFullyKycVerified());
    }

    /**
     * 12. Withdrawal is blocked until both KYC and Bank Verification are approved.
     */
    public function test_withdrawal_is_blocked_until_both_kyc_and_bank_are_approved(): void
    {
        $withdrawalService = app(WithdrawalService::class);

        // Create an eligible customer, project and payable commission for Partner 1
        $customer = Customer::create([
            'partner_id'    => $this->partner1->id,
            'customer_code' => 'CUST-KYC-001',
            'name'          => 'Client One Corp',
            'phone'         => '9876543210',
            'status'        => 'active',
        ]);

        $project = Project::create([
            'partner_id'          => $this->partner1->id,
            'customer_id'         => $customer->id,
            'project_code'        => 'PRJ-KYC-01',
            'project_title'       => 'Compliance Test Project',
            'client_name'         => 'Client One Corp',
            'project_description' => 'Test project description',
            'budget'              => 100000.00,
            'status'              => ProjectStatus::Completed,
        ]);

        Commission::create([
            'partner_id'        => $this->partner1->id,
            'project_id'        => $project->id,
            'commission_number' => 'COM-KYC-001',
            'amount'            => 5000.00,
            'type'              => CommissionType::Fixed,
            'status'            => CommissionStatus::Payable,
            'milestone_name'    => 'Full Delivery Milestone',
        ]);

        // Scenario A: KYC Not Approved
        $this->partner1->update([
            'kyc_status'               => KycStatus::Pending,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Your KYC verification is pending');
        $withdrawalService->requestWithdrawal($this->partner1, 1000.00, 'bank_transfer');
    }

    /**
     * 12b. Withdrawal is blocked when KYC is approved but Bank verification is pending.
     */
    public function test_withdrawal_is_blocked_when_kyc_approved_but_bank_pending(): void
    {
        $withdrawalService = app(WithdrawalService::class);

        $customer = Customer::create([
            'partner_id'    => $this->partner1->id,
            'customer_code' => 'CUST-KYC-002',
            'name'          => 'Client One Corp',
            'phone'         => '9876543210',
            'status'        => 'active',
        ]);

        $project = Project::create([
            'partner_id'          => $this->partner1->id,
            'customer_id'         => $customer->id,
            'project_code'        => 'PRJ-KYC-02',
            'project_title'       => 'Compliance Test Project 2',
            'client_name'         => 'Client One Corp',
            'project_description' => 'Test project description',
            'budget'              => 100000.00,
            'status'              => ProjectStatus::Completed,
        ]);

        Commission::create([
            'partner_id'        => $this->partner1->id,
            'project_id'        => $project->id,
            'commission_number' => 'COM-KYC-002',
            'amount'            => 5000.00,
            'type'              => CommissionType::Fixed,
            'status'            => CommissionStatus::Payable,
            'milestone_name'    => 'Milestone 2',
        ]);

        $this->partner1->update([
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Pending,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Your bank account verification is pending');
        $withdrawalService->requestWithdrawal($this->partner1, 1000.00, 'bank_transfer');
    }

    /**
     * 12c. Withdrawal succeeds when both KYC is approved and Bank is verified.
     */
    public function test_withdrawal_succeeds_when_both_kyc_and_bank_are_verified(): void
    {
        $withdrawalService = app(WithdrawalService::class);

        $customer = Customer::create([
            'partner_id'    => $this->partner1->id,
            'customer_code' => 'CUST-KYC-003',
            'name'          => 'Client One Corp',
            'phone'         => '9876543210',
            'status'        => 'active',
        ]);

        $project = Project::create([
            'partner_id'          => $this->partner1->id,
            'customer_id'         => $customer->id,
            'project_code'        => 'PRJ-KYC-03',
            'project_title'       => 'Compliance Test Project 3',
            'client_name'         => 'Client One Corp',
            'project_description' => 'Test project description',
            'budget'              => 100000.00,
            'status'              => ProjectStatus::Completed,
        ]);

        Commission::create([
            'partner_id'        => $this->partner1->id,
            'project_id'        => $project->id,
            'commission_number' => 'COM-KYC-003',
            'amount'            => 5000.00,
            'type'              => CommissionType::Fixed,
            'status'            => CommissionStatus::Payable,
            'milestone_name'    => 'Milestone 3',
        ]);

        $this->partner1->update([
            'kyc_status'               => KycStatus::Approved,
            'bank_verification_status' => BankVerificationStatus::Verified,
        ]);

        $withdrawal = $withdrawalService->requestWithdrawal($this->partner1, 2000.00, 'bank_transfer');
        $this->assertNotNull($withdrawal);
        $this->assertEquals(2000.00, $withdrawal->amount);
    }

    /**
     * 13. Team Member cannot access KYC details (HTTP 403 Forbidden).
     */
    public function test_team_member_cannot_access_kyc_details(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        // Team Member cannot access Admin KYC Index
        $responseIndex = $this->actingAs($this->teamUser)->get(route('admin.kyc.index'));
        $responseIndex->assertStatus(403);

        // Team Member cannot access Admin KYC Show
        $responseShow = $this->actingAs($this->teamUser)->get(route('admin.kyc.show', $kyc));
        $responseShow->assertStatus(403);

        // Team Member cannot access Partner KYC Show
        $responsePartnerShow = $this->actingAs($this->teamUser)->get(route('partner.kyc.show'));
        $responsePartnerShow->assertStatus(403);
    }

    /**
     * 14. Partner cannot view another Partner's documents (HTTP 403 / 404).
     */
    public function test_partner_cannot_view_another_partners_documents(): void
    {
        Storage::disk('local')->put('private/kyc_documents/2/pan.pdf', 'dummy content');

        $kyc2 = PartnerKyc::create([
            'partner_id'                  => $this->partner2->id,
            'legal_name'                  => 'Partner Two Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2021-02-20',
            'pan_number_encrypted'        => 'BCDEA2345G',
            'pan_number_masked'           => 'XXXXXX345G',
            'registered_address'          => 'Address 2',
            'city'                        => 'Mumbai',
            'state'                       => 'Maharashtra',
            'postal_code'                 => '400001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc_documents/2/pan.pdf',
            'identity_document_path'      => 'private/kyc_documents/2/id.pdf',
            'bank_proof_document_path'    => 'private/kyc_documents/2/bank.pdf',
            'bank_account_holder_name'    => 'Partner Two Corp',
            'bank_name'                   => 'ICICI Bank',
            'bank_account_number_encrypted' => '987654321098',
            'bank_account_number_masked'  => 'XXXXXXXX1098',
            'bank_ifsc_code'              => 'ICIC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        // Partner 1 tries to access Admin KYC download endpoint for Partner 2
        $responseAdminDownload = $this->actingAs($this->partnerUser1)->get(
            route('admin.kyc.document', ['kyc' => $kyc2, 'type' => 'pan'])
        );
        $responseAdminDownload->assertStatus(403);

        // Partner 1 tries partner document endpoint - partner 1 has no pan document yet -> 404
        $responsePartnerDownload = $this->actingAs($this->partnerUser1)->get(
            route('partner.kyc.document', ['type' => 'pan'])
        );
        $responsePartnerDownload->assertStatus(404);
    }

    /**
     * 15. Unauthorized users cannot download private KYC files.
     */
    public function test_unauthorized_users_cannot_download_private_kyc_files(): void
    {
        $kyc = PartnerKyc::create([
            'partner_id'                  => $this->partner1->id,
            'legal_name'                  => 'Partner One Corp',
            'partner_type'                => 'business',
            'dob_or_incorporation_date'   => '2020-01-15',
            'pan_number_encrypted'        => 'ABCDE1234F',
            'pan_number_masked'           => 'XXXXXX234F',
            'registered_address'          => 'Address',
            'city'                        => 'Kolkata',
            'state'                       => 'WB',
            'postal_code'                 => '700001',
            'identity_document_type'      => 'aadhaar',
            'pan_document_path'           => 'private/kyc/pan.pdf',
            'identity_document_path'      => 'private/kyc/id.pdf',
            'bank_proof_document_path'    => 'private/kyc/bank.pdf',
            'bank_account_holder_name'    => 'Partner One Corp',
            'bank_name'                   => 'HDFC Bank',
            'bank_account_number_encrypted' => '123456789012',
            'bank_account_number_masked'  => 'XXXXXXXX9012',
            'bank_ifsc_code'              => 'HDFC0001234',
            'preferred_payout_method'     => 'bank_transfer',
            'kyc_status'                  => KycStatus::Pending,
            'bank_verification_status'    => BankVerificationStatus::Pending,
            'submitted_at'                => now(),
        ]);

        // Unauthenticated guest is redirected to login
        $guestPartnerDoc = $this->get(route('partner.kyc.document', ['type' => 'pan']));
        $guestPartnerDoc->assertRedirect(route('login'));

        $guestAdminDoc = $this->get(route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'pan']));
        $guestAdminDoc->assertRedirect(route('login'));

        // Team member accessing admin doc endpoint gets 403 Forbidden
        $teamAdminDoc = $this->actingAs($this->teamUser)->get(route('admin.kyc.document', ['kyc' => $kyc, 'type' => 'pan']));
        $teamAdminDoc->assertStatus(403);
    }

    /**
     * 16. Individual partner can submit with single ID upload and without corporate registration fields.
     */
    public function test_individual_partner_can_submit_kyc_with_single_id(): void
    {
        $panPdf = UploadedFile::fake()->create('pan_card.pdf', 500, 'application/pdf');
        $passportPdf = UploadedFile::fake()->create('passport.pdf', 600, 'application/pdf');
        $bankPdf = UploadedFile::fake()->create('passbook.pdf', 700, 'application/pdf');

        $response = $this->actingAs($this->partnerUser2)->post(route('partner.kyc.store'), [
            'legal_name'                    => 'Individual Partner Name',
            'partner_type'                  => 'individual_sole_proprietor',
            'dob_or_incorporation_date'     => '1992-05-20',
            'pan_number'                    => 'PQRST5678M',
            'registered_address'            => '77 Park Street',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700016',
            'identity_document_type'        => 'passport',
            'id_contains_current_address'   => '1',
            'pan_document'                  => $panPdf,
            'identity_document'             => $passportPdf,
            'bank_proof_document'           => $bankPdf,
            'bank_account_holder_name'      => 'Individual Partner Name',
            'bank_name'                     => 'ICICI Bank',
            'bank_account_number'           => '998877665544',
            'bank_account_number_confirmation' => '998877665544',
            'bank_ifsc_code'                => 'ICIC0001234',
            'preferred_payout_method'       => 'bank_transfer',
            'declaration_accepted'          => '1',
        ]);

        $response->assertRedirect(route('partner.kyc.show'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('partner_kycs', [
            'partner_id'   => $this->partner2->id,
            'legal_name'   => 'Individual Partner Name',
            'partner_type' => 'individual_sole_proprietor',
        ]);
    }

    /**
     * 17. Aadhaar requires both front and back document uploads.
     */
    public function test_aadhaar_requires_both_front_and_back_uploads(): void
    {
        $panPdf = UploadedFile::fake()->create('pan.pdf', 500, 'application/pdf');
        $aadhaarFront = UploadedFile::fake()->create('aadhaar_front.pdf', 600, 'application/pdf');
        $bankPdf = UploadedFile::fake()->create('passbook.pdf', 700, 'application/pdf');

        // Missing identity_document_back when type is aadhaar
        $response = $this->actingAs($this->partnerUser2)->post(route('partner.kyc.store'), [
            'legal_name'                    => 'Individual Partner Name',
            'partner_type'                  => 'individual_sole_proprietor',
            'dob_or_incorporation_date'     => '1992-05-20',
            'pan_number'                    => 'PQRST5678M',
            'registered_address'            => '77 Park Street',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700016',
            'identity_document_type'        => 'aadhaar',
            'id_contains_current_address'   => '1',
            'pan_document'                  => $panPdf,
            'identity_document'             => $aadhaarFront,
            'bank_proof_document'           => $bankPdf,
            'bank_account_holder_name'      => 'Individual Partner Name',
            'bank_name'                     => 'ICICI Bank',
            'bank_account_number'           => '998877665544',
            'bank_account_number_confirmation' => '998877665544',
            'bank_ifsc_code'                => 'ICIC0001234',
            'preferred_payout_method'       => 'bank_transfer',
            'declaration_accepted'          => '1',
        ]);

        $response->assertSessionHasErrors('identity_document_back');
    }

    /**
     * 18. Address document is mandatory when id_contains_current_address is false.
     */
    public function test_address_document_is_mandatory_when_id_address_differs(): void
    {
        $panPdf = UploadedFile::fake()->create('pan.pdf', 500, 'application/pdf');
        $passportPdf = UploadedFile::fake()->create('passport.pdf', 600, 'application/pdf');
        $bankPdf = UploadedFile::fake()->create('passbook.pdf', 700, 'application/pdf');

        $response = $this->actingAs($this->partnerUser2)->post(route('partner.kyc.store'), [
            'legal_name'                    => 'Individual Partner Name',
            'partner_type'                  => 'individual_sole_proprietor',
            'dob_or_incorporation_date'     => '1992-05-20',
            'pan_number'                    => 'PQRST5678M',
            'registered_address'            => '77 Park Street',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700016',
            'identity_document_type'        => 'passport',
            'id_contains_current_address'   => '0',
            'pan_document'                  => $panPdf,
            'identity_document'             => $passportPdf,
            'bank_proof_document'           => $bankPdf,
            'bank_account_holder_name'      => 'Individual Partner Name',
            'bank_name'                     => 'ICICI Bank',
            'bank_account_number'           => '998877665544',
            'bank_account_number_confirmation' => '998877665544',
            'bank_ifsc_code'                => 'ICIC0001234',
            'preferred_payout_method'       => 'bank_transfer',
            'declaration_accepted'          => '1',
        ]);

        $response->assertSessionHasErrors('address_document');
    }

    /**
     * 19. Mandatory legal declaration must be accepted.
     */
    public function test_declaration_must_be_accepted(): void
    {
        $panPdf = UploadedFile::fake()->create('pan.pdf', 500, 'application/pdf');
        $passportPdf = UploadedFile::fake()->create('passport.pdf', 600, 'application/pdf');
        $bankPdf = UploadedFile::fake()->create('passbook.pdf', 700, 'application/pdf');

        $response = $this->actingAs($this->partnerUser2)->post(route('partner.kyc.store'), [
            'legal_name'                    => 'Individual Partner Name',
            'partner_type'                  => 'individual_sole_proprietor',
            'dob_or_incorporation_date'     => '1992-05-20',
            'pan_number'                    => 'PQRST5678M',
            'registered_address'            => '77 Park Street',
            'city'                          => 'Kolkata',
            'state'                         => 'West Bengal',
            'postal_code'                   => '700016',
            'identity_document_type'        => 'passport',
            'id_contains_current_address'   => '1',
            'pan_document'                  => $panPdf,
            'identity_document'             => $passportPdf,
            'bank_proof_document'           => $bankPdf,
            'bank_account_holder_name'      => 'Individual Partner Name',
            'bank_name'                     => 'ICICI Bank',
            'bank_account_number'           => '998877665544',
            'bank_account_number_confirmation' => '998877665544',
            'bank_ifsc_code'                => 'ICIC0001234',
            'preferred_payout_method'       => 'bank_transfer',
            // Missing declaration_accepted
        ]);

        $response->assertSessionHasErrors('declaration_accepted');
    }
}
