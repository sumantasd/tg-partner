<?php

namespace App\Http\Controllers\Admin;

use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TeamMemberController extends Controller
{
    /**
     * Display a paginated list of internal team members / staff.
     */
    public function index(Request $request): View
    {
        $status = $request->query('status', 'all');
        $department = $request->query('department');
        $search = $request->query('search');

        $query = User::where('role', UserRole::TeamMember)
            ->withCount('assignedLeads')
            ->latest();

        if ($status === 'active') {
            $query->where('status', UserStatus::Active);
        } elseif ($status === 'suspended' || $status === 'inactive') {
            $query->where('status', UserStatus::Suspended);
        }

        if ($department) {
            $query->where('department', $department);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('designation', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $teamMembers = $query->paginate(15)->withQueryString();

        $counts = [
            'all'       => User::where('role', UserRole::TeamMember)->count(),
            'active'    => User::where('role', UserRole::TeamMember)->where('status', UserStatus::Active)->count(),
            'suspended' => User::where('role', UserRole::TeamMember)->where('status', UserStatus::Suspended)->count(),
        ];

        $departments = User::where('role', UserRole::TeamMember)
            ->whereNotNull('department')
            ->distinct()
            ->pluck('department');

        return view('admin.team-members.index', compact('teamMembers', 'status', 'department', 'search', 'counts', 'departments'));
    }

    /**
     * Show form to provision a new team member account.
     */
    public function create(): View
    {
        return view('admin.team-members.create');
    }

    /**
     * Provision a new Internal Team Member account.
     * Enforces strict role isolation (UserRole::TeamMember).
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'mobile_number'         => ['nullable', 'string', 'max:30'],
            'employee_id'           => ['nullable', 'string', 'max:50', 'unique:users,employee_id'],
            'designation'           => ['nullable', 'string', 'max:100'],
            'department'            => ['nullable', 'string', 'max:100'],
            'joining_date'          => ['nullable', 'date'],
            'status'                => ['required', Rule::enum(UserStatus::class)],
            'password'              => ['required', 'confirmed', Password::defaults()],
        ]);

        $phone = $validated['phone'] ?? $validated['mobile_number'] ?? null;

        $teamMember = User::create([
            'name'              => $validated['name'],
            'email'             => strtolower(trim($validated['email'])),
            'phone'             => $phone,
            'employee_id'       => $validated['employee_id'] ?? null,
            'designation'       => $validated['designation'] ?? null,
            'department'        => $validated['department'] ?? 'Sales',
            'joining_date'      => $validated['joining_date'] ?? now()->toDateString(),
            'password'          => Hash::make($validated['password']),
            'role'              => UserRole::TeamMember, // Strictly enforced: no role escalation
            'status'            => UserStatus::from($validated['status']),
            'email_verified_at' => now(),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::TeamMemberCreated,
            module: \App\Enums\AuditLogModule::Team,
            description: "Team member {$teamMember->name} ({$teamMember->email}) onboarded by Admin.",
            subject: $teamMember,
            newValues: [
                'name' => $teamMember->name,
                'email' => $teamMember->email,
                'department' => $teamMember->department,
                'designation' => $teamMember->designation,
                'status' => $teamMember->status->value,
            ]
        );

        return redirect()->route('admin.team-members.show', $teamMember)
            ->with('success', "Team member {$teamMember->name} ({$teamMember->email}) successfully onboarded.");
    }

    /**
     * Display profile, lead statistics, and activities for a team member.
     */
    public function show(User $teamMember): View
    {
        abort_unless($teamMember->role === UserRole::TeamMember, 404);

        $teamMember->loadCount('assignedLeads');

        $assignedLeads = Lead::where('assigned_to', $teamMember->id)
            ->with('partner')
            ->latest()
            ->paginate(10);

        $stats = [
            'total_assigned' => Lead::where('assigned_to', $teamMember->id)->count(),
            'in_progress'    => Lead::where('assigned_to', $teamMember->id)->whereIn('status', [
                LeadStatus::Assigned,
                LeadStatus::Contacted,
                LeadStatus::InDiscussion,
                LeadStatus::QuotationSent,
                LeadStatus::Negotiating,
            ])->count(),
            'won_leads'      => Lead::where('assigned_to', $teamMember->id)->where('status', LeadStatus::Won)->count(),
            'lost_leads'     => Lead::where('assigned_to', $teamMember->id)->where('status', LeadStatus::Lost)->count(),
        ];

        return view('admin.team-members.show', compact('teamMember', 'assignedLeads', 'stats'));
    }

    /**
     * Show form to edit an existing team member.
     */
    public function edit(User $teamMember): View
    {
        abort_unless($teamMember->role === UserRole::TeamMember, 404);

        return view('admin.team-members.edit', compact('teamMember'));
    }

    /**
     * Update an existing team member profile and permissions.
     */
    public function update(Request $request, User $teamMember): RedirectResponse
    {
        abort_unless($teamMember->role === UserRole::TeamMember, 404);

        $validated = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($teamMember->id)],
            'phone'                 => ['nullable', 'string', 'max:30'],
            'mobile_number'         => ['nullable', 'string', 'max:30'],
            'employee_id'           => ['nullable', 'string', 'max:50', Rule::unique('users', 'employee_id')->ignore($teamMember->id)],
            'designation'           => ['nullable', 'string', 'max:100'],
            'department'            => ['nullable', 'string', 'max:100'],
            'joining_date'          => ['nullable', 'date'],
            'status'                => ['required', Rule::enum(UserStatus::class)],
            'password'              => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $phone = $validated['phone'] ?? $validated['mobile_number'] ?? $teamMember->phone;

        $updateData = [
            'name'        => $validated['name'],
            'email'       => strtolower(trim($validated['email'])),
            'phone'       => $phone,
            'employee_id' => $validated['employee_id'] ?? $teamMember->employee_id,
            'designation' => $validated['designation'] ?? $teamMember->designation,
            'department'  => $validated['department'] ?? $teamMember->department,
            'status'      => UserStatus::from($validated['status']),
        ];

        if (array_key_exists('joining_date', $validated)) {
            $updateData['joining_date'] = $validated['joining_date'];
        }

        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $oldAttributes = $teamMember->getAttributes();

        // Strict role preservation: never change role in update
        $teamMember->update($updateData);

        app(\App\Services\AuditLogService::class)->recordModelUpdate(
            model: $teamMember,
            oldAttributes: $oldAttributes,
            action: \App\Enums\AuditLogAction::TeamMemberUpdated,
            module: \App\Enums\AuditLogModule::Team,
            description: "Team member {$teamMember->name} updated by Admin."
        );

        return redirect()->route('admin.team-members.show', $teamMember)
            ->with('success', "Team member {$teamMember->name} updated successfully.");
    }

    /**
     * Quick toggle account status between Active and Suspended.
     */
    public function toggleStatus(User $teamMember): RedirectResponse
    {
        abort_unless($teamMember->role === UserRole::TeamMember, 404);

        $oldStatus = $teamMember->status->value;
        $newStatus = $teamMember->isActive() ? UserStatus::Suspended : UserStatus::Active;
        $teamMember->update(['status' => $newStatus]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::TeamMemberStatusToggled,
            module: \App\Enums\AuditLogModule::Team,
            description: "Team member {$teamMember->name} status changed from {$oldStatus} to {$newStatus->value}.",
            subject: $teamMember,
            oldValues: ['status' => $oldStatus],
            newValues: ['status' => $newStatus->value]
        );

        $label = $newStatus === UserStatus::Active ? 'activated' : 'deactivated';
        return back()->with('success', "Team member {$teamMember->name} has been {$label}.");
    }

    /**
     * Reset password directly by Admin.
     */
    public function resetPassword(Request $request, User $teamMember): RedirectResponse
    {
        abort_unless($teamMember->role === UserRole::TeamMember, 404);

        $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $teamMember->update([
            'password' => Hash::make($request->password),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::TeamMemberPasswordReset,
            module: \App\Enums\AuditLogModule::Team,
            description: "Password reset performed for team member {$teamMember->name} ({$teamMember->email}) by Admin.",
            subject: $teamMember
        );

        return back()->with('success', "Password for {$teamMember->name} successfully reset.");
    }
}
