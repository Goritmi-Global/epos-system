<?php

use App\Http\Controllers\Auth\CustomPasswordResetController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\RegisteredUserController;
/* ---------- Auth scaffolding ---------- */
use App\Http\Controllers\Auth\RoleController;
/* ---------- General ---------- */
use App\Http\Controllers\Auth\UsersController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\OnboardingController;
// (not used below, but kept for later)
use App\Http\Controllers\POS\AnalyticsController;
/* ---------- POS & Inventory ---------- */
use App\Http\Controllers\POS\InventoryCategoryController;
use App\Http\Controllers\POS\InventoryController;
use App\Http\Controllers\POS\KotController;
use App\Http\Controllers\POS\MenuCategoryController;
use App\Http\Controllers\POS\MenuController;
use App\Http\Controllers\POS\OrdersController;
use App\Http\Controllers\POS\PaymentController;
use App\Http\Controllers\POS\PosOrderController;
use App\Http\Controllers\POS\PurchaseOrderController;
use App\Http\Controllers\POS\SettingsController;
use App\Http\Controllers\POS\StockEntryController;
use App\Http\Controllers\POS\StockLogController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
/* ---------- References ---------- */
use App\Http\Controllers\Reference\AllergyController;
use App\Http\Controllers\Reference\CategoryController;
use App\Http\Controllers\Reference\ReferenceManagementController;
use App\Http\Controllers\Reference\SupplierController;
use App\Http\Controllers\Reference\TagController;
use App\Http\Controllers\Reference\UnitController;
use App\Http\Controllers\system\SystemRestoreController;
use App\Http\Controllers\VerifyAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* =========================================================
|  Public / Guest
|========================================================= */

// health/test helper
Route::get('/test-helper', fn () => class_exists(\App\Helpers\UploadHelper::class) ? 'OK' : 'Missing');

// root -> login screen (Inertia)
Route::get('/', fn () => Inertia::render('Auth/Login'));

// email verification links & OTP verify
Route::get('/verify-account/{id}', [VerifyAccountController::class, 'verify'])->name('verify.account');
Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/forgot-password', [CustomPasswordResetController::class, 'requestReset'])
    ->name('password.custom-request');


/* =========================================================
|  Authenticated (auth + verified where needed)
|========================================================= */

// Main dashboard
Route::middleware(['auth', 'verified'])->middleware('permissions')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* -------- Profile -------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* -------- Onboarding (must be reachable after login) -------- */
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::get('/data', [OnboardingController::class, 'show'])->name('show');
        Route::post('/step/{step}', [OnboardingController::class, 'saveStep'])->name('saveStep');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });

    /* -------- Inventory -------- */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/api-inventories', [InventoryController::class, 'apiList'])->name('api');
        Route::get('/create', [InventoryController::class, 'create'])->name('create');
        Route::post('/', [InventoryController::class, 'store'])->name('store');
        Route::get('/{inventory}', [InventoryController::class, 'show'])->name('show');
        Route::get('/{inventory}/edit', [InventoryController::class, 'edit'])->name('edit');
        Route::put('/{inventory}', [InventoryController::class, 'update'])->name('update');
        Route::delete('/{inventory}', [InventoryController::class, 'destroy'])->name('destroy');
    });

    /* -------- Stock Entries & Logs -------- */
    Route::prefix('stock_entries')->name('stock_entries.')->group(function () {
        Route::get('/', [StockEntryController::class, 'index'])->name('index');
        Route::post('/', [StockEntryController::class, 'store'])->name('store');
        Route::get('/by-item/{inventory}', [StockEntryController::class, 'byItem'])->name('byItem');

        // Fixed routes before parameterized
        Route::get('/stock-logs', [StockEntryController::class, 'stockLogs'])->name('stock.logs');
        Route::put('/stock-logs/{id}', [StockEntryController::class, 'updateLog'])->name('stock.update');
        Route::delete('/stock-logs/{id}', [StockEntryController::class, 'deleteLog'])->name('stock.delete');
        Route::get('/total/{product}', [StockEntryController::class, 'totalStock'])->name('total');
        Route::get('/stock-logs/{id}/allocations', [StockEntryController::class, 'allocations'])->name('stock.logs.allocations');

        // Parameterized
        Route::get('/{stockEntry}', [StockEntryController::class, 'show'])->name('show');
        Route::put('/{stockEntry}', [StockEntryController::class, 'update'])->name('update');
        Route::delete('/{stockEntry}', [StockEntryController::class, 'destroy'])->name('destroy');
    });

    /* -------- Inventory Categories -------- */
    Route::prefix('inventory-categories')->name('inventory.categories.')->group(function () {
        Route::get('/', [InventoryCategoryController::class, 'index'])->name('index');
        Route::post('/', [InventoryCategoryController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [InventoryCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [InventoryCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [InventoryCategoryController::class, 'destroy'])->name('destroy');
    });

    /* -------- Stock Logs (separate index if needed) -------- */
    Route::prefix('stock-logs')->name('stock.logs.')->group(function () {
        Route::get('/', [StockLogController::class, 'index'])->name('index');
        Route::post('/', [StockLogController::class, 'store'])->name('store');
        Route::get('/{id}', [StockLogController::class, 'show'])->name('show');
    });

    /* -------- Purchase Orders -------- */
    Route::prefix('purchase-orders')->name('purchase.orders.')->group(function () {
        Route::get('/', [PurchaseOrderController::class, 'index'])->name('index');

        Route::get('/create', [PurchaseOrderController::class, 'create'])->name('create');
        Route::post('/', [PurchaseOrderController::class, 'store'])->name('store');
        Route::get('/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('show');
        Route::put('/{purchaseOrder}', [PurchaseOrderController::class, 'update'])->name('update');
        Route::delete('/{purchaseOrder}', [PurchaseOrderController::class, 'destroy'])->name('destroy');
    });

    /* -------- Reference Management -------- */
    Route::prefix('reference')->name('reference.')->group(function () {
        Route::get('/', [ReferenceManagementController::class, 'index'])->name('index');
        Route::post('/', [ReferenceManagementController::class, 'store'])->name('store');
        Route::get('/{id}/edit', [ReferenceManagementController::class, 'edit'])->name('edit');
        Route::put('/{id}', [ReferenceManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [ReferenceManagementController::class, 'destroy'])->name('destroy');
    });

    /* -------- Suppliers -------- */
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [SupplierController::class, 'index'])->name('index');
        Route::post('/', [SupplierController::class, 'store'])->name('store');
        Route::post('/update', [SupplierController::class, 'update'])->name('update');
        Route::delete('/{supplier}', [SupplierController::class, 'destroy'])->name('destroy');

    });

    /* -------- Tags -------- */
    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::post('/', [TagController::class, 'store'])->name('store');
        Route::put('/{tag}', [TagController::class, 'update'])->name('update');
        Route::delete('/{tag}', [TagController::class, 'destroy'])->name('destroy');
    });

    /* -------- Categories -------- */
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/', [CategoryController::class, 'store'])->name('store');
        Route::get('/{id}', [CategoryController::class, 'show'])->name('show');
        Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');

        // Route::patch('/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle');
    });

    /* -------- Units -------- */
    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/', [UnitController::class, 'index'])->name('index');
        Route::post('/', [UnitController::class, 'store'])->name('store');
        Route::put('/{unit}', [UnitController::class, 'update'])->name('update');
        Route::delete('/{unit}', [UnitController::class, 'destroy'])->name('destroy');
    });

    /* -------- Allergies -------- */
    Route::prefix('allergies')->name('allergies.')->group(function () {
        Route::get('/', [AllergyController::class, 'index'])->name('index');
        Route::post('/', [AllergyController::class, 'store'])->name('store');
        Route::put('/{allergy}', [AllergyController::class, 'update'])->name('update');
        Route::delete('/{allergy}', [AllergyController::class, 'destroy'])->name('destroy');
    });

    /* -------- Menu Items -------- */
    Route::prefix('menu')->name('menu.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('/create', [MenuController::class, 'create'])->name('create');
        Route::post('/', [MenuController::class, 'store'])->name('store');
        Route::get('/{menu}/edit', [MenuController::class, 'edit'])->name('edit');
        Route::get('/{menu}', [MenuController::class, 'show'])->name('show');
        Route::put('/{menu}', [MenuController::class, 'update'])->name('update');
        Route::delete('/{menu}', [MenuController::class, 'destroy'])->name('destroy');
    });

    /* -------- Menu Categories -------- */
    Route::prefix('menu-categories')->name('menu-categories.')->group(function () {
        Route::get('/', [MenuCategoryController::class, 'index'])->name('index');
        Route::post('/', [MenuCategoryController::class, 'store'])->name('store');
        Route::get('/{id}', [MenuCategoryController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [MenuCategoryController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MenuCategoryController::class, 'update'])->name('update');
        Route::delete('/{id}', [MenuCategoryController::class, 'destroy'])->name('destroy');
        Route::put('/subcategories/{id}', [MenuCategoryController::class, 'updateSubcategory'])->name('updateSubcategory');
    });

    /* -------- POS Live Screen -------- */
    Route::prefix('pos')->name('pos.')->group(function () {
        Route::get('/order', [PosOrderController::class, 'index'])->name('order');
        Route::post('/order', [PosOrderController::class, 'store'])->name('pos-order.store');

        // Stripe redirect/callback (creates order after successful payment
        Route::get('/place-stripe-order', [PosOrderController::class, 'placeStripeOrder'])
            ->name('place-stripe-order');
    });

    Route::post('/stripe/pi/create', [PosOrderController::class, 'createIntent'])
        ->name('stripe.pi.create');

    /* -------- Orders -------- */
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrdersController::class, 'index'])->name('index');
        Route::get('/{order}', [OrdersController::class, 'show'])->name('show');
    });

    /* -------- Payment -------- */
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
    });

    /* -------- Analytics -------- */
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/', [AnalyticsController::class, 'page'])->name('index');
    });

    Route::prefix('promos')->name('promos.')->group(function () {
        Route::get('/', [PromoController::class, 'index'])->name('index');
        Route::post('/', [PromoController::class, 'store'])->name('store');
        Route::post('/{id}', [PromoController::class, 'update'])->name('update');
        Route::get('/{id}', [PromoController::class, 'show'])->name('show');

    });

    /* -------- Settings -------- */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('index');
        Route::post('/update/{step}', [SettingsController::class, 'updateStep']);
    });

    /* -------- Settings -------- */
    Route::prefix('kots')->name('kots.')->group(function () {
        Route::get('/', [KotController::class, 'index'])->name('index');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/', [PermissionController::class, 'store'])->name('store');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
    });
    // Roles
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    });

    // If you don't already have a "list all permissions" route, expose one:
    Route::get('/permissions-list', [RoleController::class, 'allPermissions'])->name('permissions.list');

    Route::prefix('users')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::put('/{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
    });

});

Route::middleware(['auth', 'role:Super Admin'])->group(function() {
    Route::post('/system/restore', [SystemRestoreController::class, 'restore'])->name('system.restore');
});

/* ---------- Public settings/locations page (if intended public) ---------- */
Route::get('/settings/locations', [IndexController::class, 'index'])->name('locations.index');

/* ---------- Breeze/Jetstream auth routes ---------- */
require __DIR__.'/auth.php';
