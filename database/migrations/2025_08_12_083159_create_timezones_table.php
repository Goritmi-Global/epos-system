<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->string('gmt_offset', 6);
            $table->boolean('is_default')->default(false);
            $table->string('country_id', 3)->nullable();
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropForeign(['default_timezone_id']);
            $table->dropColumn('default_timezone_id');
        });

        Schema::dropIfExists('timezones');
    }
};
