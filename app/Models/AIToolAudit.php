<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIToolAudit extends Model
{
    protected $table = 'ai_tool_audits';

    protected $fillable = [
        'user_id',
        'chat_message_id',
        'tool_name',
        'provider',
        'args',
        'result',
        'undo_payload',
        'confirmed',
        'executed',
        'success',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'args' => 'array',
            'result' => 'array',
            'undo_payload' => 'array',
            'confirmed' => 'boolean',
            'executed' => 'boolean',
            'success' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chatMessage(): BelongsTo
    {
        return $this->belongsTo(AIChatMessage::class, 'chat_message_id');
    }
}
