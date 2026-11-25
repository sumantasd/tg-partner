<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display partner's own converted customers.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }
        $search = $request->query('search');

        $query = Customer::where('first_partner_id', $partner->id)
            ->with(['projects', 'payments'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('partner.customers.index', compact('customers', 'search'));
    }

    /**
     * Display customer detail for partner's own referral.
     */
    public function show(Request $request, Customer $customer): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        // Strict cross-partner boundary check
        if ($customer->first_partner_id !== $partner->id) {
            abort(403, 'Unauthorized. You can only view customers referred by your account.');
        }

        $customer->load([
            'projects' => fn ($q) => $q->where('partner_id', $partner->id),
            'payments' => fn ($q) => $q->where('partner_id', $partner->id),
        ]);

        return view('partner.customers.show', compact('customer'));
    }
}
