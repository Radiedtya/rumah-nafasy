<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur bisnis baru: pasien TIDAK membayar di aplikasi.
     * Booking dibuat langsung (tanpa order), menunggu persetujuan psikolog;
     * pembayaran P2P dilakukan setelah sesi selesai.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('consultation_type')->default('video')->after('psikolog_id');
            $table->unsignedInteger('duration_minutes')->default(60)->after('consultation_type');
            $table->foreignId('requested_category_id')->nullable()->after('duration_minutes')
                ->constrained('client_categories')->nullOnDelete();
            $table->text('rejected_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['requested_category_id']);
            $table->dropColumn(['consultation_type', 'duration_minutes', 'requested_category_id', 'rejected_reason']);
        });
    }
};
