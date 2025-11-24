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
            $table->decimal('amount_received', 10, 2)->nullable(); // full total
            $table->decimal('cash_amount', 10, 2)->nullable();     // for split
            $table->decimal('card_amount', 10, 2)->nullable();     // for split

            $table->string('payment_type')->nullable();            // Cash | Card | Split
            $table->dateTime('payment_date')->default(DB::raw('CURRENT_TIMESTAMP'));

            // Stripe / card details
            $table->string('payment_status', 32)->nullable();
            $table->string('code', 64)->nullable()->index();
            $table->string('stripe_payment_intent_id', 64)->nullable()->index();
            $table->string('last_digits', 4)->nullable();
            $table->string('brand', 32)->nullable();
            $table->string('currency_code', 3)->nullable();
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();

            // refunds can be negative payments
            $table->enum('refund_status', ['none', 'partial', 'refunded'])->default('none');
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->timestamp('refund_date')->nullable();
            $table->string('refund_id')->nullable();
            $table->text('refund_reason')->nullable();
            $table->unsignedBigInteger('refunded_by')->nullable();

            $table->foreign('refunded_by')->references('id')->on('users')->nullOnDelete();

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
