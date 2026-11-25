<?php

namespace App\Services;

use App\Enums\UserRole;
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
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    /**
     * Escape special LIKE wildcard characters (%, _) to prevent wildcard injection.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    /**
     * Perform live search across permitted categories capped per category.
     *
     * @return array{
     *     query: string,
     *     total_results: int,
     *     categories: array<int, array{name: string, key: string, count: int, items: array}>
     * }
     */
    public function search(string $query, User $user, int $limitPerCategory = 5): array
    {
        $cleanQuery = trim($query);

        if (mb_strlen($cleanQuery) < 2) {
            return [
                'query' => $cleanQuery,
                'total_results' => 0,
                'categories' => [],
            ];
        }

        $categories = [];
        $totalResults = 0;

        if ($user->isAdmin()) {
            $categories = $this->searchAdmin($cleanQuery, $limitPerCategory);
        } elseif ($user->isPartner()) {
            $categories = $this->searchPartner($cleanQuery, $user, $limitPerCategory);
        } elseif ($user->isTeamMember()) {
            $categories = $this->searchStaff($cleanQuery, $user, $limitPerCategory);
        }

        foreach ($categories as $cat) {
            $totalResults += $cat['count'];
        }

        return [
            'query' => $cleanQuery,
            'total_results' => $totalResults,
            'categories' => $categories,
        ];
    }

    /**
     * Perform paginated search for the full /search page.
     */
    public function searchPaginated(
        string $query,
        User $user,
        ?string $moduleFilter = null,
        ?string $statusFilter = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
        int $perPage = 15
    ): array {
        $cleanQuery = trim($query);

        if (mb_strlen($cleanQuery) < 2) {
            return [
                'query' => $cleanQuery,
                'total_results' => 0,
                'modules' => $this->getAvailableModulesForUser($user),
                'active_module' => $moduleFilter ?: 'all',
                'results' => new LengthAwarePaginator([], 0, $perPage),
                'grouped_counts' => [],
            ];
        }

        $allCategories = $this->search($cleanQuery, $user, limitPerCategory: 100);
        $groupedCounts = [];
        $allItems = collect();

        foreach ($allCategories['categories'] as $cat) {
            $groupedCounts[$cat['key']] = count($cat['items']);
            if (!$moduleFilter || $moduleFilter === 'all' || $moduleFilter === $cat['key']) {
                foreach ($cat['items'] as $item) {
                    $allItems->push($item);
                }
            }
        }

        // Apply status filter if provided
        if (!empty($statusFilter)) {
            $allItems = $allItems->filter(function ($item) use ($statusFilter) {
                return isset($item['badge']) && strtolower($item['badge']) === strtolower($statusFilter);
            });
        }

        // Apply date filters if provided
        if (!empty($dateFrom)) {
            $allItems = $allItems->filter(function ($item) use ($dateFrom) {
                return isset($item['raw_date']) && $item['raw_date'] >= $dateFrom;
            });
        }
        if (!empty($dateTo)) {
            $allItems = $allItems->filter(function ($item) use ($dateTo) {
                return isset($item['raw_date']) && $item['raw_date'] <= $dateTo;
            });
        }

        $page = request()->get('page', 1);
        $total = $allItems->count();
        $slice = $allItems->slice(($page - 1) * $perPage, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );

        return [
            'query' => $cleanQuery,
            'total_results' => $total,
            'modules' => $this->getAvailableModulesForUser($user),
            'active_module' => $moduleFilter ?: 'all',
            'results' => $paginator,
            'grouped_counts' => $groupedCounts,
        ];
    }

    /**
     * Get module keys available for a given user role.
     */
    public function getAvailableModulesForUser(User $user): array
    {
        if ($user->isAdmin()) {
            return [
                'leads' => 'Leads',
                'partners' => 'Partners',
                'customers' => 'Customers',
                'projects' => 'Projects',
                'payments' => 'Payments',
                'commissions' => 'Commissions',
                'withdrawals' => 'Withdrawals',
                'payouts' => 'Payouts',
                'agreements' => 'Agreements',
                'kyc' => 'KYC Records',
                'staff' => 'Staff / Team',
                'disputes' => 'Lead Disputes',
                'audit_logs' => 'Audit Logs',
            ];
        }

        if ($user->isPartner()) {
            return [
                'leads' => 'My Leads',
                'customers' => 'My Customers',
                'projects' => 'My Projects',
                'commissions' => 'My Commissions',
                'withdrawals' => 'My Withdrawals',
                'agreements' => 'My Agreements',
                'disputes' => 'My Disputes',
            ];
        }

        if ($user->isTeamMember()) {
            return [
                'leads' => 'Assigned Leads',
                'customers' => 'Authorized Customers',
            ];
        }

        return [];
    }

    /* -------------------------------------------------------------------------- */
    /* ADMIN SEARCH HANDLERS                                                      */
    /* -------------------------------------------------------------------------- */

    protected function searchAdmin(string $query, int $limit): array
    {
        $escaped = self::escapeLike($query);
        $like = "%{$escaped}%";
        $categories = [];

        // 1. Leads
        $leads = Lead::with('partner')
            ->where(function ($q) use ($like) {
                $q->where('lead_number', 'like', $like)
                  ->orWhere('customer_name', 'like', $like)
                  ->orWhere('customer_company', 'like', $like)
                  ->orWhere('customer_phone', 'like', $like)
                  ->orWhere('customer_email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($leads->isNotEmpty()) {
            $categories[] = [
                'name' => 'Leads',
                'key' => 'leads',
                'count' => $leads->count(),
                'items' => $leads->map(fn ($l) => [
                    'id' => $l->id,
                    'category' => 'Leads',
                    'title' => $l->customer_name ?: 'Lead #' . $l->lead_number,
                    'subtitle' => "Ref: #{$l->lead_number} • " . ($l->customer_company ?: 'Individual') . ' • Phone: ' . ($l->customer_phone ?: '-'),
                    'badge' => $l->status?->label() ?? 'New',
                    'badge_classes' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'url' => route('admin.leads.show', $l),
                    'raw_date' => $l->created_at?->toDateString(),
                    'created_at' => $l->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 2. Partners
        $partners = Partner::with('user')
            ->where(function ($q) use ($like) {
                $q->where('company_name', 'like', $like)
                  ->orWhere('partner_code', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhereHas('user', function ($uq) use ($like) {
                      $uq->where('name', 'like', $like)
                         ->orWhere('email', 'like', $like);
                  });
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($partners->isNotEmpty()) {
            $categories[] = [
                'name' => 'Partners',
                'key' => 'partners',
                'count' => $partners->count(),
                'items' => $partners->map(fn ($p) => [
                    'id' => $p->id,
                    'category' => 'Partners',
                    'title' => $p->company_name ?: $p->user?->name,
                    'subtitle' => "Code: {$p->partner_code} • Contact: {$p->user?->name} • {$p->user?->email}",
                    'badge' => $p->kyc_status?->label() ?? 'Active',
                    'badge_classes' => 'bg-purple-50 text-purple-700 border-purple-200',
                    'url' => route('admin.partners.show', $p),
                    'raw_date' => $p->created_at?->toDateString(),
                    'created_at' => $p->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 3. Customers
        $customers = Customer::with('firstPartner')
            ->where(function ($q) use ($like) {
                $q->where('customer_code', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('company_name', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($customers->isNotEmpty()) {
            $categories[] = [
                'name' => 'Customers',
                'key' => 'customers',
                'count' => $customers->count(),
                'items' => $customers->map(fn ($c) => [
                    'id' => $c->id,
                    'category' => 'Customers',
                    'title' => $c->name,
                    'subtitle' => "Ref: #{$c->customer_code} • " . ($c->company_name ?: 'Individual') . " • {$c->email}",
                    'badge' => $c->status ?? 'active',
                    'badge_classes' => 'bg-teal-50 text-teal-700 border-teal-200',
                    'url' => route('admin.customers.show', $c),
                    'raw_date' => $c->created_at?->toDateString(),
                    'created_at' => $c->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 4. Projects
        $projects = Project::with(['customer', 'partner'])
            ->where(function ($q) use ($like) {
                $q->where('project_code', 'like', $like)
                  ->orWhere('project_title', 'like', $like)
                  ->orWhere('client_name', 'like', $like)
                  ->orWhere('client_company', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($projects->isNotEmpty()) {
            $categories[] = [
                'name' => 'Projects',
                'key' => 'projects',
                'count' => $projects->count(),
                'items' => $projects->map(fn ($pr) => [
                    'id' => $pr->id,
                    'category' => 'Projects',
                    'title' => $pr->project_title,
                    'subtitle' => "Code: #{$pr->project_code} • Client: {$pr->client_name} • Value: ₹" . number_format($pr->final_deal_value, 2),
                    'badge' => $pr->status?->label() ?? 'In Progress',
                    'badge_classes' => 'bg-sky-50 text-sky-700 border-sky-200',
                    'url' => route('admin.projects.show', $pr),
                    'raw_date' => $pr->created_at?->toDateString(),
                    'created_at' => $pr->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 5. Customer Payments
        $payments = CustomerPayment::with(['customer', 'project'])
            ->where(function ($q) use ($like) {
                $q->where('payment_number', 'like', $like)
                  ->orWhere('transaction_reference', 'like', $like)
                  ->orWhereHas('customer', fn ($cq) => $cq->where('name', 'like', $like))
                  ->orWhereHas('project', fn ($pq) => $pq->where('project_title', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($payments->isNotEmpty()) {
            $categories[] = [
                'name' => 'Customer Payments',
                'key' => 'payments',
                'count' => $payments->count(),
                'items' => $payments->map(fn ($pay) => [
                    'id' => $pay->id,
                    'category' => 'Payments',
                    'title' => "Payment #{$pay->payment_number}",
                    'subtitle' => "Client: {$pay->customer?->name} • Ref: " . ($pay->transaction_reference ?: 'N/A') . " • ₹" . number_format($pay->amount, 2),
                    'badge' => $pay->status?->label() ?? 'Recorded',
                    'badge_classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'url' => route('admin.payments.show', $pay),
                    'raw_date' => $pay->payment_date?->toDateString() ?: $pay->created_at?->toDateString(),
                    'created_at' => $pay->payment_date?->format('d M Y') ?: $pay->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 6. Commissions
        $commissions = Commission::with(['partner', 'project'])
            ->where(function ($q) use ($like) {
                $q->where('commission_number', 'like', $like)
                  ->orWhereHas('partner', fn ($pq) => $pq->where('company_name', 'like', $like))
                  ->orWhereHas('project', fn ($prq) => $prq->where('project_title', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($commissions->isNotEmpty()) {
            $categories[] = [
                'name' => 'Commissions',
                'key' => 'commissions',
                'count' => $commissions->count(),
                'items' => $commissions->map(fn ($com) => [
                    'id' => $com->id,
                    'category' => 'Commissions',
                    'title' => "Commission #{$com->commission_number}",
                    'subtitle' => "Partner: {$com->partner?->company_name} • Project: {$com->project?->project_title} • ₹" . number_format($com->amount, 2),
                    'badge' => $com->status?->label() ?? 'Approved',
                    'badge_classes' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'url' => route('admin.commissions.show', $com),
                    'raw_date' => $com->created_at?->toDateString(),
                    'created_at' => $com->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 7. Withdrawal Requests
        $withdrawals = WithdrawalRequest::with('partner')
            ->where(function ($q) use ($like) {
                $q->where('request_number', 'like', $like)
                  ->orWhereHas('partner', fn ($pq) => $pq->where('company_name', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($withdrawals->isNotEmpty()) {
            $categories[] = [
                'name' => 'Withdrawals',
                'key' => 'withdrawals',
                'count' => $withdrawals->count(),
                'items' => $withdrawals->map(fn ($w) => [
                    'id' => $w->id,
                    'category' => 'Withdrawals',
                    'title' => "Withdrawal #{$w->request_number}",
                    'subtitle' => "Partner: {$w->partner?->company_name} • Method: {$w->payout_method} • ₹" . number_format($w->amount, 2),
                    'badge' => $w->status?->label() ?? 'Pending',
                    'badge_classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'url' => route('admin.payouts.show', $w),
                    'raw_date' => $w->created_at?->toDateString(),
                    'created_at' => $w->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 8. Payouts
        $payouts = Payout::with('partner')
            ->where(function ($q) use ($like) {
                $q->where('payout_number', 'like', $like)
                  ->orWhere('transaction_reference', 'like', $like)
                  ->orWhereHas('partner', fn ($pq) => $pq->where('company_name', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($payouts->isNotEmpty()) {
            $categories[] = [
                'name' => 'Payouts',
                'key' => 'payouts',
                'count' => $payouts->count(),
                'items' => $payouts->map(fn ($po) => [
                    'id' => $po->id,
                    'category' => 'Payouts',
                    'title' => "Payout Voucher #{$po->payout_number}",
                    'subtitle' => "Partner: {$po->partner?->company_name} • UTR: {$po->transaction_reference} • ₹" . number_format($po->amount, 2),
                    'badge' => $po->payment_status?->label() ?? 'Completed',
                    'badge_classes' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    'url' => route('admin.payouts.show', $po->withdrawal_request_id),
                    'raw_date' => $po->payment_date?->toDateString() ?: $po->created_at?->toDateString(),
                    'created_at' => $po->payment_date?->format('d M Y') ?: $po->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 9. Partner Agreements
        $agreements = PartnerAgreement::with(['partner.user'])
            ->where(function ($q) use ($like) {
                $q->where('agreement_number', 'like', $like)
                  ->orWhereHas('partner', function ($pq) use ($like) {
                      $pq->where('company_name', 'like', $like)
                         ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', $like));
                  });
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($agreements->isNotEmpty()) {
            $categories[] = [
                'name' => 'Agreements',
                'key' => 'agreements',
                'count' => $agreements->count(),
                'items' => $agreements->map(fn ($ag) => [
                    'id' => $ag->id,
                    'category' => 'Agreements',
                    'title' => "Agreement #{$ag->agreement_number}",
                    'subtitle' => "Partner: {$ag->partner?->company_name} ({$ag->partner?->user?->name}) • v{$ag->version}",
                    'badge' => $ag->status?->label() ?? 'Issued',
                    'badge_classes' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'url' => route('admin.agreements.show', $ag),
                    'raw_date' => $ag->created_at?->toDateString(),
                    'created_at' => $ag->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 10. KYC Records (metadata only - legal name, partner name)
        $kycs = PartnerKyc::with('partner')
            ->where(function ($q) use ($like) {
                $q->where('legal_name', 'like', $like)
                  ->orWhereHas('partner', fn ($pq) => $pq->where('company_name', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($kycs->isNotEmpty()) {
            $categories[] = [
                'name' => 'KYC Records',
                'key' => 'kyc',
                'count' => $kycs->count(),
                'items' => $kycs->map(fn ($k) => [
                    'id' => $k->id,
                    'category' => 'KYC Records',
                    'title' => "KYC: {$k->legal_name}",
                    'subtitle' => "Partner: {$k->partner?->company_name} • Application v{$k->current_version}",
                    'badge' => $k->kyc_status?->label() ?? 'Pending',
                    'badge_classes' => 'bg-violet-50 text-violet-700 border-violet-200',
                    'url' => route('admin.kyc.show', $k),
                    'raw_date' => $k->created_at?->toDateString(),
                    'created_at' => $k->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 11. Staff / Team Members
        $staff = User::where('role', UserRole::TeamMember)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('employee_id', 'like', $like)
                  ->orWhere('designation', 'like', $like)
                  ->orWhere('department', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($staff->isNotEmpty()) {
            $categories[] = [
                'name' => 'Staff / Team',
                'key' => 'staff',
                'count' => $staff->count(),
                'items' => $staff->map(fn ($s) => [
                    'id' => $s->id,
                    'category' => 'Staff',
                    'title' => $s->name,
                    'subtitle' => ($s->designation ?: 'Team Member') . " • " . ($s->department ?: 'Sales') . " • {$s->email}",
                    'badge' => $s->status->value,
                    'badge_classes' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                    'url' => route('admin.team-members.show', $s),
                    'raw_date' => $s->created_at?->toDateString(),
                    'created_at' => $s->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 12. Lead Disputes
        $disputes = LeadDispute::with(['partner', 'lead'])
            ->where(function ($q) use ($like) {
                $q->where('dispute_number', 'like', $like)
                  ->orWhere('customer_phone_claimed', 'like', $like)
                  ->orWhere('customer_email_claimed', 'like', $like)
                  ->orWhereHas('partner', fn ($pq) => $pq->where('company_name', 'like', $like))
                  ->orWhereHas('lead', fn ($lq) => $lq->where('customer_name', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($disputes->isNotEmpty()) {
            $categories[] = [
                'name' => 'Lead Disputes',
                'key' => 'disputes',
                'count' => $disputes->count(),
                'items' => $disputes->map(fn ($d) => [
                    'id' => $d->id,
                    'category' => 'Disputes',
                    'title' => "Dispute #{$d->dispute_number}",
                    'subtitle' => "Lead: {$d->lead?->customer_name} • Partner: {$d->partner?->company_name}",
                    'badge' => $d->status?->label() ?? 'Submitted',
                    'badge_classes' => 'bg-rose-50 text-rose-700 border-rose-200',
                    'url' => route('admin.lead-disputes.show', $d),
                    'raw_date' => $d->created_at?->toDateString(),
                    'created_at' => $d->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 13. Audit Logs
        $logs = AuditLog::where(function ($q) use ($like) {
                $q->where('description', 'like', $like)
                  ->orWhere('action', 'like', $like)
                  ->orWhere('module', 'like', $like)
                  ->orWhere('actor_name_snapshot', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($logs->isNotEmpty()) {
            $categories[] = [
                'name' => 'Audit Logs',
                'key' => 'audit_logs',
                'count' => $logs->count(),
                'items' => $logs->map(fn ($al) => [
                    'id' => $al->id,
                    'category' => 'Audit Logs',
                    'title' => "Audit: {$al->action_value}",
                    'subtitle' => "{$al->description} • By: {$al->actor_name_snapshot}",
                    'badge' => $al->module,
                    'badge_classes' => 'bg-slate-100 text-slate-700 border-slate-300',
                    'url' => route('admin.audit-logs.show', $al),
                    'raw_date' => $al->created_at?->toDateString(),
                    'created_at' => $al->formatted_created_at,
                ])->toArray(),
            ];
        }

        return $categories;
    }

    /* -------------------------------------------------------------------------- */
    /* PARTNER SEARCH HANDLERS                                                    */
    /* -------------------------------------------------------------------------- */

    protected function searchPartner(string $query, User $user, int $limit): array
    {
        $partner = $user->partner;
        if (!$partner) {
            return [];
        }

        $escaped = self::escapeLike($query);
        $like = "%{$escaped}%";
        $categories = [];

        // 1. Partner's Own Leads
        $leads = Lead::where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('lead_number', 'like', $like)
                  ->orWhere('customer_name', 'like', $like)
                  ->orWhere('customer_company', 'like', $like)
                  ->orWhere('customer_phone', 'like', $like)
                  ->orWhere('customer_email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($leads->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Leads',
                'key' => 'leads',
                'count' => $leads->count(),
                'items' => $leads->map(fn ($l) => [
                    'id' => $l->id,
                    'category' => 'Leads',
                    'title' => $l->customer_name ?: 'Lead #' . $l->lead_number,
                    'subtitle' => "Ref: #{$l->lead_number} • " . ($l->customer_company ?: 'Individual') . ' • Phone: ' . ($l->customer_phone ?: '-'),
                    'badge' => $l->status?->label() ?? 'New',
                    'badge_classes' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'url' => route('partner.leads.show', $l),
                    'raw_date' => $l->created_at?->toDateString(),
                    'created_at' => $l->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 2. Partner's Own Customers
        $customers = Customer::where('first_partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('customer_code', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('company_name', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($customers->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Customers',
                'key' => 'customers',
                'count' => $customers->count(),
                'items' => $customers->map(fn ($c) => [
                    'id' => $c->id,
                    'category' => 'Customers',
                    'title' => $c->name,
                    'subtitle' => "Ref: #{$c->customer_code} • " . ($c->company_name ?: 'Individual') . " • {$c->email}",
                    'badge' => $c->status ?? 'active',
                    'badge_classes' => 'bg-teal-50 text-teal-700 border-teal-200',
                    'url' => route('partner.customers.show', $c),
                    'raw_date' => $c->created_at?->toDateString(),
                    'created_at' => $c->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 3. Partner's Own Projects
        $projects = Project::where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('project_code', 'like', $like)
                  ->orWhere('project_title', 'like', $like)
                  ->orWhere('client_name', 'like', $like)
                  ->orWhere('client_company', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($projects->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Projects',
                'key' => 'projects',
                'count' => $projects->count(),
                'items' => $projects->map(fn ($pr) => [
                    'id' => $pr->id,
                    'category' => 'Projects',
                    'title' => $pr->project_title,
                    'subtitle' => "Code: #{$pr->project_code} • Client: {$pr->client_name} • Value: ₹" . number_format($pr->final_deal_value, 2),
                    'badge' => $pr->status?->label() ?? 'In Progress',
                    'badge_classes' => 'bg-sky-50 text-sky-700 border-sky-200',
                    'url' => route('partner.projects.show', $pr),
                    'raw_date' => $pr->created_at?->toDateString(),
                    'created_at' => $pr->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 4. Partner's Own Commissions
        $commissions = Commission::with('project')
            ->where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('commission_number', 'like', $like)
                  ->orWhereHas('project', fn ($prq) => $prq->where('project_title', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($commissions->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Commissions',
                'key' => 'commissions',
                'count' => $commissions->count(),
                'items' => $commissions->map(fn ($com) => [
                    'id' => $com->id,
                    'category' => 'Commissions',
                    'title' => "Commission #{$com->commission_number}",
                    'subtitle' => "Project: {$com->project?->project_title} • Amount: ₹" . number_format($com->amount, 2),
                    'badge' => $com->status?->label() ?? 'Approved',
                    'badge_classes' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'url' => route('partner.commissions.show', $com),
                    'raw_date' => $com->created_at?->toDateString(),
                    'created_at' => $com->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 5. Partner's Own Withdrawals
        $withdrawals = WithdrawalRequest::where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('request_number', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($withdrawals->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Withdrawals',
                'key' => 'withdrawals',
                'count' => $withdrawals->count(),
                'items' => $withdrawals->map(fn ($w) => [
                    'id' => $w->id,
                    'category' => 'Withdrawals',
                    'title' => "Withdrawal #{$w->request_number}",
                    'subtitle' => "Method: {$w->payout_method} • Amount: ₹" . number_format($w->amount, 2),
                    'badge' => $w->status?->label() ?? 'Pending',
                    'badge_classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    'url' => route('partner.withdrawals.show', $w),
                    'raw_date' => $w->created_at?->toDateString(),
                    'created_at' => $w->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 6. Partner's Own Agreements
        $agreements = PartnerAgreement::where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('agreement_number', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($agreements->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Agreements',
                'key' => 'agreements',
                'count' => $agreements->count(),
                'items' => $agreements->map(fn ($ag) => [
                    'id' => $ag->id,
                    'category' => 'Agreements',
                    'title' => "Agreement #{$ag->agreement_number}",
                    'subtitle' => "Partner Agreement v{$ag->version} • Issued on " . ($ag->issue_date?->format('d M Y') ?? '-'),
                    'badge' => $ag->status?->label() ?? 'Issued',
                    'badge_classes' => 'bg-amber-50 text-amber-700 border-amber-200',
                    'url' => route('partner.agreement.show'),
                    'raw_date' => $ag->created_at?->toDateString(),
                    'created_at' => $ag->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 7. Partner's Own Disputes
        $disputes = LeadDispute::with('lead')
            ->where('partner_id', $partner->id)
            ->where(function ($q) use ($like) {
                $q->where('dispute_number', 'like', $like)
                  ->orWhere('customer_phone_claimed', 'like', $like)
                  ->orWhere('customer_email_claimed', 'like', $like)
                  ->orWhereHas('lead', fn ($lq) => $lq->where('customer_name', 'like', $like));
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($disputes->isNotEmpty()) {
            $categories[] = [
                'name' => 'My Disputes',
                'key' => 'disputes',
                'count' => $disputes->count(),
                'items' => $disputes->map(fn ($d) => [
                    'id' => $d->id,
                    'category' => 'Disputes',
                    'title' => "Dispute #{$d->dispute_number}",
                    'subtitle' => "Lead: {$d->lead?->customer_name}",
                    'badge' => $d->status?->label() ?? 'Submitted',
                    'badge_classes' => 'bg-rose-50 text-rose-700 border-rose-200',
                    'url' => route('partner.disputes.show', $d),
                    'raw_date' => $d->created_at?->toDateString(),
                    'created_at' => $d->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        return $categories;
    }

    /* -------------------------------------------------------------------------- */
    /* STAFF / TEAM SEARCH HANDLERS                                               */
    /* -------------------------------------------------------------------------- */

    protected function searchStaff(string $query, User $user, int $limit): array
    {
        $escaped = self::escapeLike($query);
        $like = "%{$escaped}%";
        $categories = [];

        // 1. Assigned Leads only
        $leads = Lead::where('assigned_to', $user->id)
            ->where(function ($q) use ($like) {
                $q->where('lead_number', 'like', $like)
                  ->orWhere('customer_name', 'like', $like)
                  ->orWhere('customer_company', 'like', $like)
                  ->orWhere('customer_phone', 'like', $like)
                  ->orWhere('customer_email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($leads->isNotEmpty()) {
            $categories[] = [
                'name' => 'Assigned Leads',
                'key' => 'leads',
                'count' => $leads->count(),
                'items' => $leads->map(fn ($l) => [
                    'id' => $l->id,
                    'category' => 'Leads',
                    'title' => $l->customer_name ?: 'Lead #' . $l->lead_number,
                    'subtitle' => "Ref: #{$l->lead_number} • " . ($l->customer_company ?: 'Individual') . ' • Phone: ' . ($l->customer_phone ?: '-'),
                    'badge' => $l->status?->label() ?? 'Assigned',
                    'badge_classes' => 'bg-blue-50 text-blue-700 border-blue-200',
                    'url' => route('team.leads.show', $l),
                    'raw_date' => $l->created_at?->toDateString(),
                    'created_at' => $l->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        // 2. Customers authorized to this staff (linked to assigned leads)
        $customers = Customer::whereHas('lead', fn ($q) => $q->where('assigned_to', $user->id))
            ->where(function ($q) use ($like) {
                $q->where('customer_code', 'like', $like)
                  ->orWhere('name', 'like', $like)
                  ->orWhere('company_name', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('email', 'like', $like);
            })
            ->latest('id')
            ->take($limit)
            ->get();

        if ($customers->isNotEmpty()) {
            $categories[] = [
                'name' => 'Authorized Customers',
                'key' => 'customers',
                'count' => $customers->count(),
                'items' => $customers->map(fn ($c) => [
                    'id' => $c->id,
                    'category' => 'Customers',
                    'title' => $c->name,
                    'subtitle' => "Ref: #{$c->customer_code} • " . ($c->company_name ?: 'Individual') . " • Phone: {$c->phone}",
                    'badge' => 'Active',
                    'badge_classes' => 'bg-teal-50 text-teal-700 border-teal-200',
                    'url' => $c->lead_id ? route('team.leads.show', $c->lead_id) : route('team.leads.index'),
                    'raw_date' => $c->created_at?->toDateString(),
                    'created_at' => $c->created_at?->timezone('Asia/Kolkata')->format('d M Y'),
                ])->toArray(),
            ];
        }

        return $categories;
    }
}
