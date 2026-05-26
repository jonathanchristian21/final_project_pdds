<?php
 
namespace App\Models;
 
use MongoDB\Laravel\Eloquent\Model;
 
class Insight extends Model
{
    // Pakai koneksi mongodb, bukan mysql
    protected $connection = 'mongodb';
    protected $collection = 'insights';
 
    protected $fillable = [
        'analysis_type',    // 'profit_analysis' | 'discount_effectiveness' | 'sales_trend'
        'dimensions',       // array — e.g. ['category' => 'Furniture', 'region' => 'West']
        'metrics',          // array — total_sales, total_profit, profit_margin, dll.
        'period_data',      // array of ['year', 'month', 'metrics' => [...]]
        'analysis_result',  // array — kesimpulan analisis
        'created_at',
    ];
 
    protected $casts = [
        'dimensions'      => 'array',
        'metrics'         => 'array',
        'period_data'     => 'array',
        'analysis_result' => 'array',
        'created_at'      => 'datetime',
    ];
 
    // Hanya created_at yang disimpan, tidak butuh updated_at
    const UPDATED_AT = null;
}
