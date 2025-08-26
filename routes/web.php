<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerifyAccountController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia; 
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;

use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\GeoController; 

use App\Http\Controllers\POS\{
    InventoryController,
    MenuController,
    PosOrderController,
    OrdersController,
    PaymentController,
    AnalyticsController,
    SettingsController
};


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
 

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    // Menu
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/create', [MenuController::class, 'create'])->name('create');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
    });

    // POS Order live screen
    Route::get('/pos/order', [PosOrderController::class, 'screen'])->name('pos.order');

    // Orders
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrdersController::class, 'index'])->name('index');
        Route::get('/{order}', [OrdersController::class, 'show'])->name('show');
    });

    // Payment
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
    });

    // Analytics
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'index'])->name('index');
    });

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/', [SettingsController::class, 'update'])->name('update');
    });
 

});

// onboarding routes
Route::middleware(['auth','verified'])->group(function () {
    Route::get('/onboarding', [OnboardingController::class, 'index'])->name('onboarding.index');
    Route::get('/onboarding/data', [OnboardingController::class, 'show'])->name('onboarding.show');
    Route::post('/onboarding/step/{step}', [OnboardingController::class, 'saveStep'])->name('onboarding.saveStep');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});




// routes/web.php
Route::get('/settings/locations', [\App\Http\Controllers\IndexController::class, 'index'])
     ->name('locations.index');


require __DIR__.'/auth.php';
