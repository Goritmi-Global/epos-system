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
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->string('name')->after('id'); 
           $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            $table->boolean('active')->default(true)->after('upload_id');
            $table->unsignedBigInteger('parent_id')->nullable()->after('active');
            $table->decimal('total_value', 15, 2)->default(0)->after('parent_id');
            $table->integer('total_items')->default(0)->after('total_value');
            $table->integer('out_of_stock')->default(0)->after('total_items');
            $table->integer('low_stock')->default(0)->after('out_of_stock');
            $table->integer('in_stock')->default(0)->after('low_stock');

            // Foreign key
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('menu_categories')
                  ->onDelete('cascade');

            // Indexes
            $table->index('parent_id');
            $table->index('active');
            $table->index(['name', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['active']);
            $table->dropIndex(['name', 'parent_id']);

            $table->dropColumn([
                'name',
                'icon',
                'active',
                'parent_id',
                'total_value',
                'total_items',
                'out_of_stock',
                'low_stock',
                'in_stock',
            ]);
        });
    }
};
