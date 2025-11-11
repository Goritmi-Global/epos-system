<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->boolean('is_taxable')->default(false);

            // just keep the fields in order (no 'after')
            $table->boolean('is_saleable')->default(false);
            $table->enum('resale_type', ['flat', 'percentage'])->nullable();
            $table->decimal('resale_value', 10, 2)->nullable();

            $table->unsignedBigInteger('category_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('status')->default(1);

            // merged new fields
            $table->foreignId('upload_id')
                ->nullable()
                ->constrained('uploads')
                ->nullOnDelete();

            $table->string('label_color', 255)->nullable();

            $table->timestamps();

            $table->foreign('category_id')
                ->references('id')
                ->on('menu_categories')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('menu_items');
        Schema::enableForeignKeyConstraints();
    }
};
