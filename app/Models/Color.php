<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;
use App\Models\Variant;
use App\Models\Color;
class Color extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $table = 'colors';
    protected $fillable = ['name','description','status', 'color_code'];
    public $timestamps = false; 
      public function variants()
    {
        return $this->hasMany(Variant::class, 'size_id');
    }
}
