<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Shift;
use App\Models\ShiftDetail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();
    //     session()->flash('show_inventory_popup', true);

    //     $request->session()->regenerate();

    //     return redirect()->intended(route('dashboard', absolute: false));
    // }

    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();
    //     $request->session()->regenerate();

    //     $user = auth()->user();
    //     $role = $user->getRoleNames()->first();
    //     $activeShift = Shift::where('status', 'open')->first();

    //     if ($role === 'Super Admin') {
    //         if ($activeShift) {
    //             session(['current_shift_id' => $activeShift->id]);
    //             return redirect()->route('dashboard'); // already active shift → go to dashboard
    //         } else {
    //             // Show start shift modal
    //             session()->flash('show_shift_modal', true);
    //             return redirect()->route('shift.manage'); // no shift → show modal
    //         }
    //     } else {
    //         if ($activeShift) {
    //             // Join active shift
    //             ShiftDetail::firstOrCreate(
    //                 ['shift_id' => $activeShift->id, 'user_id' => $user->id],
    //                 ['role' => $role, 'joined_at' => now()]
    //             );
    //             session(['current_shift_id' => $activeShift->id]);
    //             // redirect based on role
    //             if ($role === 'Cashier') {
    //                 return redirect()->route('pos.order');
    //             }
    //             return redirect()->route('dashboard');
    //         } else {
    //             // No active shift → show modal
    //             session()->flash('show_no_shift_modal', true);
    //             return redirect()->route('shift.manage');
    //         }
    //     }
    // }

    // public function store(LoginRequest $request): RedirectResponse
    // {
    //     $request->authenticate();
    //     $request->session()->regenerate();

    //     $user = auth()->user();
    //     $role = $user->getRoleNames()->first();
    //     $activeShift = Shift::where('status', 'open')->first();

    //     if ($role === 'Super Admin') {
    //         if ($activeShift) {
    //             session(['current_shift_id' => $activeShift->id]);
    //             return redirect()->route('dashboard');
    //         } else {
    //             session()->flash('show_shift_modal', true);
    //             return redirect()->route('shift.manage');
    //         }
    //     } else {
    //         if ($activeShift) {
    //             // ShiftDetail will be created by middleware
    //             session(['current_shift_id' => $activeShift->id]);

    //             if ($role === 'Cashier') {
    //                 return redirect()->route('pos.order');
    //             }
    //             return redirect()->route('dashboard');
    //         } else {
    //             session()->flash('show_no_shift_modal', true);
    //             return redirect()->route('shift.manage');
    //         }
    //     }
    // }


    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $activeShift = Shift::where('status', 'open')->first();

        // ✅ Set login time for cashiers (for auto-logout)
        if ($role === 'Cashier') {
            $lastLoginKey = 'last_login_time_' . $user->id;
            $loginTime = now()->timestamp;
            Cache::put($lastLoginKey, $loginTime, now()->addHours(24));

            \Log::info('✅ Cashier login time set', [
                'user_id' => $user->id,
                'timestamp' => $loginTime,
                'time' => now()->format('Y-m-d H:i:s')
            ]);
        }

        if ($role === 'Super Admin') {
            if ($activeShift) {
                session(['show_inventory_popup' => true]);
                session(['current_shift_id' => $activeShift->id]);
                return redirect()->route('dashboard');
            } else {
                session()->flash('show_shift_modal', true);
                return redirect()->route('shift.manage');
            }
        } else {
            if ($activeShift) {
                // ShiftDetail will be created by middleware
                session(['current_shift_id' => $activeShift->id]);

                if ($role === 'Cashier') {
                    return redirect()->route('pos.order');
                }
                return redirect()->route('dashboard');
            } else {
                session()->flash('show_no_shift_modal', true);
                return redirect()->route('shift.manage');
            }
        }
    }


    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('login');
    }
}
