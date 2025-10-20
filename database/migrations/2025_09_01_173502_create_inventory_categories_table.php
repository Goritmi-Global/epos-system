<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->decimal('total_value', 15, 2)->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('out_of_stock')->default(0);
            $table->integer('low_stock')->default(0);
            $table->integer('in_stock')->default(0);
            $table->timestamps();

            // Foreign key constraint (self-referencing inside inventory_categories)
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('inventory_categories')
                  ->onDelete('cascade');

            // Indexes for better performance
            $table->index('parent_id');
            $table->index('active');
            $table->index(['name', 'parent_id']); // Composite index
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_categories');
    }
};
