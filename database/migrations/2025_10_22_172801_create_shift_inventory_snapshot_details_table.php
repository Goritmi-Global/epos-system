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
        Schema::create('shift_inventory_snapshot_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('snap_id'); 
            $table->unsignedBigInteger('item_id'); 
            $table->integer('stock_quantity'); 
            $table->timestamps();

            // Foreign keys
            $table->foreign('snap_id')->references('id')->on('shift_inventory_snapshots')->onDelete('cascade');
            $table->foreign('item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_inventory_snapshot_details');
    }
};
