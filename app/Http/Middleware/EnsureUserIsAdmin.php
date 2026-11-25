<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
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

        if (! $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. Administrator access required.');
        }

        if ($user->isSuspended()) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized. Your administrator account has been deactivated.');
        }

        return $next($request);
    }
}
