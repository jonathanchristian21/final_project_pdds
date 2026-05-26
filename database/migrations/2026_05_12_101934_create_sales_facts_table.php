<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_facts', function (Blueprint $table) {
            $table->id('sales_fact_id');
            $table->string('order_id', 50);
 
            // Foreign keys ke tabel dimensi
            $table->string('product_id', 50);
            $table->unsignedBigInteger('location_id');
            $table->integer('order_date_id');
            $table->integer('ship_date_id');
 
            // Measures
            $table->decimal('sales', 10, 4);
            $table->integer('quantity');
            $table->decimal('discount', 5, 4); // e.g. 0.2 = 20%
            $table->decimal('profit', 10, 4);
 
            // Foreign key constraints
            $table->foreign('product_id')->references('product_id')->on('products');
            $table->foreign('location_id')->references('location_id')->on('locations');
            $table->foreign('order_date_id')->references('date_id')->on('date_dimensions');
            $table->foreign('ship_date_id')->references('date_id')->on('date_dimensions');
 
            // Index untuk query analitik yang sering dipakai
            $table->index('order_id');
            $table->index('order_date_id');
            $table->index('product_id');
            $table->index('location_id');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('sales_facts');
    }
};
