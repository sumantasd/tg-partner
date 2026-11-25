@extends('layouts.app')

@section('title', 'Global Search - Tech Googly')

@section('content')
<div class="space-y-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-6">
    <!-- Header & Search Input Box -->
    <div class="bg-white rounded-2xl border border-slate-200/90 p-5 sm:p-6 shadow-xs">
        <div class="max-w-3xl">
            <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight flex items-center space-x-2.5">
                <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 shadow-inner">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <span>Global Search</span>
            </h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-1">
                Unified role-aware search across leads, clients, projects, agreements, and financial ledger records.
            </p>
        </div>

        <!-- Search Form -->
        <form method="GET" action="{{ route('search.index') }}" class="mt-5 space-y-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="q"
                           value="{{ $query }}"
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-tg-purple focus:ring-1 focus:ring-tg-purple shadow-2xs"
                           placeholder="Search references, customer names, emails, phones, or keywords (min 2 chars)..."
                           autocomplete="off">
                </div>

                <!-- Hidden preserve active module if selecting -->
                @if(!empty($active_module) && $active_module !== 'all')
                    <input type="hidden" name="module" value="{{ $active_module }}">
                @endif

                <div class="flex items-center space-x-2">
                    <button type="submit" class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-tg-purple text-white text-xs sm:text-sm font-semibold hover:bg-indigo-700 transition shadow-sm btn-tactile">
                        Search
                    </button>
                    @if(!empty($query))
                        <a href="{{ route('search.index') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs sm:text-sm font-medium transition" title="Clear search">
                            Reset
                        </a>
                    @endif
                </div>
            </div>

            <!-- Advanced Filters Row (Dates & Status) -->
            <div class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Status Filter</label>
                    <input type="text"
                           name="status"
                           value="{{ $status_filter ?? '' }}"
                           placeholder="Filter by status (e.g. Active, Pending)"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:border-tg-purple focus:ring-tg-purple">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date From</label>
                    <input type="date"
                           name="date_from"
                           value="{{ $date_from ?? '' }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:border-tg-purple focus:ring-tg-purple">
                </div>

                <div>
                    <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Date To</label>
                    <input type="date"
                           name="date_to"
                           value="{{ $date_to ?? '' }}"
                           class="w-full px-3 py-1.5 rounded-lg border border-slate-200 text-xs focus:border-tg-purple focus:ring-tg-purple">
                </div>
            </div>
        </form>

        <!-- Module Category Tabs -->
        @if(!empty($query) && mb_strlen($query) >= 2)
            <div class="mt-5 pt-4 border-t border-slate-100 flex items-center space-x-1.5 overflow-x-auto pb-1 scrollbar-none">
                <a href="{{ route('search.index', array_merge(request()->except(['module', 'page']), ['module' => 'all'])) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $active_module === 'all' ? 'bg-tg-purple text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                    All Results
                    <span class="ml-1.5 px-1.5 py-0.5 rounded-md text-[10px] {{ $active_module === 'all' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                        {{ $total_results }}
                    </span>
                </a>

                @foreach($modules as $modKey => $modLabel)
                    @php $count = $grouped_counts[$modKey] ?? 0; @endphp
                    @if($count > 0 || $active_module === $modKey)
                        <a href="{{ route('search.index', array_merge(request()->except(['module', 'page']), ['module' => $modKey])) }}"
                           class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition {{ $active_module === $modKey ? 'bg-tg-purple text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            {{ $modLabel }}
                            <span class="ml-1.5 px-1.5 py-0.5 rounded-md text-[10px] {{ $active_module === $modKey ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">
                                {{ $count }}
                            </span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>

    <!-- Results Display -->
    @if(empty($query) || mb_strlen(trim($query)) < 2)
        <!-- Initial Blank State -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-xs">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-4 shadow-inner">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">Search Tech Googly Database</h3>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md mx-auto">
                Type at least 2 characters in the search field above or press <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded text-xs font-semibold text-slate-600">Ctrl + K</kbd> anywhere in the application.
            </p>

            @if(!empty($recent_searches))
                <div class="mt-6 pt-6 border-t border-slate-100 max-w-md mx-auto text-left">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Your Recent Searches</span>
                        <form action="{{ route('search.recent.clear') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs text-tg-purple hover:underline font-semibold">Clear History</button>
                        </form>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($recent_searches as $term)
                            <a href="{{ route('search.index', ['q' => $term]) }}" class="px-3 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-tg-purple text-xs text-slate-700 transition font-medium border border-slate-200">
                                {{ $term }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @elseif($total_results === 0)
        <!-- No Results State -->
        <div class="bg-white rounded-2xl border border-slate-200/90 p-12 text-center shadow-xs">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-800">No matching records found</h3>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-md mx-auto">
                No records matching "<span class="font-semibold text-slate-700">{{ $query }}</span>" were found within your authorized scope.
            </p>
            <div class="mt-4">
                <a href="{{ route('search.index') }}" class="inline-flex items-center text-xs font-semibold text-tg-purple hover:underline">
                    Clear search and try another query &rarr;
                </a>
            </div>
        </div>
    @else
        <!-- Results Grid -->
        <div class="space-y-3">
            <div class="flex items-center justify-between text-xs text-slate-500 px-1">
                <span>Showing {{ $results->firstItem() ?? 0 }} to {{ $results->lastItem() ?? 0 }} of {{ $total_results }} results</span>
                <span>Active module: <strong class="text-slate-700">{{ $modules[$active_module] ?? 'All Results' }}</strong></span>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200/90 divide-y divide-slate-100 shadow-xs overflow-hidden">
                @foreach($results as $item)
                    <a href="{{ $item['url'] }}"
                       class="flex items-center justify-between p-4 hover:bg-slate-50 transition group">
                        <div class="flex items-center space-x-3.5 min-w-0 pr-3">
                            <div class="w-10 h-10 rounded-xl bg-slate-100 group-hover:bg-indigo-50 group-hover:text-tg-purple flex items-center justify-center text-slate-500 transition shadow-2xs flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center space-x-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ $item['category'] }}
                                    </span>
                                    @if(isset($item['created_at']))
                                        <span class="text-[11px] text-slate-400">&bull; {{ $item['created_at'] }}</span>
                                    @endif
                                </div>
                                <h4 class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-tg-purple transition truncate mt-1">
                                    {{ $item['title'] }}
                                </h4>
                                <p class="text-xs text-slate-500 truncate mt-0.5">
                                    {{ $item['subtitle'] }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center space-x-3 flex-shrink-0">
                            @if(!empty($item['badge']))
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-md border {{ $item['badge_classes'] ?? 'bg-slate-100 text-slate-700 border-slate-200' }}">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 group-hover:text-tg-purple group-hover:bg-indigo-50 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-4">
                {{ $results->links() }}
            </div>
        </div>
    @endif
</div>
@endsection
