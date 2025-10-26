<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\PPIC\AuthController;
use App\Http\Controllers\Auth\Produksi\AuthController as ProduksiAuthController;
use App\Http\Controllers\ProductionPlanController;
use App\Http\Controllers\QualityControlController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductionReportController;

// PUBLIC ROUTES - PPIC
Route::post('/ppic/login', [AuthController::class, 'loginPPIC']);

// PROTECTED ROUTES - PPIC
Route::middleware(['auth:sanctum', 'module:ppic'])->group(function () {
    Route::get('/ppic/me', [AuthController::class, 'me']);
    Route::post('/ppic/logout', [AuthController::class, 'logout']);
    Route::post('/ppic/logout-all', [AuthController::class, 'logoutAll']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/ppic/dashboard-stats', [ProductionPlanController::class, 'getDashboardStats']);
    Route::post('/ppic/production-report/export', [ProductionPlanController::class, 'exportReport']);

    Route::prefix('production-plans')->group(function () {
        Route::get('/', [ProductionPlanController::class, 'index']);
        Route::post('/', [ProductionPlanController::class, 'store']);
        Route::get('/{id}', [ProductionPlanController::class, 'show']);
        Route::put('/{id}', [ProductionPlanController::class, 'update']);
        Route::delete('/{id}', [ProductionPlanController::class, 'destroy']);
        Route::post('/{id}/submit', [ProductionPlanController::class, 'submitForApproval']);
        Route::post('/{id}/approval', [ProductionPlanController::class, 'processApproval']);
        Route::post('/{id}/start', [ProductionPlanController::class, 'startProduction']);
        Route::put('/{id}/progress', [ProductionPlanController::class, 'updateProgress']);
    });
});

// PUBLIC ROUTES - PRODUKSI
Route::post('/produksi/login', [ProduksiAuthController::class, 'loginProduksi']);

// PROTECTED ROUTES - PRODUKSI
Route::middleware(['auth:sanctum', 'module:produksi'])->prefix('produksi')->group(function () {
    Route::get('/me', [ProduksiAuthController::class, 'me']);
    Route::post('/logout', [ProduksiAuthController::class, 'logout']);
    Route::post('/logout-all', [ProduksiAuthController::class, 'logoutAll']);
    
    Route::get('/dashboard-stats', [ProductionPlanController::class, 'getStaffDashboardStats']);
    
    Route::get('/production-plans', [ProductionPlanController::class, 'getApprovedPlans']);
    Route::get('/production-plans/{id}', [ProductionPlanController::class, 'show']);
    Route::post('/production-plans/{id}/start', [ProductionPlanController::class, 'startProduction']);
    Route::put('/production-plans/{id}/progress', [ProductionPlanController::class, 'updateProgress']);
    Route::post('/production-plans/{id}/complete', [ProductionPlanController::class, 'completeProduction']);

    Route::get('/quality-control', [QualityControlController::class, 'index']);
    Route::get('/quality-control/completed-productions', [QualityControlController::class, 'getCompletedProductions']);
    Route::get('/quality-control/statistics', [QualityControlController::class, 'getStatistics']);
    Route::get('/quality-control/product-report', [QualityControlController::class, 'getProductReport']);
    Route::get('/quality-control/{id}', [QualityControlController::class, 'show']);
    Route::post('/quality-control', [QualityControlController::class, 'store']);
    Route::post('/quality-control/{id}/start', [QualityControlController::class, 'startInspection']);
    Route::put('/quality-control/{id}', [QualityControlController::class, 'updateInspection']);
    Route::post('/quality-control/{id}/complete', [QualityControlController::class, 'completeInspection']);
    Route::delete('/quality-control/{id}', [QualityControlController::class, 'destroy']);

    Route::get('/logistics', [LogisticsController::class, 'index']);
    Route::get('/logistics/warehouses', [LogisticsController::class, 'warehouses']);
    Route::get('/logistics/low-stock-alert', [LogisticsController::class, 'lowStockAlert']);
    Route::get('/logistics/{logistics}', [LogisticsController::class, 'show']);
    Route::post('/logistics', [LogisticsController::class, 'store']);
    Route::put('/logistics/{logistics}', [LogisticsController::class, 'update']);
    Route::delete('/logistics/{logistics}', [LogisticsController::class, 'destroy']);
    
    Route::post('/logistics/{logistics}/adjust-stock', [LogisticsController::class, 'adjustStock']);
    Route::post('/logistics/sync-from-qc', [LogisticsController::class, 'syncFromQualityControl']);
    Route::post('/logistics/bulk-sync-from-qc', [LogisticsController::class, 'bulkSyncFromQualityControl']);
    
    Route::post('/production-report/export', [ProductionReportController::class, 'export']);

});