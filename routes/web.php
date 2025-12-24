<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AddonGroupController;
use App\Http\Controllers\Auth\CustomPasswordResetController;
use App\Http\Controllers\Auth\PermissionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RoleController;
use App\Http\Controllers\Auth\UsersController;
use App\Http\Controllers\AutoLogout\CashierAutoLogoutController;
use App\Http\Controllers\CustomerDisplayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PendingOrderController;
use App\Http\Controllers\POS\AnalyticsController;
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
use App\Http\Controllers\PromoScopeController;
use App\Http\Controllers\Reference\AllergyController;
use App\Http\Controllers\Reference\CategoryController;
use App\Http\Controllers\Reference\ReferenceManagementController;
use App\Http\Controllers\Reference\SupplierController;
use App\Http\Controllers\Reference\TagController;
use App\Http\Controllers\Reference\UnitController;
use App\Http\Controllers\Shifts\ShiftManagementController;
use App\Http\Controllers\System\DatabaseBackupController;
use App\Http\Controllers\system\SystemRestoreController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\VariantGroupController;
use App\Http\Controllers\VerifyAccountController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/* =========================================================
|  Public / Guest
|========================================================= */

Route::get('/test-helper', fn () => class_exists(\App\Helpers\UploadHelper::class) ? 'OK' : 'Missing');
Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
Route::get('/', fn () => Inertia::render('Auth/Login'))->name('login');
// Route::get('/', [ProfileController::class, 'frontPage'])->name('front-page');
Route::get('/verify-account/{id}', [VerifyAccountController::class, 'verify'])->name('verify.account');
Route::post('/verify-otp', [RegisteredUserController::class, 'verifyOtp'])->name('verify.otp');
Route::post('/forgot-password', [CustomPasswordResetController::class, 'requestReset'])
    ->name('password.custom-request');


Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie refreshed']);
});


/* =========================================================
|  Shift Management Routes (NO shift check - must be accessible)
|========================================================= */

Route::middleware(['auth'])->group(function () {
    Route::prefix('shift')->name('shift.')->group(function () {
        Route::get('/', [ShiftManagementController::class, 'index'])->name('index');
        Route::get('/manage', [ShiftManagementController::class, 'showShiftModal'])->name('manage');
        Route::post('/start', [ShiftManagementController::class, 'startShift'])->name('start');
        Route::get('/checklist-items', [ShiftManagementController::class, 'getChecklistItems'])->name('shift.checklist-items');
        Route::post('/checklist-items/custom', [ShiftManagementController::class, 'storeCustomChecklistItem'])
            ->name('shift.checklist.custom');
        Route::post('/check-active-shift', [ShiftManagementController::class, 'checkActiveShift'])->name('check');
        Route::post('/{shift}/close', [ShiftManagementController::class, 'closeShift'])->name('close');
        Route::get('/all', [ShiftManagementController::class, 'getAllShifts'])->name('getAllShifts');
    });
    Route::get('/api/shift/{id}/details', [ShiftManagementController::class, 'details'])->name('shift.details');

    // ✅ Add onboarding routes HERE (no shift check)
    Route::prefix('onboarding')->name('onboarding.')->group(function () {
        Route::get('/', [OnboardingController::class, 'index'])->name('index');
        Route::get('/data', [OnboardingController::class, 'show'])->name('show');
        Route::post('/step/{step}', [OnboardingController::class, 'saveStep'])->name('saveStep');
        Route::post('/complete', [OnboardingController::class, 'complete'])->name('complete');
    });
});

/* =========================================================
|  All Other Authenticated Routes (WITH shift check)
|========================================================= */

// ✅ Apply check.shift.global to EVERYTHING except shift management
Route::middleware(['auth', 'verified', 'check.shift.global', 'permissions'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    /* -------- Profile -------- */
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    /* -------- Inventory -------- */
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/api-inventories', [InventoryController::class, 'apiList'])->name('api');
        Route::get('/kpi-stats', [InventoryController::class, 'kpiStats']);
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
        Route::get('/stock-logs', [StockEntryController::class, 'stockLogs'])->name('stock.logs');
        // ✅ Add these two new routes for pagination
        Route::get('/api-stock-in-logs', [StockEntryController::class, 'apiStockInLogs'])->name('api.stock.in.logs');
        Route::get('/api-stock-out-logs', [StockEntryController::class, 'apiStockOutLogs'])->name('api.stock.out.logs');
        Route::put('/stock-logs/{id}', [StockEntryController::class, 'updateLog'])->name('stock.update');
        Route::delete('/stock-logs/{id}', [StockEntryController::class, 'deleteLog'])->name('stock.delete');
        Route::get('/total/{product}', [StockEntryController::class, 'totalStock'])->name('total');
        Route::post('/bulk-total', [StockEntryController::class, 'bulkTotalStock'])->name('bulkTotal');
        Route::get('/stock-logs/{id}/allocations', [StockEntryController::class, 'allocations'])->name('stock.logs.allocations');
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

    /* -------- Stock Logs -------- */
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

    //     Route::get('/purchase-orders/{id}/invoice',
    //     [PurchaseOrderController::class, 'generateInvoice']
    // )->name('purchase-orders.invoice');

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
        Route::patch('/{menu}/status', [MenuController::class, 'toggleStatus'])->name('toggleStatus');
    });

    Route::prefix('deals')->name('deals.')->group(function () {
        Route::get('/', [DealsController::class, 'index'])->name('index');
        Route::post('/', [DealsController::class, 'store'])->name('store');
        Route::put('/{deal}', [DealsController::class, 'update'])->name('update');
        Route::patch('/{deal}/status', [DealsController::class, 'updateStatus'])->name('updateStatus');
        Route::delete('/{deal}', [DealsController::class, 'destroy'])->name('destroy');
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
        Route::get('/place-stripe-order', [PosOrderController::class, 'placeStripeOrder'])->name('place-stripe-order');
        Route::post('/check-ingredients', [PosOrderController::class, 'checkIngredients']);
        Route::post('/order-without-payment', [PosOrderController::class, 'storeWithoutPayment']);
        Route::post('/orders/{orderId}/complete-payment', [PosOrderController::class, 'completePayment']);
        Route::get('/walk-in/next', [PosOrderController::class, 'getNextWalkInNumber']);
        Route::get('/walk-in/current', [PosOrderController::class, 'getCurrentWalkInNumber']);
    });

    /* -------- Pending Orders -------- */
    Route::prefix('pending-orders')->name('pending-orders.')->group(function () {
        Route::post('/', [PendingOrderController::class, 'store']);
        Route::get('/', [PendingOrderController::class, 'index']);
        Route::get('/{id}', [PendingOrderController::class, 'show']);
        Route::delete('/{id}', [PendingOrderController::class, 'destroy']);
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

    /* -------- KOTs -------- */
    Route::prefix('kots')->name('kots.')->group(function () {
        Route::get('/', [KotController::class, 'index'])->name('index');
    });

    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [PermissionController::class, 'index'])->name('index');
        Route::post('/', [PermissionController::class, 'store'])->name('store');
        Route::put('/{permission}', [PermissionController::class, 'update'])->name('update');
    });

    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleController::class, 'index'])->name('index');
        Route::get('/{role}', [RoleController::class, 'show'])->name('show');
        Route::post('/', [RoleController::class, 'store'])->name('store');
        Route::put('/{role}', [RoleController::class, 'update'])->name('update');
    });

    Route::get('/permissions-list', [RoleController::class, 'allPermissions'])->name('permissions.list');

    Route::prefix('users')->group(function () {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::put('/{user}', [UsersController::class, 'update'])->name('update');
        Route::delete('/{user}', [UsersController::class, 'destroy'])->name('destroy');
    });
    // routes/api.php or routes/web.php
    Route::prefix('promo-scopes')->group(function () {
        Route::get('/', [PromoScopeController::class, 'index']);
        Route::post('/', [PromoScopeController::class, 'store']);
        Route::put('/{promoScope}', [PromoScopeController::class, 'update']);
        Route::delete('/{promoScope}', [PromoScopeController::class, 'destroy']);
    });
});

/* -------- Super Admin Only -------- */
Route::middleware(['auth', 'role:Super Admin'])->group(function () {
    Route::post('/system/restore', [SystemRestoreController::class, 'restore'])->name('system.restore');

    Route::post('/settings/verify-password', [SettingsController::class, 'verifyPassword'])
        ->name('settings.verify-password');

    Route::post('/settings/restore-system', [SettingsController::class, 'restoreSystem'])
    ->name('settings.restore');
    Route::post('/database/backup', [DatabaseBackupController::class, 'backup'])->name('database.backup');
});

/* ---------- Public Routes ---------- */
Route::get('/settings/locations', [IndexController::class, 'index'])->name('locations.index');

// Addon Groups page
Route::get('/addon-groups', [AddonGroupController::class, 'index'])
    ->name('addon-groups.index');

// Addons page
Route::get('/addons', [AddonController::class, 'index'])
    ->name('addons.index');

// routes for Meals
Route::middleware(['auth'])->group(function () {
    Route::get('/meals', [MealController::class, 'index'])->name('meals.index');
    Route::post('/meals', [MealController::class, 'store'])->name('meals.store');
    Route::post('/meals/{meal}', [MealController::class, 'update'])->name('meals.update');
    Route::delete('/meals/{meal}', [MealController::class, 'destroy'])->name('meals.destroy');
});

Route::get('/variant-groups', [VariantGroupController::class, 'index'])
    ->name('variant-groups.index');
Route::get('/variants', [VariantController::class, 'index'])
    ->name('variants.index');

Route::middleware(['web'])->group(function () {
    Route::get('/check-auto-logout', [CashierAutoLogoutController::class, 'check'])
        ->name('check.auto.logout');
});

Route::prefix('customer-display')->name('customer-display.')->group(function () {
    Route::get('/{terminal?}', [CustomerDisplayController::class, 'index'])
        ->name('index');
});

Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * Discount Management Routes
     * These are the main CRUD routes for the Discount module
     */
    Route::resource('discounts', DiscountController::class);

    /**
     * API Discount Routes
     * These are used by the Vue frontend via AJAX/Axios
     */
});

Route::get('/api/shift/{shift}/x-report', [ShiftManagementController::class, 'generateXReport'])
    ->name('shift.x-report');

Route::get('/api/shift/{shift}/x-report/pdf', [ShiftManagementController::class, 'downloadXReportPdf'])
    ->name('shift.x-report.pdf');

// Z Report Routes (for closed shifts)
Route::get('/api/shift/{shift}/z-report', [ShiftManagementController::class, 'generateZReport'])
    ->name('shift.z-report');

Route::get('/api/shift/{shift}/z-report/pdf', [ShiftManagementController::class, 'downloadZReportPdf'])
    ->name('shift.z-report.pdf');

require __DIR__.'/auth.php';
