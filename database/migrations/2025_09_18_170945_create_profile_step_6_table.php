<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('profile_step_6', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('receipt_header')->nullable();
            $table->text('receipt_footer')->nullable();
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            $table->boolean('show_qr_on_receipt')->default(false);
            $table->boolean('tax_breakdown_on_receipt')->default(false);
            $table->string('customer_printer')->nullable();
            $table->string('kot_printer')->nullable();
            $table->json('printers')->nullable();
            $table->unique('user_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_step_6');
    }
};
