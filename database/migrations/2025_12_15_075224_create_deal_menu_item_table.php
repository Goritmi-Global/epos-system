<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deal_menu_item', function (Blueprint $table) {
            $table->id();

            $table->foreignId('deal_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('menu_item_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            $table->timestamps();

            // Prevent duplicate menu in same deal
            $table->unique(['deal_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_menu_item');
    }
};
