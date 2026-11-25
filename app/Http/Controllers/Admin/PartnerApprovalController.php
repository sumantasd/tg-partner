<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerApprovalController extends Controller
{
    /**
     * List partners with filtering by status.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $query = Partner::with(['user', 'approver'])->latest();

        if ($status === 'pending') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Pending));
        } elseif ($status === 'active') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Active));
        } elseif ($status === 'suspended') {
            $query->whereHas('user', fn ($q) => $q->where('status', UserStatus::Suspended));
        }

        $partners = $query->paginate(15)->withQueryString();

        $counts = [
            'pending'   => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Pending))->count(),
            'active'    => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Active))->count(),
            'suspended' => Partner::whereHas('user', fn ($q) => $q->where('status', UserStatus::Suspended))->count(),
        ];

        return view('admin.partners.index', compact('partners', 'status', 'counts'));
    }

    /**
     * Approve a pending partner.
     */
    public function approve(Partner $partner): RedirectResponse
    {
        $partner->user->update([
            'status' => UserStatus::Active,
        ]);

        $partner->update([
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return back()->with('success', "Partner #{$partner->partner_code} ({$partner->company_name}) has been approved and activated.");
    }

    /**
     * Reject a pending partner.
     */
    public function reject(Request $request, Partner $partner): RedirectResponse
    {
        $reason = $request->input('reason', 'Application did not meet partnership criteria.');

        $partner->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $partner->update([
            'internal_notes' => trim(($partner->internal_notes ?? '') . "\n[" . now()->toDateTimeString() . " by " . auth()->user()->name . "] Rejected: " . $reason),
        ]);

        return back()->with('info', "Partner #{$partner->partner_code} application has been rejected.");
    }

    /**
     * Suspend an active partner.
     */
    public function suspend(Request $request, Partner $partner): RedirectResponse
    {
        $reason = $request->input('reason', 'Account suspended by administrator.');

        $partner->user->update([
            'status' => UserStatus::Suspended,
        ]);

        $partner->update([
            'internal_notes' => trim(($partner->internal_notes ?? '') . "\n[" . now()->toDateTimeString() . " by " . auth()->user()->name . "] Suspended: " . $reason),
        ]);

        return back()->with('warning', "Partner #{$partner->partner_code} has been suspended.");
    }

    /**
     * Reactivate a suspended partner.
     */
    public function activate(Partner $partner): RedirectResponse
    {
        $partner->user->update([
            'status' => UserStatus::Active,
        ]);

        $partner->update([
            'internal_notes' => trim(($partner->internal_notes ?? '') . "\n[" . now()->toDateTimeString() . " by " . auth()->user()->name . "] Reactivated to Active status."),
        ]);

        return back()->with('success', "Partner #{$partner->partner_code} has been reactivated.");
    }
}
