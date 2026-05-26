<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Product extends Model
{
    protected $table = 'products';
    protected $primaryKey = 'product_id';
    public $incrementing = false;   // PK berupa string (e.g. FUR-BO-10001798)
    protected $keyType = 'string';
    public $timestamps = false;
 
    protected $fillable = [
        'product_id',
        'product_name',
        'category',
        'sub_category',
    ];
 
    public function salesFacts()
    {
        return $this->hasMany(SalesFact::class, 'product_id', 'product_id');
    }
}
