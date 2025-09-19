<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_4', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_tax_registered')->default(false);
            $table->string('tax_type')->nullable();
            $table->decimal('tax_rate', 8, 2)->nullable();
            $table->json('extra_tax_rates')->nullable(); // array/object
            $table->boolean('price_includes_tax')->default(false);
            $table->unique('user_id');
            $table->timestamps();
            
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_4');
    }
};
