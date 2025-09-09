<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->integer('minAlert')->default(0);
            $table->string('unit');         // keep as scalar (e.g., "kg", "pcs")
            $table->string('supplier');     // keep as scalar (e.g., "ABC Traders")
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();

            // removed: category, subcategory, nutrition, allergies, tags (now handled by relations)

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained()           // ->references('id')->on('users')
                  ->nullOnDelete();

            $table->foreignId('upload_id')
                  ->nullable()
                  ->constrained('uploads')  // ->references('id')->on('uploads')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
