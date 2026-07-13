<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ProductModel;
use App\Models\Variant;
use App\Models\Flashsale;

class Flashsaleitem extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'flash_sales_items';
    protected $fillable = ['flash_sale_id', 'product_variant_id', 'discount_value', 'quantity_limit'];

    public $timestamps = false; 

    public function productVariant(){
        return $this->belongsTo(Variant::class, 'product_variant_id');
    }

    public function flashSale(){
        return $this->belongsTo(Flashsale::class, 'flash_sale_id');
    }
    
}
