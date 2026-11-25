<?php

use App\Http\Controllers\Admin\AgreementController as AdminAgreementController;
use App\Http\Controllers\Admin\AgreementTemplateController as AdminAgreementTemplateController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditLogController;
use App\Http\Controllers\Admin\CommissionController as AdminCommissionController;
use App\Http\Controllers\Admin\CommissionPolicyController as AdminCommissionPolicyController;
use App\Http\Controllers\Admin\CommissionSettingController as AdminCommissionSettingController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\DemoDataController as AdminDemoDataController;
use App\Http\Controllers\Admin\FinancialDashboardController;
use App\Http\Controllers\Admin\InactivityController as AdminInactivityController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LeadDisputeController as AdminLeadDisputeController;
use App\Http\Controllers\Admin\LeadSlaController as AdminLeadSlaController;
use App\Http\Controllers\Admin\PartnerController as AdminPartnerController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\PaymentScheduleController as AdminPaymentScheduleController;
use App\Http\Controllers\Admin\PayoutController as AdminPayoutController;
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\TeamMemberController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\Partner\AgreementController as PartnerAgreementController;
use App\Http\Controllers\Partner\CommissionController as PartnerCommissionController;
use App\Http\Controllers\Partner\CustomerController as PartnerCustomerController;
use App\Http\Controllers\Partner\KycController as PartnerKycController;
use App\Http\Controllers\Partner\LeadController as PartnerLeadController;
use App\Http\Controllers\Partner\LeadDisputeController as PartnerLeadDisputeController;
use App\Http\Controllers\Partner\NotificationController as PartnerNotificationController;
use App\Http\Controllers\Partner\PayoutSettingsController as PartnerPayoutSettingsController;
use App\Http\Controllers\Partner\ProfileController as PartnerProfileController;
use App\Http\Controllers\Partner\ProjectController as PartnerProjectController;
use App\Http\Controllers\Partner\WithdrawalController as PartnerWithdrawalController;
use App\Http\Controllers\Partner\WorkspaceController as PartnerWorkspaceController;
use App\Http\Controllers\Team\LeadController as TeamLeadController;
use App\Http\Controllers\Team\ProfileController as TeamProfileController;
use App\Http\Controllers\Team\WorkspaceController as TeamWorkspaceController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public landing & Guest Authentication
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return match ($user->role) {
            \App\Enums\UserRole::Admin => redirect()->route('admin.dashboard'),
            \App\Enums\UserRole::TeamMember => redirect()->route('team.workspace'),
            \App\Enums\UserRole::Partner => redirect()->route('partner.workspace'),
        };
    }

    return app(LoginController::class)->showLoginForm();
})->name('home');

// Admin Entry Point: /admin
Route::get('/admin', function () {
    if (auth()->check()) {
        $user = auth()->user();
        return match ($user->role) {
            \App\Enums\UserRole::Admin => redirect()->route('admin.dashboard'),
            \App\Enums\UserRole::TeamMember => redirect()->route('team.workspace'),
            \App\Enums\UserRole::Partner => redirect()->route('partner.workspace'),
        };
    }

    return redirect()->route('login');
})->name('admin.home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:10,1');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Global Search (Authenticated)
Route::middleware('auth')->group(function () {
    Route::get('/search/suggest', [GlobalSearchController::class, 'suggest'])->name('search.suggest');
    Route::get('/search', [GlobalSearchController::class, 'index'])->name('search.index');
    Route::post('/search/recent/clear', [GlobalSearchController::class, 'clearRecent'])->name('search.recent.clear');
});

// Partner Account Status Notices (accessible when authenticated as partner)
Route::middleware(['auth', 'role.partner'])->prefix('partner')->group(function () {
    Route::get('/pending-approval', function () {
        return view('auth.partner-pending');
    })->name('partner.pending');

    Route::get('/suspended', function () {
        return view('auth.partner-suspended');
    })->name('partner.suspended');
});

// Partner Protected Workspace (requires active approval)
Route::middleware(['auth', 'role.partner', 'partner.active'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/workspace', [PartnerWorkspaceController::class, 'index'])->name('workspace');
    Route::redirect('/dashboard', '/partner/workspace')->name('dashboard');

    // Lead Management
    Route::get('/leads', [PartnerLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [PartnerLeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [PartnerLeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [PartnerLeadController::class, 'show'])->name('leads.show');

    // Lead Ownership Disputes
    Route::get('/disputes', [PartnerLeadDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/create', [PartnerLeadDisputeController::class, 'create'])->name('disputes.create');
    Route::post('/disputes', [PartnerLeadDisputeController::class, 'store'])->name('disputes.store');
    Route::get('/disputes/{dispute}', [PartnerLeadDisputeController::class, 'show'])->name('disputes.show');
    Route::get('/disputes/{dispute}/evidence', [PartnerLeadDisputeController::class, 'downloadEvidence'])->name('disputes.evidence');

    // Converted Customers & Projects
    Route::get('/customers', [PartnerCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [PartnerCustomerController::class, 'show'])->name('customers.show');
    Route::get('/projects', [PartnerProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [PartnerProjectController::class, 'show'])->name('projects.show');

    // Commission Ledger & Statements
    Route::get('/commissions', [PartnerCommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/{commission}', [PartnerCommissionController::class, 'show'])->name('commissions.show');

    // Partner Withdrawals & Payout Management
    Route::get('/withdrawals', [PartnerWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/create', [PartnerWithdrawalController::class, 'create'])->name('withdrawals.create');
    Route::post('/withdrawals', [PartnerWithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::get('/withdrawals/{withdrawal}', [PartnerWithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::post('/withdrawals/{withdrawal}/cancel', [PartnerWithdrawalController::class, 'cancel'])->name('withdrawals.cancel');
    Route::get('/withdrawals/{withdrawal}/proof', [PartnerWithdrawalController::class, 'downloadProof'])->name('withdrawals.proof');

    // Bank & Payout Destination Details
    Route::get('/payout-settings', [PartnerPayoutSettingsController::class, 'edit'])->name('payout-settings.edit');
    Route::put('/payout-settings', [PartnerPayoutSettingsController::class, 'update'])->name('payout-settings.update');

    // Partner Profile & Security Settings
    Route::get('/profile', [PartnerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [PartnerProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [PartnerProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [PartnerProfileController::class, 'updatePhoto'])->name('profile.photo');

    // Partner KYC & Bank Verification
    Route::get('/kyc', [PartnerKycController::class, 'show'])->name('kyc.show');
    Route::get('/kyc/submit', [PartnerKycController::class, 'create'])->name('kyc.create');
    Route::post('/kyc/submit', [PartnerKycController::class, 'store'])->name('kyc.store');
    Route::get('/kyc/correction', [PartnerKycController::class, 'correction'])->name('kyc.correction');
    Route::post('/kyc/correction', [PartnerKycController::class, 'resubmit'])->name('kyc.resubmit');
    Route::get('/kyc/document/{type}', [PartnerKycController::class, 'downloadDocument'])->name('kyc.document');

    // Partner Agreement & Legal Compliance
    Route::get('/agreement', [PartnerAgreementController::class, 'show'])->name('agreement.show');
    Route::post('/agreement/accept', [PartnerAgreementController::class, 'accept'])->name('agreement.accept');
    Route::get('/agreement/download', [PartnerAgreementController::class, 'download'])->name('agreement.download');
    Route::get('/agreement/preview', [PartnerAgreementController::class, 'preview'])->name('agreement.preview');

    // Notifications
    Route::get('/notifications', [PartnerNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [PartnerNotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [PartnerNotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');
});

// Internal Team Member Protected Workspace
Route::middleware(['auth', 'role.team'])->prefix('team')->name('team.')->group(function () {
    Route::get('/workspace', [TeamWorkspaceController::class, 'index'])->name('workspace');

    // Assigned Leads & Telecalling CRM
    Route::get('/leads', [TeamLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [TeamLeadController::class, 'show'])->name('leads.show');
    Route::post('/leads/{lead}/activity', [TeamLeadController::class, 'storeActivity'])->name('leads.activity');
    Route::post('/leads/{lead}/status', [TeamLeadController::class, 'updateStatus'])->name('leads.status');

    // Team Member Profile & Password Settings
    Route::get('/profile', [TeamProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [TeamProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [TeamProfileController::class, 'updatePassword'])->name('profile.password');
    Route::post('/profile/photo', [TeamProfileController::class, 'updatePhoto'])->name('profile.photo');
});

// Admin / Owner Control Workspace
Route::middleware(['auth', 'role.admin'])->prefix('admin')->name('admin.')->group(function () {
    // 1. Dashboard
    Route::get('/dashboard', [FinancialDashboardController::class, 'index'])->name('dashboard');

    // 2. Partner Management (Complete CRUD)
    Route::get('/partners', [AdminPartnerController::class, 'index'])->name('partners.index');
    Route::get('/partners/create', [AdminPartnerController::class, 'create'])->name('partners.create');
    Route::post('/partners', [AdminPartnerController::class, 'store'])->name('partners.store');
    Route::get('/partners/{partner}', [AdminPartnerController::class, 'show'])->name('partners.show');
    Route::get('/partners/{partner}/edit', [AdminPartnerController::class, 'edit'])->name('partners.edit');
    Route::put('/partners/{partner}', [AdminPartnerController::class, 'update'])->name('partners.update');
    Route::post('/partners/{partner}/approve', [AdminPartnerController::class, 'approve'])->name('partners.approve');
    Route::post('/partners/{partner}/reject', [AdminPartnerController::class, 'reject'])->name('partners.reject');
    Route::post('/partners/{partner}/suspend', [AdminPartnerController::class, 'suspend'])->name('partners.suspend');
    Route::post('/partners/{partner}/activate', [AdminPartnerController::class, 'activate'])->name('partners.activate');

    // 3. Team Member / Staff Management (Complete CRUD)
    Route::get('/team-members', [TeamMemberController::class, 'index'])->name('team-members.index');
    Route::get('/team-members/create', [TeamMemberController::class, 'create'])->name('team-members.create');
    Route::post('/team-members', [TeamMemberController::class, 'store'])->name('team-members.store');
    Route::get('/team-members/{team_member}', [TeamMemberController::class, 'show'])->name('team-members.show');
    Route::get('/team-members/{team_member}/edit', [TeamMemberController::class, 'edit'])->name('team-members.edit');
    Route::put('/team-members/{team_member}', [TeamMemberController::class, 'update'])->name('team-members.update');
    Route::post('/team-members/{team_member}/toggle-status', [TeamMemberController::class, 'toggleStatus'])->name('team-members.toggle-status');
    Route::post('/team-members/{team_member}/reset-password', [TeamMemberController::class, 'resetPassword'])->name('team-members.reset-password');

    // 4. Lead Management & Conversion (with Add Lead create & store)
    Route::get('/leads', [AdminLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/create', [AdminLeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [AdminLeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [AdminLeadController::class, 'show'])->name('leads.show');
    Route::post('/leads/{lead}/assign', [AdminLeadController::class, 'assign'])->name('leads.assign');
    Route::post('/leads/{lead}/status', [AdminLeadController::class, 'updateStatus'])->name('leads.status');
    Route::post('/leads/{lead}/activity', [AdminLeadController::class, 'storeActivity'])->name('leads.activity');
    Route::post('/leads/{lead}/resolve-duplicate', [AdminLeadController::class, 'resolveDuplicate'])->name('leads.resolve-duplicate');
    Route::post('/leads/{lead}/reassign-ownership', [AdminLeadController::class, 'reassignOwnership'])->name('leads.reassign-ownership');
    Route::post('/leads/{lead}/extend-ownership', [AdminLeadController::class, 'extendOwnership'])->name('leads.extend-ownership');
    Route::post('/leads/{lead}/release-ownership', [AdminLeadController::class, 'releaseOwnership'])->name('leads.release-ownership');

    // Lead Ownership Disputes Management
    Route::get('/lead-disputes', [AdminLeadDisputeController::class, 'index'])->name('lead-disputes.index');
    Route::get('/lead-disputes/{dispute}', [AdminLeadDisputeController::class, 'show'])->name('lead-disputes.show');
    Route::post('/lead-disputes/{dispute}/status', [AdminLeadDisputeController::class, 'updateStatus'])->name('lead-disputes.status');
    Route::post('/lead-disputes/{dispute}/notes', [AdminLeadDisputeController::class, 'addNotes'])->name('lead-disputes.notes');
    Route::post('/lead-disputes/{dispute}/resolve', [AdminLeadDisputeController::class, 'resolve'])->name('lead-disputes.resolve');
    Route::post('/lead-disputes/{dispute}/reject', [AdminLeadDisputeController::class, 'reject'])->name('lead-disputes.reject');
    Route::get('/lead-disputes/{dispute}/evidence', [AdminLeadDisputeController::class, 'downloadEvidence'])->name('lead-disputes.evidence');

    // Lead SLA Management & Escalations
    Route::get('/lead-sla', [AdminLeadSlaController::class, 'index'])->name('lead-sla.index');
    Route::get('/lead-sla/settings', [AdminLeadSlaController::class, 'settings'])->name('lead-sla.settings');
    Route::post('/lead-sla/settings', [AdminLeadSlaController::class, 'updateSettings'])->name('lead-sla.settings.update');
    Route::get('/lead-sla/escalations', [AdminLeadSlaController::class, 'escalations'])->name('lead-sla.escalations');

    // Lead to Customer Conversion
    Route::get('/leads/{lead}/convert', [AdminCustomerController::class, 'showConvertForm'])->name('leads.convert.form');
    Route::post('/leads/{lead}/convert', [AdminCustomerController::class, 'convert'])->name('leads.convert');

    // 5. Customer Management (Complete CRUD)
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [AdminCustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [AdminCustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/edit', [AdminCustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [AdminCustomerController::class, 'update'])->name('customers.update');

    // 6. Project Management
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [AdminProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [AdminProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/partner-commission', [AdminProjectController::class, 'resolvePartnerCommission'])->name('projects.partner-commission');
    Route::get('/projects/{project}', [AdminProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [AdminProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [AdminProjectController::class, 'update'])->name('projects.update');
    Route::post('/projects/{project}/schedules/pause', [AdminPaymentScheduleController::class, 'pauseProject'])->name('projects.schedules.pause');
    Route::post('/projects/{project}/schedules/resume', [AdminPaymentScheduleController::class, 'resumeProject'])->name('projects.schedules.resume');
    Route::post('/schedules/{schedule}/payment', [AdminPaymentScheduleController::class, 'storePayment'])->name('schedules.payment.store');
    Route::post('/schedules/{schedule}/pause', [AdminPaymentScheduleController::class, 'pause'])->name('schedules.pause');
    Route::post('/schedules/{schedule}/resume', [AdminPaymentScheduleController::class, 'resume'])->name('schedules.resume');
    Route::post('/schedules/{schedule}/cancel', [AdminPaymentScheduleController::class, 'cancel'])->name('schedules.cancel');

    // 7. Customer Payment Management & Verification
    Route::get('/payments', [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/create', [AdminPaymentController::class, 'create'])->name('payments.create');
    Route::post('/payments', [AdminPaymentController::class, 'store'])->name('payments.store');
    Route::get('/payments/{payment}', [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::get('/payments/{payment}/edit', [AdminPaymentController::class, 'edit'])->name('payments.edit');
    Route::put('/payments/{payment}', [AdminPaymentController::class, 'update'])->name('payments.update');
    Route::post('/payments/{payment}/verify', [AdminPaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/reject', [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/payments/{payment}/reverse', [AdminPaymentController::class, 'reverse'])->name('payments.reverse');
    Route::post('/payments/{payment}/partial-refund', [AdminPaymentController::class, 'partialRefund'])->name('payments.partial-refund');
    Route::get('/payments/{payment}/proof', [AdminPaymentController::class, 'downloadProof'])->name('payments.proof');

    // 8. Authoritative Commission Ledger
    Route::get('/commissions', [AdminCommissionController::class, 'index'])->name('commissions.index');
    Route::get('/commissions/{commission}', [AdminCommissionController::class, 'show'])->name('commissions.show');

    // 9. Payouts & Partner Withdrawals
    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::get('/payouts/pending', [AdminPayoutController::class, 'pending'])->name('payouts.pending');
    Route::get('/payouts/{payout}', [AdminPayoutController::class, 'show'])->name('payouts.show');
    Route::post('/payouts/{payout}/approve', [AdminPayoutController::class, 'approve'])->name('payouts.approve');
    Route::post('/payouts/{payout}/process', [AdminPayoutController::class, 'process'])->name('payouts.process');
    Route::post('/payouts/{payout}/reject', [AdminPayoutController::class, 'reject'])->name('payouts.reject');
    Route::post('/payouts/{payout}/complete', [AdminPayoutController::class, 'complete'])->name('payouts.complete');
    Route::get('/payouts/{payout}/proof', [AdminPayoutController::class, 'downloadProof'])->name('payouts.proof');

    // 10. Reports & Analytics
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');

    // 11. Partner KYC & Bank Verification Management
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/pending', [AdminKycController::class, 'pending'])->name('kyc.pending');
    Route::get('/kyc/approved', [AdminKycController::class, 'approved'])->name('kyc.approved');
    Route::get('/kyc/rejected', [AdminKycController::class, 'rejected'])->name('kyc.rejected');
    Route::get('/kyc/{kyc}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/start-review', [AdminKycController::class, 'startReview'])->name('kyc.start-review');
    Route::post('/kyc/{kyc}/approve-kyc', [AdminKycController::class, 'approveKyc'])->name('kyc.approve-kyc');
    Route::post('/kyc/{kyc}/verify-bank', [AdminKycController::class, 'verifyBank'])->name('kyc.verify-bank');
    Route::post('/kyc/{kyc}/reject-kyc', [AdminKycController::class, 'rejectKyc'])->name('kyc.reject-kyc');
    Route::post('/kyc/{kyc}/reject-bank', [AdminKycController::class, 'rejectBank'])->name('kyc.reject-bank');
    Route::post('/kyc/{kyc}/request-correction', [AdminKycController::class, 'requestCorrection'])->name('kyc.request-correction');
    Route::get('/kyc/{kyc}/document/{type}', [AdminKycController::class, 'downloadDocument'])->name('kyc.document');

    // 12. Commission Policy Management & Rate Configuration
    Route::get('/settings/commission', [AdminCommissionSettingController::class, 'index'])->name('settings.commission.index');
    Route::post('/settings/commission', [AdminCommissionSettingController::class, 'update'])->name('settings.commission.update');
    Route::post('/settings/letterhead', [AdminCommissionSettingController::class, 'updateLetterhead'])->name('settings.letterhead.update');

    Route::get('/settings/commission-policies', [AdminCommissionPolicyController::class, 'index'])->name('settings.commission-policies.index');
    Route::get('/settings/commission-policies/create', [AdminCommissionPolicyController::class, 'create'])->name('settings.commission-policies.create');
    Route::post('/settings/commission-policies', [AdminCommissionPolicyController::class, 'store'])->name('settings.commission-policies.store');
    Route::get('/settings/commission-policies/{commission_policy}', [AdminCommissionPolicyController::class, 'show'])->name('settings.commission-policies.show');
    Route::get('/settings/commission-policies/{commission_policy}/revision', [AdminCommissionPolicyController::class, 'createRevision'])->name('settings.commission-policies.revision.create');
    Route::post('/settings/commission-policies/{commission_policy}/revision', [AdminCommissionPolicyController::class, 'storeRevision'])->name('settings.commission-policies.revision.store');
    Route::post('/settings/commission-policies/{commission_policy}/toggle-status', [AdminCommissionPolicyController::class, 'toggleStatus'])->name('settings.commission-policies.toggle-status');

    // 13. Agreement Template Foundation
    Route::get('/settings/agreement-templates', [AdminAgreementTemplateController::class, 'index'])->name('settings.agreement-templates.index');
    Route::get('/settings/agreement-templates/create', [AdminAgreementTemplateController::class, 'create'])->name('settings.agreement-templates.create');
    Route::post('/settings/agreement-templates', [AdminAgreementTemplateController::class, 'store'])->name('settings.agreement-templates.store');
    Route::get('/settings/agreement-templates/{agreement_template}', [AdminAgreementTemplateController::class, 'show'])->name('settings.agreement-templates.show');
    Route::get('/settings/agreement-templates/{agreement_template}/edit', [AdminAgreementTemplateController::class, 'edit'])->name('settings.agreement-templates.edit');
    Route::put('/settings/agreement-templates/{agreement_template}', [AdminAgreementTemplateController::class, 'update'])->name('settings.agreement-templates.update');
    Route::post('/settings/agreement-templates/{agreement_template}/activate', [AdminAgreementTemplateController::class, 'activate'])->name('settings.agreement-templates.activate');

    // 14. Partner Agreements & Legal Audit
    Route::get('/agreements', [AdminAgreementController::class, 'index'])->name('agreements.index');
    Route::get('/agreements/{agreement}', [AdminAgreementController::class, 'show'])->name('agreements.show');
    Route::get('/agreements/{agreement}/download', [AdminAgreementController::class, 'download'])->name('agreements.download');
    Route::get('/agreements/{agreement}/preview', [AdminAgreementController::class, 'preview'])->name('agreements.preview');
    Route::post('/agreements/{agreement}/revoke', [AdminAgreementController::class, 'revoke'])->name('agreements.revoke');
    Route::post('/agreements/{agreement}/reissue', [AdminAgreementController::class, 'reissue'])->name('agreements.reissue');
    Route::post('/partners/{partner}/issue-agreement', [AdminAgreementController::class, 'issueForPartner'])->name('partners.issue-agreement');

    // 15. Inactivity Management System
    Route::get('/inactivity', [AdminInactivityController::class, 'index'])->name('inactivity.index');
    Route::get('/inactivity/settings', [AdminInactivityController::class, 'settings'])->name('inactivity.settings');
    Route::post('/inactivity/settings', [AdminInactivityController::class, 'updateSettings'])->name('inactivity.settings.update');
    Route::get('/inactivity/{user}/history', [AdminInactivityController::class, 'history'])->name('inactivity.history');
    Route::post('/inactivity/{user}/warning', [AdminInactivityController::class, 'sendWarning'])->name('inactivity.warning');
    Route::post('/inactivity/{user}/flag', [AdminInactivityController::class, 'flagForReview'])->name('inactivity.flag');
    Route::post('/inactivity/{user}/unflag', [AdminInactivityController::class, 'unflagReview'])->name('inactivity.unflag');
    Route::post('/inactivity/{user}/suspend', [AdminInactivityController::class, 'suspend'])->name('inactivity.suspend');
    Route::post('/inactivity/{user}/reactivate', [AdminInactivityController::class, 'reactivate'])->name('inactivity.reactivate');

    // 16. Centralized Audit Log System
    Route::get('/audit-logs', [AdminAuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/{audit_log}', [AdminAuditLogController::class, 'show'])->name('audit-logs.show');

    // 17. Demo Data Backup & Cleanup System
    Route::get('/demo-data', [AdminDemoDataController::class, 'index'])->name('demo-data.index');
    Route::post('/demo-data/backup', [AdminDemoDataController::class, 'createBackup'])->name('demo-data.backup.create');
    Route::get('/demo-data/backups/{backup}', [AdminDemoDataController::class, 'showBackup'])->name('demo-data.backup.show');
    Route::post('/demo-data/backups/{backup}/verify', [AdminDemoDataController::class, 'verifyBackup'])->name('demo-data.backup.verify');
    Route::get('/demo-data/backups/{backup}/download', [AdminDemoDataController::class, 'downloadBackup'])->name('demo-data.backup.download');
    Route::delete('/demo-data/backups/{backup}', [AdminDemoDataController::class, 'destroyBackup'])->name('demo-data.backup.destroy');
    Route::get('/demo-data/backups/{backup}/restore', [AdminDemoDataController::class, 'previewRestore'])->name('demo-data.backup.restore.preview');
    Route::post('/demo-data/backups/{backup}/restore', [AdminDemoDataController::class, 'executeRestore'])->name('demo-data.backup.restore.execute');
    Route::get('/demo-data/cleanup', [AdminDemoDataController::class, 'previewCleanup'])->name('demo-data.cleanup.preview');
    Route::post('/demo-data/cleanup', [AdminDemoDataController::class, 'executeCleanup'])->name('demo-data.cleanup.execute');
});
