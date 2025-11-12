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
        Schema::create('shift_checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id');
             $table->enum('type', ['started', 'ended']);
            $table->json('checklist_item_ids')->nullable(); 
            $table->timestamps();

            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_checklists');
    }
};
