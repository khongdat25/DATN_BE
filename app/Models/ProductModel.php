<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Images;
use App\Models\Category;
use App\Models\Variant;
use App\Models\rating;
use App\Models\Brand;
use App\Models\Image;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $fillable = [
        'name', 
        'slug', 
        'category_id', 
        'brand_id',
        'description',
        'status', 
        'sold'
    ];
    public $timestamps = false;
    protected $appends = ['avg_rating','min_price'];
    
      public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
    public function variants()
    {
        return $this->hasMany(Variant::class, 'product_id');
    }
     public function rating()
    {
        return $this->hasMany(rating::class, 'product_id');
    }
     public function images()
    {
        return $this->hasMany(Image::class, 'product_id');
    }
    public function getAvgRatingAttribute()
        {
            return round($this->rating()->avg('rating') ?? 0,1);
        }
    public function getMinPriceAttribute()
    {
            return $this->variants()->min('price');
    }

    public function brand()
    {
            return $this->belongsTo(Brand::class);
    }
}
