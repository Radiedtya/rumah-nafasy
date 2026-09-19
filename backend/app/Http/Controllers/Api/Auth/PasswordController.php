<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\EmailOtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    /**
     * ── Ubah / atur password (halaman Settings/Profil) ───────────────────
     *
     * Dua jalur dalam satu endpoint aman:
     *
     * 1. User BELUM punya password (akun Google murni) → boleh set password
     *    BARU tanpa password lama. Verifikasi kepemilikan akun lewat OTP
     *    email (Resend) yang dikirim server — tanpa kode yang benar,
     *    sesi curian tidak bisa memasang password.
     *
     * 2. User SUDAH punya password → WAJIB kirim current_password yang benar.
     *
     * Setelah sukses: token API LAIN dicabut (revoke) supaya sesi lama di
     * perangkat lain tidak tetap hidup setelah kredensial berubah.
     */
    public function update(Request $request, EmailOtpService $otpService): JsonResponse
    {
        $user = $request->user();
        $hasPassword = (bool) $user->password;

        $rules = [
            // Password baru mengikuti kebijakan kuat (sinkron register)
            'password' => ['required', 'string', PasswordRule::min(8)->letters()->mixedCase()->numbers(), 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];

        if ($hasPassword) {
            $rules['current_password'] = ['required', 'string', function (string $attribute, mixed $value, \Closure $fail) use ($user) {
                if (! Hash::check((string) $value, $user->password)) {
                    $fail(__('Password lama salah'));
                }
            }];
        } else {
            // Jalur "set password" — wajib OTP email yang valid & terverifikasi
            $rules['otp_code'] = ['required', 'string', 'digits:6'];
            $rules['otp_token'] = ['required', 'string', 'size:60'];
        }

        $request->validate($rules, [
            'password.required' => 'Password baru wajib diisi',
            'password.min' => 'Password minimal 8 karakter',
            'password.confirmed' => 'Konfirmasi password tidak cocok',
            'password_confirmation.required' => 'Konfirmasi password wajib diisi',
            'current_password.required' => 'Password lama wajib diisi',
            'otp_code.required' => 'Kode verifikasi email wajib diisi',
            'otp_code.digits' => 'Kode verifikasi harus 6 digit angka',
        ]);

        // ── Jalur 2: verifikasi OTP email ────────────────────────────────
        if (! $hasPassword) {
            if (! $this->consumePasswordSetOtp($user, (string) $request->input('otp_token'), (string) $request->input('otp_code'))) {
                return $this->errorResponse('Kode verifikasi email tidak valid atau kedaluwarsa', 422);
            }
        }

        // ── Simpan password baru ─────────────────────────────────────────
        $user->forceFill(['password' => Hash::make($request->input('password'))])->save();

        // Revoke semua token KECUALI yang sedang dipakai — kredensial baru,
        // sesi lama mati (praktik standar perubahan password).
        // Auth via Bearer → pertahankan token aktif; via session
        // (TransientToken, tanpa id) → cabut SEMUA token API.
        $current = $request->user()->currentAccessToken();

        $revokeQuery = $request->user()->tokens();

        if ($current instanceof PersonalAccessToken) {
            $revokeQuery->where('id', '!=', $current->id);
        }

        $revokeQuery->delete();

        return $this->successResponse(
            new UserResource($user->fresh()->load('roles', 'psikologProfile')),
            $hasPassword
                ? 'Password berhasil diubah. Sesi lain telah dikeluarkan.'
                : 'Password berhasil diatur — kini bisa login email + password.'
        );
    }

    /**
     * Terbitkan OTP email untuk proses "set password" (user tanpa password).
     * Publik bagi user terautentikasi; rate limit di routes/api.php.
     */
    public function sendSetPasswordOtp(Request $request, EmailOtpService $otpService): JsonResponse
    {
        $user = $request->user();

        if ($user->password) {
            return $this->errorResponse('Anda sudah memiliki password — gunakan ubah password dengan password lama', 422);
        }

        // OTP dikirim hanya ke email user yang login (bukan input bebas),
        // memakai engine OTP yang sama (hash + attempt + cooldown + kuota).
        $result = $otpService->issue($user, isResend: true);

        if (! ($result['ok'] ?? false)) {
            if (($result['reason'] ?? '') === 'cooldown') {
                return $this->errorResponse(
                    'Terlalu cepat — tunggu :detik detik sebelum meminta kode baru.',
                    429,
                    ['retry_after' => $result['retry_after']]
                );
            }

            if (($result['reason'] ?? '') === 'quota_exceeded') {
                return $this->errorResponse('Batas permintaan kode tercapai. Coba lagi dalam satu jam.', 429);
            }
        }

        return $this->successResponse([
            'sent' => true,
            'email' => $user->email,
            'otp_token' => $this->issuePasswordSetOtpToken($user),
            'retry_after' => $otpService->cooldownRemaining($user->email),
        ], 'Kode verifikasi dikirim ke email Anda.');
    }

    /**
     * Token pengikat OTP↔user untuk alur set-password: random 60 char,
     * disimpan di Cache dengan TTL 15 menit, sekali pakai, terikat user id.
     */
    private function issuePasswordSetOtpToken(User $user): string
    {
        $token = Str::random(60);
        Cache::put(
            "pw_set_otp:{$user->id}:{$token}",
            $user->id,
            now()->addMinutes(15)
        );

        return $token;
    }

    private function consumePasswordSetOtp(User $user, string $token, string $code): bool
    {
        $key = "pw_set_otp:{$user->id}:{$token}";

        // Pull → token sekali pakai (anti replay).
        $boundUserId = Cache::pull($key);

        if (! $boundUserId || (int) $boundUserId !== (int) $user->id) {
            return false;
        }

        $otpService = app(EmailOtpService::class);
        $result = $otpService->verify($user, $code);

        return (bool) ($result['ok'] ?? false);
    }
}
