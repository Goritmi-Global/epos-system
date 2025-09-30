<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\POS\AnalyticsController;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\POS\OrdersController;

Route::get('/countries', [IndexController::class, 'countries']);
Route::get('/country/{code}', [IndexController::class, 'countryDetails']);
Route::get('/geo', [GeoController::class, 'info']);

Route::get('/test-api', function () {
    return response()->json(['status' => 'API file is loading']);
});
Route::get('/analytics', [AnalyticsController::class, 'index'])
    ->name('api.analytics.index');


Route::prefix('orders')->name('api.orders.')->group(function () {
    Route::get('/all', [OrdersController::class, 'fetchAllOrders'])->name('all');
});
