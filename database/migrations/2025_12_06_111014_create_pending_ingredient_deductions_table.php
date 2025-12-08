<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pending_ingredient_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('pos_orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('pos_order_items')->onDelete('cascade');
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->onDelete('cascade');
            $table->string('inventory_item_name');
            $table->decimal('required_quantity', 10, 2);
            $table->decimal('available_quantity', 10, 2)->default(0);
            $table->decimal('pending_quantity', 10, 2); // Quantity to deduct when stock arrives
            $table->enum('status', ['pending', 'fulfilled', 'cancelled'])->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('fulfilled_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['inventory_item_id', 'status']);
            $table->index(['order_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_ingredient_deductions');
    }
};