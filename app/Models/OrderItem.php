<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_item';

    public $timestamps = false;

    protected $fillable = [
        'order_id', 'variant_id', 'quantity', 'price',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function rating()
    {
        return $this->hasOne(rating::class, 'order_item_id');
    }
}
