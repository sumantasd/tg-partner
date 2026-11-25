<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuditLogAction;
use App\Enums\AuditLogModule;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display a paginated, filterable audit log stream.
     */
    public function index(Request $request): View
    {
        $query = AuditLog::with('actor')->latest('created_at');

        // Apply filters
        if ($request->filled('module')) {
            $query->module($request->module);
        }

        if ($request->filled('action')) {
            $query->action($request->action);
        }

        if ($request->filled('actor_id')) {
            $query->actor($request->actor_id);
        }

        if ($request->filled('role')) {
            $query->role($request->role);
        }

        if ($request->filled('subject_type')) {
            $query->subjectType($request->subject_type);
        }

        if ($request->filled('from_date') || $request->filled('to_date')) {
            $query->dateRange($request->from_date, $request->to_date);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        $logs = $query->paginate(20)->withQueryString();

        // Calculate KPI counts
        $todayStart = now()->timezone('Asia/Kolkata')->startOfDay()->timezone('UTC');
        $kpis = [
            'total_events'   => AuditLog::count(),
            'today_events'   => AuditLog::where('created_at', '>=', $todayStart)->count(),
            'admin_events'   => AuditLog::where('actor_role_snapshot', UserRole::Admin->value)->count(),
            'partner_events' => AuditLog::where('actor_role_snapshot', UserRole::Partner->value)->count(),
            'staff_events'   => AuditLog::where('actor_role_snapshot', UserRole::TeamMember->value)->count(),
        ];

        $modules = AuditLogModule::cases();
        $actions = AuditLogAction::cases();

        $actors = User::select('id', 'name', 'email', 'role')
            ->whereIn('id', AuditLog::whereNotNull('actor_user_id')->distinct()->pluck('actor_user_id'))
            ->orderBy('name')
            ->get();

        $subjectTypes = AuditLog::whereNotNull('subject_type')
            ->distinct()
            ->pluck('subject_type')
            ->map(function ($fullClass) {
                return [
                    'class' => $fullClass,
                    'name' => class_basename($fullClass),
                ];
            });

        return view('admin.audit-logs.index', compact(
            'logs',
            'kpis',
            'modules',
            'actions',
            'actors',
            'subjectTypes'
        ));
    }

    /**
     * Display a single audit event dossier with full diffs and metadata.
     */
    public function show(AuditLog $audit_log): View
    {
        $audit_log->load(['actor', 'subject']);

        return view('admin.audit-logs.show', [
            'log' => $audit_log,
        ]);
    }
}
