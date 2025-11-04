<?php

namespace App\Http\Controllers\AutoLogout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\ProfileStep7;
use App\Models\User;

class CashierAutoLogoutController extends Controller
{
    public function check(Request $request)
    {
        // ✅ ALWAYS return JSON, never allow redirects
        try {
            // Handle unauthenticated users
            if (!Auth::check()) {
                return response()->json(['should_logout' => true], 200);
            }

            $user = Auth::user();

            if (!$user->hasRole('Cashier')) {
                return response()->json(['should_logout' => false]);
            }

            $superAdmin = User::where('is_first_super_admin', true)->first();

            if (!$superAdmin) {
                return response()->json(['should_logout' => false]);
            }

            $settings = ProfileStep7::where('user_id', $superAdmin->id)->first();

            if (!$settings || !$settings->logout_after_time || !$settings->logout_time_minutes) {
                return response()->json(['should_logout' => false]);
            }

            $lastLoginKey = 'last_login_time_' . $user->id;
            $lastLoginTime = Cache::get($lastLoginKey);

            if (!$lastLoginTime) {
                return response()->json(['should_logout' => false]);
            }

            $currentTimestamp = now()->timestamp;
            $secondsSinceLogin = $currentTimestamp - $lastLoginTime;
            $minutesSinceLogin = floor($secondsSinceLogin / 60);
            $logoutInterval = $settings->logout_time_minutes;

            if ($minutesSinceLogin >= $logoutInterval) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Cache::forget($lastLoginKey);
                Cache::forget('auto_logout_user_' . $user->id);

                return response()->json(['should_logout' => true]);
            }

            return response()->json(['should_logout' => false]);
        } catch (\Exception $e) {
            // ✅ Even on exceptions, return JSON
            \Log::error('Auto-logout check error: ' . $e->getMessage());
            return response()->json(['should_logout' => false, 'error' => 'Check failed'], 200);
        }
    }
}
