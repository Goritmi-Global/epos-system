<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Route;

class PermissionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        $role = $user->getRoleNames()->first();
        if ($role === 'Super Admin') {
            return $next($request);
        }

        $routeName = Route::currentRouteName();
        $userPermissions = $user->getAllPermissions()->pluck('name')->toArray();

        if (!in_array($routeName, $userPermissions)) {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
