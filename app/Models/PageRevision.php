<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable snapshot of a Page's full spec (page columns + sections tree
 * + EditorJS blocks). Captured before/after every AI compile so the editor
 * can roll back if the AI breaks something.
 *
 * @property int $id
 * @property int $page_id
 * @property array $spec
 * @property string $source pre-ai-create | post-ai-create | pre-ai-edit | post-ai-edit
 * @property string|null $prompt
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 */
class PageRevision extends Model
{
    use HasFactory;

    /** Revisions are immutable: written once, never updated. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'page_id', 'spec', 'source', 'prompt', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'spec' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Human-readable label for the history UI.
     */
    public function sourceLabel(): string
    {
        return match ($this->source) {
            'pre-ai-create' => 'Before creation (AI)',
            'post-ai-create' => 'After creation (AI)',
            'pre-ai-edit' => 'Before edit (AI)',
            'post-ai-edit' => 'After edit (AI)',
            default => $this->source,
        };
    }
}
