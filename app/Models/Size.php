<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProductModel;
use App\Models\Variant;
use App\Models\Color;
class Size extends Model
{
    use HasFactory;
    protected $table = 'sizes';
}
