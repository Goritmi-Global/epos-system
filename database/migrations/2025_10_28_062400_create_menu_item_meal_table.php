<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Support;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_meal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('meal_id')->constrained('meals')->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['menu_item_id', 'meal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_meal');
    }
};