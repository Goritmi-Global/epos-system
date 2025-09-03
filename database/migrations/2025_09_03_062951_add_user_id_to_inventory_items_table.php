<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            if (!Schema::hasColumn('inventory_items', 'user_id')) {
                $table->foreignId('user_id')->nullable()->after('tags')->constrained()->onDelete('cascade');
            }
        });
    }



    public function down()
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
