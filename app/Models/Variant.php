<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Product;
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
        'flash_sale_id',
        'sku',
        'stock',
        'price',
        'sale_price',
        'image',
        'status',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id');
    }

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id');
    }

    public function flashSale()
    {
        return $this->belongsTo(Flashsale::class, 'flash_sale_id');
    }

    public function getEffectivePriceAttribute()
    {
        $now = \Carbon\Carbon::now();
        if ($this->sale_price !== null && $this->flashSale && (int) $this->flashSale->status === 1) {
            $startTime = \Carbon\Carbon::parse($this->flashSale->start_time);
            $endTime = \Carbon\Carbon::parse($this->flashSale->end_time);
            if ($now->gte($startTime) && $now->lte($endTime)) {
                return (float) $this->sale_price;
            }
        }
        return (float) $this->price;
    }
}
