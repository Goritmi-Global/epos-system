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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('message');
            $table->enum('status', ['out_of_stock', 'low_stock', 'expired', 'near_expiry']);
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            // Optional: Add foreign key constraint (if inventory_items table exists)
            $table->foreign('product_id')
                  ->references('id')
                  ->on('inventory_items')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
