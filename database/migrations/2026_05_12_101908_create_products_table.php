<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('product_id', 50)->primary(); // e.g. FUR-BO-10001798
            $table->string('product_name', 255);
            $table->string('category', 50);
            $table->string('sub_category', 50);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
