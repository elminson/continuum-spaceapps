<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('areas/{area}', [PageController::class, 'area'])->name('areas.show');
Route::get('method', [PageController::class, 'method'])->name('method');
Route::get('locale/{locale}', [PageController::class, 'locale'])->name('locale');
