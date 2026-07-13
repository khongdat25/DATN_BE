<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
class Color extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'colors';
    protected $fillable = ['name','description','status', 'color_code'];
    public $timestamps = false; 

    public function variants()
    {
        return $this->hasMany(Variant::class, 'color_id');
    }
}
