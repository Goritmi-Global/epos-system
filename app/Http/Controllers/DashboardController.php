<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\ProfileStep1;
use App\Models\ProfileStep2;
use App\Models\ProfileStep3;
use App\Models\ProfileStep4;
use App\Models\ProfileStep5;
use App\Models\ProfileStep6;
use App\Models\ProfileStep7;
use App\Models\ProfileStep8;
use App\Models\ProfileStep9;

class DashboardController extends Controller
{
    public function index()
{
    $user = auth()->user();

    // Only check steps if user is on dashboard
    $stepsCompleted = ProfileStep1::where('user_id', $user->id)->exists()
        && ProfileStep2::where('user_id', $user->id)->exists()
        && ProfileStep3::where('user_id', $user->id)->exists()
        && ProfileStep4::where('user_id', $user->id)->exists()
        && ProfileStep5::where('user_id', $user->id)->exists()
        && ProfileStep6::where('user_id', $user->id)->exists()
        && ProfileStep7::where('user_id', $user->id)->exists()
        && ProfileStep8::where('user_id', $user->id)->exists()
        && ProfileStep9::where('user_id', $user->id)->exists();

    // Only redirect if not already on onboarding
    if (! $stepsCompleted && request()->routeIs('dashboard')) {
        session()->forget('url.intended');
        return redirect()->route('onboarding.index');
    }

    return Inertia::render('Backend/Dashboard/Index');
}

}
