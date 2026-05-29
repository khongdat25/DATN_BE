<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\FlashSaleItem;

class Flashsale extends Model
{
    use HasFactory;
    protected $table = 'flash_sales';
    public function items(){
    return $this->hasMany(FlashSaleItem::class, 'flash_sale_id');
}
}
