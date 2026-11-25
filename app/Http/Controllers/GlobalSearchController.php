<?php

namespace App\Http\Controllers;

use App\Services\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GlobalSearchController extends Controller
{
    public function __construct(
        protected GlobalSearchService $searchService
    ) {}

    /**
     * Live search suggestion endpoint for dropdown and Cmd+K palette.
     */
    public function suggest(Request $request): JsonResponse
    {
        $query = (string) $request->query('q', '');
        $trimmed = trim($query);

        if (mb_strlen($trimmed) < 2) {
            return response()->json([
                'query' => $trimmed,
                'total_results' => 0,
                'categories' => [],
                'recent_searches' => session()->get('recent_searches', []),
            ]);
        }

        $result = $this->searchService->search($trimmed, $request->user(), limitPerCategory: 5);

        // Track isolated session recent search
        $recent = session()->get('recent_searches', []);
        if (!in_array($trimmed, $recent, true)) {
            array_unshift($recent, $trimmed);
            $recent = array_slice($recent, 0, 5);
            session()->put('recent_searches', $recent);
        }
        $result['recent_searches'] = $recent;

        return response()->json($result);
    }

    /**
     * Full search results page (/search).
     */
    public function index(Request $request): View
    {
        $query = (string) $request->query('q', '');
        $module = $request->query('module');
        $status = $request->query('status');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $searchData = $this->searchService->searchPaginated(
            query: $query,
            user: $request->user(),
            moduleFilter: $module,
            statusFilter: $status,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            perPage: 15
        );

        $searchData['status_filter'] = $status;
        $searchData['date_from'] = $dateFrom;
        $searchData['date_to'] = $dateTo;
        $searchData['recent_searches'] = session()->get('recent_searches', []);

        return view('search.index', $searchData);
    }

    /**
     * Clear user session recent searches.
     */
    public function clearRecent(Request $request): JsonResponse|RedirectResponse
    {
        session()->forget('recent_searches');

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['status' => 'cleared']);
        }

        return back()->with('info', 'Recent searches have been cleared.');
    }
}
