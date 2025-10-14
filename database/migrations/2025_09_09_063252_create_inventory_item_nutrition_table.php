<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_nutrition', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                  ->constrained('inventory_items')
                  ->cascadeOnDelete();

            $table->decimal('calories', 10, 3)->default(0);
            $table->decimal('fat', 10, 3)->default(0);
            $table->decimal('carbs', 10, 3)->default(0);
            $table->decimal('protein', 10, 3)->default(0);

            $table->timestamps();

            $table->unique('inventory_item_id', 'inv_nutrition_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_nutrition');
    }
};
