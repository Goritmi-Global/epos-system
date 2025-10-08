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

        // If Super user than bypass all the permissions check
        if (!empty(auth()->user()->getRoleNames()->toArray()) && count(auth()->user()->getRoleNames()) > 0) {
            if (auth()->user()->getRoleNames()[0] == 'Super Admin') {
                return $next($request);
            }
        }

        // If not Super Admin User
        $routeName = Route::currentRouteName();

        $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();

        // Check if the user has the required permission
        if (!in_array($routeName, $userPermissions)) {
            abort(403, 'Unauthorized');
        }

        // If user has the permission
        return $next($request);
    }
}
