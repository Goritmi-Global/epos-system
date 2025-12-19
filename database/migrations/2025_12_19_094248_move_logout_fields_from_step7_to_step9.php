<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * Remove fields from profile_step_7
         */
        Schema::table('profile_step_7', function (Blueprint $table) {
            $table->dropColumn([
                'logout_after_order',
                'logout_after_time',
                'logout_manual_only',
                'logout_time_minutes',
            ]);
        });

        /**
         * Add fields to profile_step_9
         */
        Schema::table('profile_step_9', function (Blueprint $table) {
            $table->boolean('logout_after_order')->default(false)->after('theme_preference');
            $table->boolean('logout_after_time')->default(false)->after('logout_after_order');
            $table->boolean('logout_manual_only')->default(false)->after('logout_after_time');
            $table->integer('logout_time_minutes')->nullable()->default(30)->after('logout_manual_only');
        });
    }

    public function down(): void
    {
        /**
         * Rollback: add fields back to step 7
         */
        Schema::table('profile_step_7', function (Blueprint $table) {
            $table->boolean('logout_after_order')->default(false);
            $table->boolean('logout_after_time')->default(false);
            $table->boolean('logout_manual_only')->default(false);
            $table->integer('logout_time_minutes')->nullable()->default(30);
        });

        /**
         * Rollback: remove fields from step 9
         */
        Schema::table('profile_step_9', function (Blueprint $table) {
            $table->dropColumn([
                'logout_after_order',
                'logout_after_time',
                'logout_manual_only',
                'logout_time_minutes',
            ]);
        });
    }
};
