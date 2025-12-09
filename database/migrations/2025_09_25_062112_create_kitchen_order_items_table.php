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
        Schema::create('kitchen_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kitchen_order_id')->constrained('kitchen_orders')->onDelete('cascade');
            $table->string('item_name');
            $table->string('variant_name')->nullable();
            $table->integer('quantity')->default(1);
            $table->json('ingredients')->nullable();
            $table->text('item_kitchen_note')->nullable();
            $table->enum('status', ['Waiting', 'In Progress', 'Done', 'Cancelled'])->default('Waiting');   
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_order_items');
    }
};
