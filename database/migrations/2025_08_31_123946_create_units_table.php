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
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);

            // New fields for conversion functionality
            $table->unsignedBigInteger('base_unit_id')->nullable(); // If null → Base unit
            $table->decimal('conversion_factor', 15, 4)->default(1); // default 1 for base unit

            $table->timestamps();

            // Optional FK so that if a base unit is deleted, derived units are also removed
            $table->foreign('base_unit_id')
                  ->references('id')
                  ->on('units')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
