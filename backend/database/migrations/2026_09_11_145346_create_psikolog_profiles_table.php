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
        Schema::create('psikolog_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialization_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slug')->unique();
            $table->text('bio')->nullable();
            $table->integer('experience_years')->default(0);
            $table->string('license_no')->nullable();
            $table->string('education')->nullable();
            $table->string('workplace')->nullable();
            $table->decimal('custom_rate', 10, 2)->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_available')->default(true);
            $table->decimal('rating_avg', 3, 2)->default(0.00);
            $table->integer('total_reviews')->default(0);
            $table->integer('total_consultations')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('psikolog_profiles');
    }
};
