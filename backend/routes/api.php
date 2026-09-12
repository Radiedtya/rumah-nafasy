<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Rumah Natasy
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ============================================
    // AUTH ROUTES
    // ============================================
    Route::prefix('auth')->group(function () {
        // Public (no auth)
        Route::post('/register', [RegisterController::class, 'register']);
        Route::post('/login', [LoginController::class, 'login']);

        // Protected (auth required)
        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [LoginController::class, 'logout']);
            Route::get('/me', [ProfileController::class, 'me']);
            Route::put('/profile', [ProfileController::class, 'update']);
        });
    });

    // ============================================
    // PUBLIC ROUTES (no auth needed)
    // ============================================
    Route::prefix('public')->group(function () {
        // Daftar psikolog
        // Route::get('/psikolog', [PublicController::class, 'psikolog']);
        // Route::get('/psikolog/{slug}', [PublicController::class, 'psikologDetail']);

        // Kategori & pricing
        // Route::get('/categories', [PublicController::class, 'categories']);
        // Route::get('/durations', [PublicController::class, 'durations']);
    });

    // ============================================
    // PASIEN ROUTES (auth + role:pasien)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:pasien'])->prefix('pasien')->group(function () {
        // Orders & booking
        // Route::apiResource('orders', OrderController::class);
        // Route::post('/orders/{order}/schedule', [BookingController::class, 'store']);
    });

    // ============================================
    // PSIKOLOG ROUTES (auth + role:psikolog)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:psikolog'])->prefix('psikolog')->group(function () {
        // Route::get('/dashboard', [DashboardController::class, 'index']);
        // Route::apiResource('schedules', ScheduleController::class);
    });

    // ============================================
    // ADMIN ROUTES (auth + role:admin)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // Route::get('/dashboard', [DashboardController::class, 'index']);
    });
});