<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Banners extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'banners';

    protected $fillable = [
        'name',
        'image',
        'link',
        'description',
        'position',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'position' => 'integer',
    ];
}

