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

            // FK columns instead of strings
            $table->foreignId('supplier_id')->nullable()
                ->constrained('suppliers')->nullOnDelete();
            $table->foreignId('unit_id')->nullable()
                ->constrained('units')->nullOnDelete();

            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();

            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
