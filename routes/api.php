<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\GpsController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\RouteController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // --- Admin-only ---
    Route::middleware('role:admin')->group(function () {
        // Deliveries
        Route::get('/deliveries', [DeliveryController::class, 'index']);
        Route::post('/deliveries', [DeliveryController::class, 'store']);
        Route::get('/deliveries/{delivery}', [DeliveryController::class, 'show']);

        // Routes
        Route::post('/routes/generate', [RouteController::class, 'generate']);
        Route::post('/routes/{route}/assign', [RouteController::class, 'assign']);
        Route::get('/routes', [RouteController::class, 'index']);
        Route::get('/routes/{route}', [RouteController::class, 'show']);

        // Reconciliation
        Route::get('/reconciliation/daily', [ReconciliationController::class, 'daily']);
        Route::get('/reconciliation/driver/{driver}', [ReconciliationController::class, 'driverWallet']);

        // Dashboard
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/audit-logs', [DashboardController::class, 'auditLogs']);
    });

    // --- Driver-only ---
    Route::middleware('role:driver')->group(function () {
        Route::post('/gps/ping', [GpsController::class, 'ping']);
        Route::get('/gps/history', [GpsController::class, 'history']);
        Route::post('/deliveries/{delivery}/confirm-payment', [DeliveryController::class, 'confirmPayment']);
    });

    // --- Shared (both roles) ---
    Route::put('/deliveries/{delivery}/status', [DeliveryController::class, 'updateStatus']);
});