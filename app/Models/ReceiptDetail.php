<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReceiptDetail extends Model
{
    use HasFactory;
    protected $table = 'receipt_details';
     protected $fillable = [
        'receipt_id',
        'variant_id',
        'sku',
        'doc_quantity',
        'quantity',
        'price',
        'total_price'
    ];
        public $timestamps = false;
    function receipt()
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
    function variant()
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}
