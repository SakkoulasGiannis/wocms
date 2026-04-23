<?php

namespace App\Services\AI\Tools;

use App\Models\Blog;
use App\Models\ContentNode;
use App\Models\Home;
use App\Models\Page;
use App\Models\Template;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateContentEntryTool extends BaseTool
{
    /**
     * Map of built-in template slugs to their Eloquent Model classes.
     *
     * @var array<string, class-string>
     */
    protected const MODEL_MAP = [
        'blog' => Blog::class,
        'page' => Page::class,
        'home' => Home::class,
    ];

    public function name(): string
    {
        return 'create_content_entry';
    }

    public function label(): string
    {
        return 'Create Content Entry';
    }

    public function description(): string
    {
        return 'Create a new content entry (Blog post, Page, or Home node) with an auto-generated unique slug and an optional ContentNode record for URL routing. Use this when the user wants to create a new page, blog post, or home entry on the site.';
    }

    public function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'template_slug' => [
                    'type' => 'string',
                    'description' => "The template slug. Common values: 'blog', 'page', 'home'. Also accepts any existing Template slug.",
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Title of the entry (also used to derive the slug).',
                    'minLength' => 1,
                ],
                'body' => [
                    'type' => 'string',
                    'description' => 'HTML body content of the entry.',
                ],
                'excerpt' => [
                    'type' => 'string',
                    'description' => 'Optional short summary (used for blog posts).',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'draft'],
                    'description' => "Entry status: 'active' (published) or 'draft'. Defaults to 'active'.",
                ],
            ],
            'required' => ['template_slug', 'title'],
            'additionalProperties' => false,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'template_slug' => 'required|string',
            'title' => 'required|string|min:1',
            'body' => 'sometimes|string',
            'excerpt' => 'sometimes|string',
            'status' => 'sometimes|string|in:active,draft',
        ];
    }

    public function previewMessage(array $args): string
    {
        $type = $args['template_slug'] ?? 'entry';
        $title = $args['title'] ?? '(χωρίς τίτλο)';
        $slug = Str::slug($title);
        $url = $this->computeUrlPath($type, $slug);

        return "Θα δημιουργήσω {$type} '{$title}' στο URL {$url}";
    }

    public function execute(array $args): array
    {
        $errors = $this->validate($args);
        if (! empty($errors)) {
            return $this->error('Validation failed: '.implode(', ', $errors));
        }

        $templateSlug = $args['template_slug'];
        $title = $args['title'];
        $body = $args['body'] ?? '';
        $excerpt = $args['excerpt'] ?? null;
        $status = $args['status'] ?? 'active';

        // Resolve Template record (may be null for pure model-based slugs)
        $template = Template::where('slug', $templateSlug)->first();

        // Resolve model class
        $modelClass = self::MODEL_MAP[$templateSlug] ?? null;
        if (! $modelClass && $template && $template->model_class) {
            $modelClass = $template->model_class;
        }

        if (! $modelClass) {
            return $this->error("Δεν βρέθηκε model για το template '{$templateSlug}'.");
        }

        // Prevent duplicate Home entries (only one Home allowed)
        if ($modelClass === Home::class && Home::count() > 0) {
            return $this->error('Υπάρχει ήδη Home entry. Δεν επιτρέπεται η δημιουργία δεύτερου.');
        }

        // Generate unique slug
        $slug = $this->generateUniqueSlug($modelClass, Str::slug($title));

        $createData = [
            'title' => $title,
            'slug' => $slug,
            'body' => $body,
            'status' => $status,
        ];

        if ($excerpt !== null) {
            $createData['excerpt'] = $excerpt;
        }

        try {
            $entry = DB::transaction(function () use ($modelClass, $createData, $template, $templateSlug, $title, $slug) {
                /** @var \Illuminate\Database\Eloquent\Model $entry */
                $entry = $this->createModelEntry($modelClass, $createData);

                // Create ContentNode for URL routing
                $nodeData = [
                    'template_id' => $template?->id,
                    'parent_id' => null,
                    'content_type' => $modelClass,
                    'content_id' => $entry->id,
                    'title' => $title,
                    'slug' => $templateSlug === 'home' ? '' : $slug,
                    'is_published' => true,
                    'sort_order' => 0,
                ];

                // For Home, explicitly set url_path='/'
                if ($modelClass === Home::class) {
                    $nodeData['url_path'] = '/';
                    $nodeData['tree_path'] = '/';
                    $nodeData['is_default'] = true;
                }

                $node = ContentNode::create($nodeData);

                return ['entry' => $entry, 'node' => $node];
            });
        } catch (\Throwable $e) {
            return $this->error('❌ Σφάλμα κατά τη δημιουργία: '.$e->getMessage());
        }

        /** @var \Illuminate\Database\Eloquent\Model $savedEntry */
        $savedEntry = $entry['entry'];
        /** @var ContentNode $node */
        $node = $entry['node'];

        $url = url($node->url_path ?: '/');
        $editUrl = $this->buildEditUrl($templateSlug, $savedEntry->id);

        $modelBasename = class_basename($modelClass);

        return $this->success(
            "✅ Δημιούργησα το {$templateSlug} '{$title}'",
            [
                'id' => $savedEntry->id,
                'title' => $title,
                'url' => $url,
                'edit_url' => $editUrl,
            ],
            [
                'model' => $modelBasename,
                'id' => $savedEntry->id,
                'node_id' => $node->id,
            ]
        );
    }

    /**
     * Create the model entry using create() for Eloquent models.
     */
    protected function createModelEntry(string $modelClass, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Set published_at if status is active (scopes rely on it)
        if (($data['status'] ?? null) === 'active') {
            $data['published_at'] = now();
        }

        /** @var \Illuminate\Database\Eloquent\Model $instance */
        $instance = new $modelClass;

        // Only fill attributes that the model actually fillable-accepts.
        $fillable = $instance->getFillable();
        $fillData = [];
        foreach ($data as $key => $value) {
            if (in_array($key, $fillable, true)) {
                $fillData[$key] = $value;
            }
        }

        $instance->fill($fillData);

        // Some models (like Home) may not have `excerpt`/`body` as fillable depending on migration;
        // attributes that are not fillable are silently dropped by fill().
        $instance->save();

        return $instance;
    }

    /**
     * Ensure slug is unique for the given model class, appending -2, -3, etc. on conflict.
     */
    protected function generateUniqueSlug(string $modelClass, string $baseSlug): string
    {
        if ($baseSlug === '') {
            $baseSlug = 'entry';
        }

        $slug = $baseSlug;
        $counter = 2;

        while ($modelClass::where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Compute the URL path for a given template + slug combination.
     */
    protected function computeUrlPath(string $templateSlug, string $slug): string
    {
        if ($templateSlug === 'home') {
            return '/';
        }

        return '/'.$slug;
    }

    /**
     * Build an admin edit URL for an entry.
     */
    protected function buildEditUrl(string $templateSlug, int $entryId): string
    {
        try {
            return route('admin.template-entries.edit', [
                'templateSlug' => $templateSlug,
                'entryId' => $entryId,
            ]);
        } catch (\Throwable $e) {
            return url("/admin/{$templateSlug}/{$entryId}/edit");
        }
    }
}
