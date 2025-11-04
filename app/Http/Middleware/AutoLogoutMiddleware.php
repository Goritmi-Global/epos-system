<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\ProfileStep7;
use App\Models\User;

class AutoLogoutMiddleware
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if (!$user) {
            return $next($request);
        }

        // Check if user has Cashier role
        if (!$user->hasRole('Cashier')) {
            return $next($request);
        }

        // Get super admin
        $superAdmin = User::where('is_first_super_admin', true)->first();
        
        if (!$superAdmin) {
            return $next($request);
        }

        // Get settings
        $settings = ProfileStep7::where('user_id', $superAdmin->id)->first();

        if (!$settings) {
            return $next($request);
        }

        // Check if time-based logout is enabled
        if ($settings->logout_after_time && $settings->logout_time_minutes) {
            $lastLoginKey = 'last_login_time_' . $user->id;
            $lastLoginTime = Cache::get($lastLoginKey);

            // First time - set login time
            if (!$lastLoginTime) {
                $loginTime = now()->timestamp;
                Cache::put($lastLoginKey, $loginTime, now()->addHours(24));
                return $next($request);
            }

            // Calculate elapsed time in minutes
            $currentTimestamp = now()->timestamp;
            $secondsSinceLogin = $currentTimestamp - $lastLoginTime;
            $minutesSinceLogin = floor($secondsSinceLogin / 60);
            $logoutInterval = $settings->logout_time_minutes;

            // Check if it's time to logout
            if ($minutesSinceLogin >= $logoutInterval) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Cache::forget($lastLoginKey);
                Cache::forget('auto_logout_user_' . $user->id);

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}