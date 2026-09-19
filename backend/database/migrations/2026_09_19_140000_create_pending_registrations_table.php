<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pendaftaran yang MENUNGGU verifikasi OTP email.
     *
     * Email pendaftar TIDAK ditulis ke tabel `users` sebelum terbukti
     * miliknya (bukti = OTP yang dikirim via Resend berhasil dimasukkan).
     * Baris di sini hanyalah kandidat akun; setelah OTP benar, baris ini
     * dipromosikan menjadi user terverifikasi lalu dihapus.
     */
    public function up(): void
    {
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('password');                    // sudah bcrypt
            $table->string('verify_handle', 64)->unique(); // pengenal acak halaman OTP
            $table->timestamp('expires_at')->index();      // masa berlaku sesi pendaftaran
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_registrations');
    }
};
