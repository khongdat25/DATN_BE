<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductModel;
use App\Models\Size;
use App\Models\Color;
use App\Models\Flashsaleitem;
use Carbon\Carbon;

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
    protected $appends = ['flashsale','after_fs'];
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
public function flashSaleItems()
{
    return $this->hasMany(Flashsaleitem::class, 'product_variant_id'); 
}
 public function getFlashsaleAttribute()
    {
        $now = Carbon::now();
        return $this->flashSaleItems()
            ->whereHas('flashSale', function ($query) use ($now) {
                $query->where('status', 1)
                      ->where('start_time', '<=', $now)
                      ->where('end_time', '>=', $now);
            })->exists();
    }
    public function getAfterFsAttribute()
    {
        if (!$this->flashsale) {
            return null; 
        }

        $now = Carbon::now();
        $flashSaleItem = $this->flashSaleItems()
            ->whereHas('flashSale', function ($query) use ($now) {
                $query->where('status', 1)
                      ->where('start_time', '<=', $now)
                      ->where('end_time', '>=', $now);
            })->first();

        if ($flashSaleItem) {
            return max(0, $this->price - (float) $flashSaleItem->discount_value);
        }}
}
