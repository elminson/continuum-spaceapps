<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\SavedBoxController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\ZoneRequestController;
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
Route::get('judges', [PageController::class, 'judges'])->name('judges');
Route::get('ai', [PageController::class, 'ai'])->name('ai');
Route::get('questions', [PageController::class, 'questions'])->name('questions');
Route::get('alerts/subscribe', [SubscriptionController::class, 'create'])->name('subscriptions.create');
Route::post('alerts/sample', [SubscriptionController::class, 'sample'])->middleware('throttle:10,1')->name('subscriptions.sample');
Route::post('subscriptions', [SubscriptionController::class, 'store'])->middleware('throttle:10,1')->name('subscriptions.store');
Route::get('subscriptions/{token}', [SubscriptionController::class, 'show'])->name('subscriptions.show');
Route::post('subscriptions/{token}/test', [SubscriptionController::class, 'test'])->middleware('throttle:5,1')->name('subscriptions.test');
Route::patch('subscriptions/{token}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
Route::delete('subscriptions/{token}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');
Route::post('zones', [ZoneRequestController::class, 'store'])->middleware('throttle:10,1')->name('zones.store');

Route::middleware('auth')->group(function () {
    Route::post('boxes', [SavedBoxController::class, 'store'])->name('boxes.store');
    Route::delete('boxes/{box}', [SavedBoxController::class, 'destroy'])->name('boxes.destroy');
});

Route::get('locale/{locale}', [PageController::class, 'locale'])->name('locale');

require __DIR__.'/auth.php';
