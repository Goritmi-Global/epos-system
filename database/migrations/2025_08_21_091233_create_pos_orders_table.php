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
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('customer_name')->nullable();
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);

            $table->decimal('tax', 10, 2)->nullable();
            $table->decimal('service_charges', 10, 2)->nullable();
            $table->decimal('delivery_charges', 10, 2)->nullable();
            $table->decimal('sales_discount', 10, 2)->default(0);
            $table->decimal('approved_discounts', 10, 2)->default(0);

            $table->string('status')->default('paid');
            $table->text('note')->nullable();
            $table->text('kitchen_note')->nullable();

            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');

            $table->date('order_date')->nullable();
            $table->time('order_time')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_orders');
    }
};
