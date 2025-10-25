<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;

class CheckShiftActiveGlobal
{
    public function handle($request, Closure $next)
{
    $user = Auth::user();
    
    if (!$user) {
        return $next($request);
    }

    // Skip check for certain routes
    $allowedRoutes = [
        'shift.index',
        'shift.manage',
        'shift.start',
        'shift.close',
        'shift.getAllShifts',
        'shift.details',
        'shift.check',
        'logout',
        'onboarding.index',
        'onboarding.show',
        'onboarding.saveStep',
        'onboarding.complete',
        'profile.edit',
        'profile.update',
    ];

    if (in_array($request->route()->getName(), $allowedRoutes)) {
        return $next($request);
    }

    // ✅ Check if first super admin needs to complete onboarding
    if ($user->is_first_super_admin) {
        $onboardingCompleted = ProfileStep1::where('user_id', $user->id)->exists()
            && ProfileStep2::where('user_id', $user->id)->exists()
            && ProfileStep3::where('user_id', $user->id)->exists()
            && ProfileStep4::where('user_id', $user->id)->exists()
            && ProfileStep5::where('user_id', $user->id)->exists()
            && ProfileStep6::where('user_id', $user->id)->exists()
            && ProfileStep7::where('user_id', $user->id)->exists()
            && ProfileStep8::where('user_id', $user->id)->exists()
            && ProfileStep9::where('user_id', $user->id)->exists();

        if (!$onboardingCompleted) {
            return redirect()->route('onboarding.index');
        }
    }

    // ✅ Check for active shift
    $activeShift = Shift::where('status', 'open')->first();

    if (!$activeShift) {
        if ($user->hasRole('Super Admin')) {
            session()->forget('current_shift_id');
            session()->flash('show_shift_modal', true);
            session()->flash('warning', 'No active shift found. Please start a shift to continue.');
            
            return redirect()->route('shift.manage');
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login')
            ->with('error', 'Shift is closed. You have been logged out.');
    }

    // 🔥 NEW: Always ensure ShiftDetail exists for this user
    if (!$user->hasRole('Super Admin')) {
        $role = $user->getRoleNames()->first();
        
        \App\Models\ShiftDetail::firstOrCreate(
            [
                'shift_id' => $activeShift->id,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'joined_at' => now(),
            ]
        );
    }

    // Update session with current shift ID
    $currentShiftId = session('current_shift_id');
    if (!$currentShiftId || $currentShiftId != $activeShift->id) {
        session(['current_shift_id' => $activeShift->id]);
    }

    return $next($request);
}
}