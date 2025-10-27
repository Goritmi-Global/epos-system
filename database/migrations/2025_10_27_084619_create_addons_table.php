
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            
          
            $table->string('name');
            
          
            $table->foreignId('addon_group_id')
                  ->constrained('addon_groups')
                  ->onDelete('cascade'); 
            
          
            $table->decimal('price', 10, 2)->default(0);
            
            
            $table->text('description')->nullable();
            
           
            $table->enum('status', ['active', 'inactive'])->default('active');
            
           
            $table->integer('sort_order')->default(0);
            
            $table->timestamps();
            $table->softDeletes(); 
            
            
            $table->unique(['name', 'addon_group_id']);
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('addons');
    }
};