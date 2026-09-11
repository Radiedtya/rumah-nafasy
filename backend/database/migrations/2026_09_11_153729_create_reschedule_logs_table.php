<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reschedule_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->date('old_date');
            $table->time('old_start_time');
            $table->time('old_end_time');
            $table->date('new_date');
            $table->time('new_start_time');
            $table->time('new_end_time');
            $table->text('reason')->nullable();
            $table->string('rescheduled_by');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reschedule_logs');
    }
};
