<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OTP verifikasi email untuk pendaftar metode email biasa.
     *
     * Keamanan:
     * - Kode disimpan sebagai HASH (bukan plaintext) — bocornya DB tidak
     *   langsung membocorkan OTP.
     * - attempts_at: batas tebak (default 5 kali) → anti brute-force OTP.
     * - expires_at: masa hidup OTP (default 10 menit).
     * - expires_at + last_sent_at + index (email, created_at) → memungkinkan
     *   cooldown dan kuota pengiriman per-jam ditegakkan di service.
     */
    public function up(): void
    {
        Schema::create('email_verification_otps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('email')->index();
            $table->string('code_hash');                       // OTP ter-hash — tidak pernah plaintext
            $table->timestamp('expires_at')->index();          // masa hidup OTP
            $table->timestamp('last_sent_at');                 // untuk cooldown antar kirim
            $table->unsignedTinyInteger('attempts_left')->default(5); // batas tebakan
            $table->timestamp('consumed_at')->nullable();      // OTP berhasil dipakai
            $table->string('invalidated_reason')->nullable();  // new_request|too_many_attempts|expired
            $table->timestamps();

            $table->index(['email', 'created_at']);            // kuota per-jam per email
            $table->index(['user_id', 'consumed_at']);         // pencarian OTP aktif per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_otps');
    }
};
