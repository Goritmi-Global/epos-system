<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_5', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('order_types')->nullable();
            $table->boolean('table_management_enabled')->default(false);
            $table->boolean('online_ordering_enabled')->default(false);
            $table->foreignId('profile_table_id')->nullable()->constrained('profile_tables')->nullOnDelete();
            $table->unique('user_id');
            $table->timestamps();
           
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_5');
    }
};
