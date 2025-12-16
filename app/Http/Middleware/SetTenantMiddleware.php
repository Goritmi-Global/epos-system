<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SetTenantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            if (!$request->is('register', 'login', 'verify-otp', 'forgot-password')) {
                return redirect()->route('register');
            }
            return $next($request);
        }

        // Switch to tenant database
        $tenant->configure();
        DB::setDefaultConnection('tenant');

        app()->instance('tenant', $tenant);
        session(['tenant_id' => $tenant->id]);

        $response = $next($request);

        // Switch back
        DB::setDefaultConnection('mysql');

        return $response;
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        if (Auth::check() && Auth::user()->tenant_id) {
            return Tenant::find(Auth::user()->tenant_id);
        }

        if (session()->has('tenant_id')) {
            return Tenant::find(session('tenant_id'));
        }

        return null;
    }
}