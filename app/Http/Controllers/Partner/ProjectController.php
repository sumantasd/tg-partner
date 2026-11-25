<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display partner's own projects.
     */
    public function index(Request $request): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Project::where('partner_id', $partner->id)
            ->with(['customer', 'commissions'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('project_code', 'like', "%{$search}%")
                  ->orWhere('project_title', 'like', "%{$search}%")
                  ->orWhere('client_name', 'like', "%{$search}%");
            });
        }

        $projects = $query->paginate(15)->withQueryString();

        return view('partner.projects.index', compact('projects', 'status', 'search'));
    }

    /**
     * Display project detail for partner's own project.
     */
    public function show(Request $request, Project $project): View
    {
        $partner = $request->user()->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        // Strict cross-partner boundary check
        if ($project->partner_id !== $partner->id) {
            abort(403, 'Unauthorized. You can only view projects linked to your partner account.');
        }

        $project->load([
            'customer',
            'commissions' => fn ($q) => $q->where('partner_id', $partner->id),
        ]);

        return view('partner.projects.show', compact('project'));
    }
}
