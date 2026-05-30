<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;
use App\Models\Size;
use App\Models\Color;

class Variant extends Model
{
    use HasFactory;
    protected $table = 'product_variants';
    public $timestamps = false;
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
