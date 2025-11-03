<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_step_7', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('cash_enabled')->default(true);
            $table->boolean('card_enabled')->default(false);
            $table->boolean('logout_after_order')->default(false);
            $table->boolean('logout_after_time')->default(false);
            $table->boolean('logout_manual_only')->default(false);

            // Optional: logout time in minutes (if logout_after_time is true)
            $table->integer('logout_time_minutes')->nullable()->default(30);

            $table->unique('user_id');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_7');
    }
};
