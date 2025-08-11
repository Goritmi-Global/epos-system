<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->string('id', 6)->primary();                  // varchar(6)
            $table->string('name', 59)->nullable();
            $table->string('state_id', 8)->nullable();
            $table->string('state_code', 10)->nullable();
            $table->string('state_name', 56)->nullable();
            $table->string('country_id', 10)->nullable();
            $table->string('country_code', 12)->nullable();
            $table->string('country_name', 32)->nullable();

            $table->index('state_id');
            $table->index('state_code');
            $table->index('country_id');
            $table->index('country_code');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
