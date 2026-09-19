<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\PendingRegistration;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * ── Verifikasi OTP ───────────────────────────────────────────────────
     *
     * Dua jalur (dual-path):
     *  1. Pendaftar BARU → owner-nya PendingRegistration. OTP benar =
     *     bukti kepemilikan email → baris pending DIPROMOSIKAN menjadi
     *     user terverifikasi (email baru masuk tabel users di sini) →
     *     token diterbitkan. Inilah perbaikan celah: sebelum OTP benar,
     *     email tidak pernah tersentuh tabel users.
     *  2. Akun LAMA (pra-pending) yang masih unverified → OTP divalidasi
     *     lalu email_verified_at diisi (jalur legacy, akan lenyap sendiri).
     *
     * Rate limit: limiter `otp` (per-IP + per-email) di routes/api.php.
     */
    public function verify(Request $request, EmailOtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'code' => ['required', 'string', 'digits:6'],
            // Pengenal acak dari response register — pengikat sesi OTP.
            'verify_handle' => ['nullable', 'string', 'size:64'],
        ], [
            'code.required' => 'Kode verifikasi wajib diisi',
            'code.digits' => 'Kode verifikasi harus 6 digit angka',
        ]);

        $email = strtolower($request->input('email'));

        // ── Jalur 1: pendaftar baru (pending) ────────────────────────────
        $pending = PendingRegistration::where('email', $email)->first();

        if ($pending) {
            // Kedaluwarsa → respons generik; kandidat dibuang.
            if ($pending->isExpired()) {
                $pending->delete();

                return $this->errorResponse('Kode verifikasi tidak valid atau kedaluwarsa', 422);
            }

            // Handle SALAH → tolak. Handle KOSONG → teruskan: kode OTP yang
            // benar SUDAH menjadi bukti kepemilikan email (hash + batas
            // percobaan + TTL). Handle hanyalah pengenal sesi — mewajibkannya
            // justru menggagalkan pengguna sah yang membuka halaman OTP dari
            // tab sesi lama/berbeda (bug riil yang dilaporkan pengguna).
            $handle = (string) $request->input('verify_handle', '');

            if ($handle !== '' && ! hash_equals($pending->verify_handle, $handle)) {
                return $this->errorResponse('Kode verifikasi tidak valid atau kedaluwarsa', 422);
            }

            $result = $otpService->verify($pending, (string) $request->input('code'));

            if (! ($result['ok'] ?? false)) {
                $payload = ['reason' => $result['reason'] ?? 'invalid'];

                if (isset($result['attempts_left'])) {
                    $payload['attempts_left'] = $result['attempts_left'];
                }

                return $this->errorResponse('Kode verifikasi tidak valid atau kedaluwarsa', 422, $payload);
            }

            // OTP benar = email terbukti milik pendaftar → jadikan user.
            // Guard: selama menunggu OTP, email bisa saja terdaftar lewat
            // jalur lain (mis. login Google auto-link) — pakai akun itu
            // alih-alih gagal duplikat; bukti OTP tetap berlaku.
            $existing = User::where('email', $pending->email)->first();

            if ($existing) {
                $otpService->purgeOwner($pending);
                $pending->delete();
                $existing->forceFill(['email_verified_at' => $existing->email_verified_at ?? now()])->save();

                $token = $existing->createToken('email-verified-token')->plainTextToken;

                return $this->successResponse([
                    'user' => new UserResource($existing->fresh()->load('roles')),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'verified' => true,
                ], 'Email berhasil diverifikasi. Selamat datang di Rumah Natasy!');
            }

            $user = $pending->promoteToUser();

            $token = $user->createToken('email-verified-token')->plainTextToken;

            // OTP milik pending sudah consumed → bersihkan baris yatim.
            $otpService->purgeOwner($pending);

            return $this->successResponse([
                'user' => new UserResource($user->load('roles')),
                'token' => $token,
                'token_type' => 'Bearer',
                'verified' => true,
            ], 'Email berhasil diverifikasi. Selamat datang di Rumah Natasy!');
        }

        // ── Jalur 2: akun legacy (pra-pending) yang masih unverified ─────
        $user = User::where('email', $email)->first();

        // Anti user-enumeration: respons identik untuk email tak dikenal.
        if (! $user || $user->email_verified_at || ! $user->is_active) {
            return $this->errorResponse('Kode verifikasi tidak valid atau kedaluwarsa', 422);
        }

        $result = $otpService->verify($user, (string) $request->input('code'));

        if (! ($result['ok'] ?? false)) {
            $payload = ['reason' => $result['reason'] ?? 'invalid'];

            if (isset($result['attempts_left'])) {
                $payload['attempts_left'] = $result['attempts_left'];
            }

            return $this->errorResponse('Kode verifikasi tidak valid atau kedaluwarsa', 422, $payload);
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        $token = $user->createToken('email-verified-token')->plainTextToken;

        return $this->successResponse([
            'user' => new UserResource($user->fresh()->load('roles')),
            'token' => $token,
            'token_type' => 'Bearer',
            'verified' => true,
        ], 'Email berhasil diverifikasi. Selamat datang di Rumah Natasy!');
    }

    /**
     * ── Kirim ulang OTP ──────────────────────────────────────────────────
     *
     * Rate limit: limiter `otp` + cooldown 60 detik + kuota 3/jam di service.
     * Respons selalu sukses-generik untuk email yang tak dikenal (anti
     * user-enumeration) — kecuali cooldown aktif bagi pemilik sesi.
     */
    public function resend(Request $request, EmailOtpService $otpService): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower($request->input('email'));

        $pending = PendingRegistration::where('email', $email)->first();

        if ($pending && ! $pending->isExpired()) {
            // Handle salah → generik. Handle kosong → tetap proses; cooldown +
            // kuota per-jam di service yang menjaga dari penyalahgunaan.
            $handle = (string) $request->input('verify_handle', '');

            if ($handle !== '' && ! hash_equals($pending->verify_handle, $handle)) {
                return $this->genericResendResponse();
            }

            $result = $otpService->issue($pending, isResend: true);

            if (($result['reason'] ?? '') === 'cooldown') {
                return $this->errorResponse(
                    'Terlalu cepat — tunggu :detik detik sebelum meminta kode baru.',
                    429,
                    ['retry_after' => $result['retry_after']]
                );
            }

            if (($result['reason'] ?? '') === 'quota_exceeded') {
                return $this->errorResponse(
                    'Batas permintaan kode tercapai. Coba lagi dalam satu jam.',
                    429,
                    ['retry_after' => null]
                );
            }

            return $this->successResponse(
                ['resent' => true, 'retry_after' => $otpService->cooldownRemaining($email)],
                'Kode verifikasi baru telah dikirim ke email Anda.'
            );
        }

        $user = User::where('email', $email)->first();

        if (! $user || $user->email_verified_at || ! $user->is_active) {
            // Tetap 200 + pesan sama — penyerang tidak bisa membedakan.
            return $this->genericResendResponse();
        }

        $result = $otpService->issue($user, isResend: true);

        if (($result['reason'] ?? '') === 'cooldown') {
            return $this->errorResponse(
                'Terlalu cepat — tunggu :detik detik sebelum meminta kode baru.',
                429,
                ['retry_after' => $result['retry_after']]
            );
        }

        if (($result['reason'] ?? '') === 'quota_exceeded') {
            return $this->errorResponse(
                'Batas permintaan kode tercapai. Coba lagi dalam satu jam.',
                429,
                ['retry_after' => null]
            );
        }

        return $this->successResponse(
            ['resent' => true, 'retry_after' => $otpService->cooldownRemaining($email)],
            'Kode verifikasi baru telah dikirim ke email Anda.'
        );
    }

    /**
     * Status verifikasi (publik, rate limit ketat) — dipakai halaman OTP
     * untuk sinkron countdown cooldown tanpa membocorkan data sensitif.
     * `registered` hanya true bagi pemilik sesi (handle cocok) atau akun
     * legacy — email asing tetap terlihat "tidak terdaftar".
     */
    public function status(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = strtolower($request->input('email'));

        $pending = PendingRegistration::where('email', $email)->first();

        if ($pending && ! $pending->isExpired()) {
            $handleOk = $request->filled('verify_handle')
                && hash_equals($pending->verify_handle, (string) $request->input('verify_handle'));

            if (! $handleOk) {
                return $this->successResponse(
                    ['registered' => false, 'verified' => false, 'retry_after' => null],
                    'Status verifikasi'
                );
            }

            return $this->successResponse([
                'registered' => true,
                'verified' => false,
                'retry_after' => app(EmailOtpService::class)->cooldownRemaining($email),
            ], 'Status verifikasi');
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return $this->successResponse(['registered' => false, 'verified' => false, 'retry_after' => null], 'Status verifikasi');
        }

        return $this->successResponse([
            'registered' => true,
            'verified' => (bool) $user->email_verified_at,
            'retry_after' => $user->email_verified_at ? null : app(EmailOtpService::class)->cooldownRemaining($email),
        ], 'Status verifikasi');
    }

    private function genericResendResponse(): JsonResponse
    {
        return $this->successResponse(
            ['resent' => false, 'retry_after' => null],
            'Jika email Anda terdaftar, kode verifikasi baru telah dikirim.'
        );
    }
}
