<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartnerIsActive
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Admins bypass partner status checks
        if ($user->isAdmin()) {
            return $next($request);
        }

        if ($user->isPending()) {
            return redirect()->route('partner.pending');
        }

        if ($user->isSuspended()) {
            return redirect()->route('partner.suspended');
        }

        return $next($request);
    }
}
