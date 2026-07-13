<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Blogs extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'blogs';

    protected $fillable = [
        'name',
        'slug',
        'avatar',
        'comment',
        'content',
        'featuring',
        'views',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->name);
            }
        });

        static::updating(function ($blog) {
            if (empty($blog->slug)) {
                $blog->slug = Str::slug($blog->name);
            }
        });
    }
}
