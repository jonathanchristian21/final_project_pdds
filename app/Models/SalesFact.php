<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class SalesFact extends Model
{
    protected $table = 'sales_facts';
    protected $primaryKey = 'sales_fact_id';
    public $timestamps = false;
 
    protected $fillable = [
        'order_id',
        'product_id',
        'location_id',
        'order_date_id',
        'ship_date_id',
        'sales',
        'quantity',
        'discount',
        'profit',
    ];
 
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'product_id');
    }
 
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id', 'location_id');
    }
 
    public function orderDate()
    {
        return $this->belongsTo(DateDimension::class, 'order_date_id', 'date_id');
    }
 
    public function shipDate()
    {
        return $this->belongsTo(DateDimension::class, 'ship_date_id', 'date_id');
    }
}
