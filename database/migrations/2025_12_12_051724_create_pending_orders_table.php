<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pending_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('customer_name')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('delivery_location')->nullable();
            $table->string('order_type'); // Eat_in, Delivery, Takeaway
            $table->string('table_number')->nullable();
            
            // Order totals
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('service_charges', 10, 2)->default(0);
            $table->decimal('delivery_charges', 10, 2)->default(0);
            $table->decimal('sale_discount', 10, 2)->default(0);
            $table->decimal('promo_discount', 10, 2)->default(0);
            $table->decimal('approved_discounts', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            
            // Order details
            $table->text('note')->nullable();
            $table->text('kitchen_note')->nullable();
            $table->json('order_items'); 
            $table->json('applied_promos')->nullable();
            $table->json('approved_discount_details')->nullable();
            $table->json('selected_discounts')->nullable();
            
            // Metadata
            $table->string('terminal_id')->nullable();
            $table->timestamp('held_at');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pending_orders');
    }
};