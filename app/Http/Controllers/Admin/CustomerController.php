<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\Partner;
use App\Services\CustomerService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * List all converted and directly created customers with filters and search.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $partnerId = $request->query('partner_id');

        $query = Customer::with(['firstPartner.user', 'projects', 'payments'])
            ->latest();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('customer_code', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($partnerId) {
            $query->where('first_partner_id', $partnerId);
        }

        $customers = $query->paginate(15)->withQueryString();
        $partners = Partner::with('user')->orderBy('company_name')->get();

        $metrics = [
            'total_customers'  => Customer::count(),
            'active_customers' => Customer::where('status', 'active')->count(),
            'with_projects'    => Customer::has('projects')->count(),
        ];

        return view('admin.customers.index', compact('customers', 'partners', 'search', 'partnerId', 'metrics'));
    }

    /**
     * Show form to directly create a new customer without prior lead.
     */
    public function create(): View
    {
        $partners = Partner::with('user')
            ->whereHas('user', fn ($q) => $q->where('status', 'active'))
            ->orderBy('company_name')
            ->get();

        return view('admin.customers.create', compact('partners'));
    }

    /**
     * Store a newly created direct customer.
     */
    public function store(Request $request, CustomerService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name'             => ['required', 'string', 'max:150'],
            'company_name'     => ['nullable', 'string', 'max:200'],
            'phone'            => ['required', 'string', 'max:30'],
            'whatsapp_number'  => ['nullable', 'string', 'max:30'],
            'email'            => ['nullable', 'email', 'max:255'],
            'address_line1'    => ['nullable', 'string', 'max:255'],
            'city'             => ['nullable', 'string', 'max:100'],
            'state'            => ['nullable', 'string', 'max:100'],
            'postal_code'      => ['nullable', 'string', 'max:20'],
            'country'          => ['nullable', 'string', 'max:100'],
            'first_partner_id' => ['nullable', 'exists:partners,id'],
            'status'           => ['required', 'in:active,inactive'],
            'notes'            => ['nullable', 'string'],
        ]);

        $customer = $service->createCustomer($validated, $request->user());

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Customer #{$customer->customer_code} ({$customer->name}) created successfully.");
    }

    /**
     * Display a single customer profile with linked projects and payments.
     */
    public function show(Customer $customer): View
    {
        $customer->load([
            'firstPartner.user',
            'lead',
            'creator',
            'projects.assignedTo',
            'projects.payments',
            'payments.project',
        ]);

        return view('admin.customers.show', compact('customer'));
    }

    /**
     * Show form to edit an existing customer profile.
     */
    public function edit(Customer $customer): View
    {
        $customer->load('firstPartner.user');

        return view('admin.customers.edit', compact('customer'));
    }

    /**
     * Update an existing customer profile.
     */
    public function update(Request $request, Customer $customer, CustomerService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'company_name'    => ['nullable', 'string', 'max:200'],
            'phone'           => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'email'           => ['nullable', 'email', 'max:255'],
            'address_line1'   => ['nullable', 'string', 'max:255'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'postal_code'     => ['nullable', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:100'],
            'status'          => ['required', 'in:active,inactive'],
            'notes'           => ['nullable', 'string'],
        ]);

        $service->updateCustomer($customer, $validated);

        return redirect()->route('admin.customers.show', $customer)
            ->with('success', "Customer #{$customer->customer_code} updated successfully.");
    }

    /**
     * Show confirmation/review screen before converting a Won lead.
     */
    public function showConvertForm(Lead $lead): View|RedirectResponse
    {
        if ($lead->status !== LeadStatus::Won) {
            return redirect()->route('admin.leads.show', $lead)
                ->with('error', "Only Won leads can be converted to Customers (current status: {$lead->status->label()}).");
        }

        if ($lead->converted_customer_id !== null) {
            return redirect()->route('admin.customers.show', $lead->converted_customer_id)
                ->with('info', "Lead #{$lead->lead_number} is already converted to Customer ID #{$lead->converted_customer_id}.");
        }

        return view('admin.leads.convert', compact('lead'));
    }

    /**
     * Execute transactional conversion of a Won lead into a Customer.
     */
    public function convert(Request $request, Lead $lead, CustomerService $service): RedirectResponse
    {
        $validated = $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'company_name'    => ['nullable', 'string', 'max:200'],
            'phone'           => ['required', 'string', 'max:30'],
            'whatsapp_number' => ['nullable', 'string', 'max:30'],
            'email'           => ['nullable', 'email', 'max:255'],
            'address_line1'   => ['nullable', 'string', 'max:255'],
            'city'            => ['nullable', 'string', 'max:100'],
            'state'           => ['nullable', 'string', 'max:100'],
            'postal_code'     => ['nullable', 'string', 'max:20'],
            'country'         => ['nullable', 'string', 'max:100'],
            'notes'           => ['nullable', 'string'],
        ]);

        try {
            $customer = $service->convertLeadToCustomer($lead, $validated, $request->user());

            return redirect()->route('admin.customers.show', $customer)
                ->with('success', "Lead #{$lead->lead_number} was successfully converted to Customer #{$customer->customer_code}!");
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }
    }
}
