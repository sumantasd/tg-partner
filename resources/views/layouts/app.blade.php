<!DOCTYPE html>
<html lang="en" class="h-full bg-[#F6F7FB]">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- PWA Primary Meta Tags -->
    <meta name="theme-color" content="#6B3D95">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="TG Partner">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/icon-192x192.png') }}">

    <title>@yield('title', 'Portal') — Tech Googly Partner Program</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased text-tg-dark bg-[#F6F7FB] overflow-x-hidden selection:bg-tg-purple selection:text-white">
    <div class="min-h-full flex w-full max-w-full min-w-0">
        
        <!-- ======================================================== -->
        <!-- 1. DESKTOP SIDEBAR (>= lg)                               -->
        <!-- Uses exact same component as mobile drawer                -->
        <!-- ======================================================== -->
        <aside class="hidden lg:flex lg:flex-col lg:w-72 lg:fixed lg:inset-y-0 bg-white border-r border-slate-200/90 z-30 shadow-[1px_0_10px_rgba(0,0,0,0.02)]">
            @include('layouts.sidebar-content', ['isMobile' => false])
        </aside>

        <!-- ======================================================== -->
        <!-- 2. MOBILE OFF-CANVAS SLIDE-IN DRAWER (< lg)              -->
        <!-- Opens from left, exact same navigation structure          -->
        <!-- ======================================================== -->
        <div id="mobile-drawer-backdrop" class="lg:hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 transition-opacity hidden opacity-0 duration-300"></div>

        <aside id="mobile-drawer-panel"
               class="lg:hidden fixed inset-y-0 left-0 w-72 sm:w-80 max-w-[85vw] h-full bg-white z-50 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out flex flex-col pt-safe pb-safe"
               aria-label="Mobile Navigation Drawer">
            @include('layouts.sidebar-content', ['isMobile' => true])
        </aside>

        <!-- ======================================================== -->
        <!-- 3. MAIN APPLICATION CONTENT WRAPPER                      -->
        <!-- ======================================================== -->
        <div class="flex-1 flex flex-col lg:pl-72 min-h-screen min-w-0 w-full max-w-full">
            
            <!-- A. MOBILE STICKY TOP APP BAR (< lg) -->
            <header class="lg:hidden sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/90 pt-safe px-4 py-2.5 shadow-[0_1px_4px_rgba(0,0,0,0.03)] w-full">
                <div class="flex items-center justify-between w-full">
                    <!-- Brand & Portal Mark -->
                    <div class="flex items-center space-x-2.5">
                        <a href="{{ auth()->user()->isAdmin() ? route('admin.dashboard') : (auth()->user()->isTeamMember() ? route('team.workspace') : route('partner.workspace')) }}" class="flex items-center space-x-2">
                            <img src="{{ asset('images/brand/tech-googly-logo.png') }}" alt="Tech Googly" class="h-8 w-auto flex-shrink-0 drop-shadow-sm">
                            <div>
                                <span class="font-extrabold text-tg-dark text-sm block leading-tight">Tech Googly</span>
                                <span class="text-[9px] uppercase tracking-wider font-bold text-tg-pink block -mt-0.5">
                                    @if(auth()->user()->isAdmin()) Admin Control @elseif(auth()->user()->isTeamMember()) Sales CRM @else Partner Portal @endif
                                </span>
                            </div>
                        </a>
                    </div>

                    <!-- Right Quick Actions: Search, Notifications, Monogram, Hamburger -->
                    <div class="flex items-center space-x-1.5">
                        <!-- Global Search Quick Trigger (< lg) -->
                        <button type="button" onclick="window.openGlobalSearch()" class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition touch-target" title="Global Search (Ctrl + K)" aria-label="Global Search">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        @if(auth()->user()->isPartner())
                            @php $mobUnread = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
                            <a href="{{ route('partner.notifications.index') }}" class="relative p-2 rounded-xl text-slate-600 hover:bg-slate-100 transition touch-target" title="Notifications">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($mobUnread > 0)
                                    <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-tg-pink ring-2 ring-white"></span>
                                @endif
                            </a>
                        @endif

                        <!-- User Profile Monogram Chip -->
                        @if(auth()->user()->isPartner())
                            <a href="{{ route('partner.profile.edit') }}" title="Partner Profile & Settings" class="w-8 h-8 rounded-xl bg-tg-purple text-white font-extrabold text-xs flex items-center justify-center shadow-sm overflow-hidden touch-target hover:opacity-90 transition">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                @endif
                            </a>
                        @elseif(auth()->user()->isTeamMember())
                            <a href="{{ route('team.profile.edit') }}" title="Staff Profile & Settings" class="w-8 h-8 rounded-xl bg-tg-purple text-white font-extrabold text-xs flex items-center justify-center shadow-sm overflow-hidden touch-target hover:opacity-90 transition">
                                @if(auth()->user()->profile_photo_path)
                                    <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                                @else
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                @endif
                            </a>
                        @else
                            <div class="w-8 h-8 rounded-xl bg-tg-purple text-white font-extrabold text-xs flex items-center justify-center shadow-sm">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </div>
                        @endif

                        <!-- Hamburger Button (Opens Mobile Sidebar Drawer) -->
                        <button type="button" id="open-drawer-btn"
                                class="p-2 rounded-xl text-slate-700 hover:bg-slate-100 transition touch-target min-h-[44px] min-w-[44px] flex items-center justify-center"
                                aria-label="Open Navigation Menu">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </header>

            <!-- B. DESKTOP TOP HEADER (>= lg) -->
            <header class="hidden lg:flex bg-white border-b border-slate-200/90 sticky top-0 z-20 shadow-[0_1px_3px_rgba(0,0,0,0.02)] w-full max-w-full">
                <div class="px-8 h-16 flex items-center justify-between w-full max-w-full">
                    <div class="flex items-center space-x-3">
                        <span class="text-xs uppercase font-extrabold tracking-wider text-tg-purple">
                            @if(auth()->user()->isAdmin()) Tech Googly Admin Control Center
                            @elseif(auth()->user()->isTeamMember()) Tech Googly Internal Telecalling CRM
                            @else Tech Googly Business Partner Portal @endif
                        </span>
                        <span class="text-slate-300">&bull;</span>
                        <span class="text-xs font-semibold text-tg-pink">Innovation For You</span>
                    </div>

                    <div class="flex items-center space-x-4 text-xs">
                        <!-- Desktop Global Search Input Trigger (Ctrl + K) -->
                        <button type="button"
                                onclick="window.openGlobalSearch()"
                                class="flex items-center space-x-2.5 px-3.5 py-1.5 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-400 hover:text-slate-600 border border-slate-200/80 transition text-xs shadow-2xs group cursor-pointer"
                                title="Press Ctrl + K to search anytime">
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-tg-purple transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <span class="text-slate-500 font-medium">Search records...</span>
                            <kbd class="hidden xl:inline-block px-1.5 py-0.5 text-[10px] font-bold text-slate-400 bg-white border border-slate-200 rounded shadow-xs">Ctrl K</kbd>
                        </button>
                        @if(auth()->user()->isPartner())
                            @php $deskUnread = auth()->user()->notifications()->where('is_read', false)->count(); @endphp
                            <a href="{{ route('partner.notifications.index') }}" class="relative p-2 rounded-xl text-slate-500 hover:text-tg-purple hover:bg-slate-100 transition" title="Notifications">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                                @if($deskUnread > 0)
                                    <span class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-tg-pink ring-2 ring-white"></span>
                                @endif
                            </a>

                            <a href="{{ route('partner.profile.edit') }}"
                               class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl text-slate-700 hover:text-tg-purple hover:bg-slate-50 transition border border-slate-200 font-semibold"
                               title="Partner Profile & Settings">
                                <div class="w-5 h-5 rounded-md bg-tg-purple text-white font-bold text-[10px] flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if(auth()->user()->profile_photo_path)
                                        <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    @endif
                                </div>
                                <span class="truncate max-w-[120px]">{{ auth()->user()->name }}</span>
                            </a>
                        @elseif(auth()->user()->isTeamMember())
                            <a href="{{ route('team.profile.edit') }}"
                               class="inline-flex items-center space-x-2 px-3 py-1.5 rounded-xl text-slate-700 hover:text-tg-purple hover:bg-slate-50 transition border border-slate-200 font-semibold"
                               title="Staff Profile & Settings">
                                <div class="w-5 h-5 rounded-md bg-tg-purple text-white font-bold text-[10px] flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if(auth()->user()->profile_photo_path)
                                        <img src="{{ Storage::url(auth()->user()->profile_photo_path) }}" alt="{{ auth()->user()->name }}" class="w-full h-full object-cover">
                                    @else
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                    @endif
                                </div>
                                <span class="truncate max-w-[120px]">{{ auth()->user()->name }}</span>
                            </a>
                        @endif

                        <a href="https://techgoogly.com" target="_blank"
                           class="inline-flex items-center px-3.5 py-1.5 rounded-xl text-slate-600 hover:text-tg-purple hover:bg-slate-50 transition border border-slate-200 font-medium">
                            <span>techgoogly.com</span>
                            <svg class="w-3.5 h-3.5 ml-1 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 rounded-xl bg-tg-purple-light text-tg-purple font-semibold hover:bg-tg-purple hover:text-white border border-tg-purple-border transition text-xs btn-tactile">
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- C. MAIN CONTENT VIEWPORT CONTAINER -->
            <!-- pb-28 ensures bottom navigation bar never obscures bottom cards, forms, or actions on mobile -->
            <main class="flex-1 px-3 sm:px-6 lg:px-8 py-5 sm:py-8 w-full max-w-full min-w-0 pb-28 lg:pb-8 animate-fade-in">
                <!-- Flash Messages -->
                @if (session('success'))
                    <div class="mb-6 rounded-2xl bg-emerald-50 p-4 border border-emerald-200 text-xs sm:text-sm font-semibold text-emerald-800 flex items-center shadow-sm">
                        <svg class="w-5 h-5 text-emerald-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('warning'))
                    <div class="mb-6 rounded-2xl bg-amber-50 p-4 border border-amber-200 text-xs sm:text-sm font-semibold text-amber-800 flex items-center shadow-sm">
                        <svg class="w-5 h-5 text-amber-600 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-2xl bg-tg-blue-light p-4 border border-tg-blue-border text-xs sm:text-sm font-semibold text-tg-blue-hover shadow-sm">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-6 rounded-2xl bg-rose-50 p-4 border border-rose-200 text-xs sm:text-sm text-rose-800 shadow-sm">
                        <div class="font-bold mb-1">Please correct the following:</div>
                        <ul class="list-disc list-inside text-xs space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>

            <!-- D. BRANDED DESKTOP FOOTER -->
            <footer class="hidden lg:block bg-white border-t border-slate-200/90 py-5 mt-auto w-full max-w-full">
                <div class="px-8 flex items-center justify-between text-xs text-slate-500 w-full max-w-full">
                    <div class="flex items-center space-x-3">
                        <img src="{{ asset('images/brand/tech-googly-logo.png') }}" alt="Tech Googly Logo" class="h-7 w-auto object-contain">
                        <div>
                            <span class="font-bold text-tg-dark">Tech Googly</span> &bull; <span class="font-semibold text-tg-pink">Innovation For You</span>
                        </div>
                    </div>
                    <div class="text-right text-[11px] text-slate-400">
                        &copy; {{ date('Y') }} Tech Googly. All rights reserved.
                    </div>
                </div>
            </footer>
        </div>

        <!-- ======================================================== -->
        <!-- 4. ORIGINAL MOBILE BOTTOM NAVIGATION BAR (< lg)          -->
        <!-- 5 original items per role, fixed at bottom of viewport    -->
        <!-- ======================================================== -->
        <nav id="mobile-bottom-nav" class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white/95 backdrop-blur-md border-t border-slate-200/90 shadow-[0_-4px_25px_rgba(0,0,0,0.06)] pb-safe">
            <div class="grid grid-cols-5 h-16 max-w-md mx-auto items-center px-1">
                @if (auth()->user()->isAdmin())
                    <!-- Admin Tab 1: Dashboard -->
                    @php $isAdminDash = request()->routeIs('admin.dashboard'); @endphp
                    <a href="{{ route('admin.dashboard') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isAdminDash ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isAdminDash ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                            </svg>
                            @if($isAdminDash)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Dashboard</span>
                    </a>

                    <!-- Admin Tab 2: Partners -->
                    @php $isAdminPartners = request()->routeIs('admin.partners.*'); @endphp
                    <a href="{{ route('admin.partners.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isAdminPartners ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isAdminPartners ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            @if($isAdminPartners)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Partners</span>
                    </a>

                    <!-- Admin Tab 3: Leads -->
                    @php $isAdminLeads = request()->routeIs('admin.leads.*'); @endphp
                    <a href="{{ route('admin.leads.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isAdminLeads ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isAdminLeads ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                            </svg>
                            @if($isAdminLeads)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Leads</span>
                    </a>

                    <!-- Admin Tab 4: Finance / Payouts -->
                    @php $isAdminFinance = request()->routeIs('admin.payouts.*', 'admin.payments.*', 'admin.commissions.*'); @endphp
                    <a href="{{ route('admin.payouts.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isAdminFinance ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isAdminFinance ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            @if($isAdminFinance)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Finance</span>
                    </a>

                    <!-- Admin Tab 5: More Drawer -->
                    <button type="button" class="open-drawer-btn flex flex-col items-center justify-center py-1 text-slate-500 hover:text-tg-purple transition touch-target min-h-[44px]" aria-label="Open More Menu Drawer">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <span class="text-[10px] mt-1 font-medium">More</span>
                    </button>
                @elseif (auth()->user()->isTeamMember())
                    <!-- Staff Tab 1: Home / Workspace -->
                    @php $isStaffHome = request()->routeIs('team.workspace'); @endphp
                    <a href="{{ route('team.workspace') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isStaffHome ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isStaffHome ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            @if($isStaffHome)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Home</span>
                    </a>

                    <!-- Staff Tab 2: My Leads -->
                    @php $isStaffLeads = request()->routeIs('team.leads.index') && !request()->hasAny(['followup_today', 'status']); @endphp
                    <a href="{{ route('team.leads.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isStaffLeads ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isStaffLeads ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            @if($isStaffLeads)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">My Leads</span>
                    </a>

                    <!-- Staff Tab 3: Today's Follow-ups -->
                    @php $isStaffFollow = request('followup_today') === '1'; @endphp
                    <a href="{{ route('team.leads.index', ['followup_today' => '1']) }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isStaffFollow ? 'text-amber-600 font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isStaffFollow ? 'text-amber-600' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            @if($isStaffFollow)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-amber-500 rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Calls Today</span>
                    </a>

                    <!-- Staff Tab 4: Pipeline -->
                    @php $isStaffPipe = request('status') === 'in_discussion'; @endphp
                    <a href="{{ route('team.leads.index', ['status' => 'in_discussion']) }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isStaffPipe ? 'text-tg-blue font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isStaffPipe ? 'text-tg-blue' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                            @if($isStaffPipe)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-blue rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Pipeline</span>
                    </a>

                    <!-- Staff Tab 5: More Drawer -->
                    <button type="button" class="open-drawer-btn flex flex-col items-center justify-center py-1 text-slate-500 hover:text-tg-purple transition touch-target min-h-[44px]" aria-label="Open More Menu Drawer">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <span class="text-[10px] mt-1 font-medium">More</span>
                    </button>
                @elseif (auth()->user()->isPartner())
                    <!-- Partner Tab 1: Dashboard -->
                    @php $isPartDash = request()->routeIs('partner.workspace'); @endphp
                    <a href="{{ route('partner.workspace') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isPartDash ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isPartDash ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                            </svg>
                            @if($isPartDash)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Home</span>
                    </a>

                    <!-- Partner Tab 2: My Leads -->
                    @php $isPartLeads = request()->routeIs('partner.leads.*'); @endphp
                    <a href="{{ route('partner.leads.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isPartLeads ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isPartLeads ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                            @if($isPartLeads)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">My Leads</span>
                    </a>

                    <!-- Partner Tab 3: Earnings -->
                    @php $isPartEarn = request()->routeIs('partner.commissions.*'); @endphp
                    <a href="{{ route('partner.commissions.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isPartEarn ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isPartEarn ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            @if($isPartEarn)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Earnings</span>
                    </a>

                    <!-- Partner Tab 4: Withdrawals -->
                    @php $isPartWith = request()->routeIs('partner.withdrawals.*'); @endphp
                    <a href="{{ route('partner.withdrawals.index') }}"
                       class="flex flex-col items-center justify-center py-1 transition touch-target min-h-[44px] {{ $isPartWith ? 'text-tg-purple font-bold' : 'text-slate-500 hover:text-slate-800 font-medium' }}">
                        <div class="relative">
                            <svg class="w-5 h-5 {{ $isPartWith ? 'text-tg-purple' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            @if($isPartWith)
                                <span class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 w-1 h-1 bg-tg-purple rounded-full"></span>
                            @endif
                        </div>
                        <span class="text-[10px] mt-1">Withdraw</span>
                    </a>

                    <!-- Partner Tab 5: More Drawer -->
                    <button type="button" class="open-drawer-btn flex flex-col items-center justify-center py-1 text-slate-500 hover:text-tg-purple transition touch-target min-h-[44px]" aria-label="Open More Menu Drawer">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/>
                        </svg>
                        <span class="text-[10px] mt-1 font-medium">More</span>
                    </button>
                @endif
            </div>
        </nav>
    </div>

    <!-- Admin Sidebar Single-Active Accordion Script & Mobile Drawer Handler -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // 1. Sidebar Accordion Setup Function (Independent for container)
            function setupSidebarAccordion(container) {
                if (!container) return;

                const buttons = container.querySelectorAll('.sidebar-accordion-btn');

                buttons.forEach(button => {
                    button.addEventListener('click', function (e) {
                        e.preventDefault();
                        const targetId = this.getAttribute('data-target');
                        const targetGroup = this.getAttribute('data-group');
                        const targetSubmenu = container.querySelector('#' + targetId) || container.querySelector(`[data-submenu="${targetGroup}"]`);
                        if (!targetSubmenu) return;

                        const isCurrentlyOpen = !targetSubmenu.classList.contains('hidden');

                        // Single-active accordion system: Close all submenus in this container
                        buttons.forEach(otherBtn => {
                            const otherTargetId = otherBtn.getAttribute('data-target');
                            const otherGroup = otherBtn.getAttribute('data-group');
                            const otherSubmenu = container.querySelector('#' + otherTargetId) || container.querySelector(`[data-submenu="${otherGroup}"]`);
                            if (otherSubmenu) {
                                otherSubmenu.classList.add('hidden');
                            }
                            otherBtn.setAttribute('aria-expanded', 'false');
                            otherBtn.classList.remove('text-tg-purple', 'bg-tg-purple-light/70', 'font-bold');
                            otherBtn.classList.add('text-slate-700', 'font-medium');
                            const otherChevron = otherBtn.querySelector('.accordion-chevron');
                            if (otherChevron) {
                                otherChevron.classList.remove('rotate-180', 'text-tg-purple');
                                otherChevron.classList.add('text-slate-400');
                            }
                            const otherParentIcon = otherBtn.querySelector('.parent-nav-icon');
                            if (otherParentIcon) {
                                otherParentIcon.classList.remove('text-tg-purple');
                                otherParentIcon.classList.add('text-slate-400');
                            }
                        });

                        // If it was closed, open it now
                        if (!isCurrentlyOpen) {
                            targetSubmenu.classList.remove('hidden');
                            this.setAttribute('aria-expanded', 'true');
                            this.classList.add('text-tg-purple', 'bg-tg-purple-light/70', 'font-bold');
                            this.classList.remove('text-slate-700', 'font-medium');
                            const chevron = this.querySelector('.accordion-chevron');
                            if (chevron) {
                                chevron.classList.add('rotate-180', 'text-tg-purple');
                                chevron.classList.remove('text-slate-400');
                            }
                            const parentIcon = this.querySelector('.parent-nav-icon');
                            if (parentIcon) {
                                parentIcon.classList.add('text-tg-purple');
                                parentIcon.classList.remove('text-slate-400');
                            }
                        }
                    });
                });
            }

            // Initialize accordion for desktop sidebar and mobile drawer
            setupSidebarAccordion(document.querySelector('aside.hidden.lg\\:flex'));
            setupSidebarAccordion(document.querySelector('#mobile-drawer-panel'));

            // 2. Mobile Off-Canvas Drawer Open/Close Logic
            const backdrop = document.getElementById('mobile-drawer-backdrop');
            const panel = document.getElementById('mobile-drawer-panel');
            const openButtons = document.querySelectorAll('#open-drawer-btn, .open-drawer-btn');
            const closeButton = document.getElementById('close-drawer-btn');

            function openDrawer() {
                if (!backdrop || !panel) return;
                backdrop.classList.remove('hidden');
                // Force browser reflow for clean opacity transition
                void backdrop.offsetWidth;
                backdrop.classList.remove('opacity-0');
                panel.classList.remove('-translate-x-full');
                panel.classList.add('translate-x-0');
                document.body.style.overflow = 'hidden';
            }

            function closeDrawer() {
                if (!backdrop || !panel) return;
                backdrop.classList.add('opacity-0');
                panel.classList.remove('translate-x-0');
                panel.classList.add('-translate-x-full');
                setTimeout(() => {
                    backdrop.classList.add('hidden');
                    document.body.style.overflow = '';
                }, 300);
            }

            openButtons.forEach(btn => btn.addEventListener('click', openDrawer));
            if (closeButton) closeButton.addEventListener('click', closeDrawer);
            if (backdrop) backdrop.addEventListener('click', closeDrawer);

            // Close drawer after clicking any navigation link
            if (panel) {
                const links = panel.querySelectorAll('a');
                links.forEach(link => {
                    link.addEventListener('click', function () {
                        closeDrawer();
                    });
                });
            }

            // Close on Escape key press
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && panel && !panel.classList.contains('-translate-x-full')) {
                    closeDrawer();
                }
            });
        });
    </script>
    <!-- Global Search Modal Component -->
    @include('layouts.global-search-modal')

</body>
</html>
