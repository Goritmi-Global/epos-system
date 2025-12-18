<?php

use App\Http\Controllers\AddonController;
use App\Http\Controllers\AddonGroupController;
use App\Http\Controllers\Auth\ApiKeyController;
use App\Http\Controllers\DealsController;
use App\Http\Controllers\DiscountApprovalController;
use App\Http\Controllers\DiscountController;
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
use App\Http\Controllers\POS\PaymentController;
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
// In routes/api.php
Route::post('/pos/orders/{order}/cancel', [PosOrderController::class, 'cancel']);
Route::post('/pos/orders/{order}/refund', [PosOrderController::class, 'refund']);
Route::get('/test-api', function () {
    return response()->json(['status' => 'API file is loading']);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->name('api.analytics.index');
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

    Route::prefix('payments')->name('api.payments.')->group(function () {
        Route::get('/all', [PaymentController::class, 'getAllPayments'])->name('fetchAll');
    });

    // Promos
    Route::prefix('promos')->name('api.promos.')->group(function () {
        Route::get('/all', [PromoController::class, 'fetchAllPromos'])->name('fetchAll');
        Route::get('/today', [PromoController::class, 'getTodayPromos'])->name('today');
        Route::get('/current', [PromoController::class, 'getAllPromos']);
        Route::post('/import', [PromoController::class, 'import'])->name('import');
        Route::get('/for-item/{item}', [PromoController::class, 'getPromosForItem']);
        Route::patch('/{id}/toggle-status', [PromoController::class, 'toggleStatus'])->name('toggle');
    });

    // POS Orders
    Route::prefix('pos')->name('api.pos.')->group(function () {
        Route::get('/fetch-menu-categories', [PosOrderController::class, 'fetchMenuCategories'])->name('menu.categories');
        Route::get('/fetch-menu-items', [PosOrderController::class, 'fetchMenuItems'])->name('menu.items');
        Route::get('/fetch-profile-tables', [PosOrderController::class, 'fetchProfileTables'])->name('profile.tables');
        Route::get('/orders/today', [PosOrderController::class, 'getTodaysOrders'])->name('orders.today');
        Route::put('/kot-item/{item}/status', [PosOrderController::class, 'updateKotItemStatus'])->name('kot.item.status');

        // ✅ Terminal State Management Routes
        // Version check endpoint (lightweight - should be first for priority)
        Route::get('/terminal/{terminalId}/version', [PosOrderController::class, 'getTerminalVersion'])->name('terminal.version');

        // Full state endpoint
        Route::get('/terminal/{terminalId}/state', [PosOrderController::class, 'getTerminalState'])->name('terminal.state');

        // Update endpoints
        Route::post('/terminal/update-cart', [PosOrderController::class, 'updateTerminalCart'])->name('terminal.cart');
        Route::post('/terminal/update-ui', [PosOrderController::class, 'updateTerminalUI'])->name('terminal.ui');
        Route::post('/terminal/update-both', [PosOrderController::class, 'updateTerminalBoth'])->name('terminal.both');

        // ✅ Optional: Add cache clear endpoint (for debugging)
        Route::delete('/terminal/{terminalId}/cache', [PosOrderController::class, 'clearTerminalCache'])->name('terminal.cache.clear');
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
    Route::prefix('deals')->name('api.deals.')->group(function () {
        Route::get('/', [DealsController::class, 'apiIndex']);
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
        Route::put('/pos/kot-item/{itemId}/status', [KotController::class, 'updateItemStatus']);
        Route::get('/statistics', [KotController::class, 'getOrderStatistics']);
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
        Route::get('/{shift}/x-report', [ShiftManagementController::class, 'generateXReport'])->name('shift.x-report');
        Route::get('/{shift}/x-report/pdf', [ShiftManagementController::class, 'downloadXReportPdf'])->name('shift.x-report.pdf');
        Route::get('/{shift}/z-report', [ShiftManagementController::class, 'generateZReport'])->name('shift.z-report');
        Route::get('/{shift}/z-report/pdf', [ShiftManagementController::class, 'downloadZReportPdf'])->name('shift.z-report.pdf');
    });

    Route::prefix('meals')->name('api.meals.')->group(function () {
        Route::get('/all', [MealController::class, 'fetchAllMeals'])->name('all');
        Route::get('/active', [MealController::class, 'getActiveMeals']);
        Route::post('/import', [MealController::class, 'import']);
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
    Route::post('/profile/verify-credentials', [ProfileController::class, 'verifyCredentials']);
    Route::post('/profile/update', [ProfileController::class, 'updateProfile'])->name('api.profile.update');
    Route::post('/printers/{shift}/z-report/print', [PrinterController::class, 'printZReport'])->name('shift.z-report.print');

    Route::prefix('addon-groups')->group(function () {

        Route::post('/import', [AddonGroupController::class, 'import']);
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

    Route::prefix('discounts')->name('api.discounts.')->group(function () {
        Route::get('/all', [DiscountController::class, 'fetchAllDiscounts'])->name('discounts.all');
        Route::get('/today', [DiscountController::class, 'getTodayDiscounts'])->name('discounts.today');
        Route::patch('/{id}/toggle-status', [DiscountController::class, 'toggleStatus'])->name('discounts.toggle-status');
        Route::get('/active', [DiscountController::class, 'getActiveDiscounts'])->name('discounts.active');
        Route::post('/import', [DiscountController::class, 'import'])->name('import');
    });

    /*
    |--------------------------------------------------------------------------
    | Addon Routes
    |--------------------------------------------------------------------------
    | These routes handle individual addon items
    */

    Route::prefix('addons')->group(function () {

        Route::post('/import', [AddonController::class, 'import']);
        Route::get('/unique-groups', [AddonController::class, 'getUniqueGroups']);
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

    Route::post('/discount-approvals/request', [DiscountApprovalController::class, 'requestApproval']);
    Route::post('/discount-approvals/check-status', [DiscountApprovalController::class, 'checkApprovalStatus']);
    Route::get('/discount-approvals/pending', [DiscountApprovalController::class, 'getPendingRequests']);
    Route::post('/discount-approvals/{id}/respond', [DiscountApprovalController::class, 'respondToRequest']);
    Route::get('/discount-approvals/history', [DiscountApprovalController::class, 'getApprovalHistory']);
});
Route::post('/verify-super-admin', [ApiKeyController::class, 'verifyCredentials']);
Route::post('/store-api-key', [ApiKeyController::class, 'storeApiKey']);
Route::post('/get-api-key', [ApiKeyController::class, 'getApiKey']);
