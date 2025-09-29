<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_9', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->boolean('enable_loyalty_system')->default(false);
            $table->boolean('enable_inventory_tracking')->default(false);
            $table->boolean('enable_cloud_backup')->default(false);
            $table->boolean('enable_multi_location')->default(false);

            $table->string('theme_preference')->nullable(); 

            $table->unique('user_id');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('profile_step_9');
    }
};
