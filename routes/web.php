<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SavedBoxController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('areas/{area}', [PageController::class, 'area'])->name('areas.show');
Route::get('explore', [PageController::class, 'explore'])->name('explore');
Route::get('method', [PageController::class, 'method'])->name('method');
Route::get('areas', [PageController::class, 'areas'])->name('areas.index');
Route::get('alerts', [PageController::class, 'alerts'])->name('alerts');
Route::get('data', [PageController::class, 'data'])->name('data');
Route::get('how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('api-docs', [PageController::class, 'apiDocs'])->name('api-docs');

Route::middleware('auth')->group(function () {
    Route::post('boxes', [SavedBoxController::class, 'store'])->name('boxes.store');
    Route::delete('boxes/{box}', [SavedBoxController::class, 'destroy'])->name('boxes.destroy');
});

Route::get('locale/{locale}', [PageController::class, 'locale'])->name('locale');

require __DIR__.'/auth.php';
