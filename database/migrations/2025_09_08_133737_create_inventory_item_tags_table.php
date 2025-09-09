<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_item_tags', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                  ->constrained('inventory_items')
                  ->cascadeOnDelete();

            $table->foreignId('tag_id')
                  ->constrained('tags')
                  ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['inventory_item_id', 'tag_id'], 'inv_tag_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_item_tags');
    }
};
