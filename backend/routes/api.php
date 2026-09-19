<?php

use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\ProfileController;
use App\Http\Controllers\Api\Public;
use App\Http\Controllers\Api\Psikolog;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\Pasien\OrderController;
use App\Http\Controllers\Api\Pasien\PaymentController;
use App\Http\Controllers\Api\Pasien\BookingController;
use App\Http\Controllers\Api\Webhook\MidtransController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ============================================
    // AUTH ROUTES
    // ============================================
    Route::prefix('auth')->group(function () {
        // Rate limit ketat di endpoint publik — penahan brute-force
        Route::post('/register', [RegisterController::class, 'register'])
            ->middleware('throttle:5,1');
        Route::post('/login', [LoginController::class, 'login'])
            ->middleware('throttle:5,1');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('/logout', [LoginController::class, 'logout']);
            Route::get('/me', [ProfileController::class, 'me']);
            Route::put('/profile', [ProfileController::class, 'update']);
            Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
            Route::delete('/profile/avatar', [ProfileController::class, 'deleteAvatar']);
        });
    });

    // ============================================
    // PUBLIC ROUTES (no auth)
    // ============================================
    Route::prefix('public')->middleware('throttle:60,1')->group(function () {
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
    // Alur bisnis baru: booking langsung TANPA pembayaran.
    // Pembayaran P2P dilakukan ke psikolog setelah sesi selesai —
    // tidak diproses aplikasi.
    // ============================================
    Route::middleware(['auth:sanctum', 'role:pasien'])->prefix('pasien')->group(function () {
        // Booking langsung (pilih psikolog → paket → jadwal → selesai)
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings', [BookingController::class, 'storeDirect'])
            ->middleware('throttle:10,1');
        Route::put('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

        // Available slots (cek sebelum booking)
        Route::get('/psikolog/{psikologId}/slots', [BookingController::class, 'availableSlots']);

        /*
        | ── DORMAN: Alur order + pembayaran ─────────────────────────
        | Dimatikan sesuai keputusan klien (pembayaran P2P di luar app).
        | Kode controller/model/service TIDAK dihapus — bisa diaktifkan
        | kembali dengan membuka komentar blok ini.
        |
        | Route::get('/orders', [OrderController::class, 'index']);
        | Route::post('/orders', [OrderController::class, 'store']);
        | Route::get('/orders/{order}', [OrderController::class, 'show']);
        | Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);
        | Route::post('/orders/{order}/payment', [PaymentController::class, 'create']);
        | Route::get('/payments/{payment}', [PaymentController::class, 'show']);
        | Route::post('/orders/{order}/mock-success', [PaymentController::class, 'mockSuccess']);
        | Route::post('/orders/{order}/schedule', [BookingController::class, 'store']);
        */
    });

    /*
    | ── DORMAN: Webhook Midtrans ─────────────────────────────────
    | Dipakai hanya oleh alur pembayaran yang sedang dorman.
    |
    | Route::prefix('webhooks')->group(function () {
    |     Route::post('/midtrans', [MidtransController::class, 'handle'])
    |         ->middleware('throttle:20,1');
    | });
    */

    // ============================================
    // PSIKOLOG ROUTES (auth + role:psikolog)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:psikolog'])->prefix('psikolog')->group(function () {
        // Dashboard
        Route::get('/dashboard', [Psikolog\DashboardController::class, 'index']);

        // Schedules
        Route::apiResource('schedules', Psikolog\ScheduleController::class);

        // Bookings
        Route::get('/bookings', [Psikolog\BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [Psikolog\BookingController::class, 'show']);
        Route::put('/bookings/{booking}/status', [Psikolog\BookingController::class, 'updateStatus']);

        // Consultations
        Route::get('/consultations', [Psikolog\ConsultationController::class, 'index']);
        Route::get('/consultations/{consultation}', [Psikolog\ConsultationController::class, 'show']);
        Route::post('/bookings/{booking}/consultation/start', [Psikolog\ConsultationController::class, 'start']);
        Route::post('/consultations/{consultation}/end', [Psikolog\ConsultationController::class, 'end']);

        // Consultation Notes
        Route::get('/consultations/{consultation}/notes', [Psikolog\ConsultationNoteController::class, 'index']);
        Route::post('/consultations/{consultation}/notes', [Psikolog\ConsultationNoteController::class, 'store']);

        // Profile
        Route::get('/profile', [Psikolog\ProfileController::class, 'show']);
        Route::put('/profile', [Psikolog\ProfileController::class, 'update']);

        // Income
        Route::get('/income', [Psikolog\IncomeController::class, 'index']);
        Route::get('/income/report', [Psikolog\IncomeController::class, 'report']);
    });

    // ============================================
    // ADMIN ROUTES (auth + role:admin)
    // ============================================
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [Admin\DashboardController::class, 'index']);

        // Manage Psikolog — CRUD lengkap
        Route::get('/psikolog', [Admin\PsikologController::class, 'index']);
        Route::post('/psikolog', [Admin\PsikologController::class, 'store']);
        Route::get('/psikolog/{user}', [Admin\PsikologController::class, 'show']);
        Route::put('/psikolog/{user}', [Admin\PsikologController::class, 'update']);
        Route::delete('/psikolog/{user}', [Admin\PsikologController::class, 'destroy']);
        Route::put('/psikolog/{user}/verify', [Admin\PsikologController::class, 'verify']);
        Route::put('/psikolog/{user}/suspend', [Admin\PsikologController::class, 'suspend']);
        Route::put('/psikolog/{user}/activate', [Admin\PsikologController::class, 'activate']);

        // Semua Jadwal (lintas psikolog)
        Route::get('/bookings', [Admin\BookingController::class, 'index']);
        Route::get('/bookings/{booking}', [Admin\BookingController::class, 'show']);
        Route::put('/bookings/{booking}/status', [Admin\BookingController::class, 'updateStatus']);

        // Semua Konsultasi (lintas psikolog)
        Route::get('/consultations', [Admin\ConsultationController::class, 'index']);
        Route::get('/consultations/{consultation}', [Admin\ConsultationController::class, 'show']);

        // Categories (CRUD)
        Route::apiResource('categories', Admin\CategoryController::class);

        // Durations (CRUD)
        Route::apiResource('durations', Admin\DurationController::class);

        // Specializations (CRUD)
        Route::apiResource('specializations', Admin\SpecializationController::class);

        // Transactions
        Route::get('/transactions', [Admin\TransactionController::class, 'index']);
        Route::get('/transactions/{order}', [Admin\TransactionController::class, 'show']);

        // Refunds
        Route::get('/refunds', [Admin\RefundController::class, 'index']);
        Route::put('/refunds/{refund}/approve', [Admin\RefundController::class, 'approve']);
        Route::put('/refunds/{refund}/reject', [Admin\RefundController::class, 'reject']);

        // Reports
        Route::get('/reports/transactions', [Admin\ReportController::class, 'transactions']);
        Route::get('/reports/psikolog', [Admin\ReportController::class, 'psikolog']);
        Route::get('/reports/bookings', [Admin\ReportController::class, 'bookings']);
    });

    // ============================================
    // MEETING ROUTES (auth only, both pasien & psikolog)
    // ============================================
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/bookings/{booking}/meeting', [MeetingController::class, 'show']);
    });
});