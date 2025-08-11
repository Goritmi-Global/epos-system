<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerifyAccountController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia; 
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\OnboardingController;



Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify.otp');

Route::get('/', function () {
    return Inertia::render('Auth/Login');
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    //     'laravelVersion' => Application::VERSION,
    //     'phpVersion' => PHP_VERSION,
    // ]);
});
// Auth::routes(['verify' => true]);
Route::get('/verify-account/{id}', [VerifyAccountController::class, 'verify'])->name('verify.account');
Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify.otp');


// Dashboard -> Controller@index
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// onboarding routes
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::get('/onboarding/data', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/step/{step}', [OnboardingController::class, 'saveStep'])->name('onboarding.saveStep');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

require __DIR__.'/auth.php';
