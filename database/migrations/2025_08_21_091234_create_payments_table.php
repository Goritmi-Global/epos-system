<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->nullable()->constrained('pos_orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');

            // Payment details
            $table->decimal('amount_received', 10, 2)->nullable();
            $table->string('payment_type')->nullable();
            $table->dateTime('payment_date')->default(DB::raw('CURRENT_TIMESTAMP'));


            $table->string('payment_status', 32)->nullable();
            $table->string('code', 64)->nullable()->index();
            $table->string('stripe_payment_intent_id', 64)->nullable()->index();
            $table->string('last_digits', 4)->nullable();
            $table->string('brand', 32)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
