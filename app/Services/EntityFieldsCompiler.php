<?php

namespace App\Services;

use App\Models\EntityRevision;
use App\Models\Template;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Compile an array of field values into a real Eloquent entity (any model
 * defined by a Template — Property, Blog Post, Service, etc.). Same audit
 * pattern as PageCompiler: capture pre + post snapshots so every AI op is
 * undoable.
 *
 *   $r = EntityFieldsCompiler::for('Property', $templateSlug)
 *       ->withFields(['title' => 'Villa Knossos', 'bedrooms' => 4])
 *       ->withRevisionMeta(source: 'ai-create', prompt: $userPrompt)
 *       ->compile();
 *
 *   $r => ['ok' => bool, 'entity_id' => int, 'entity_type' => str, 'pre_revision_id'?, 'post_revision_id'?, 'created' => bool, 'warnings' => [...]]
 *
 * Safety rails:
 *  - Only fillable columns are written.
 *  - File / gallery / repeater fields are NEVER touched (left to the user).
 *  - Unknown field names are skipped with a warning.
 *  - All in a single DB transaction.
 */
class EntityFieldsCompiler
{
    /**
     * Field types the AI is allowed to fill. Image/gallery/file uploads and
     * repeater groups stay user-controlled — they're hard to express in JSON
     * and easy to corrupt.
     */
    public const AI_FILLABLE_TYPES = [
        'text', 'textarea', 'string',
        'email', 'url', 'slug', 'color',
        'number', 'integer', 'decimal', 'float',
        'date', 'datetime', 'time',
        'checkbox', 'boolean', 'switch',
        'select', 'radio',
        'wysiwyg', 'html', 'grapejs', 'editorjs',
        'tags', 'tag',
    ];

    protected string $modelClass;

    protected ?Template $template = null;

    protected array $fields = [];

    protected ?int $entityId = null;

    protected ?string $revisionSource = null;

    protected ?string $revisionPrompt = null;

    protected ?int $revisionUserId = null;

    protected array $warnings = [];

    public static function for(string $modelShort, string $templateSlug): self
    {
        $self = new self;
        $fqn = str_contains($modelShort, '\\') ? $modelShort : "App\\Models\\{$modelShort}";
        if (! class_exists($fqn)) {
            throw new \InvalidArgumentException("Model class not found: {$fqn}");
        }
        $self->modelClass = $fqn;
        $self->template = Template::with('fields')->where('slug', $templateSlug)->first();
        if (! $self->template) {
            throw new \InvalidArgumentException("Template not found: {$templateSlug}");
        }

        return $self;
    }

    public function withFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    public function withEntityId(?int $entityId): self
    {
        $this->entityId = $entityId;

        return $this;
    }

    public function withRevisionMeta(string $source, ?string $prompt = null, ?int $userId = null): self
    {
        $this->revisionSource = $source;
        $this->revisionPrompt = $prompt;
        $this->revisionUserId = $userId ?? Auth::id();

        return $this;
    }

    public function compile(): array
    {
        try {
            return DB::transaction(function () {
                /** @var Model $existing */
                $existing = $this->entityId
                    ? $this->modelClass::find($this->entityId)
                    : null;
                $isNew = ! $existing;

                // ── REVISION: pre-state for edit ─────────────────────────
                $preRevisionId = null;
                if (! $isNew && $this->revisionSource) {
                    $preRevisionId = $this->recordRevision($existing, 'pre-'.$this->revisionSource);
                }

                $entity = $existing ?? new $this->modelClass;

                // Filter incoming fields against the template schema + the
                // model's fillable list. This prevents the AI from writing
                // weird/dangerous columns it shouldn't.
                $safeValues = $this->filterValues($entity, $this->fields);

                // Auto-generate slug from title/name if missing on create
                if ($isNew && empty($safeValues['slug'])) {
                    $titleSource = $safeValues['title'] ?? $safeValues['name'] ?? null;
                    if ($titleSource && in_array('slug', $entity->getFillable(), true)) {
                        $safeValues['slug'] = $this->uniqueSlug($titleSource);
                    }
                }

                foreach ($safeValues as $key => $val) {
                    $entity->{$key} = $val;
                }
                $entity->save();

                // ── REVISION: post-state ─────────────────────────────────
                $postRevisionId = null;
                if ($this->revisionSource) {
                    $entity->refresh();
                    $postRevisionId = $this->recordRevision($entity, 'post-'.$this->revisionSource);
                }

                return [
                    'ok' => true,
                    'created' => $isNew,
                    'entity_type' => $this->modelClass,
                    'entity_id' => $entity->id,
                    'template_slug' => $this->template->slug,
                    'fields_written' => array_keys($safeValues),
                    'pre_revision_id' => $preRevisionId,
                    'post_revision_id' => $postRevisionId,
                    'warnings' => $this->warnings,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('EntityFieldsCompiler failed', [
                'model' => $this->modelClass,
                'entity' => $this->entityId,
                'error' => $e->getMessage(),
                'fields' => array_keys($this->fields),
            ]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'warnings' => $this->warnings,
            ];
        }
    }

    /**
     * Keep only field values that (1) match an AI-fillable template field
     * and (2) are part of the model's fillable list. Everything else is
     * skipped with a warning.
     */
    protected function filterValues(Model $entity, array $incoming): array
    {
        $fillable = $entity->getFillable();
        $schemaByName = [];
        foreach ($this->template->fields as $f) {
            $schemaByName[$f->name] = $f;
        }

        $out = [];
        foreach ($incoming as $name => $val) {
            if (! isset($schemaByName[$name])) {
                $this->warnings[] = "Skipped unknown field '{$name}' — not in template schema";

                continue;
            }
            $type = $schemaByName[$name]->type;
            if (! in_array($type, self::AI_FILLABLE_TYPES, true)) {
                $this->warnings[] = "Skipped field '{$name}' — type '{$type}' is not AI-fillable";

                continue;
            }
            if (! empty($fillable) && ! in_array($name, $fillable, true)) {
                $this->warnings[] = "Skipped field '{$name}' — not in model fillable list";

                continue;
            }

            $out[$name] = $this->castValue($val, $type);
        }

        return $out;
    }

    protected function castValue(mixed $val, string $type): mixed
    {
        return match ($type) {
            'integer' => is_numeric($val) ? (int) $val : null,
            'number', 'decimal', 'float' => is_numeric($val) ? (float) $val : null,
            'boolean', 'checkbox', 'switch' => (bool) filter_var($val, FILTER_VALIDATE_BOOL),
            'tags', 'tag' => is_array($val) ? $val : (is_string($val) ? array_filter(array_map('trim', explode(',', $val))) : []),
            'editorjs' => is_array($val) ? $val : (is_string($val) ? json_decode($val, true) ?? $val : $val),
            default => is_scalar($val) ? (string) $val : (is_array($val) ? json_encode($val) : null),
        };
    }

    protected function uniqueSlug(string $source): string
    {
        $base = Str::slug($source);
        if ($base === '') {
            $base = 'item-'.substr(md5((string) microtime(true)), 0, 6);
        }
        $candidate = $base;
        $i = 1;
        while ($this->modelClass::where('slug', $candidate)->exists()) {
            $candidate = $base.'-'.(++$i);
        }

        return $candidate;
    }

    /**
     * Snapshot the entity's current AI-fillable field values into entity_revisions.
     */
    protected function recordRevision(Model $entity, string $source): ?int
    {
        try {
            $snapshot = [];
            foreach ($this->template->fields as $f) {
                if (in_array($f->type, self::AI_FILLABLE_TYPES, true)
                    && array_key_exists($f->name, $entity->getAttributes())) {
                    $snapshot[$f->name] = $entity->{$f->name};
                }
            }

            $rev = EntityRevision::create([
                'entity_type' => $this->modelClass,
                'entity_id' => $entity->id,
                'fields_json' => $snapshot,
                'source' => $source,
                'prompt' => $this->revisionPrompt,
                'user_id' => $this->revisionUserId,
            ]);

            return $rev->id;
        } catch (\Throwable $e) {
            Log::warning('EntityFieldsCompiler::recordRevision failed', [
                'model' => $this->modelClass,
                'entity' => $entity->id,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
