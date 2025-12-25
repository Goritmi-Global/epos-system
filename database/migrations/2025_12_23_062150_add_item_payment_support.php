<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add to pos_order_items table
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->decimal('amount_paid', 10, 2)->default(0)->after('price');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid')->after('amount_paid');
        });

        // Add to payments table
        Schema::table('payments', function (Blueprint $table) {
            $table->json('paid_items')->nullable()->after('payment_type');
        });
    }

    public function down()
    {
        Schema::table('pos_order_items', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'payment_status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('paid_items');
        });
    }
};
