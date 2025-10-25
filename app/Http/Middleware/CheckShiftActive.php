<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;

class CheckShiftActive
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        
        // Skip check if user is not authenticated
        if (!$user) {
            return $next($request);
        }

        // Check if there's an active shift
        $activeShift = Shift::where('status', 'open')->first();

        if (!$activeShift) {
            // Handle Super Admin - redirect to shift management with modal
            if ($user->hasRole('Super Admin')) {
                session()->flash('show_shift_modal', true);
                
                return redirect()->route('shift.manage')
                    ->with('info', 'Please start a shift to access POS.');
            }
            
            // Handle regular users - logout
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            
            return redirect()->route('login')
                ->with('error', 'Shift is closed. You have been logged out.');
        }

        // Optional: Update session with current shift ID
        if (!session('current_shift_id') || session('current_shift_id') != $activeShift->id) {
            session(['current_shift_id' => $activeShift->id]);
        }

        return $next($request);
    }
}