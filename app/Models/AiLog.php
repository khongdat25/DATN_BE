<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiLog extends Model
{
    use HasFactory;

    protected $table = 'ai_logs';

    protected $fillable = [
        'user_id',
        'session_id',
        'user_name',
        'user_email',
        'user_phone',
        'topic',
        'messages_count',
        'user_message',
        'bot_reply',
        'recommended_product_ids',
        'tokens_used',
        'feedback',
    ];

    protected $casts = [
        'recommended_product_ids' => 'array',
        'messages_count' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
