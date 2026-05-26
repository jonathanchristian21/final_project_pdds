<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class Location extends Model
{
    protected $table = 'locations';
    protected $primaryKey = 'location_id';
    public $timestamps = false;
 
    protected $fillable = [
        'country',
        'region',
        'state',
        'city',
    ];
 
    public function salesFacts()
    {
        return $this->hasMany(SalesFact::class, 'location_id', 'location_id');
    }
}
