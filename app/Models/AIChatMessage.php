<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIChatMessage extends Model
{
    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'user_id',
        'role',
        'message',
        'intent',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
