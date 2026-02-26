<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Role-check middleware for protected routes.
 * Adds a simple, reusable role gate before controller execution.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->role, $roles, true)) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'Forbidden: insufficient role privileges',
            ], 403);
        }

        return $next($request);
    }
}
