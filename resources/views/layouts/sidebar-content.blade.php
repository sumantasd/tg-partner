@php
    $isMobile = $isMobile ?? false;
    $prefix = $isMobile ? 'mob-' : 'desk-';
@endphp

<!-- Brand Block -->
<div class="p-5 border-b border-slate-100 flex items-center justify-between">
    <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isTeamMember() ? route('team.workspace') : route('partner.workspace')) }}" class="flex items-center space-x-3 transition hover:opacity-95">
        <img src="{{ asset('images/brand/tech-googly-logo.png') }}"
             alt="Tech Googly Logo"
             class="h-10 sm:h-11 w-auto object-contain flex-shrink-0 drop-shadow-sm">
        <div class="leading-tight">
            <span class="font-extrabold text-tg-dark text-base block tracking-tight">Tech Googly</span>
            <span class="text-[10px] uppercase tracking-wider font-bold text-tg-pink block -mt-0.5">Innovation For You</span>
        </div>
    </a>

    @if ($isMobile)
        <!-- Mobile Drawer Close Button -->
        <button type="button"
                id="close-drawer-btn"
                class="p-2 rounded-xl text-slate-400 hover:text-tg-pink hover:bg-slate-100 transition touch-target"
                aria-label="Close Navigation Menu">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>

<!-- Role Badge Banner -->
<div class="px-5 py-2.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">
        @if(auth()->user()->isAdmin())
            Admin Control
        @elseif(auth()->user()->isTeamMember())
            Sales CRM Workspace
        @elseif(auth()->user()->isPartner())
            Partner Portal
        @endif
    </span>
    @if(auth()->user()->partner)
        <span class="font-mono text-[11px] font-bold text-tg-purple bg-tg-purple-light px-2 py-0.5 rounded-md border border-tg-purple-border">
            {{ auth()->user()->partner->partner_code }}
        </span>
    @elseif(auth()->user()->employee_id)
        <span class="font-mono text-[10px] font-bold text-tg-blue bg-tg-blue-light px-2 py-0.5 rounded-md border border-tg-blue-border">
            {{ auth()->user()->employee_id }}
        </span>
    @else
        <span class="text-[10px] font-bold text-tg-green bg-tg-green-light px-2 py-0.5 rounded-md border border-tg-green-border">
            Verified
        </span>
    @endif
</div>

<!-- Navigation Links -->
<nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto text-xs font-medium table-scroll-container">
    <!-- Global Search Shortcut Link -->
    <a href="{{ route('search.index') }}"
       class="flex items-center justify-between px-3.5 py-2.5 rounded-xl text-slate-700 hover:text-tg-purple hover:bg-tg-purple-light/50 transition mb-2 border border-slate-200/60 bg-slate-50/50 group"
       title="Open Global Search (Ctrl + K)">
        <div class="flex items-center space-x-2.5">
            <svg class="w-4 h-4 text-slate-400 group-hover:text-tg-purple transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="font-semibold">Global Search</span>
        </div>
        <kbd class="px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-white border border-slate-200 rounded">Ctrl K</kbd>
    </a>

    @if (auth()->user()->isAdmin())
        @php
            $activeGroup = null;
            if (request()->routeIs('admin.partners.*')) {
                $activeGroup = 'partners';
            } elseif (request()->routeIs('admin.team-members.*')) {
                $activeGroup = 'team-members';
            } elseif (request()->routeIs('admin.leads.*') || request()->routeIs('admin.lead-disputes.*') || request()->routeIs('admin.lead-sla.*')) {
                $activeGroup = 'leads';
            } elseif (request()->routeIs('admin.customers.*')) {
                $activeGroup = 'customers';
            } elseif (request()->routeIs('admin.projects.*')) {
                $activeGroup = 'projects';
            } elseif (request()->routeIs('admin.payments.*')) {
                $activeGroup = 'payments';
            } elseif (request()->routeIs('admin.commissions.*') || request()->routeIs('admin.settings.commission.*') || request()->routeIs('admin.settings.commission-policies.*')) {
                $activeGroup = 'commissions';
            } elseif (request()->routeIs('admin.kyc.*')) {
                $activeGroup = 'kyc';
            }
        @endphp

        <!-- 1. Executive Dashboard -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pb-1">Overview</div>
        <a href="{{ route('admin.dashboard') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.dashboard') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span>Financial Dashboard</span>
        </a>

        <!-- 2. Partners Management (Accordion Group) -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">Network &amp; Staff</div>
        <div class="space-y-0.5">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'partners' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-partners"
                    data-group="partners"
                    aria-expanded="{{ $activeGroup === 'partners' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'partners' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <span>Partners</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'partners' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-partners"
                 data-submenu="partners"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'partners' ? '' : 'hidden' }}">
                <a href="{{ route('admin.partners.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.partners.index', 'admin.partners.show', 'admin.partners.edit') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Partners
                </a>
                <a href="{{ route('admin.partners.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.partners.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    + Add New Partner
                </a>
            </div>
        </div>

        <!-- 3. Team Members (Accordion Group) -->
        <div class="space-y-0.5 pt-1">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'team-members' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-team-members"
                    data-group="team-members"
                    aria-expanded="{{ $activeGroup === 'team-members' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'team-members' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span>Team Members</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'team-members' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-team-members"
                 data-submenu="team-members"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'team-members' ? '' : 'hidden' }}">
                <a href="{{ route('admin.team-members.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.team-members.index', 'admin.team-members.show', 'admin.team-members.edit') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Team Members
                </a>
                <a href="{{ route('admin.team-members.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.team-members.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    + Add Team Member
                </a>
            </div>
        </div>

        <!-- 4. KYC Verification (Accordion Group) -->
        <div class="space-y-0.5 pt-1">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'kyc' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-kyc"
                    data-group="kyc"
                    aria-expanded="{{ $activeGroup === 'kyc' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'kyc' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    <span>KYC Verification</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'kyc' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-kyc"
                 data-submenu="kyc"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'kyc' ? '' : 'hidden' }}">
                <a href="{{ route('admin.kyc.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.kyc.index', 'admin.kyc.show') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    KYC Dashboard
                </a>
                <a href="{{ route('admin.kyc.pending') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.kyc.pending') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Pending KYC
                </a>
                <a href="{{ route('admin.kyc.approved') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.kyc.approved') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Approved KYC
                </a>
                <a href="{{ route('admin.kyc.rejected') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.kyc.rejected') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Rejected KYC
                </a>
            </div>
        </div>

        <!-- Inactivity Monitor -->
        <div class="pt-1">
            <a href="{{ route('admin.inactivity.index') }}"
               class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.inactivity.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
                <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Inactivity Monitor</span>
            </a>
        </div>

        <!-- 5. Leads Management (Accordion Group) -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">CRM &amp; Business</div>
        <div class="space-y-0.5">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'leads' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-leads"
                    data-group="leads"
                    aria-expanded="{{ $activeGroup === 'leads' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'leads' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>Leads</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'leads' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-leads"
                 data-submenu="leads"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'leads' ? '' : 'hidden' }}">
                <a href="{{ route('admin.leads.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.leads.index') && !request()->has('view') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Leads
                </a>
                <a href="{{ route('admin.leads.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.leads.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Add New Lead
                </a>
                <a href="{{ route('admin.leads.index', ['view' => 'pipeline']) }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->get('view') === 'pipeline' ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Lead Pipeline
                </a>
                <a href="{{ route('admin.lead-disputes.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.lead-disputes.*') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Lead Disputes
                </a>
                <a href="{{ route('admin.lead-sla.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.lead-sla.*') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Lead SLA Dashboard
                </a>
            </div>
        </div>

        <!-- 6. Customers Management (Accordion Group) -->
        <div class="space-y-0.5 pt-1">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'customers' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-customers"
                    data-group="customers"
                    aria-expanded="{{ $activeGroup === 'customers' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'customers' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <span>Customers</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'customers' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-customers"
                 data-submenu="customers"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'customers' ? '' : 'hidden' }}">
                <a href="{{ route('admin.customers.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.customers.index', 'admin.customers.show', 'admin.customers.edit') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Customers
                </a>
                <a href="{{ route('admin.customers.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.customers.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    + Add Customer
                </a>
            </div>
        </div>

        <!-- 7. Projects Management (Accordion Group) -->
        <div class="space-y-0.5 pt-1">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'projects' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-projects"
                    data-group="projects"
                    aria-expanded="{{ $activeGroup === 'projects' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'projects' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span>Projects</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'projects' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-projects"
                 data-submenu="projects"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'projects' ? '' : 'hidden' }}">
                <a href="{{ route('admin.projects.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.projects.index', 'admin.projects.show', 'admin.projects.edit') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Projects
                </a>
                <a href="{{ route('admin.projects.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.projects.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    + Add Project
                </a>
            </div>
        </div>

        <!-- 8. Payments Management (Accordion Group) -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">Finance &amp; Ledger</div>
        <div class="space-y-0.5">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'payments' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-payments"
                    data-group="payments"
                    aria-expanded="{{ $activeGroup === 'payments' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'payments' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Payments</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'payments' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-payments"
                 data-submenu="payments"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'payments' ? '' : 'hidden' }}">
                <a href="{{ route('admin.payments.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.payments.index') && !request()->has('status') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    All Payments
                </a>
                <a href="{{ route('admin.payments.create') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.payments.create') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    + Record Payment
                </a>
                <a href="{{ route('admin.payments.index', ['status' => 'submitted']) }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->get('status') === 'submitted' ? 'text-amber-700 font-bold bg-amber-50' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Pending Verification
                </a>
            </div>
        </div>

        <!-- 9. Commissions (Accordion Group) -->
        <div class="space-y-0.5 pt-1">
            <button type="button"
                    class="sidebar-accordion-btn w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition cursor-pointer {{ $activeGroup === 'commissions' ? 'text-tg-purple bg-tg-purple-light/70 font-bold' : 'text-slate-700 hover:text-tg-purple hover:bg-slate-100 font-medium' }}"
                    data-target="{{ $prefix }}submenu-commissions"
                    data-group="commissions"
                    aria-expanded="{{ $activeGroup === 'commissions' ? 'true' : 'false' }}">
                <div class="flex items-center space-x-2.5">
                    <svg class="w-4 h-4 flex-shrink-0 parent-nav-icon {{ $activeGroup === 'commissions' ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Commissions</span>
                </div>
                <svg class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200 transform accordion-chevron {{ $activeGroup === 'commissions' ? 'rotate-180 text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="{{ $prefix }}submenu-commissions"
                 data-submenu="commissions"
                 class="sidebar-submenu pl-4 space-y-0.5 border-l-2 border-slate-100 ml-3.5 my-1 transition-all duration-200 {{ $activeGroup === 'commissions' ? '' : 'hidden' }}">
                <a href="{{ route('admin.commissions.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.commissions.*') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Commission Ledger
                </a>
                <a href="{{ route('admin.settings.commission.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.settings.commission.index') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Commission Settings
                </a>
                <a href="{{ route('admin.settings.commission-policies.index') }}"
                   class="block px-2.5 py-1.5 rounded-lg transition {{ request()->routeIs('admin.settings.commission-policies.*') ? 'text-tg-purple font-bold bg-tg-purple-light' : 'text-slate-600 hover:text-tg-purple hover:bg-slate-50' }}">
                    Commission Policies
                </a>
            </div>
        </div>

        <!-- 10. Payouts & Withdrawals (Direct Link) -->
        <a href="{{ route('admin.payouts.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.payouts.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Payouts &amp; Withdrawals</span>
        </a>

        <!-- 11. Reports & Analytics (Direct Link) -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">Intelligence &amp; Settings</div>
        <a href="{{ route('admin.reports.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.reports.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Reports &amp; Analytics</span>
        </a>

        <!-- 12. Agreement Templates (Direct Link) -->
        <a href="{{ route('admin.settings.agreement-templates.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.settings.agreement-templates.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Agreement Templates</span>
        </a>

        <!-- 13. Partner Agreements (Direct Link) -->
        <a href="{{ route('admin.agreements.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.agreements.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
            <span>Partner Agreements</span>
        </a>

        <!-- 14. Settings (Direct Link) -->
        <a href="{{ route('admin.settings.commission.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.settings.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>System Settings</span>
        </a>

        <!-- 15. Audit Logs (Direct Link) -->
        <a href="{{ route('admin.audit-logs.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.audit-logs.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>Central Audit Logs</span>
        </a>

        <!-- 16. Demo Data & Backup (Direct Link) -->
        <a href="{{ route('admin.demo-data.index') }}"
           class="flex items-center px-3 py-2 rounded-xl transition {{ request()->routeIs('admin.demo-data.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple font-medium' }}">
            <svg class="w-4 h-4 mr-2.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
            </svg>
            <span>Demo Data &amp; Backup</span>
        </a>
    @elseif (auth()->user()->isTeamMember())
        <!-- Staff / Team Member Navigation -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pb-1">Sales CRM Workspace</div>
        <a href="{{ route('team.workspace') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('team.workspace') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>CRM Dashboard</span>
        </a>

        <a href="{{ route('team.leads.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('team.leads.index') && !request()->has('followup_today') && !request()->has('overdue') && !request()->has('status') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>My Assigned Leads</span>
        </a>

        <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request('followup_today') === '1' ? 'bg-amber-500 text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-amber-600' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <span>Today's Follow-ups</span>
        </a>

        <a href="{{ route('team.leads.index', ['status' => 'in_discussion']) }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request('status') === 'in_discussion' ? 'bg-tg-blue text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-blue' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
            </svg>
            <span>Active Pipeline</span>
        </a>
    @elseif (auth()->user()->isPartner())
        <!-- Partner Navigation -->
        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pb-1">Portal</div>
        <a href="{{ route('partner.workspace') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.workspace') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Dashboard Overview</span>
        </a>

        <a href="{{ route('partner.leads.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.leads.index', 'partner.leads.show') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <span>My Submitted Leads</span>
        </a>

        <a href="{{ route('partner.disputes.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.disputes.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>Ownership Disputes</span>
        </a>

        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">Business Portfolio</div>
        <a href="{{ route('partner.customers.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.customers.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Converted Customers</span>
        </a>

        <a href="{{ route('partner.projects.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.projects.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Active Projects</span>
        </a>

        <div class="text-[10px] uppercase font-bold tracking-wider text-slate-400 px-3 pt-3 pb-1">Earnings &amp; Payouts</div>
        <a href="{{ route('partner.commissions.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.commissions.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Commission Earnings</span>
        </a>

        <a href="{{ route('partner.withdrawals.index') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.withdrawals.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Withdrawals &amp; Payouts</span>
        </a>

        <a href="{{ route('partner.kyc.show') }}"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.kyc.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>KYC &amp; Verification</span>
            </div>
            @php $sbPartner = auth()->user()->partner; @endphp
            @if ($sbPartner)
                @if ($sbPartner->isFullyKycVerified())
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">Verified</span>
                @elseif ($sbPartner->kyc_status?->value === 'under_review')
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800">Review</span>
                @elseif ($sbPartner->kyc_status?->value === 'correction_requested' || $sbPartner->bank_verification_status?->value === 'correction_requested')
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-100 text-rose-800">Action</span>
                @elseif ($sbPartner->kyc_status?->value === 'rejected' || $sbPartner->bank_verification_status?->value === 'rejected')
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-800">Rejected</span>
                @else
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 text-slate-700">Pending</span>
                @endif
            @endif
        </a>

        <a href="{{ route('partner.agreement.show') }}"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.agreement.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Partner Agreement</span>
            </div>
            @if ($sbPartner)
                @if ($sbPartner->hasAcceptedAgreement())
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800">Accepted</span>
                @elseif ($sbPartner->hasPendingAgreement())
                    <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 animate-pulse">Action</span>
                @endif
            @endif
        </a>

        <a href="{{ route('partner.payout-settings.edit') }}"
           class="flex items-center px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.payout-settings.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span>Bank &amp; Payout Settings</span>
        </a>

        <a href="{{ route('partner.notifications.index') }}"
           class="flex items-center justify-between px-3.5 py-2.5 rounded-xl transition {{ request()->routeIs('partner.notifications.*') ? 'bg-tg-purple text-white shadow-sm font-semibold' : 'text-slate-700 hover:bg-slate-100 hover:text-tg-purple' }}">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span>Notifications</span>
            </div>
            @php $unreadPartnerCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
            @if ($unreadPartnerCount > 0)
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-tg-pink text-white">
                    {{ $unreadPartnerCount }}
                </span>
            @endif
        </a>

        <div class="pt-3">
            <a href="{{ route('partner.leads.create') }}"
               class="flex items-center justify-center px-4 py-2.5 rounded-xl bg-tg-purple text-white font-bold text-xs hover:bg-tg-purple-hover shadow-sm transition btn-tactile">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Submit New Lead
            </a>
        </div>
    @endif
</nav>

<!-- Bottom User Profile in Sidebar -->
<div class="p-4 border-t border-slate-100 bg-slate-50/70">
    <div class="flex items-center justify-between">
        @if(auth()->user()->isPartner())
            <a href="{{ route('partner.profile.edit') }}" class="flex items-center space-x-3 overflow-hidden group hover:opacity-90 transition flex-1 min-w-0 mr-2" title="View & Edit Partner Profile">
                <div class="w-9 h-9 rounded-xl bg-tg-purple text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden group-hover:ring-2 group-hover:ring-tg-purple-border transition">
                    @if(auth()->user()->profile_photo_path)
                        <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="truncate">
                    <div class="font-bold text-xs text-tg-dark truncate group-hover:text-tg-purple transition-colors">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-slate-500 capitalize flex items-center gap-1">
                        <span>{{ auth()->user()->role->label() }}</span>
                        <svg class="w-3 h-3 text-slate-400 group-hover:text-tg-purple transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
        @elseif(auth()->user()->isTeamMember())
            <a href="{{ route('team.profile.edit') }}" class="flex items-center space-x-3 overflow-hidden group hover:opacity-90 transition flex-1 min-w-0 mr-2" title="View & Edit Staff Profile">
                <div class="w-9 h-9 rounded-xl bg-tg-purple text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm overflow-hidden group-hover:ring-2 group-hover:ring-tg-purple-border transition">
                    @if(auth()->user()->profile_photo_path)
                        <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                    @else
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    @endif
                </div>
                <div class="truncate">
                    <div class="font-bold text-xs text-tg-dark truncate group-hover:text-tg-purple transition-colors">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-slate-500 capitalize flex items-center gap-1">
                        <span>{{ auth()->user()->designation ?: 'Staff Member' }}</span>
                        <svg class="w-3 h-3 text-slate-400 group-hover:text-tg-purple transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                </div>
            </a>
        @else
            <div class="flex items-center space-x-3 overflow-hidden flex-1 min-w-0 mr-2">
                <div class="w-9 h-9 rounded-xl bg-tg-purple text-white font-bold text-xs flex items-center justify-center flex-shrink-0 shadow-sm">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="truncate">
                    <div class="font-bold text-xs text-tg-dark truncate">{{ auth()->user()->name }}</div>
                    <div class="text-[10px] text-slate-500 capitalize">{{ auth()->user()->role->label() }}</div>
                </div>
            </div>
        @endif
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" title="Sign Out" class="p-2 rounded-lg text-slate-400 hover:text-tg-pink hover:bg-tg-pink-light transition touch-target">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>
</div>
