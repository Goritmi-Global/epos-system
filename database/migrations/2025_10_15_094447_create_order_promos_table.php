<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_promos', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('order_id')
                  ->constrained('pos_orders')
                  ->onDelete('cascade');

            $table->foreignId('promo_id')
                  ->nullable()
                  ->constrained('promos')
                  ->onDelete('set null');

            // Promo details snapshot at order time
            $table->string('promo_name')->nullable();
            $table->enum('promo_type', ['flat', 'percent'])->default('flat');
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_promos');
    }
};

