<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('date_dimensions', function (Blueprint $table) {
            $table->integer('date_id')->primary(); // Format YYYYMMDD, e.g. 20160811
            $table->date('full_date');
            $table->tinyInteger('day');
            $table->tinyInteger('month');
            $table->string('month_name', 20);
            $table->tinyInteger('quarter');
            $table->smallInteger('year');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('date_dimensions');
    }
};
