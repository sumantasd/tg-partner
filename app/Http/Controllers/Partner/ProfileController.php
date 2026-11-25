<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Services\WithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        protected WithdrawalService $withdrawalService
    ) {}

    /**
     * Show Partner Profile overview and settings.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $partner = $user->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $balanceSummary = $this->withdrawalService->getPartnerBalanceSummary($partner);

        return view('partner.profile.edit', compact('user', 'partner', 'balanceSummary'));
    }

    /**
     * Update editable partner and business profile information.
     * Enforces strict immutability for verified credentials, role, and account status.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $partner = $user->partner;
        if (! $partner) {
            abort(403, 'No partner profile associated with this account.');
        }

        $validated = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'company_name'  => ['required', 'string', 'max:255'],
            'website'       => ['nullable', 'string', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'city'          => ['nullable', 'string', 'max:100'],
            'state'         => ['nullable', 'string', 'max:100'],
            'postal_code'   => ['nullable', 'string', 'max:20'],
        ]);

        $user->update([
            'name' => $validated['name'],
        ]);

        $partner->update([
            'company_name'  => $validated['company_name'],
            'website'       => !empty($validated['website']) ? trim($validated['website']) : null,
            'address_line1' => !empty($validated['address_line1']) ? trim($validated['address_line1']) : null,
            'city'          => !empty($validated['city']) ? trim($validated['city']) : null,
            'state'         => !empty($validated['state']) ? trim($validated['state']) : null,
            'postal_code'   => !empty($validated['postal_code']) ? trim($validated['postal_code']) : null,
        ]);

        return back()->with('success', 'Profile and business details updated successfully.');
    }

    /**
     * Securely update password.
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
            description: "Partner {$user->name} changed their account password.",
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
