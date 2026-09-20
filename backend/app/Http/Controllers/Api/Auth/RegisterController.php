<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\PendingRegistration;
use App\Services\EmailOtpService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    /**
     * ── Registrasi metode email biasa ────────────────────────────────────
     *
     * PENTING — email TIDAK disimpan ke tabel `users` di tahap ini.
     * Data pendaftar disimpan sebagai kandidat akun di `pending_registrations`
     * dan OTP 6 digit dikirim via Resend. OTP yang benar = bukti kepemilikan
     * email; baris pending baru dipromosikan menjadi user terverifikasi
     * pada endpoint auth/email/verify.
     *
     * Keamanan:
     * - Tidak ada baris users "zombie" tanpa verifikasi.
     * - verify_handle acak (64 char) = pengenal halaman OTP; pemilik email
     *   tidak bisa diverifikasi oleh pihak lain tanpa kode OTP.
     * - verify_handle baru di setiap register → handle lama mati.
     */
    public function register(RegisterRequest $request, EmailOtpService $otpService)
    {
        $email = strtolower($request->email);

        // Timpa kandidat lama untuk email yang sama (jika ada) — hanya satu
        // pendaftaran aktif per email. (Unique di tabel menjamin ini.)
        PendingRegistration::where('email', $email)->delete();

        $pending = PendingRegistration::create([
            'name' => $request->name,
            'email' => $email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'verify_handle' => Str::random(64),
            'expires_at' => now()->addMinutes((int) config('emailotp.pending_ttl_minutes', 60)),
        ]);

        $otpService->issue($pending);

        return $this->successResponse([
            'email' => $pending->email,
            'verify_handle' => $pending->verify_handle,
            'requires_verification' => true,
        ], 'Registrasi berhasil. Kode verifikasi telah dikirim ke email Anda.', 201);
    }
}
