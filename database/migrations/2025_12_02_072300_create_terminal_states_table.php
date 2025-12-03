<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('terminal_states', function (Blueprint $table) {
            $table->id();
            $table->string('terminal_id')->unique()->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('cart_data')->nullable();
            $table->json('ui_data')->nullable();
            $table->unsignedInteger('version')->default(0)->index(); // Added version tracking
            $table->timestamp('last_updated')->useCurrent()->index(); // Added index for queries
            // Removed $table->timestamps() since we're using last_updated instead

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('terminal_states');
    }
};