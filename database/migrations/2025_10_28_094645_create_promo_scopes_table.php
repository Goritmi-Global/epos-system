<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop tables if they already exist to avoid errors
        Schema::dropIfExists('promo_scope_menu_item');
        Schema::dropIfExists('promo_scope_meal');
        Schema::dropIfExists('scope_promo');
        Schema::dropIfExists('promo_scopes');

        // Promo scopes table
        Schema::create('promo_scopes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        // Pivot table: promo_scope ↔ promos
        Schema::create('scope_promo', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_scope_id');
            $table->unsignedBigInteger('promo_id');
            $table->timestamps();

            $table->foreign('promo_scope_id')->references('id')->on('promo_scopes')->onDelete('cascade');
            $table->foreign('promo_id')->references('id')->on('promos')->onDelete('cascade');

            $table->unique(['promo_scope_id', 'promo_id']); // prevent duplicates
        });

        // Pivot table: promo_scope ↔ meals
        Schema::create('promo_scope_meal', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_scope_id');
            $table->unsignedBigInteger('meal_id');
            $table->timestamps();

            $table->foreign('promo_scope_id')->references('id')->on('promo_scopes')->onDelete('cascade');
            $table->foreign('meal_id')->references('id')->on('meals')->onDelete('cascade');

            $table->unique(['promo_scope_id', 'meal_id']); // prevent duplicates
        });

        // Pivot table: promo_scope ↔ menu items
        Schema::create('promo_scope_menu_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('promo_scope_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->timestamps();

            $table->foreign('promo_scope_id')->references('id')->on('promo_scopes')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');

            $table->unique(['promo_scope_id', 'menu_item_id']); // prevent duplicates
        });
    }

    public function down()
    {
        Schema::dropIfExists('promo_scope_menu_item');
        Schema::dropIfExists('promo_scope_meal');
        Schema::dropIfExists('scope_promo');
        Schema::dropIfExists('promo_scopes');
    }
};
