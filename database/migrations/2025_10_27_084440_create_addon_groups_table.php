
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  
    public function up(): void
    {
        Schema::create('addon_groups', function (Blueprint $table) {
            $table->id();
            
          
            $table->string('name')->unique();
            
        
            $table->integer('min_select')->default(0);
     
            $table->integer('max_select')->default(1);
            
           
            $table->text('description')->nullable();
            
            
            $table->enum('status', ['active', 'inactive'])->default('active');
            
            $table->timestamps();
            $table->softDeletes(); 
        });
    }

  
    public function down(): void
    {
        Schema::dropIfExists('addon_groups');
    }
};