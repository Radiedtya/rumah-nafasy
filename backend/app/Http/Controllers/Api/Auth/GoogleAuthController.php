<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * ── Alur Login Google (backend-driven redirect, dukung popup) ───────
     *
     * 1. GET  {backend}/auth/google/redirect?redirect={url_landing_spa}
     *        → 302 ke konsen Google (state anti-CSRF disimpan session)
     * 2. GET  {backend}/auth/google/callback
     *        → validasi state, cari/buat user, simpan kode sekali pakai
     *          di Cache → 302 kembali ke URL landing SPA + ?code=…
     * 3. POST {backend}/api/v1/auth/google/exchange  { code }
     *        → kode sekali pakai → Sanctum token
     *
     * Landing SPA bisa berupa popup (/auth/google/callback?popup=1) yang
     * meneruskan kode ke jendela asal via postMessage — penukaran token
     * tetap dilakukan backend; client secret tidak pernah keluar dari sini.
     */

    public function redirect(Request $request): RedirectResponse
    {
        $request->validate([
            // URL SPA tempat browser mendarat setelah callback — wajib
            // origin frontend yang dipercaya (lihat safeSpaRedirect).
            'redirect' => ['nullable', 'string'],
        ]);

        $landing = $this->safeSpaRedirect($request->query('redirect'));

        // Dibawa bolak-balik lewat session agar tidak bocor di URL Google
        session(['google_spa_redirect' => $landing]);

        // Mode STATEFUL (default): Socialite memvalidasi parameter `state`
        // anti-CSRF terhadap session — proteksi tetap aktif.
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $landing = session('google_spa_redirect');
        session()->forget('google_spa_redirect');

        $spaOrigin = $this->originOf($landing);
        $landingPath = $this->landingPathOf($landing);

        // Semua kegagalan kembali ke URL landing SPA (popup ikut tertutup rapi)
        $fail = fn (string $kind): RedirectResponse => redirect()->away(
            $spaOrigin . $landingPath . (str_contains($landingPath, '?') ? '&' : '?') . 'google=' . $kind
        );

        try {
            // Validasi state anti-CSRF + tukar kode dengan Google terjadi di sini
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return $fail('error');
        }

        if (!$googleUser->getEmail()) {
            return $fail('error');
        }

        $user = $this->findOrCreateUser($googleUser);

        // Akun dinonaktifkan admin → tolak
        if (!$user->is_active) {
            return $fail('blocked');
        }

        // Kode sekali pakai — CACHE server-side, kedaluwarsa 10 menit,
        // dipull (auto-hapus) saat ditukar di endpoint exchange.
        $code = Str::random(64);
        Cache::put("google_exchange_{$code}", $user->id, now()->addMinutes(10));

        // Landing path sudah boleh membawa query milik SPA (?popup=1&redirect=…)
        $sep = str_contains($landingPath, '?') ? '&' : '?';

        return redirect()->away($spaOrigin . $landingPath . $sep . 'code=' . $code);
    }

    /**
     * API: menukar kode sekali pakai menjadi Sanctum token.
     * Publik + rate limit ketat (lihat routes/api.php).
     */
    public function exchange(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:64'],
        ]);

        $userId = Cache::pull("google_exchange_{$request->code}"); // sekali pakai

        if (!$userId) {
            return $this->errorResponse('Kode login tidak valid, kedaluwarsa, atau sudah digunakan', 422);
        }

        $user = User::with('roles')->find($userId);
        if (!$user || !$user->is_active) {
            return $this->errorResponse('Akun tidak tersedia', 403);
        }

        $token = $user->createToken('google-login')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login Google berhasil');
    }

    private function findOrCreateUser(\Laravel\Socialite\Contracts\User $googleUser): User
    {
        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            // Akun lama (email/password) di-link dengan Google — email terverifikasi Google
            if (!$user->google_id) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            return $user;
        }

        // User baru — default role pasien
        $user = User::create([
            'name' => $googleUser->getName() ?: 'Pengguna Google',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => Hash::make(Str::random(32)), // tak dipakai login, mencegah NULL
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->assignRole('pasien');

        return $user;
    }

    /**
     * Validasi URL landing SPA — HARUS origin yang terdaftar persis
     * (bandingkan scheme://host:port hasil parse, BUKAN prefix string,
     * supaya `http://localhost:5173.evil.com` tidak lolos).
     */
    private function safeSpaRedirect(?string $target): string
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) config('services.google.allowed_spa_origins'))));
        $default = rtrim(config('services.google.default_spa_origin') ?: config('app.url'), '/');

        if (!$target) {
            return $default;
        }

        $targetOrigin = $this->originOf($target);

        foreach ($allowed as $origin) {
            if (strcasecmp(rtrim($origin, '/'), $targetOrigin) === 0) {
                return $target;
            }
        }

        return $default;
    }

    /** scheme://host[:port] dari sebuah URL — fallback ke default bila rusak. */
    private function originOf(?string $url): string
    {
        if ($url) {
            $parts = parse_url($url);
            if (!empty($parts['host'])) {
                $origin = strtolower(($parts['scheme'] ?? 'http') . '://' . $parts['host']);
                if (isset($parts['port'])) {
                    $origin .= ':' . $parts['port'];
                }

                return $origin;
            }
        }

        $default = rtrim(config('services.google.default_spa_origin') ?: config('app.url'), '/');

        return $this->originOf($default);
    }

    /** Path+query landing; bila URL hanya origin, default ke path callback. */
    private function landingPathOf(?string $url): string
    {
        if (!$url) {
            return '/auth/google/callback';
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if ($path === '' || $path === '/') {
            return '/auth/google/callback';
        }

        return $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }
}
