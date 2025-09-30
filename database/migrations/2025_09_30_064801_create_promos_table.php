<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['flat', 'percent'])->default('percent');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('min_purchase', 10, 2)->default(0);
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};