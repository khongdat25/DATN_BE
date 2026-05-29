<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;

class Brand extends Model
{
    use HasFactory;
    protected $table = 'brands';
    
    public function products()
        {
            return $this->hasMany(ProductModel::class);
        }
}