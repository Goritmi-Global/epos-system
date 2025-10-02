<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\{
    AnalyticsController,
    InventoryController,
    MenuCategoryController,
    MenuController,
    OrdersController,
    PosOrderController,
    PurchaseOrderController
};
use App\Http\Controllers\Reference\{
    AllergyController,
    CategoryController,
    SupplierController,
    TagController,
    UnitController
};
use App\Http\Controllers\{
    GeoController,
    IndexController,
    PromoController
};

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
        Route::post('/import', [TagController::class, 'import'])->name('tags.import');
    });

    // Supplier
    Route::prefix('suppliers')->name('api.suppliers.')->group(function () {
        Route::post('/import', [SupplierController::class, 'import'])->name('suppliers.import');
        Route::get('/pluck', [SupplierController::class, 'pluck'])->name('pluck'); // special
    });
    
    // Orders 
    Route::prefix('orders')->name('api.orders.')->group(function () {
        Route::get('/all', [OrdersController::class, 'fetchAllOrders'])->name('fetchAll');
    });

    // Promos
    Route::prefix('promos')->name('api.promos.')->group(function () {
        Route::get('/all', [PromoController::class, 'fetchAllPromos'])->name('fetchAll');
        Route::patch('/{id}/toggle-status', [PromoController::class, 'toggleStatus'])->name('toggle');
    });

    // POS Orders
    Route::prefix('pos')->name('api.pos.')->group(function () {
        Route::get('/fetch-menu-categories', [PosOrderController::class, 'fetchMenuCategories'])->name('menu.categories');
        Route::get('/fetch-menu-items', [PosOrderController::class, 'fetchMenuItems'])->name('menu.items');
        Route::get('/fetch-profile-tables', [PosOrderController::class, 'fetchProfileTables'])->name('profile.tables');
        Route::get('/orders/today', [PosOrderController::class, 'getTodaysOrders'])->name('orders.today');
        Route::put('/kot/{kot}/status', [PosOrderController::class, 'updateKotStatus']);
    });

    // Menu Categories
    Route::prefix('menu-categories')->name('api.menu-categories.')->group(function () {
        Route::get('/parents/list', [MenuCategoryController::class, 'getParents'])->name('parents');      
        Route::get('/statistics/summary', [MenuCategoryController::class, 'statistics'])->name('stats');  
        Route::patch('/{id}/toggle-status', [MenuCategoryController::class, 'toggleStatus'])->name('toggle'); 
        Route::post('/import', [MenuCategoryController::class, 'import'])->name('import'); 

    });

    // Categories
    Route::prefix('categories')->name('api.categories.')->group(function () {
        Route::put('/subcategories/{id}', [CategoryController::class, 'updateSubcategory'])->name('updateSubcategory');

        Route::get('/parents/list', [CategoryController::class, 'getParents'])->name('parents');
        Route::get('/statistics/summary', [CategoryController::class, 'statistics'])->name('stats');
        Route::patch('/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('toggle');
        Route::post('/import', [CategoryController::class, 'import'])->name('import');
    });

    // Menu Items
    Route::prefix('menu')->name('api.menu.')->group(function () {
        Route::get('/items', [MenuController::class, 'apiIndex'])->name('items');
        Route::post('/menu_items/import', [MenuController::class, 'import'])->name('menu_items.import');
    });

    // PURCHASE ORDER
    Route::prefix('purchase-orders')->name('api.purchase.orders.')->group(function () {
        Route::get('/fetch-orders', [PurchaseOrderController::class, 'fetchOrders'])->name('fetchOrders');
    });

    Route::prefix('inventory')->name('api.inventory.')->group(function () {
        Route::post('/import', [InventoryController::class, 'import'])->name('items.import');
    });
});
