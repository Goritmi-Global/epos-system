<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('upload_id')->nullable(); 
            $table->boolean('status')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // Foreign key for upload
            $table->foreign('upload_id')->references('id')->on('uploads')->onDelete('set null');
        });

        // Pivot table for deals and menu items
        Schema::create('deal_menu_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->onDelete('cascade');
            $table->foreignId('menu_item_id')->constrained('menu_items')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_menu_item');
        Schema::dropIfExists('deals');
    }
};
