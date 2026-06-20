<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Template;
use App\Services\AI\AIManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Generic AI agent that fills/edits Template-driven entity fields. Mirrors
 * PageBuilderAgent but for any entity type that's not Page (Properties,
 * Blog, Services, Maps, …).
 *
 *   $agent = app(EntityFieldsAgent::class);
 *
 *   // Create — AI invents field values from a description
 *   $r = $agent->createEntity(
 *       templateSlug: 'properties',
 *       userPrompt:   'Βίλα στον Άγιο Νικόλαο, 4 υπνοδωμάτια, 250τμ, με θέα στη θάλασσα',
 *   );
 *
 *   // Edit — AI patches only the fields the user mentions
 *   $r = $agent->editEntity(
 *       templateSlug: 'properties',
 *       entityId:     15,
 *       userPrompt:   'Άλλαξε τον τίτλο σε "Πανέμορφη βίλα" και πρόσθεσε ότι έχει 2 parking',
 *   );
 *
 *   $r => ['ok' => bool, 'entity_type' => str, 'entity_id' => int, 'created' => bool, 'pre_revision_id'?, 'post_revision_id'?, 'warnings' => [...]]
 */
class EntityFieldsAgent
{
    public function __construct(protected AIManager $ai) {}

    public function createEntity(string $templateSlug, string $userPrompt): array
    {
        $template = $this->resolveTemplate($templateSlug);
        if (! $template) {
            return ['ok' => false, 'error' => "Template not found: {$templateSlug}"];
        }

        $system = $this->buildSystemPrompt($template, mode: 'create');

        $response = $this->ai->chatWithTools(
            messages: [['role' => 'user', 'content' => $userPrompt]],
            tools: [],
            system: $system,
        );

        $fields = $this->parseJson($response->text ?? '');
        if (! is_array($fields)) {
            return ['ok' => false, 'error' => 'AI did not return a JSON object', 'raw' => mb_substr($response->text ?? '', 0, 500)];
        }

        try {
            return EntityFieldsCompiler::for($this->fullModelName($template), $templateSlug)
                ->withFields($fields)
                ->withRevisionMeta(source: 'ai-create', prompt: $userPrompt, userId: Auth::id())
                ->compile()
                + ['ai_response_preview' => mb_substr(json_encode($fields, JSON_UNESCAPED_UNICODE), 0, 400)];
        } catch (\Throwable $e) {
            Log::warning('EntityFieldsAgent createEntity compile failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Compile failed: '.$e->getMessage(), 'fields' => $fields];
        }
    }

    public function editEntity(string $templateSlug, int $entityId, string $userPrompt): array
    {
        $template = $this->resolveTemplate($templateSlug);
        if (! $template) {
            return ['ok' => false, 'error' => "Template not found: {$templateSlug}"];
        }

        $modelClass = $this->fullModelName($template);
        $entity = $modelClass::find($entityId);
        if (! $entity) {
            return ['ok' => false, 'error' => "Entity not found: {$modelClass}#{$entityId}"];
        }

        // Snapshot the current AI-fillable values to feed the AI as context
        $currentValues = [];
        foreach ($template->fields as $f) {
            if (in_array($f->type, EntityFieldsCompiler::AI_FILLABLE_TYPES, true)
                && array_key_exists($f->name, $entity->getAttributes())) {
                $currentValues[$f->name] = $entity->{$f->name};
            }
        }

        $system = $this->buildSystemPrompt($template, mode: 'edit');

        $response = $this->ai->chatWithTools(
            messages: [
                ['role' => 'user', 'content' => "CURRENT FIELD VALUES:\n".json_encode($currentValues, JSON_UNESCAPED_UNICODE)],
                ['role' => 'assistant', 'content' => 'Understood. I have the current values. What edit should I apply?'],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            tools: [],
            system: $system,
        );

        $patch = $this->parseJson($response->text ?? '');
        if (! is_array($patch)) {
            return ['ok' => false, 'error' => 'AI did not return a JSON object'];
        }

        try {
            return EntityFieldsCompiler::for($this->fullModelName($template), $templateSlug)
                ->withFields($patch)
                ->withEntityId($entityId)
                ->withRevisionMeta(source: 'ai-edit', prompt: $userPrompt, userId: Auth::id())
                ->compile()
                + ['ai_response_preview' => mb_substr(json_encode($patch, JSON_UNESCAPED_UNICODE), 0, 400)];
        } catch (\Throwable $e) {
            Log::warning('EntityFieldsAgent editEntity compile failed', ['error' => $e->getMessage()]);

            return ['ok' => false, 'error' => 'Compile failed: '.$e->getMessage(), 'fields' => $patch];
        }
    }

    /* ── internals ───────────────────────────────────────────────────── */
    protected function resolveTemplate(string $slug): ?Template
    {
        return Template::with('fields')->where('slug', $slug)->first();
    }

    /**
     * Class identifier we pass to EntityFieldsCompiler::for().
     * If the template stores a short name like "Property", we expand it to
     * the conventional App\Models\Property. If it stores a full FQN (e.g.
     * Modules\Properties\Models\Property), we hand it through verbatim so
     * module-namespaced models work too.
     */
    protected function fullModelName(Template $template): string
    {
        $mc = $template->model_class;

        return str_contains($mc, '\\') ? $mc : "App\\Models\\{$mc}";
    }

    /**
     * Build the system message: configurable prompt from Settings + the
     * resolved template's field schema (only AI-fillable fields).
     */
    protected function buildSystemPrompt(Template $template, string $mode): string
    {
        $base = Setting::get('prompt_entity_fields_filler', config('ai-prompts.entity_fields_filler', ''));

        $schema = [];
        foreach ($template->fields as $f) {
            if (! in_array($f->type, EntityFieldsCompiler::AI_FILLABLE_TYPES, true)) {
                continue;
            }
            $entry = [
                'name' => $f->name,
                'label' => $f->label,
                'type' => $f->type,
                'is_required' => (bool) $f->is_required,
            ];
            if (! empty($f->description)) {
                $entry['description'] = $f->description;
            }
            if (! empty($f->options)) {
                $entry['options'] = $f->options;
            }
            $schema[] = $entry;
        }

        $context = "\n\nTEMPLATE: {$template->name} (slug: {$template->slug})\n"
                 ."FIELD SCHEMA (only these names are valid):\n"
                 .json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                 ."\n\nMODE: {$mode}";

        return trim($base.$context);
    }

    protected function parseJson(string $text): mixed
    {
        $text = trim($text);
        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/s', $text, $m)) {
            $text = trim($m[1]);
        }

        return json_decode($text, true);
    }
}
