<?php

namespace App\Services;

use App\Mail\VerifyEmailOtp;
use App\Models\EmailVerificationOtp;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailOtpService
{
    /**
     * ── OTP Verifikasi Email — anti spam & brute-force ────────────────────
     *
     * Owner OTP polimorfik:
     *  - App\Models\PendingRegistration → pendaftar metode email (email
     *    belum ditulis ke tabel users — baru dipromosikan setelah OTP benar)
     *  - App\Models\User → akun eksisting (jalur legacy + set-password)
     *
     * Lapisan pertahanan (semua ditegakkan SERVER-SIDE):
     *  1. Hash kode di DB (SHA3-256/SHA-256) — bocornya DB ≠ bocornya OTP.
     *  2. `attempts_left` (default 5) — matikan OTP setelah batas tebakan.
     *  3. TTL 10 menit per OTP.
     *  4. Cooldown 60 detik antar kirim (per email).
     *  5. Kuota 3 kirim / jam / email (Cache per email — lintas IP).
     *  6. Pengiriman async (queue) — API tidak diblokir SMTP/Resend.
     *
     * Pesan error SELALU generik: tidak mengungkap apakah email terdaftar.
     */
    public const REASON_NEW_REQUEST = 'new_request';

    public const REASON_TOO_MANY_ATTEMPTS = 'too_many_attempts';

    public const REASON_EXPIRED = 'expired';

    public function issue(Model $owner, bool $isResend = false): array
    {
        $email = strtolower($owner->email);
        $cooldown = (int) config('emailotp.resend_cooldown_seconds', 60);
        $quota = (int) config('emailotp.max_sends_per_hour', 3);
        $ttl = (int) config('emailotp.ttl_minutes', 10);

        // ── Kuota per-jam (CEK DULU, sebelum apa pun) ────────────────────
        $quotaKey = "emailotp:quota:{$email}";
        if (Cache::get($quotaKey, 0) >= $quota) {
            return ['ok' => false, 'reason' => 'quota_exceeded', 'retry_after' => null];
        }

        // ── Cooldown antar kirim ─────────────────────────────────────────
        $last = EmailVerificationOtp::where('email', $email)
            ->orderByDesc('last_sent_at')
            ->value('last_sent_at');

        if ($last && $last->diffInSeconds(now()) < $cooldown) {
            return [
                'ok' => false,
                'reason' => 'cooldown',
                'retry_after' => $cooldown - (int) $last->diffInSeconds(now()),
            ];
        }

        // ── Invalidasi OTP lama milik owner ini ──────────────────────────
        EmailVerificationOtp::where('owner_type', $owner::class)
            ->where('owner_id', $owner->id)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_reason')
            ->update(['invalidated_reason' => self::REASON_NEW_REQUEST]);

        // ── Generate + simpan hash ───────────────────────────────────────
        $code = $this->generateCode();
        $now = now();

        EmailVerificationOtp::create([
            'owner_type' => $owner::class,
            'owner_id' => $owner->id,
            'email' => $email,
            'code_hash' => $this->hashCode($code),
            'expires_at' => $now->copy()->addMinutes($ttl),
            'last_sent_at' => $now,
            'attempts_left' => (int) config('emailotp.max_attempts', 5),
        ]);

        // ── Tandai kuota (TTL 1 jam) ─────────────────────────────────────
        Cache::put($quotaKey, Cache::get($quotaKey, 0) + 1, now()->addHour());

        // ── Kirim (queue — async) ────────────────────────────────────────
        Mail::to($owner->email)->queue(
            new VerifyEmailOtp($owner->name, $code, $this->maskEmail($owner->email), $ttl)
        );

        Log::info('OTP email verification issued', [
            'owner_type' => $owner::class,
            'owner_id' => $owner->id,
            'email' => $email,
            'is_resend' => $isResend,
        ]);

        return ['ok' => true, 'expires_in' => $ttl * 60];
    }

    /**
     * Verifikasi kode untuk owner mana pun. Generic error message — tidak
     * membocorkan status akun.
     */
    public function verify(Model $owner, string $code): array
    {
        $code = trim($code);

        if (! preg_match('/^\d{6}$/', $code)) {
            return ['ok' => false, 'reason' => 'invalid'];
        }

        $otp = EmailVerificationOtp::where('owner_type', $owner::class)
            ->where('owner_id', $owner->id)
            ->whereNull('consumed_at')
            ->whereNull('invalidated_reason')
            ->orderByDesc('id')
            ->first();

        // Tidak ada OTP aktif → generik
        if (! $otp || ! $otp->isValid()) {
            return ['ok' => false, 'reason' => 'invalid'];
        }

        // OTP kedaluwarsa → tandai + generik
        if ($otp->isExpired()) {
            $otp->update(['invalidated_reason' => self::REASON_EXPIRED]);

            return ['ok' => false, 'reason' => 'invalid'];
        }

        // Attempt habis → invalidasi + generik
        if ($otp->attempts_left <= 0) {
            $otp->update(['invalidated_reason' => self::REASON_TOO_MANY_ATTEMPTS]);

            return ['ok' => false, 'reason' => 'invalid'];
        }

        // Kode salah → kurangi attempt; habis → invalidasi
        if (! hash_equals($otp->code_hash, $this->hashCode($code))) {
            $left = $otp->attempts_left - 1;
            $otp->update([
                'attempts_left' => $left,
                'invalidated_reason' => $left <= 0 ? self::REASON_TOO_MANY_ATTEMPTS : null,
            ]);

            return [
                'ok' => false,
                'reason' => 'invalid',
                'attempts_left' => max(0, $left),
            ];
        }

        // ── BERHASIL ─────────────────────────────────────────────────────
        $otp->update(['consumed_at' => now()]);

        return ['ok' => true];
    }

    /**
     * Hapus semua baris OTP milik owner (mis. pending yang sudah dipromosi
     * menjadi user — mencegah baris yatim menumpuk di tabel OTP).
     */
    public function purgeOwner(Model $owner): void
    {
        EmailVerificationOtp::where('owner_type', $owner::class)
            ->where('owner_id', $owner->id)
            ->delete();
    }

    /**
     * Sisa detik cooldown untuk email — dipakai endpoint resend.
     */
    public function cooldownRemaining(string $email): int
    {
        $last = EmailVerificationOtp::where('email', strtolower($email))
            ->orderByDesc('last_sent_at')
            ->value('last_sent_at');

        if (! $last) {
            return 0;
        }

        $cooldown = (int) config('emailotp.resend_cooldown_seconds', 60);
        $remaining = $cooldown - (int) $last->diffInSeconds(now());

        return max(0, $remaining);
    }

    /**
     * Hash OTP — SHA3-256 bila tersedia, fallback SHA-256 (konsisten untuk
     * issue & verify karena berasal dari PHP binary yang sama).
     */
    private function hashCode(string $code): string
    {
        return in_array('sha3-256', hash_algos(), true)
            ? hash('sha3-256', $code)
            : hash('sha256', $code);
    }

    private function generateCode(): string
    {
        $length = (int) config('emailotp.length', 6);
        $max = (int) (10 ** $length) - 1;

        return str_pad((string) random_int(0, $max), $length, '0', STR_PAD_LEFT);
    }

    /**
     * Mask email untuk ditampilkan di body email — r***@domain.com.
     */
    private function maskEmail(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

        $first = $local !== '' ? $local[0] : 'u';

        return $first.str_repeat('*', max(3, strlen($local) - 1)).'@'.$domain;
    }
}
