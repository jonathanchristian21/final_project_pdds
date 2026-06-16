<?php

use App\Http\Controllers\DebugController;
use App\Http\Controllers\InsightController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InsightController::class, 'index'])->name('home');

Route::prefix('insights')->name('insights.')->group(function () {
    Route::get('/', [InsightController::class, 'index'])->name('index');
    Route::get('/profitability', [InsightController::class, 'profitability'])->name('profitability');
    Route::get('/discount-effectiveness', [InsightController::class, 'discount'])->name('discount');
    Route::get('/sales-trend', [InsightController::class, 'trend'])->name('trend');
    Route::get('/profitability/drilldown', [InsightController::class, 'profitabilityDrilldown'])->name('profitability.drilldown');

    // Data Management
    Route::post('/upload', [InsightController::class, 'upload'])->name('upload');
    Route::post('/clear', [InsightController::class, 'clear'])->name('clear');
});

// Route::get('/debug', [DebugController::class, 'index'])->name('debug.index');
