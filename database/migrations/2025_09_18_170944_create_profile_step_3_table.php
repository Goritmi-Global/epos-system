<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_3', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('currency')->nullable();
            $table->string('currency_symbol_position')->nullable(); // before|after
            $table->string('number_format')->nullable();
            $table->string('date_format')->nullable();
            $table->string('time_format')->nullable();
            $table->unique('user_id'); // 12-hour|24-hour
            $table->timestamps();
           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_3');
    }
};
