<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Shift;

class CheckShiftActive
{
    public function handle($request, Closure $next)
    {
        $activeShift = Shift::where('status', 'open')->first();

        if (!$activeShift) {
            Auth::logout();
            session()->flash('error', 'Shift is closed. You have been logged out.');
            return redirect()->route('login');
        }

        return $next($request);
    }
}
