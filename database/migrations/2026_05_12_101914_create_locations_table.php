<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id('location_id');
            $table->string('country', 100);
            $table->string('region', 50);
            $table->string('state', 100);
            $table->string('city', 100);
 
            // Composite unique constraint — satu kombinasi city+state+region harus unik
            $table->unique(['city', 'state', 'region']);
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('locations');
    }
};
