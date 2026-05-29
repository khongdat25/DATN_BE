<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;
use App\Models\Flashsale;

class Flashsaleitem extends Model
{
    use HasFactory;
    protected $table = 'flash_sales_items';

    public function product(){
        return $this->belongsTo(ProductModel::class);
    }

    public function flashSale(){
        return $this->belongsTo(Flashsale::class, 'flash_sale_id');
    }
}
