<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display Team Member profile details and credentials.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // Get personal CRM performance summary for the profile page
        $leadStats = [
            'assigned' => $user->assignedLeads()->count(),
            'won'      => $user->assignedLeads()->where('status', \App\Enums\LeadStatus::Won)->count(),
        ];
        $leadStats['conversion_rate'] = $leadStats['assigned'] > 0
            ? round(($leadStats['won'] / $leadStats['assigned']) * 100, 1)
            : 0.0;

        return view('team.profile.edit', compact('user', 'leadStats'));
    }

    /**
     * Update editable staff profile information.
     * Enforces strict immutability for Employee ID, Designation, Department,
     * Joining Date, Email, Role, and Account Status.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user->update([
            'name'  => $validated['name'],
            'phone' => !empty($validated['phone']) ? trim($validated['phone']) : null,
        ]);

        return back()->with('success', 'Profile contact details updated successfully.');
    }

    /**
     * Securely update login password.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::PasswordChanged,
            module: \App\Enums\AuditLogModule::Auth,
            description: "Team member {$user->name} changed their account password.",
            subject: $user,
            actor: $user
        );

        return back()->with('success', 'Your login password has been changed successfully.');
    }

    /**
     * Upload or update profile avatar.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $user = $request->user();

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $path = $request->file('photo')->store('profile_photos', 'public');

        $user->update([
            'profile_photo_path' => $path,
        ]);

        return back()->with('success', 'Profile photo updated successfully.');
    }
}
