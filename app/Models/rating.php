<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class rating extends Model
{
    use HasFactory;

    protected $table = 'rating';

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'product_id', 'order_item_id', 'rating', 'comment', 'reply', 'status', 'images',
    ];

    protected $casts = [
        'images' => 'array',
    ];

    protected $appends = ['image_urls'];

    public function getImageUrlsAttribute()
    {
        $imgs = $this->images;
        if (is_string($imgs)) {
            $imgs = json_decode($imgs, true);
        }
        if (! is_array($imgs)) {
            return [];
        }

        return array_map(function ($img) {
            if (empty($img)) return '';
            if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://') || str_starts_with($img, 'data:')) {
                return $img;
            }
            if (! str_starts_with($img, 'images/') && ! str_starts_with($img, '/images/')) {
                return url('images/reviews/' . $img);
            }
            return url($img);
        }, $imgs);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
