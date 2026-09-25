<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    /**
     * ── Alur Login & Penghubungan Google (backend-driven) ───────────────
     *
     * LOGIN (publik — mekanisme "no dead end"):
     *   Google → cari user by google_id → else by email (auto-link) →
     *   else BUAT akun baru (role pasien). Satu tombol, hasil pasti masuk.
     *
     * CONNECT (hanya user terautentikasi):
     *   POST auth/google/connect/start (auth:sanctum) menerbitkan token
     *   one-time → popup membuka /auth/google/redirect?intent=connect&token=
     *   → token ditukar jadi identitas di session → Google → callback
     *   → google_id ditautkan KE user itu (dengan pemeriksaan konflik).
     *
     * Keamanan:
     * - State anti-CSRF Socialite (session) aktif di semua jalur.
     * - Token connect: one-time, TTL 10 menit, hanya lewat endpoint auth.
     * - Connect MENOLAK bila email/Google ID sudah dipakai user lain —
     *   tidak ada pengambilalihan akun secara diam-diam.
     * - Kode exchange: sekali pakai, TTL 10 menit, disimpan Cache server.
     */
    private const CODE_TTL_MINUTES = 10;

    private const CONNECT_TTL_MINUTES = 10;

    public function redirect(Request $request): RedirectResponse
    {
        $request->validate([
            // URL SPA tempat browser mendarat setelah callback — wajib
            // origin frontend yang dipercaya (lihat safeSpaRedirect).
            'redirect' => ['nullable', 'string'],
            'intent' => ['nullable', 'in:login,connect'],
            'token' => ['nullable', 'string', 'size:64'],
        ]);

        $landing = $this->safeSpaRedirect($request->query('redirect'));
        $landingPath = $this->landingPathOf($landing);
        $sep = str_contains($landingPath, '?') ? '&' : '?';

        session(['google_spa_redirect' => $landing]);
        session()->forget('google_connect_user_id');

        if ($request->query('intent') === 'connect') {
            // Connect wajib membawa token one-time yang diterbitkan
            // endpoint terautentikasi (auth:sanctum) — tidak bisa dipanggil
            // sembarangan untuk menautkan akun paksa.
            $userId = Cache::pull('google_connect_'.$request->query('token'));

            if (! $userId) {
                return redirect()->away($this->originOf($landing).$landingPath.$sep.'google=token');
            }

            session(['google_connect_user_id' => (int) $userId]);
        }

        // Mode STATEFUL (default): Socialite memvalidasi parameter `state`
        // anti-CSRF terhadap session — proteksi tetap aktif.
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        $landing = session('google_spa_redirect');
        $connectUserId = session('google_connect_user_id');
        session()->forget('google_spa_redirect');
        session()->forget('google_connect_user_id');

        $spaOrigin = $this->originOf($landing);
        $landingPath = $this->landingPathOf($landing);
        $sep = str_contains($landingPath, '?') ? '&' : '?';

        // Semua kegagalan kembali ke URL landing SPA (popup ikut tertutup rapi)
        $fail = fn (string $kind): RedirectResponse => redirect()->away(
            $spaOrigin.$landingPath.$sep.'google='.$kind
        );

        try {
            // Validasi state anti-CSRF + tukar kode dengan Google terjadi di sini
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return $fail('error');
        }

        if (! $googleUser->getEmail()) {
            return $fail('error');
        }

        if ($connectUserId) {
            // ── Jalur CONNECT: tautkan Google ke user yang meminta ──
            $user = User::find($connectUserId);

            if (! $user || ! $user->is_active) {
                return $fail('error');
            }

            // Google ID ini sudah dipakai akun lain → tolak
            $linkedElsewhere = User::where('google_id', $googleUser->getId())
                ->where('id', '!=', $user->id)
                ->exists();

            if ($linkedElsewhere) {
                return $fail('linked');
            }

            // Email Google dipakai akun LAIN → tolak (jangan takeover diam-diam)
            $emailTaken = User::where('email', $googleUser->getEmail())
                ->where('id', '!=', $user->id)
                ->exists();

            if ($emailTaken) {
                return $fail('conflict');
            }

            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        } else {
            // ── Jalur LOGIN: no dead end — link atau buat ──
            $user = $this->findOrCreateUser($googleUser);

            if (! $user->is_active) {
                return $fail('blocked');
            }
        }

        // Kode sekali pakai — CACHE server-side, kedaluwarsa 10 menit,
        // dipull (auto-hapus) saat ditukar di endpoint exchange.
        $code = Str::random(64);
        Cache::put("google_exchange_{$code}", $user->id, now()->addMinutes(self::CODE_TTL_MINUTES));

        return redirect()->away($spaOrigin.$landingPath.$sep.'code='.$code);
    }

    /**
     * API (auth:sanctum): terbitkan URL popup penghubungan akun Google.
     * URL mengandung token one-time yang terikat ke user yang login.
     */
    public function connectStart(Request $request): JsonResponse
    {
        $request->validate([
            'landing' => ['nullable', 'string'],
        ]);

        $landing = $this->safeSpaRedirect($request->input('landing'));

        $token = Str::random(64);
        Cache::put("google_connect_{$token}", $request->user()->id, now()->addMinutes(self::CONNECT_TTL_MINUTES));

        $url = rtrim(config('app.url'), '/').'/auth/google/redirect?'.http_build_query([
            'intent' => 'connect',
            'token' => $token,
            'redirect' => $landing,
        ]);

        return $this->successResponse([
            'url' => $url,
            'expires_in' => self::CONNECT_TTL_MINUTES * 60,
        ], 'URL penghubungan Google dibuat');
    }

    /**
     * API (auth:sanctum): putuskan akun Google.
     * WAJIB konfirmasi password bila user punya password — mencegah
     * penyerang sesi merusak jalur login user. User tanpa password
     * dilarang memutus (akan terkunci) — harus atur password dulu.
     */
    public function disconnect(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->google_id) {
            return $this->errorResponse('Google belum terhubung ke akun ini', 422);
        }

        if (! $user->password) {
            return $this->errorResponse('Atur password terlebih dahulu sebelum memutus Google', 422);
        }

        if (! Hash::check((string) $request->input('password', ''), $user->password)) {
            return $this->errorResponse('Password salah — konfirmasi diperlukan untuk memutus Google', 422);
        }

        $user->forceFill(['google_id' => null])->save();

        return $this->successResponse(
            new UserResource($user->fresh()->load('roles', 'psikologProfile')),
            'Akun Google berhasil diputus'
        );
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

        if (! $userId) {
            return $this->errorResponse('Kode login tidak valid, kedaluwarsa, atau sudah digunakan', 422);
        }

        $user = User::with('roles')->find($userId);
        if (! $user || ! $user->is_active) {
            return $this->errorResponse('Akun tidak tersedia', 403);
        }

        $token = $user->createToken('google-login')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Login Google berhasil');
    }

    /**
     * Resolusi identitas LOGIN — urutan yang benar:
     * 1. google_id  → identitas Google itu sendiri (aman bila email Google berubah)
     * 2. email      → auto-link (akun email/password eksisting dipakai langsung)
     * 3. buat baru  → role pasien, email terverifikasi
     */
    private function findOrCreateUser(\Laravel\Socialite\Contracts\User $googleUser): User
    {
        // 1. Sudah pernah link via google_id?
        $user = User::where('google_id', $googleUser->getId())->first();
        if ($user) {
            $this->ensureDefaultRole($user);

            return $user;
        }

        // 2. Email dikenal → auto-link (mekanisme "register Google → masuk akun lama")
        $user = User::where('email', $googleUser->getEmail())->first();
        if ($user) {
            if (! $user->google_id) {
                $user->forceFill([
                    'google_id' => $googleUser->getId(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ])->save();
            }

            $this->ensureDefaultRole($user);

            return $user;
        }

        // 3. Belum ada → buat akun baru
        $user = User::create([
            'name' => $googleUser->getName() ?: 'Pengguna Google',
            'email' => $googleUser->getEmail(),
            'google_id' => $googleUser->getId(),
            'password' => Hash::make(Str::random(32)), // tak dipakai login, mencegah NULL
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        $user->assignRoleSafe('pasien');

        return $user;
    }

    /**
     * Self-healing: user yang terlanjur dibuat TANPA role (efek bug lama
     * saat tabel roles kosong) dipulihkan otomatis saat login berikutnya.
     * User yang SUDAH punya role (admin/psikolog/pasien) tidak tersentuh.
     */
    private function ensureDefaultRole(User $user): void
    {
        if ($user->roles()->exists()) {
            return;
        }

        $user->assignRoleSafe('pasien');
    }

    /**
     * Validasi URL landing SPA — HARUS origin yang terdaftar persis
     * (bandingkan scheme://host:port hasil parse, BUKAN prefix string,
     * supaya `http://localhost:3000.evil.com` tidak lolos).
     */
    private function safeSpaRedirect(?string $target): string
    {
        $allowed = array_filter(array_map('trim', explode(',', (string) config('services.google.allowed_spa_origins'))));
        $default = rtrim(config('services.google.default_spa_origin') ?: config('app.url'), '/');

        if (! $target) {
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
            if (! empty($parts['host'])) {
                $origin = strtolower(($parts['scheme'] ?? 'http').'://'.$parts['host']);
                if (isset($parts['port'])) {
                    $origin .= ':'.$parts['port'];
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
        if (! $url) {
            return '/auth/google/callback';
        }

        $parts = parse_url($url);
        $path = $parts['path'] ?? '';

        if ($path === '' || $path === '/') {
            return '/auth/google/callback';
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
