<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Images;
use App\Models\Category;
use App\Models\Variant;
use App\Models\rating;
use App\Models\Collabs;

class ProductModel extends Model
{
    use HasFactory;
    protected $table = 'products';
    protected $appends = ['avg_rating','min_price'];
       public function images()
    {
        return $this->hasMany(Images::class, 'product_id');
    }
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
    public function collab()
        {
            return $this->belongsTo(Collab::class);
        }
}
