<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pos_order_item_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_order_item_id')->constrained('pos_order_items')->onDelete('cascade');
            $table->foreignId('addon_id')->constrained('addons')->onDelete('cascade');
            $table->string('addon_name'); // Store name at time of order
            $table->decimal('price', 10, 2); // Store price at time of order
            $table->integer('quantity')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_order_item_addons');
    }
};