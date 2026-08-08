<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'collections';

    protected $fillable = [
        'name',
        'slug',
        'banner',
        'excerpt',
        'description',
        'meta_title',
        'meta_description',
        'focus_keyword',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    protected $appends = ['banner_url'];

    public function products()
    {
        return $this->belongsToMany(ProductModel::class, 'collection_product', 'collection_id', 'product_id')->withTimestamps();
    }

    public function getBannerUrlAttribute()
    {
        $banner = $this->banner;
        if (empty($banner)) {
            return url('/images/placeholder.png');
        }
        if (str_starts_with($banner, 'http://') || str_starts_with($banner, 'https://') || str_starts_with($banner, 'data:')) {
            return $banner;
        }
        if (str_starts_with($banner, '/uploads/') || str_starts_with($banner, 'uploads/')) {
            $path = str_starts_with($banner, '/') ? $banner : '/'.$banner;
            return url($path);
        }
        if (! str_starts_with($banner, 'images/') && ! str_starts_with($banner, '/images/')) {
            return url('images/'.$banner);
        }

        return url($banner);
    }
}
