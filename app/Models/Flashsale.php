<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Flashsale extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $timestamps = false;

    protected $table = 'flash_sales';
    protected $fillable = ['name', 'start_time', 'end_time', 'status','deleted_at'];
    protected $appends = ['date', 'timeSlot', 'discountPercent'];

    public function items()
    {
        return $this->hasMany(Flashsaleitem::class, 'flash_sale_id');
    }

    public function getDateAttribute()
    {
        return $this->start_time ? Carbon::parse($this->start_time)->format('Y-m-d') : null;
    }

    public function getTimeSlotAttribute()
    {
        if (! $this->start_time || ! $this->end_time) {
            return '';
        }

        return Carbon::parse($this->start_time)->format('H:i').' - '.Carbon::parse($this->end_time)->format('H:i');
    }

    public function getDiscountPercentAttribute()
    {
        if ($this->relationLoaded('items')) {
            $firstItem = $this->items->first();

            return $firstItem ? (int) $firstItem->discount_value : 0;
        }

        return 0;
    }
}
