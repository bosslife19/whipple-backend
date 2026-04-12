<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->can_access_admin) {
            abort(Response::HTTP_FORBIDDEN, 'Admin access only.');
        }

        return $next($request);
    }
}
