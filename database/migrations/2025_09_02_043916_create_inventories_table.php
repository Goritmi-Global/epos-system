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
            $table->string('category');
            $table->string('subcategory')->nullable();
            $table->integer('minAlert')->default(0);
            $table->string('unit');
            $table->string('supplier');
            $table->string('sku')->nullable();
            $table->text('description')->nullable();
            $table->json('nutrition')->nullable(); // calories, fat, protein, carbs
            $table->json('allergies')->nullable(); // store as array
            $table->json('tags')->nullable(); // store as array
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
