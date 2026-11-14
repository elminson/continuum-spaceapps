<?php

use App\Http\Controllers\Api\AreaApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function () {
    Route::get('areas', [AreaApiController::class, 'index']);
    Route::get('areas/{area}/monthly', [AreaApiController::class, 'monthly']);
    Route::get('areas/{area}/monthly.csv', [AreaApiController::class, 'csv']);
    Route::get('areas/{area}/alerts', [AreaApiController::class, 'alerts']);
    Route::get('areas/{area}/retirement', [AreaApiController::class, 'retirement']);
});
