<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Public;
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
        Route::post('/register', [RegisterController::class, 'register']);
        Route::post('/login', [LoginController::class, 'login']);

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
        // Psikolog
        Route::get('/psikolog', [Public\PsikologController::class, 'index']);
        Route::get('/psikolog/{slug}', [Public\PsikologController::class, 'show']);

        // Reviews psikolog
        Route::get('/psikolog/{slug}/reviews', [Public\ReviewController::class, 'index']);

        // Specializations
        Route::get('/specializations', [Public\SpecializationController::class, 'index']);
        Route::get('/specializations/{slug}', [Public\SpecializationController::class, 'show']);

        // Categories & durations
        Route::get('/categories', [Public\CategoryController::class, 'index']);
        Route::get('/durations', [Public\DurationController::class, 'index']);
    });

    // ============================================
    // PASIEN ROUTES (auth + role:pasien)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:pasien'])->prefix('pasien')->group(function () {
        // TODO: Step 5
    });

    // ============================================
    // PSIKOLOG ROUTES (auth + role:psikolog)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:psikolog'])->prefix('psikolog')->group(function () {
        // TODO: Step 6
    });

    // ============================================
    // ADMIN ROUTES (auth + role:admin)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // TODO: Step 7
    });
});