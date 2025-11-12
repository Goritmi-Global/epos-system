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
        Schema::create('pos_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade'); 
            $table->string('title');
            $table->integer('quantity');
            $table->string('variant_name')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_discount_per_item', 10, 2)->default(0);
            $table->text('note')->nullable();
            $table->text('kitchen_note')->nullable();
            $table->text('item_kitchen_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_order_items');
    }
};
