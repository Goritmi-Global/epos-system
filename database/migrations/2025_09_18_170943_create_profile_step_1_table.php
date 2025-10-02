<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_1', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Add country_id foreign key
            $table->string('country_id', 3)->nullable();
            $table->foreign('country_id')->references('id')->on('countries');

            $table->unsignedBigInteger('timezone_id')->nullable();
            $table->foreign('timezone_id')->references('id')->on('timezones')->nullOnDelete();
            $table->string('language')->nullable();

            $table->unique('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_1');
    }
};
