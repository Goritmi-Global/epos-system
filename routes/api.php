<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AddonGroupController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\MealController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\POS\AnalyticsController;
use App\Http\Controllers\POS\InventoryController;
use App\Http\Controllers\POS\KotController;
use App\Http\Controllers\POS\MenuCategoryController;
use App\Http\Controllers\POS\MenuController;
use App\Http\Controllers\POS\OrdersController;
use App\Http\Controllers\POS\PosOrderController;
use App\Http\Controllers\POS\PurchaseOrderController;
use App\Http\Controllers\Printer\PrinterController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromoController;
use App\Http\Controllers\Reference\AllergyController;
use App\Http\Controllers\Reference\CategoryController;
use App\Http\Controllers\Reference\SupplierController;
use App\Http\Controllers\Reference\TagController;
use App\Http\Controllers\Reference\UnitController;
use App\Http\Controllers\Shifts\ShiftManagementController;
use App\Http\Controllers\VariantController;
use App\Http\Controllers\VariantGroupController;
use Illuminate\Support\Facades\Route;

Route::get('/countries', [IndexController::class, 'countries']);
Route::get('/country/{code}', [IndexController::class, 'countryDetails']);
Route::get('/geo', [GeoController::class, 'info']);

Route::get('/test-api', function () {
    return response()->json(['status' => 'API file is loading']);
});
Route::get('/analytics', [AnalyticsController::class, 'index'])
    ->name('api.analytics.index');

Route::middleware(['auth', 'verified'])->group(function () {
    // Allergies
    Route::prefix('allergies')->name('api.allergies.')->group(function () {
        Route::post('/import', [AllergyController::class, 'import'])->name('import');
    });

    // Units
    Route::prefix('units')->name('api.units.')->group(function () {
        Route::post('/import', [UnitController::class, 'import'])->name('import');
    });

    // Tags
    Route::prefix('tags')->name('api.tags.')->group(function () {
        Route::post('/import', [TagController::class, 'import'])->name('import');
    });

    // Supplier
    Route::prefix('suppliers')->name('api.suppliers.')->group(function () {
        Route::post('/import', [SupplierController::class, 'import'])->name('import');
        Route::get('/pluck', [SupplierController::class, 'pluck'])->name('pluck'); // special
    });

    // Orders
    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/all', [OrdersController::class, 'fetchAllOrders'])->name('fetchAll');
    });

    // Promos
    Route::prefix('promos')->name('api.promos.')->group(function () {
        Route::get('/all', [PromoController::class, 'fetchAllPromos'])->name('fetchAll');
        Route::get('/today', [PromoController::class, 'getTodayPromos'])->name('today');
        Route::patch('/{id}/toggle-status', [PromoController::class, 'toggleStatus'])->name('toggle');
    });

    // POS Orders
    Route::prefix('pos')->name('api.pos.')->group(function () {
        Route::get('/fetch-menu-categories', [PosOrderController::class, 'fetchMenuCategories'])->name('menu.categories');
        Route::get('/fetch-menu-items', [PosOrderController::class, 'fetchMenuItems'])->name('menu.items');
        Route::get('/fetch-profile-tables', [PosOrderController::class, 'fetchProfileTables'])->name('profile.tables');
        Route::get('/orders/today', [PosOrderController::class, 'getTodaysOrders'])->name('orders.today');
        Route::put('/kot-item/{item}/status', [PosOrderController::class, 'updateKotItemStatus'])->name('kot.item.status');
    });

    // Menu Categories
    Route::prefix('menu-categories')->name('api.menu-categories.')->group(function () {
        Route::get('/parents/list', [MenuCategoryController::class, 'getParents'])->name('parents');
        Route::get('/statistics/summary', [MenuCategoryController::class, 'statistics'])->name('stats');
        Route::post('/import', [MenuCategoryController::class, 'import'])->name('import');
    });

    // Categories
    Route::prefix('categories')->name('api.categories.')->group(function () {
        Route::put('/subcategories/{id}', [CategoryController::class, 'updateSubcategory'])->name('updateSubcategory');

        Route::get('/parents/list', [CategoryController::class, 'getParents'])->name('parents');
        Route::get('/statistics/summary', [CategoryController::class, 'statistics'])->name('stats');
        Route::post('/import', [CategoryController::class, 'import'])->name('import');
    });

    // Menu Items
    Route::prefix('menu')->name('api.menu.')->group(function () {
        Route::get('/items', [MenuController::class, 'apiIndex'])->name('items');
        Route::post('/menu_items/import', [MenuController::class, 'import'])->name('items.import');
    });

    // PURCHASE ORDER
    Route::prefix('purchase-orders')->name('api.purchase.orders.')->group(function () {
        Route::get('/fetch-orders', [PurchaseOrderController::class, 'fetchOrders'])->name('fetchOrders');
    });

    Route::prefix('inventory')->name('api.inventory.')->group(function () {
        Route::post('/import', [InventoryController::class, 'import'])->name('items.import');
    });

    // KOT Orders
    Route::prefix('kots')->name('api.kots.')->group(function () {
        Route::get('/all-orders', [KotController::class, 'getAllKotOrders'])->name('index');
    });

    // Notifications
    Route::prefix('notifications')->name('api.notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::post('/mark-as-read/{id}', [NotificationController::class, 'markRead'])->name('markRead');
        Route::post('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('markAllRead');
    });

    Route::prefix('shift')->name('api.shift.')->group(function () {
        Route::get('/all', [ShiftManagementController::class, 'getAllShifts'])->name('all');
        Route::patch('/{shift}/close', [ShiftManagementController::class, 'closeShift'])->name('close');

    });

    Route::prefix('meals')->name('api.meals.')->group(function () {
        Route::get('/all', [MealController::class, 'fetchAllMeals'])->name('all');
        Route::get('/active', [MealController::class, 'getActiveMeals']);
    });

    Route::prefix('variants')->name('api.variants.')->group(function () {
        Route::get('/all', [VariantController::class, 'all']);
        Route::get('/by-group/{groupId}', [VariantController::class, 'byGroup']);
        Route::get('/statistics', [VariantController::class, 'statistics']);
        Route::post('/', [VariantController::class, 'store']);
        Route::get('/{id}', [VariantController::class, 'show']);
        Route::post('/{id}', [VariantController::class, 'update']);
        Route::delete('/{id}', [VariantController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [VariantController::class, 'toggleStatus']);
        Route::post('/update-sort-order', [VariantController::class, 'updateSortOrder']);
        Route::post('/validate-selection', [VariantController::class, 'validateSelection']);
        Route::post('/calculate-price-modifier', [VariantController::class, 'calculatePriceModifier']);
    });
    
    Route::prefix('variant-groups')->name('api.variant-groups.')->group(function () {
        Route::get('/all', [VariantGroupController::class, 'all']);
        Route::get('/statistics', [VariantGroupController::class, 'statistics']);
        Route::get('/active', [VariantGroupController::class, 'active']);
        Route::post('/', [VariantGroupController::class, 'store']);
        Route::get('/{id}', [VariantGroupController::class, 'show']);
        Route::post('/{id}', [VariantGroupController::class, 'update']);
        Route::delete('/{id}', [VariantGroupController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [VariantGroupController::class, 'toggleStatus']);
        Route::post('/update-sort-order', [VariantGroupController::class, 'updateSortOrder']);
    });

    

    // Get Printers
    Route::get('/printers', [PrinterController::class, 'index']);
    Route::post('/customer/print-receipt', [PrinterController::class, 'printReceipt']);
    Route::post('/kot/print-receipt', [PrinterController::class, 'printKot']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('api.profile.update');

    Route::prefix('addon-groups')->group(function () {
        // Get all addon groups with their addon counts
        Route::get('/all', [AddonGroupController::class, 'all']);

        // Get statistics for KPI cards
        Route::get('/statistics', [AddonGroupController::class, 'statistics']);

        // Get only active groups (for dropdowns)
        Route::get('/active', [AddonGroupController::class, 'active']);

        // CRUD operations
        Route::get('/{id}', [AddonGroupController::class, 'show']);
        Route::post('/', [AddonGroupController::class, 'store']);
        Route::post('/{id}', [AddonGroupController::class, 'update']);
        Route::delete('/{id}', [AddonGroupController::class, 'destroy']);

        // Toggle status (active/inactive)
        Route::patch('/{id}/toggle-status', [AddonGroupController::class, 'toggleStatus']);
    });

    /*
    |--------------------------------------------------------------------------
    | Addon Routes
    |--------------------------------------------------------------------------
    | These routes handle individual addon items
    */

    Route::prefix('addons')->group(function () {
        // Get all addons with their group information
        Route::get('/all', [AddonController::class, 'all']);

        // Get statistics for KPI cards
        Route::get('/statistics', [AddonController::class, 'statistics']);

        // Get addons by specific group
        Route::get('/group/{groupId}', [AddonController::class, 'byGroup']);

        // CRUD operations
        Route::get('/{id}', [AddonController::class, 'show']);
        Route::post('/', [AddonController::class, 'store']);
        Route::post('/{id}', [AddonController::class, 'update']);
        Route::delete('/{id}', [AddonController::class, 'destroy']);

        // Toggle status (active/inactive)
        Route::patch('/{id}/toggle-status', [AddonController::class, 'toggleStatus']);

        // Update sort order (drag & drop functionality)
        Route::post('/sort-order', [AddonController::class, 'updateSortOrder']);
    });

});
