<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Collab extends Model
{
    use HasFactory;

    protected $table = 'collabs';

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
