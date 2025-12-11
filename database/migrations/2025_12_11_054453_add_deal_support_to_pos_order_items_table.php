<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('menu_item_id')->nullable()->change();
            $table->unsignedBigInteger('deal_id')->nullable()->after('menu_item_id');
            $table->boolean('is_deal')->default(false)->after('deal_id');
            $table->foreign('deal_id')->references('id')->on('deals')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropForeign(['deal_id']);
            $table->dropColumn(['deal_id', 'is_deal']);
            
            // Revert menu_item_id to non-nullable if needed
            // $table->unsignedBigInteger('menu_item_id')->nullable(false)->change();
        });
    }
};