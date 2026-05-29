<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;

class Category extends Model
{
    use HasFactory;
    protected $table = 'categories';
    public function products(){
    return $this->hasMany(ProductModel::class);
}
}
