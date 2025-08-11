<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GeoController;
use App\Http\Controllers\IndexController;


Route::get('/countries', [IndexController::class, 'countries']);
Route::get('/country/{code}', [IndexController::class, 'countryDetails']);
Route::get('/geo', [GeoController::class, 'info']);

Route::get('/test-api', function () {
    return response()->json(['status' => 'API file is loading']);
});
