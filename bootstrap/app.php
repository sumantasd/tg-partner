<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectTo(
            guests: '/login',
            users: function (\Illuminate\Http\Request $request) {
                $user = $request->user();
                if (! $user) {
                    return '/login';
                }
                return match ($user->role) {
                    \App\Enums\UserRole::Admin => route('admin.dashboard'),
                    \App\Enums\UserRole::TeamMember => route('team.workspace'),
                    \App\Enums\UserRole::Partner => route('partner.workspace'),
                };
            }
        );

        $middleware->alias([
            'role.admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
            'role.team' => \App\Http\Middleware\EnsureUserIsTeamMember::class,
            'role.partner' => \App\Http\Middleware\EnsureUserIsPartner::class,
            'partner.active' => \App\Http\Middleware\EnsurePartnerIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
