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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')
                ->nullable()
                ->unique()
                ->after('email');

            $table->string('avatar')
                ->nullable()
                ->after('phone');

            $table->boolean('is_active')
                ->default(true)
                ->after('avatar');

            $table->timestamp('phone_verified_at')
                ->nullable()
                ->after('is_active');

            $table->softDeletes()->after('phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'phone',
                'avatar',
                'is_active',
                'phone_verified_at',
            ]);
        });
    }
};
