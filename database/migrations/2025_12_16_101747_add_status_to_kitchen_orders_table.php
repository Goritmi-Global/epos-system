<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->string('status')->default('Waiting')->after('kitchen_note');
            // Possible values: Waiting, In Progress, Done, Cancelled
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_orders', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};