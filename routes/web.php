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
    InventoryCategoryController,
    StockLogController,
    PurchaseOrderController,
    StockEntryController,
   
    MenuController,
    MenuCategoryController,
    PosOrderController,
    OrdersController,
    PaymentController,
    AnalyticsController,
    SettingsController,
};
use App\Http\Controllers\Reference\{
    ReferenceManagementController,
    SupplierController,
    CategoryController,
    TagController,
    AllergyController,
    UnitController
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
        Route::get('/api-inventories', [InventoryController::class, 'apiList']);
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{inventory}', [InventoryController::class, 'show'])->name('show');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    // Stock In/Out entries
    Route::prefix('stock_entries')->name('stock_entries.')->group(function () {
    Route::get('/', [StockEntryController::class, 'index'])->name('index'); 
    Route::post('/', [StockEntryController::class, 'store'])->name('store'); 
    
    // Put specific routes BEFORE parameterized routes
    Route::get('/stock-logs', [StockEntryController::class, 'stockLogs'])->name('stock.logs');
    Route::put('/stock-logs/{id}', [StockEntryController::class, 'updateLog']);
    Route::delete('/stock-logs/{id}', [StockEntryController::class, 'deleteLog']);
    Route::get('/total/{product}', [StockEntryController::class, 'totalStock'])->name('total');
    
    // Parameterized routes come last
    Route::get('/{stockEntry}', [StockEntryController::class, 'show'])->name('show'); 
    Route::put('/{stockEntry}', [StockEntryController::class, 'update'])->name('update');
    Route::delete('/{stockEntry}', [StockEntryController::class, 'destroy'])->name('destroy'); 
});


    // Inventory Categories
Route::prefix('inventory-categories')->name('inventory.categories.')->group(function () {
    Route::get('/', [InventoryCategoryController::class, 'index'])->name('index');
    Route::post('/', [InventoryCategoryController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [InventoryCategoryController::class, 'edit'])->name('edit');
    Route::put('/{id}', [InventoryCategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [InventoryCategoryController::class, 'destroy'])->name('destroy');
});

// Stock Logs
Route::prefix('stock-logs')->name('stock.logs.')->group(function () {
    Route::get('/', [StockLogController::class, 'index'])->name('index');
    Route::post('/', [StockLogController::class, 'store'])->name('store');
    Route::get('/{id}', [StockLogController::class, 'show'])->name('show');
});

// Purchase Orders
Route::prefix('purchase-orders')->name('purchase.orders.')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');
    Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
    Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
    Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
    Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
    Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
});

// Reference Management
Route::prefix('reference')->name('reference.')->group(function () {
    Route::get('/', [ReferenceManagementController::class, 'index'])->name('index');
    Route::post('/', [ReferenceManagementController::class, 'store'])->name('store');
    Route::get('/{id}/edit', [ReferenceManagementController::class, 'edit'])->name('edit');
    Route::put('/{id}', [ReferenceManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [ReferenceManagementController::class, 'destroy'])->name('destroy');
});


Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
Route::post('/suppliers/update', [SupplierController::class, 'update'])->name('suppliers.update');
Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

Route::prefix('tags')->group(function () {
    Route::get('/', [TagController::class, 'index']);
    Route::post('/', [TagController::class, 'store']);
    Route::put('/{tag}', [TagController::class, 'update']);
    Route::delete('/{tag}', [TagController::class, 'destroy']);
});

// Category Management Routes
Route::prefix('categories')->group(function () {
    // CRUD Operations
    Route::get('/', [CategoryController::class, 'index']);
    Route::post('/', [CategoryController::class, 'store']);
    Route::get('/{id}', [CategoryController::class, 'show']);
    Route::put('/{id}', [CategoryController::class, 'update']);
    Route::delete('/{id}', [CategoryController::class, 'destroy']);
    
    // Additional Operations
    Route::get('/parents/list', [CategoryController::class, 'getParents']);
    Route::get('/statistics/summary', [CategoryController::class, 'statistics']);
    Route::patch('/{id}/toggle-status', [CategoryController::class, 'toggleStatus']);
});


Route::prefix('units')->group(function () {
    Route::get('/', [UnitController::class, 'index']);
    Route::post('/', [UnitController::class, 'store']);
    Route::put('/{unit}', [UnitController::class, 'update']);
    Route::delete('/{unit}', [UnitController::class, 'destroy']);
});

Route::prefix('allergies')->group(function () {
    Route::get('/', [AllergyController::class, 'index']);
    Route::post('/', [AllergyController::class, 'store']);
    Route::put('/{allergy}', [AllergyController::class, 'update']);
    Route::delete('/{allergy}', [AllergyController::class, 'destroy']);
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

    // Menu Categories
    Route::prefix('menu-categories')->name('menu.categories.')->group(function () {
        Route::get('/', [MenuCategoryController::class, 'index'])->name('index');
        Route::get('/create', [MenuCategoryController::class, 'create'])->name('create');
        Route::post('/', [MenuCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [MenuCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MenuCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [MenuCategoryController::class, 'destroy'])->name('destroy');
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
