<!-- Global Search Palette Modal (Ctrl + K / Cmd + K) -->
<div id="global-search-modal"
     class="fixed inset-0 z-50 overflow-y-auto hidden"
     role="dialog"
     aria-modal="true"
     aria-labelledby="global-search-title">

    <!-- Backdrop -->
    <div id="global-search-backdrop"
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity opacity-0"
         onclick="window.closeGlobalSearch()"></div>

    <!-- Dialog Positioner -->
    <div class="min-h-full flex items-start justify-center p-4 sm:p-6 md:p-12 text-center">
        <!-- Dialog Surface -->
        <div id="global-search-panel"
             class="relative w-full max-w-2xl transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all border border-slate-200/90 divide-y divide-slate-100 opacity-0 scale-95 duration-200">

            <!-- Search Header Bar -->
            <div class="relative flex items-center px-4 py-3 bg-white">
                <!-- Search Icon -->
                <svg class="w-5 h-5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>

                <!-- Input Field -->
                <input type="text"
                       id="global-search-input"
                       class="w-full bg-transparent border-0 pl-3 pr-20 text-slate-800 placeholder-slate-400 text-sm sm:text-base focus:ring-0 focus:outline-none"
                       placeholder="Search leads, projects, customers, partners, reference codes..."
                       autocomplete="off"
                       spellcheck="false">

                <!-- Loading Spinner & Clear Button -->
                <div class="absolute right-4 flex items-center space-x-2">
                    <div id="global-search-spinner" class="hidden">
                        <svg class="animate-spin h-4 w-4 text-tg-purple" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </div>

                    <button type="button"
                            id="global-search-clear-btn"
                            class="hidden text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition"
                            title="Clear search">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>

                    <kbd class="hidden sm:inline-block px-1.5 py-0.5 text-[10px] font-semibold text-slate-400 bg-slate-100 border border-slate-200 rounded">ESC</kbd>
                </div>
            </div>

            <!-- Search Results Body -->
            <div id="global-search-body" class="max-h-[60vh] overflow-y-auto p-2 divide-y divide-slate-100">
                <!-- Initial / Empty State -->
                <div id="global-search-empty-state" class="p-8 text-center">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-3 shadow-inner">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">Quick Global Search</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Type at least 2 characters to search across authorized records, reference codes, customers, and financial documents.
                    </p>

                    <!-- Recent Searches Section (Populated dynamically) -->
                    <div id="global-search-recent-section" class="mt-4 pt-4 border-t border-slate-100 text-left hidden">
                        <div class="flex items-center justify-between mb-2 px-2">
                            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Recent Searches</span>
                            <button type="button" onclick="window.clearRecentSearches()" class="text-[11px] text-tg-purple hover:underline font-medium">Clear</button>
                        </div>
                        <div id="global-search-recent-tags" class="flex flex-wrap gap-1.5 px-2"></div>
                    </div>
                </div>

                <!-- No Results State -->
                <div id="global-search-no-results" class="p-8 text-center hidden">
                    <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-sm font-semibold text-slate-800">No records found</h3>
                    <p id="global-search-no-results-text" class="text-xs text-slate-500 mt-1">We couldn't find any authorized records matching your query.</p>
                </div>

                <!-- Results Container (Grouped Categories) -->
                <div id="global-search-results-list" class="space-y-3 p-1 hidden"></div>
            </div>

            <!-- Footer: View All & Keyboard Shortcuts -->
            <div class="px-4 py-2.5 bg-slate-50/80 flex items-center justify-between text-xs text-slate-500 rounded-b-2xl">
                <a id="global-search-view-all-link"
                   href="{{ route('search.index') }}"
                   class="inline-flex items-center text-tg-purple hover:text-indigo-700 font-semibold hover:underline">
                    <span>View all matching results</span>
                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>

                <div class="hidden sm:flex items-center space-x-3 text-[11px] text-slate-400">
                    <span><kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[10px] text-slate-600">↑</kbd> <kbd class="px-1 py-0.5 bg-white border border-slate-200 rounded text-[10px] text-slate-600">↓</kbd> navigate</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] text-slate-600">↵</kbd> open</span>
                    <span><kbd class="px-1.5 py-0.5 bg-white border border-slate-200 rounded text-[10px] text-slate-600">esc</kbd> close</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('global-search-modal');
        const backdrop = document.getElementById('global-search-backdrop');
        const panel = document.getElementById('global-search-panel');
        const input = document.getElementById('global-search-input');
        const spinner = document.getElementById('global-search-spinner');
        const clearBtn = document.getElementById('global-search-clear-btn');
        const emptyState = document.getElementById('global-search-empty-state');
        const noResults = document.getElementById('global-search-no-results');
        const noResultsText = document.getElementById('global-search-no-results-text');
        const resultsList = document.getElementById('global-search-results-list');
        const viewAllLink = document.getElementById('global-search-view-all-link');
        const recentSection = document.getElementById('global-search-recent-section');
        const recentTags = document.getElementById('global-search-recent-tags');

        let debounceTimer = null;
        let selectedIndex = -1;
        let resultItems = [];

        window.openGlobalSearch = function (presetQuery = '') {
            if (!modal) return;
            modal.classList.remove('hidden');
            void backdrop.offsetWidth; // Reflow
            backdrop.classList.remove('opacity-0');
            panel.classList.remove('opacity-0', 'scale-95');
            panel.classList.add('opacity-100', 'scale-100');
            document.body.style.overflow = 'hidden';

            if (presetQuery) {
                input.value = presetQuery;
                triggerSearch(presetQuery);
            } else {
                fetchRecentSearches();
            }

            setTimeout(() => input.focus(), 50);
        };

        window.closeGlobalSearch = function () {
            if (!modal) return;
            backdrop.classList.add('opacity-0');
            panel.classList.remove('opacity-100', 'scale-100');
            panel.classList.add('opacity-0', 'scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = '';
                input.value = '';
                clearBtn.classList.add('hidden');
                resetView();
            }, 200);
        };

        function resetView() {
            emptyState.classList.remove('hidden');
            noResults.classList.add('hidden');
            resultsList.classList.add('hidden');
            resultsList.innerHTML = '';
            viewAllLink.href = "{{ route('search.index') }}";
            selectedIndex = -1;
            resultItems = [];
        }

        // Global Keyboard Shortcut: Ctrl + K / Cmd + K
        window.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                if (modal.classList.contains('hidden')) {
                    window.openGlobalSearch();
                } else {
                    window.closeGlobalSearch();
                }
            } else if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                e.preventDefault();
                window.closeGlobalSearch();
            }
        });

        // Input event with 250ms debouncing
        input.addEventListener('input', function () {
            const query = input.value.trim();
            if (query.length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }

            clearTimeout(debounceTimer);
            if (query.length < 2) {
                resetView();
                fetchRecentSearches();
                return;
            }

            debounceTimer = setTimeout(() => {
                triggerSearch(query);
            }, 250);
        });

        clearBtn.addEventListener('click', function () {
            input.value = '';
            clearBtn.classList.add('hidden');
            resetView();
            fetchRecentSearches();
            input.focus();
        });

        // Keyboard Navigation (Up, Down, Enter)
        input.addEventListener('keydown', function (e) {
            if (resultItems.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = (selectedIndex + 1) % resultItems.length;
                updateSelection();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = (selectedIndex - 1 + resultItems.length) % resultItems.length;
                updateSelection();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedIndex >= 0 && selectedIndex < resultItems.length) {
                    const targetUrl = resultItems[selectedIndex].getAttribute('data-url');
                    if (targetUrl) window.location.href = targetUrl;
                } else {
                    // Navigate to full search page
                    const query = input.value.trim();
                    if (query.length >= 2) {
                        window.location.href = "{{ route('search.index') }}?q=" + encodeURIComponent(query);
                    }
                }
            }
        });

        function updateSelection() {
            resultItems.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.classList.add('bg-indigo-50/80', 'ring-1', 'ring-indigo-300');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-indigo-50/80', 'ring-1', 'ring-indigo-300');
                }
            });
        }

        function triggerSearch(query) {
            spinner.classList.remove('hidden');
            viewAllLink.href = "{{ route('search.index') }}?q=" + encodeURIComponent(query);

            fetch("{{ route('search.suggest') }}?q=" + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('hidden');
                renderResults(data);
            })
            .catch(err => {
                spinner.classList.add('hidden');
                console.error('Global search error:', err);
            });
        }

        function renderResults(data) {
            selectedIndex = -1;
            resultItems = [];

            if (!data.categories || data.categories.length === 0 || data.total_results === 0) {
                emptyState.classList.add('hidden');
                noResults.classList.remove('hidden');
                resultsList.classList.add('hidden');
                noResultsText.textContent = `No authorized records matching "${data.query}".`;
                return;
            }

            emptyState.classList.add('hidden');
            noResults.classList.add('hidden');
            resultsList.classList.remove('hidden');
            resultsList.innerHTML = '';

            data.categories.forEach(category => {
                if (!category.items || category.items.length === 0) return;

                const catDiv = document.createElement('div');
                catDiv.className = 'category-group mb-3';

                const catHeader = document.createElement('div');
                catHeader.className = 'flex items-center justify-between px-2 py-1 mb-1';
                catHeader.innerHTML = `
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">${escapeHtml(category.name)}</span>
                    <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full">${category.count}</span>
                `;
                catDiv.appendChild(catHeader);

                category.items.forEach(item => {
                    const a = document.createElement('a');
                    a.href = item.url;
                    a.setAttribute('data-url', item.url);
                    a.className = 'search-result-item flex items-center justify-between p-2.5 rounded-xl hover:bg-indigo-50/60 transition group cursor-pointer border border-transparent hover:border-indigo-100';

                    a.innerHTML = `
                        <div class="flex items-center space-x-3 min-w-0 pr-2">
                            <div class="w-8 h-8 rounded-lg bg-slate-100 group-hover:bg-white flex items-center justify-center flex-shrink-0 text-slate-500 shadow-xs transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs sm:text-sm font-semibold text-slate-800 truncate group-hover:text-tg-purple transition">
                                    ${escapeHtml(item.title)}
                                </p>
                                <p class="text-[11px] text-slate-500 truncate mt-0.5">
                                    ${escapeHtml(item.subtitle)}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            ${item.badge ? `<span class="text-[10px] font-semibold px-2 py-0.5 rounded-md border ${item.badge_classes || 'bg-slate-100 text-slate-700 border-slate-200'}">${escapeHtml(item.badge)}</span>` : ''}
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-tg-purple transition transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>
                    `;

                    catDiv.appendChild(a);
                    resultItems.push(a);
                });

                resultsList.appendChild(catDiv);
            });
        }

        function fetchRecentSearches() {
            fetch("{{ route('search.suggest') }}?q=", {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.recent_searches && data.recent_searches.length > 0) {
                    recentSection.classList.remove('hidden');
                    recentTags.innerHTML = '';
                    data.recent_searches.forEach(term => {
                        const chip = document.createElement('button');
                        chip.type = 'button';
                        chip.className = 'px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-indigo-50 hover:text-tg-purple text-xs text-slate-600 transition font-medium border border-slate-200/60';
                        chip.textContent = term;
                        chip.onclick = function () {
                            input.value = term;
                            clearBtn.classList.remove('hidden');
                            triggerSearch(term);
                        };
                        recentTags.appendChild(chip);
                    });
                } else {
                    recentSection.classList.add('hidden');
                }
            })
            .catch(() => {});
        }

        window.clearRecentSearches = function () {
            fetch("{{ route('search.recent.clear') }}", {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(() => {
                recentSection.classList.add('hidden');
                recentTags.innerHTML = '';
            })
            .catch(() => {});
        };

        function escapeHtml(text) {
            if (!text) return '';
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    })();
</script>
