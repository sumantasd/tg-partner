<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsTeamMember
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

        if (! $user->isTeamMember() && ! $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. Team Member access required.');
        }

        if ($user->isSuspended()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. Your staff account has been deactivated.');
        }

        return $next($request);
    }
}
