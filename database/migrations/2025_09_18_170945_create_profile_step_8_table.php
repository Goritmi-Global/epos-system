<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_8', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Relations
            $table->foreignId('disable_order_after_hours_id')
                  ->nullable()
                  ->constrained('disable_order_after_hours')
                  ->cascadeOnDelete();

            $table->foreignId('business_hours_id')
                  ->nullable()
                  ->constrained('business_hours')
                  ->cascadeOnDelete();

            $table->unique('user_id');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('profile_step_8');
    }
};
