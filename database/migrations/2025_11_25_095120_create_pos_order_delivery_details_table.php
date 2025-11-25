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
        Schema::create('pos_order_delivery_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')
                ->constrained('pos_orders')
                ->onDelete('cascade');

            $table->string('phone_number')->nullable();
            $table->text('delivery_location')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_delivery_details');
    }
};
