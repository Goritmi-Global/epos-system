<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_step_4', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_tax_registered')->default(false);
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->nullable();
            $table->string('tax_id')->nullable();
            $table->decimal('extra_tax_rates', 8, 2)->nullable();
            $table->boolean('price_includes_tax')->default(false);
            $table->boolean('has_service_charges')->default(false);
            $table->decimal('service_charge_flat', 10, 2)->nullable();
            $table->decimal('service_charge_percentage', 8, 2)->nullable();
            $table->boolean('has_delivery_charges')->default(false);
            $table->decimal('delivery_charge_flat', 10, 2)->nullable();
            $table->decimal('delivery_charge_percentage', 8, 2)->nullable();
            $table->unique('user_id');
            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_4');
    }
};
