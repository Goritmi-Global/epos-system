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
        Schema::create('shift_inventory_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id'); 
            $table->enum('type', ['started', 'ended']); 
            $table->unsignedBigInteger('user_id'); 
            $table->timestamp('created_at')->useCurrent(); 

            // Foreign keys
            $table->foreign('shift_id')->references('id')->on('shifts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_inventory_snapshots');
    }
};
