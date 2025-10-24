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
       Schema::create('menu_allergies', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('menu_item_id');
        $table->unsignedBigInteger('allergy_id');
        $table->boolean('type')->default(0)->comment('1 = Contain, 0 = Trace');

        $table->timestamps();

        $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
        $table->foreign('allergy_id')->references('id')->on('allergies')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_allergies');
    }
};
