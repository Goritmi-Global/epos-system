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
        Schema::create('stock_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->nullable()
                ->constrained('suppliers')
                ->nullOnDelete(); // if supplier is deleted, set NULL

            $table->foreignId('product_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->integer('quantity')->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('value', 12, 2)->default(0);

            $table->string('operation_type'); // purchase / stockout
            $table->string('stock_type');     // stockin / stockout
            $table->date('expiry_date')->nullable();
            $table->text('description')->nullable();
            $table->date('purchase_date')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_entries');
    }
};
