<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Keluhan & catatan pasien untuk psikolog, ditulis dengan format
     * Markdown pada langkah terpisah di wizard booking. Disimpan mentah;
     * dirender & disanitasi di sisi frontend.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('complaint_markdown')->nullable()->after('rejected_reason');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('complaint_markdown');
        });
    }
};
