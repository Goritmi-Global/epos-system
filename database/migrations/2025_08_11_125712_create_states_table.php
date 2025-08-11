<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->string('id', 4)->primary();                  // varchar(4)
            $table->string('name', 56)->nullable();
            $table->string('country_id', 10)->nullable();
            $table->string('country_code', 12)->nullable();
            $table->string('country_name', 32)->nullable();
            $table->string('state_code', 10)->nullable();

            $table->index('country_id');
            $table->index('country_code');
            $table->index('state_code');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
