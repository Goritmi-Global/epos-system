<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\RestaurantProfile;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // If profile missing, send to onboarding
        $hasProfile = RestaurantProfile::where('user_id', $user->id)->exists();
        // dd($user,$hasProfile);
        if (! $hasProfile) {
            // optional: make extra-sure no stale intended redirect lingers
            session()->forget('url.intended');

            return redirect()->route('onboarding.index');
        }

        // otherwise render dashboard
        return Inertia::render('Backend/Dashboard/Index');
    }
}
