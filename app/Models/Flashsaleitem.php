<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductModel;
use App\Models\Flashsale;

class Flashsaleitem extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'flash_sales_items';
    protected $fillable = ['flash_sale_id', 'product_id', 'discount_value', 'quantity_limit'];

    public $timestamps = false;

    public function product(){
        return $this->belongsTo(ProductModel::class);
    }

    public function flashSale()
    {
        return $this->belongsTo(Flashsale::class, 'flash_sale_id');
    }
    
}
