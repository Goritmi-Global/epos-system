<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->string('name'); // e.g. Small, Medium, Large
            $table->decimal('price', 10, 2)->default(0);
            $table->boolean('is_saleable')->default(false)->after('price');
            $table->enum('resale_type', ['flat', 'percentage'])->nullable()->after('is_saleable');
            $table->decimal('resale_value', 10, 2)->nullable()->after('resale_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_variants');
    }
};
