<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('timezones', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);          // e.g. "Europe/London"
            $table->string('gmt_offset', 6);     // e.g. "+01:00"
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Add a foreign key to countries for default timezone
        Schema::table('countries', function (Blueprint $table) {
            $table->unsignedBigInteger('default_timezone_id')->nullable()->after('subregion');

            $table->foreign('default_timezone_id')
                ->references('id')
                ->on('timezones')
                ->onDelete('set null');
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
