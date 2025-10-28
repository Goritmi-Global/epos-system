<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the variant_groups table (e.g., Size, Temperature, Crust Type)
     */
    public function up(): void
    {
        Schema::create('variant_groups', function (Blueprint $table) {
            $table->id();
            
            // Group name (e.g., "Size", "Temperature", "Crust Type")
            $table->string('name');
        
            // Optional description for the group
            $table->text('description')->nullable();
            
            // Status: active or inactive
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            // Display order (lower numbers appear first)
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes(); // Soft delete support
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_groups');
    }
};