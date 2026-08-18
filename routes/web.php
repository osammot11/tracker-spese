<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\EnsurePinVerified;
use Illuminate\Support\Facades\Route;

// PIN Authentication Routes (Public)
Route::get('/pin', [PinController::class, 'show'])->name('pin.show');
Route::post('/pin', [PinController::class, 'verify'])->name('pin.verify');
Route::get('/logout', [PinController::class, 'logout'])->name('pin.logout');
Route::post('/logout', [PinController::class, 'logout'])->name('pin.logout.post');

// Protected Routes (Require PIN verification)
Route::middleware([EnsurePinVerified::class])->group(function () {
    // Web Views
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
    Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
    Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

    Route::post('/categories/{category}/subcategories', [CategoryController::class, 'storeSubcategory'])->name('categories.subcategories.store');
    Route::delete('/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory'])->name('subcategories.destroy');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export');

    // API routes for Vue reactive components
    Route::prefix('api')->group(function () {
        Route::get('/transactions', [TransactionController::class, 'apiList']);
        Route::post('/transactions', [TransactionController::class, 'store']);
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy']);

        Route::get('/categories', [CategoryController::class, 'apiCategories']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);
        Route::post('/categories/{category}/subcategories', [CategoryController::class, 'storeSubcategory']);
        Route::delete('/subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory']);
    });
});
