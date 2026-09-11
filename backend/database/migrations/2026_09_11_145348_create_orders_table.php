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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('pasien_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('psikolog_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('client_categories')->cascadeOnDelete();
            $table->foreignId('duration_id')->constrained('duration_options')->cascadeOnDelete();
            $table->decimal('calculated_price', 10, 2);
            $table->string('consultation_type')->default('video');
            $table->string('status')->default('pending_payment');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
