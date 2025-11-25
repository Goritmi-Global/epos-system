<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_out_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_out_entry_id')->constrained('stock_entries')->cascadeOnDelete();
            $table->foreignId('stock_in_entry_id')->constrained('stock_entries')->cascadeOnDelete();

            // denormalized helpers
            $table->foreignId('product_id')->constrained('inventory_items');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2)->nullable(); 
            $table->date('expiry_date')->nullable();          

            $table->timestamps();
            $table->index(['product_id', 'stock_in_entry_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_out_allocations');
    }
};
