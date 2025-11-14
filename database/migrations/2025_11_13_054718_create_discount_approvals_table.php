<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->decimal('discount_percentage', 5, 2); 
            $table->string('discount_name'); 

            $table->json('order_items')->nullable();
            $table->decimal('order_subtotal', 10, 2);
            $table->text('request_note')->nullable();
            $table->text('approval_note')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discount_approvals');
    }
};
