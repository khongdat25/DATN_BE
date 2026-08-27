<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    public $timestamps = false; 
    protected $table = 'brands';
    protected $fillable = ['name','description','status'];
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
