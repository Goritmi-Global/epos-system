<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_addon_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->foreignId('addon_group_id')->constrained('addon_groups')->onDelete('cascade');
            $table->timestamps();
            
            // Optional: Prevent duplicate entries
            $table->unique(['menu_item_id', 'addon_group_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_addon_groups');
    }
};