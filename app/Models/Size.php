<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
class Size extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'sizes';
    public $timestamps = false; 
    protected $fillable = ['name','description','status'];
      public function variants()
    {
        return $this->hasMany(Variant::class, 'size_id');
    }
}
