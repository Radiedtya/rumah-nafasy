<?php

use App\Http\Controllers\Api\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — OAuth Google (alur redirect penuh, bukan SPA-fetched)
|--------------------------------------------------------------------------
| Controller OAuth HIDUP DI SINI (middleware `web`) karena Socialite
| butuh HTTP session untuk menyimpan parameter `state` anti-CSRF.
| SPA memanggil API `POST auth/google/exchange` (CSRF-exempt) hanya untuk
| menukar `code` satu kali menjadi Sanctum token.
*/

// Langkah 1: SPA menautkan browser ke sini → redirect 302 ke Google
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
    ->name('auth.google.redirect');

// Langkah 2: Google menautkan browser ke sini setelah user konsen
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('auth.google.callback');
