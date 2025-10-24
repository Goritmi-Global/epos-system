<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('started_by'); // Super admin who started the shift
            $table->timestamp('start_time')->nullable();
            $table->decimal('opening_cash', 10, 2);
            $table->text('cash_notes')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('end_time')->nullable();
            $table->unsignedBigInteger('ended_by')->nullable();
            $table->decimal('closing_cash', 10, 2)->nullable();
            $table->decimal('sales_total', 10, 2)->nullable();
            $table->timestamps();

            // Optional: add foreign keys if using users table
            $table->foreign('started_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ended_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
