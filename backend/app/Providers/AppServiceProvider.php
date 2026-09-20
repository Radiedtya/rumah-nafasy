<?php

namespace App\Providers;

use Dedoc\Scramble\Scramble;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Scramble::configure()->routes(function (Route $route) {
            return Str::startsWith($route->uri, 'api/');
        });

        $this->configureRateLimiters();
    }

    /**
     * ── Rate limiter khusus endpoint sensitif (anti spam / DDoS) ────────
     *
     * `otp`      : per-IP DAN per-email. Key per-IP menahan flood satu
     *              mesin; key per-email menahan penyerang yang ganti IP
     *              (botnet kecil) untuk menargetkan satu email.
     * `password` : per-user terautentikasi — endpoint ubah password.
     *
     * Ketiganya memakai hitungan mundur (Retry-After) otomatis dari
     * Laravel — response 429 membawa `retry_after`.
     */
    private function configureRateLimiters(): void
    {
        // Verifikasi / kirim ulang OTP email
        RateLimiter::for('otp', function (Request $request) {
            $email = strtolower((string) $request->input('email', 'no-email'));
            $emailKey = Str::transliterate($email);

            return [
                // per-IP: 10 hit/menit, lockout 60 detik saat kelebihan
                Limit::perMinute(10)->by('otp:ip:'.$request->ip())->response(
                    fn () => response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak permintaan. Coba lagi setelah satu menit.',
                    ], 429)
                ),
                // per-email: 5 hit/menit — menahan spray lintas IP
                Limit::perMinute(5)->by('otp:email:'.$emailKey),
            ];
        });

        // Ubah / atur password (endpoint kredensial paling sensitif)
        RateLimiter::for('password', function (Request $request) {
            $id = $request->user()?->id ?? $request->ip();

            return [
                Limit::perMinute(5)->by('password:'.$id)->response(
                    fn () => response()->json([
                        'success' => false,
                        'message' => 'Terlalu banyak percobaan. Coba lagi setelah satu menit.',
                    ], 429)
                ),
            ];
        });
    }
}
