<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductModel;
use App\Models\Size;
use App\Models\Color;

class Variant extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'product_variants';
    public $timestamps = false;
     protected $fillable = [
        'product_id',
        'size_id', 
        'color_id', 
        'sku', 
        'stock', 
        'price', 
        'sale',
        'image',
        'status'
    ];
    public function product()
{
    return $this->belongsTo(ProductModel::class, 'product_id');
}

public function size()
{
    return $this->belongsTo(Size::class, 'size_id');
}

public function color()
{
    return $this->belongsTo(Color::class, 'color_id');
}
}
