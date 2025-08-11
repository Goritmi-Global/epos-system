<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->string('id', 3)->primary();                 // varchar(3)
            $table->string('name', 36)->nullable();
            $table->string('iso3', 4)->nullable();
            $table->string('iso2', 4)->nullable();
            $table->string('numeric_code', 12)->nullable();
            $table->string('phone_code', 16)->nullable();
            $table->string('capital', 20)->nullable();
            $table->string('currency', 8)->nullable();
            $table->string('currency_name', 39)->nullable();
            $table->string('currency_symbol', 15)->nullable();
            $table->string('tld', 3)->nullable();
            $table->string('native', 50)->nullable();
            $table->string('region', 8)->nullable();
            $table->string('subregion', 25)->nullable();

            $table->index('iso2');
            $table->index('iso3');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
