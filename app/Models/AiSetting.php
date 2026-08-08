<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiSetting extends Model
{
    use HasFactory;

    protected $table = 'ai_settings';

    protected $fillable = [
        'assistant_name',
        'ai_model',
        'temperature',
        'is_enabled',
        'persona_style',
        'store_address',
        'hotline',
        'shipping_policy',
        'system_prompt',
        'size_chart_guide',
    ];

    protected $casts = [
        'temperature' => 'float',
        'is_enabled' => 'boolean',
    ];
}
