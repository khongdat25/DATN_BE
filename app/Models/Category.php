<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;
use App\Models\Variant;

class Category extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'categories';
    public $timestamps = false; 
    protected $fillable = [
        'name', 
        'slug', 
        'description',
        'status'
    ];

    public function products(){
    return $this->hasMany(ProductModel::class);
    }

    public function variants(){
    return $this->hasManyThrough(Variant::class, ProductModel::class, 'category_id', 'product_id');
    }
}
