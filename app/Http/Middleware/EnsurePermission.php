<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->is_active || ! $user->can($permission)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No autorizado para esta acción.',
                ], 403);
            }

            abort(403);
        }

        return $next($request);
    }
}
