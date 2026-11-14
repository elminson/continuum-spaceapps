<?php

use App\Http\Controllers\Api\AreaApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function () {
    Route::get('areas', [AreaApiController::class, 'index']);
    Route::get('explore', [AreaApiController::class, 'explore']);
    Route::get('week', [AreaApiController::class, 'boxWeek']);
    Route::get('areas/{area}/monthly', [AreaApiController::class, 'monthly']);
    Route::get('areas/{area}/monthly.csv', [AreaApiController::class, 'csv']);
    Route::get('areas/{area}/season', [AreaApiController::class, 'season']);
    Route::get('areas/{area}/compare', [AreaApiController::class, 'compare']);
    Route::get('areas/{area}/alerts', [AreaApiController::class, 'alerts']);
    Route::get('areas/{area}/week', [AreaApiController::class, 'week']);
    Route::get('areas/{area}/retirement', [AreaApiController::class, 'retirement']);
});
