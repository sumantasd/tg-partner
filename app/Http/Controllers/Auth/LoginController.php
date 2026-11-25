<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    /**
     * Show the application login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        $normalizedEmail = Str::lower(trim($credentials['email']));
        $throttleKey = Str::transliterate($normalizedEmail . '|' . $request->ip());

        // Login rate limiting & brute-force protection (5 attempts per minute)
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            Log::warning('Login rate limit exceeded', [
                'email'           => $normalizedEmail,
                'ip'              => $request->ip(),
                'lockout_seconds' => $seconds,
            ]);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LoginFailed,
                module: \App\Enums\AuditLogModule::Auth,
                description: "Login rate limit exceeded for {$normalizedEmail}.",
                metadata: ['email' => $normalizedEmail, 'lockout_seconds' => $seconds, 'reason' => 'rate_limited'],
                ipAddress: $request->ip(),
                userAgent: $request->userAgent()
            );

            return back()->withErrors([
                'email' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->onlyInput('email');
        }

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            RateLimiter::clear($throttleKey);
            $request->session()->regenerate();

            $user = Auth::user();

            // Handle deactivated / suspended accounts
            if ($user->isSuspended()) {
                app(\App\Services\AuditLogService::class)->record(
                    action: \App\Enums\AuditLogAction::LoginFailed,
                    module: \App\Enums\AuditLogModule::Auth,
                    description: "Suspended {$user->role->label()} account ({$user->email}) attempted login.",
                    subject: $user,
                    metadata: ['reason' => 'account_suspended', 'role' => $user->role->value],
                    actor: $user
                );

                if ($user->role === UserRole::Partner) {
                    Log::info('Suspended partner accessed login, redirecting to suspended notice', [
                        'user_id' => $user->id,
                        'email'   => $user->email,
                        'ip'      => $request->ip(),
                    ]);

                    return redirect()->route('partner.suspended');
                }

                // Deactivated Admin or Team Member cannot log in
                Log::warning('Deactivated staff member attempted login', [
                    'user_id' => $user->id,
                    'role'    => $user->role->value,
                    'ip'      => $request->ip(),
                ]);

                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact an administrator.',
                ])->onlyInput('email');
            }

            Log::info('User authenticated successfully', [
                'user_id' => $user->id,
                'role'    => $user->role->value,
                'ip'      => $request->ip(),
            ]);

            app(\App\Services\InactivityService::class)->recordActivity($user, 'login');

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::LoginSuccess,
                module: \App\Enums\AuditLogModule::Auth,
                description: "User {$user->name} ({$user->email}) authenticated successfully.",
                subject: $user,
                metadata: ['role' => $user->role->value],
                actor: $user
            );

            return match ($user->role) {
                UserRole::Admin      => redirect()->intended(route('admin.dashboard')),
                UserRole::TeamMember => redirect()->intended(route('team.workspace')),
                UserRole::Partner    => $user->isPending()
                    ? redirect()->route('partner.pending')
                    : redirect()->intended(route('partner.workspace')),
            };
        }

        RateLimiter::hit($throttleKey, 60);

        Log::warning('Failed login attempt', [
            'email' => $normalizedEmail,
            'ip'    => $request->ip(),
        ]);

        app(\App\Services\AuditLogService::class)->record(
            action: \App\Enums\AuditLogAction::LoginFailed,
            module: \App\Enums\AuditLogModule::Auth,
            description: "Failed login attempt for email {$normalizedEmail}.",
            metadata: ['email' => $normalizedEmail, 'reason' => 'invalid_credentials'],
            ipAddress: $request->ip(),
            userAgent: $request->userAgent()
        );

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user) {
            app(\App\Services\InactivityService::class)->recordLogout($user);

            app(\App\Services\AuditLogService::class)->record(
                action: \App\Enums\AuditLogAction::Logout,
                module: \App\Enums\AuditLogModule::Auth,
                description: "User {$user->name} ({$user->email}) logged out.",
                subject: $user,
                actor: $user
            );
        }
        $userId = $user?->id;
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('User logged out', [
            'user_id' => $userId,
            'ip'      => $request->ip(),
        ]);

        return redirect()->route('login');
    }
}
