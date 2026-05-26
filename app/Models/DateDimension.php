<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class DateDimension extends Model
{
    protected $table = 'date_dimensions';
    protected $primaryKey = 'date_id';
    public $incrementing = false;   // PK bukan auto-increment (YYYYMMDD)
    protected $keyType = 'integer';
    public $timestamps = false;     // Tabel dimensi tidak butuh created_at/updated_at
 
    protected $fillable = [
        'date_id',
        'full_date',
        'day',
        'month',
        'month_name',
        'quarter',
        'year',
    ];
}
