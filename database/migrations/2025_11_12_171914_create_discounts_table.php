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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); 
            $table->text('description')->nullable(); 
            $table->enum('type', ['flat', 'percent'])->default('percent'); 
            $table->decimal('discount_amount', 10, 2); 
            $table->decimal('min_purchase', 10, 2)->default(0); 
            $table->decimal('max_discount', 10, 2)->nullable(); 
            $table->enum('status', ['active', 'inactive'])->default('active'); 
            $table->date('start_date');
            $table->date('end_date'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
