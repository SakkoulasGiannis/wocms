<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Polymorphic snapshot of a Template entity (Property, Blog post, Service, …)
 * captured before/after every AI fields-fill operation. Mirrors PageRevision
 * but for non-Page entities — pages still have their own dedicated
 * page_revisions table because their spec is much richer (sections + EditorJS).
 *
 * @property int $id
 * @property string $entity_type
 * @property int $entity_id
 * @property array $fields_json
 * @property string $source pre-ai-create | post-ai-create | pre-ai-edit | post-ai-edit
 * @property string|null $prompt
 * @property int|null $user_id
 * @property \Carbon\Carbon $created_at
 */
class EntityRevision extends Model
{
    use HasFactory;

    /** Revisions are immutable. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'entity_type', 'entity_id', 'fields_json',
        'source', 'prompt', 'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fields_json' => 'array',
        ];
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

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
