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
        Schema::create('patient_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasien_id')->constrained('users')->cascadeOnDelete();
            $table->string('id_type');
            $table->string('id_number');
            $table->string('id_file_path')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_verifications');
    }
};
