<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Public;
use App\Http\Controllers\Api\Pasien\OrderController;
use App\Http\Controllers\Api\Pasien\PaymentController;
use App\Http\Controllers\Api\Webhook\MidtransController;
use Illuminate\Support\Facades\Route;

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
    // PUBLIC ROUTES (no auth)
    // ============================================
    Route::prefix('public')->group(function () {
        Route::get('/psikolog', [Public\PsikologController::class, 'index']);
        Route::get('/psikolog/{slug}', [Public\PsikologController::class, 'show']);
        Route::get('/psikolog/{slug}/reviews', [Public\ReviewController::class, 'index']);
        Route::get('/specializations', [Public\SpecializationController::class, 'index']);
        Route::get('/specializations/{slug}', [Public\SpecializationController::class, 'show']);
        Route::get('/categories', [Public\CategoryController::class, 'index']);
        Route::get('/durations', [Public\DurationController::class, 'index']);
    });

    // ============================================
    // PASIEN ROUTES (auth + role:pasien)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:pasien'])->prefix('pasien')->group(function () {
        // Orders
        Route::get('/orders', [OrderController::class, 'index']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        // Payment
        Route::post('/orders/{order}/payment', [PaymentController::class, 'create']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);

        // Booking (TODO: Step 5b)
        // Route::post('/orders/{order}/schedule', [BookingController::class, 'store']);
        // Route::put('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
        // Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
    });

    // ============================================
    // WEBHOOK ROUTES (no auth, external callback)
    // ============================================
    Route::prefix('webhooks')->group(function () {
        Route::post('/midtrans', [MidtransController::class, 'handle']);
        // Also allow GET for mock testing
        Route::get('/midtrans', [MidtransController::class, 'handle']);
    });

    // ============================================
    // PSIKOLOG ROUTES (TODO: Step 6)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:psikolog'])->prefix('psikolog')->group(function () {
        //
    });

    // ============================================
    // ADMIN ROUTES (TODO: Step 7)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        //
    });
});