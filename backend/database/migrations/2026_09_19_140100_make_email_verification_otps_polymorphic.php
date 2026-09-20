<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * OTP dipisah dari tabel users: pendaftar baru belum berhak punya
     * baris `users`, tetapi OTP tetap perlu dikirim & diverifikasi.
     * Owner jadi polimorfik — bisa User (jalur lama & set-password)
     * maupun PendingRegistration (pendaftar baru).
     */
    public function up(): void
    {
        // Daftar index yang benar-benar ada (aman utk SQLite & varian lama)
        $existing = collect(DB::select("PRAGMA index_list('email_verification_otps')"))
            ->pluck('name')
            ->all();

        foreach ([
            'email_verification_otps_user_id_index',
            'email_verification_otps_user_id_consumed_at_index',
        ] as $index) {
            if (in_array($index, $existing, true)) {
                Schema::table('email_verification_otps', fn (Blueprint $t) => $t->dropIndex($index));
            }
        }

        if (Schema::hasColumn('email_verification_otps', 'user_id')) {
            Schema::table('email_verification_otps', fn (Blueprint $t) => $t->dropColumn('user_id'));
        }

        Schema::table('email_verification_otps', function (Blueprint $table) {
            // Nullable demi kompatibilitas ALTER di SQLite; service SELALU
            // mengisi keduanya saat membuat OTP.
            $table->string('owner_type')->nullable()->after('id');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type');

            $table->index(['owner_type', 'owner_id', 'consumed_at']);
        });
    }

    public function down(): void
    {
        Schema::table('email_verification_otps', function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id', 'consumed_at']);
            $table->dropColumn(['owner_type', 'owner_id']);

            $table->unsignedBigInteger('user_id')->index();
            $table->index(['user_id', 'consumed_at']);
        });
    }
};
