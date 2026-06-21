<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use SoftDeletes;
    protected $fillable = [
        'name', 
        'slug', 
        'category_id', 
        'brand_id',
        'description',
        'status', 
        'sold',
        'images'
    ];
    public $timestamps = false;
    protected $appends = ['avg_rating','min_price','image_urls'];
    
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
    public function getAvgRatingAttribute()
        {
            return round($this->rating()->avg('rating') ?? 0,1);
        }
    public function getMinPriceAttribute()
    {
            return $this->variants()->min('price');
    }

    public function getImageUrlsAttribute()
    {
        $images = $this->images;
        if (!is_array($images)) {
            return [];
        }
        return array_map(function ($image) {
            if (empty($image)) {
                return url('/images/placeholder.png');
            }
            if (str_starts_with($image, 'http://') || str_starts_with($image, 'https://') || str_starts_with($image, 'data:')) {
                return $image;
            }
            if (!str_starts_with($image, 'images/') && !str_starts_with($image, '/images/')) {
                return url('images/' . $image);
            }
            return url($image);
        }, $images);
    }

    public function brand()
    {
            return $this->belongsTo(Brand::class);
    }
       protected $casts = [
        'images' => 'array',
    ];
}
