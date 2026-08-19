<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use app\Models\ReceiptDetail;
use app\Models\Document_type;
use app\Models\Supplier;

class Receipt extends Model
{
    use HasFactory;
    protected $table = 'receipts';
    protected $fillable = [
        'type_id',
        'update_at',
        'total',
        'supplier_id',
    ];
    public $timestamps = false;

    function receiptDetails()
    {
        return $this->hasMany(ReceiptDetail::class, 'receipt_id');
    }
    function documentType()
    {
        return $this->belongsTo(Document_type::class, 'type_id');
    }
    function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
