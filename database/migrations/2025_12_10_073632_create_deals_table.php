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
            $table->boolean('is_taxable')->default(0);
            $table->string('label_color')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Foreign keys
            $table->foreign('upload_id')->references('id')->on('uploads')->onDelete('set null');
            $table->foreignId('subcategory_id')->nullable()->constrained('menu_categories')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_menu_item');
        Schema::dropIfExists('deals');
    }
};
