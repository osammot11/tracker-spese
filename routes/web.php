<?php

use App\Http\Controllers\Api\V1\AiApiController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PinController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Middleware\EnsurePinVerified;
use App\Http\Middleware\EnsureValidApiKey;
use Illuminate\Support\Facades\Route;

// Public OpenAPI Schema for ChatGPT Custom GPT Actions
Route::get('/api/v1/openapi.json', [OpenApiController::class, 'schema'])->name('openapi.schema');

// ChatGPT & External AI REST API v1 (Protected with Bearer / API Key)
Route::prefix('api/v1')->middleware([EnsureValidApiKey::class])->group(function () {
    Route::get('/overview', [AiApiController::class, 'overview'])->name('api.v1.overview');
    Route::get('/transactions', [AiApiController::class, 'listTransactions'])->name('api.v1.transactions.list');
    Route::post('/transactions', [AiApiController::class, 'createTransaction'])->name('api.v1.transactions.create');
    Route::delete('/transactions/{transaction}', [AiApiController::class, 'deleteTransaction'])->name('api.v1.transactions.delete');

    Route::get('/categories', [AiApiController::class, 'listCategories'])->name('api.v1.categories.list');
    Route::post('/categories', [AiApiController::class, 'createCategory'])->name('api.v1.categories.create');
    Route::post('/categories/{category}/subcategories', [AiApiController::class, 'createSubcategory'])->name('api.v1.categories.subcategories.create');
});

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
