<?php

namespace App\Services;

use App\Models\Page;
use App\Models\PageRevision;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\PageBuilder\Models\PageSection;
use Modules\PageBuilder\Models\SectionTemplate;

/**
 * Compile a JSON page spec into a real Page + PageSections tree in a single
 * transaction. Mirrors the schema emitted by `php artisan page:export`.
 *
 *   $result = PageCompiler::fromJson($json)->compile();
 *   if ($result['ok']) { /* $result['page_id'] / 'slug' / 'url' */ /*}
 *
 * Behaviour:
 *  - Page slug exists → smart-merge by section id and EditorJS block id
 *    (only changed fields are updated, untouched user content is preserved).
 *  - Page slug doesn't exist → create everything fresh, generating ids.
 *  - Each section_type must match an active SectionTemplate slug. Sections
 *    whose section_type is unknown are skipped with a warning in result['warnings'].
 */
class PageCompiler
{
    protected array $spec = [];

    protected array $warnings = [];

    /**
     * Optional metadata that turns this compile into an audited revision.
     * Set via withRevisionMeta() — callers like PageBuilderAgent fill it in
     * so the history UI knows which user / which prompt drove the change.
     */
    protected ?string $revisionSource = null;          // 'ai-create' | 'ai-edit'

    protected ?string $revisionPrompt = null;          // user prompt verbatim

    protected ?int $revisionUserId = null;             // who triggered it

    /**
     * Mark this compile as an auditable change. The compiler will record a
     * pre-state revision (when editing an existing page) and a post-state
     * revision so the editor can roll back.
     */
    public function withRevisionMeta(string $source, ?string $prompt = null, ?int $userId = null): self
    {
        $this->revisionSource = $source;
        $this->revisionPrompt = $prompt;
        $this->revisionUserId = $userId ?? Auth::id();

        return $this;
    }

    public static function fromJson(string $json): self
    {
        $i = new self;
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            throw new \InvalidArgumentException('PageCompiler: input is not valid JSON');
        }
        $i->spec = $decoded;

        return $i;
    }

    public static function fromArray(array $spec): self
    {
        $i = new self;
        $i->spec = $spec;

        return $i;
    }

    public function compile(): array
    {
        try {
            return DB::transaction(function () {
                if (($this->spec['type'] ?? null) !== 'page') {
                    throw new \InvalidArgumentException('Expected spec with type=page');
                }

                $pageData = $this->spec['page'] ?? [];
                if (empty($pageData['slug'])) {
                    throw new \InvalidArgumentException('page.slug is required');
                }

                $existingPage = Page::where('slug', $pageData['slug'])->first();
                $isNew = ! $existingPage;

                // ── REVISION: capture pre-state so we can roll back ─────────
                // Only meaningful when editing — for new pages there's nothing
                // to snapshot beforehand.
                $preRevisionId = null;
                if (! $isNew && $this->revisionSource) {
                    $preRevisionId = $this->recordRevision(
                        page: $existingPage,
                        source: 'pre-'.$this->revisionSource,
                    );
                }

                $page = $existingPage ?? new Page;

                // Map direct columns
                foreach (['title', 'slug', 'status', 'render_mode', 'featured_image', 'body', 'body_css'] as $col) {
                    if (array_key_exists($col, $pageData)) {
                        $page->{$col} = $pageData[$col];
                    }
                }
                // Status vocabulary bridge: the AI spec uses published/draft,
                // but the CMS content-visibility check (FrontendController)
                // expects 'active' to render — anything else 404s. Map so
                // AI-built pages are actually visible on the frontend.
                if (array_key_exists('status', $pageData)) {
                    $page->status = match ($pageData['status']) {
                        'published' => 'active',
                        'draft' => 'draft',
                        default => $pageData['status'],
                    };
                }
                // SEO sub-object → seo_* columns
                foreach (($pageData['seo'] ?? []) as $key => $val) {
                    $col = 'seo_'.$key;
                    if (in_array($col, $page->getFillable(), true)) {
                        $page->{$col} = $val;
                    }
                }
                $page->save();

                // ── ROUTING: ensure a ContentNode exists ────────────────────
                // The frontend router resolves URLs strictly via
                // ContentNode.url_path (content_tree table). A Page row alone
                // is unreachable — without a matching ContentNode it 404s.
                // Mirror the admin EntryForm behaviour: find-or-create a node
                // pointing at this Page so the compiled page is actually live.
                $nodeUrl = $this->syncContentNode($page);

                // Compile sections (recursive)
                $existing = $isNew ? collect() : PageSection::where('sectionable_type', Page::class)
                    ->where('sectionable_id', $page->id)
                    ->get()->keyBy('id');

                $touched = [];
                $this->compileSections(
                    $this->spec['sections'] ?? [],
                    Page::class,
                    $page->id,
                    null,
                    $existing,
                    $touched
                );

                // Sections that existed before but weren't touched → delete
                // (user removed them from the spec).
                //
                // SAFETY: skip auto-delete when we have ANY unknown-type
                // warnings. The AI may have lost / mis-typed sections it
                // couldn't classify, and a delete here would be silent data
                // loss. Better to leave orphan sections than nuke them.
                if (! $isNew && empty($this->warnings)) {
                    $toDelete = $existing->keys()->diff($touched)->all();
                    if (! empty($toDelete)) {
                        PageSection::whereIn('id', $toDelete)->delete();
                    }
                } elseif (! $isNew && ! empty($this->warnings)) {
                    $orphanCount = $existing->keys()->diff($touched)->count();
                    if ($orphanCount > 0) {
                        $this->warnings[] = "{$orphanCount} existing sections were left intact (auto-delete skipped because of warnings above).";
                    }
                }

                // ── REVISION: capture post-state (new baseline) ─────────────
                $postRevisionId = null;
                if ($this->revisionSource) {
                    // Re-fetch the page so we see the just-saved tree.
                    $page->refresh();
                    $postRevisionId = $this->recordRevision(
                        page: $page,
                        source: 'post-'.$this->revisionSource,
                    );
                }

                return [
                    'ok' => true,
                    'created' => $isNew,
                    'page_id' => $page->id,
                    'slug' => $page->slug,
                    'url' => $nodeUrl ?? '/'.ltrim($page->slug, '/'),
                    'sections_touched' => count($touched),
                    'warnings' => $this->warnings,
                    'pre_revision_id' => $preRevisionId,
                    'post_revision_id' => $postRevisionId,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('PageCompiler failed', ['error' => $e->getMessage(), 'spec' => $this->spec]);

            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'warnings' => $this->warnings,
            ];
        }
    }

    /**
     * Snapshot a Page (columns + full sections tree + EditorJS blocks) into
     * the page_revisions table. Used to capture both rollback points (pre-)
     * and new baselines (post-).
     */
    protected function recordRevision(Page $page, string $source): ?int
    {
        try {
            $rev = PageRevision::create([
                'page_id' => $page->id,
                'spec' => $this->exportPageSpec($page),
                'source' => $source,
                'prompt' => $this->revisionPrompt,
                'user_id' => $this->revisionUserId,
            ]);

            return $rev->id;
        } catch (\Throwable $e) {
            // History recording must never break the actual compile. Log and
            // keep going.
            Log::warning('PageCompiler::recordRevision failed', [
                'page_id' => $page->id,
                'source' => $source,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Find-or-create the ContentNode (content_tree) that makes a Page
     * reachable on the frontend. Returns the node's url_path, or null when
     * no "page" template exists to host it (page is still saved, just not
     * routable — recorded as a warning).
     *
     * Mirrors App\Livewire\Admin\TemplateEntries\EntryForm::createContentNode:
     *  - template_id  = the template whose model_class is Page (slug "page")
     *  - content_type = App\Models\Page, content_id = page id
     *  - is_published = page.status === 'published'
     *  - url_path auto-generates from the ContentNode boot() hook.
     */
    protected function syncContentNode(Page $page): ?string
    {
        $pageTemplate = \App\Models\Template::query()
            ->where(fn ($q) => $q->where('model_class', 'Page')->orWhere('model_class', Page::class))
            ->orWhere('slug', 'page')
            ->first();

        if (! $pageTemplate) {
            $this->warnings[] = 'No "page" template found — page saved but not routable (no ContentNode created).';

            return null;
        }

        $node = \App\Models\ContentNode::query()
            ->where('content_type', Page::class)
            ->where('content_id', $page->id)
            ->first();

        $title = $page->title ?: ('Page #'.$page->id);
        // Page.status is normalised to the CMS vocabulary ('active' = visible)
        // by the time we get here. Accept both 'active' and the raw spec
        // value 'published' for safety.
        $isPublished = in_array($page->status ?? 'active', ['active', 'published'], true);

        if (! $node) {
            $node = new \App\Models\ContentNode([
                'template_id' => $pageTemplate->id,
                'content_type' => Page::class,
                'content_id' => $page->id,
                'title' => $title,
                'slug' => $page->slug,
                'is_published' => $isPublished,
            ]);
            $node->save(); // boot() hook generates url_path
        } else {
            // Keep the node in sync if the page's slug / title / status changed.
            $node->title = $title;
            $node->slug = $page->slug;
            $node->is_published = $isPublished;
            $node->save();
        }

        // Bust the URL-resolution cache so the page is immediately reachable.
        try {
            \Illuminate\Support\Facades\Cache::forget('content_node.path.'.$node->url_path);
        } catch (\Throwable $e) {
            // non-fatal
        }

        return $node->url_path;
    }

    /**
     * Export a Page (columns + sections tree + EditorJS blocks) as a JSON
     * spec — same shape PageCompiler::fromArray() consumes.
     */
    protected function exportPageSpec(Page $page): array
    {
        $seo = [];
        foreach ($page->getAttributes() as $col => $val) {
            if (str_starts_with($col, 'seo_')) {
                $seo[substr($col, 4)] = $val;
            }
        }

        return [
            'type' => 'page',
            'page' => array_merge(
                $page->only(['title', 'slug', 'status', 'render_mode', 'featured_image', 'body', 'body_css']),
                ['seo' => $seo]
            ),
            'sections' => $this->exportSectionsTree($page->id, null),
        ];
    }

    /**
     * Recursively dump every section under a page into the spec format.
     */
    protected function exportSectionsTree(int $pageId, ?int $parentId): array
    {
        $rows = PageSection::where('sectionable_type', Page::class)
            ->where('sectionable_id', $pageId)
            ->where('parent_section_id', $parentId)
            ->orderBy('order')
            ->get();

        return $rows->map(function ($s) use ($pageId) {
            $entry = $s->only([
                'id', 'section_type', 'name', 'order', 'scope',
                'is_active', 'is_visible', 'section_template_id', 'edit_mode',
                'content', 'settings', 'css',
            ]);
            $children = $this->exportSectionsTree($pageId, $s->id);
            if (! empty($children)) {
                $entry['children'] = $children;
            }

            return $entry;
        })->all();
    }

    /**
     * Recursively compile a list of section specs into PageSection rows.
     */
    protected function compileSections(
        array $sections,
        string $sectionableType,
        int $sectionableId,
        ?int $parentSectionId,
        \Illuminate\Support\Collection $existing,
        array &$touched
    ): void {
        foreach ($sections as $idx => $spec) {
            // ── Resolve the existing DB section first ────────────────────
            // If the AI passes an `id` that matches a real row, we can rely
            // on it for type / template inference even when the AI dropped
            // those fields from its response.
            $id = $spec['id'] ?? null;
            $existingSection = ($id && $existing->has($id)) ? $existing->get($id) : null;

            // ── Resolve section_type with multiple fallbacks ─────────────
            //   1) explicit spec.section_type
            //   2) existing DB row's section_type (when the AI omitted it)
            //   3) the slug of the SectionTemplate found via spec.section_template_id
            $slug = $spec['section_type']
                ?? ($existingSection->section_type ?? null);

            if (! $slug && ! empty($spec['section_template_id'])) {
                $byId = SectionTemplate::find($spec['section_template_id']);
                if ($byId) {
                    $slug = $byId->slug;
                }
            }

            if (! $slug) {
                // Existing row: keep it untouched rather than overwriting blindly.
                if ($existingSection) {
                    $this->warnings[] = "Section at index {$idx} (#{$existingSection->id}) had no section_type in AI response — kept existing values, no update applied";
                    $touched[] = $existingSection->id;

                    if (! empty($spec['children']) && is_array($spec['children'])) {
                        $this->compileSections($spec['children'], $sectionableType, $sectionableId, $existingSection->id, $existing, $touched);
                    }

                    continue;
                }

                // Brand-new section with content but no type — AUTO-DEFAULT to
                // `wysiwyg` rather than dropping the user's content. The wysiwyg
                // template accepts any HTML/EditorJS payload, so it's a safe
                // catch-all when the AI is sloppy about declaring section_type.
                if ($this->specHasContent($spec)) {
                    $slug = 'wysiwyg';
                    // Hoist any orphan HTML/text the AI dumped at the section
                    // root (instead of nesting it under `content`) into the
                    // proper wysiwyg shape before downstream normalisation
                    // runs. Without this the section gets `section_type=wysiwyg`
                    // but `content=[]` — visually empty.
                    $spec['content'] = $this->extractContentFromSloppyShape($spec);
                    $this->warnings[] = "Section at index {$idx} had no section_type — defaulted to 'wysiwyg' to preserve the AI-generated content";
                } else {
                    $this->warnings[] = "Section at index {$idx} has no section_type and no content — skipped";

                    // Log the FULL spec for debugging — the AI used a shape we
                    // didn't recognise. Without this we're flying blind.
                    Log::warning('PageCompiler skipped a typeless+contentless section', [
                        'index' => $idx,
                        'spec_keys' => array_keys($spec),
                        'spec_preview' => mb_substr(json_encode($spec, JSON_UNESCAPED_UNICODE), 0, 800),
                    ]);

                    continue;
                }
            }

            // Try the slug as-is, then with hyphen/underscore variants. Live data
            // has `custom_html` while the SectionTemplate slug is `custom-html`.
            $template = $this->findTemplateFlexible($slug);

            if (! $template && ! $existingSection) {
                $this->warnings[] = "Unknown section_type '{$slug}' — skipped (no matching SectionTemplate and no existing section to preserve)";

                continue;
            }

            $section = $existingSection ?? new PageSection;

            $section->sectionable_type = $sectionableType;
            $section->sectionable_id = $sectionableId;
            $section->parent_section_id = $parentSectionId;
            // Keep the existing DB section_type when we couldn't find a template
            // but the row exists — this preserves the original (possibly legacy)
            // slug variant rather than overwriting it with the AI's guess.
            $section->section_type = $template ? $slug : ($existingSection->section_type ?? $slug);
            $section->section_template_id = $spec['section_template_id']
                ?? ($template->id ?? $existingSection->section_template_id);
            $section->name = $spec['name'] ?? ($template->name ?? $existingSection->name ?? $slug);
            $section->order = $spec['order'] ?? $idx;
            $section->scope = $spec['scope'] ?? null;
            $section->is_active = $spec['is_active'] ?? true;
            $section->is_visible = $spec['is_visible'] ?? true;
            $section->edit_mode = $spec['edit_mode'] ?? 'simple';
            $section->content = $this->normalizeContent($spec['content'] ?? [], $section->content ?? []);
            $section->settings = $spec['settings'] ?? [];
            if (isset($spec['css'])) {
                $section->css = $spec['css'];
            }
            $section->save();

            $touched[] = $section->id;

            if (! empty($spec['children']) && is_array($spec['children'])) {
                $this->compileSections($spec['children'], $sectionableType, $sectionableId, $section->id, $existing, $touched);
            }
        }
    }

    /**
     * Look up a SectionTemplate by slug, tolerantly. Live DB has section_types
     * with underscores (`custom_html`) while SectionTemplate.slug uses hyphens
     * (`custom-html`). Try the slug as-is, then with separators swapped, before
     * giving up.
     */
    protected function findTemplateFlexible(string $slug): ?SectionTemplate
    {
        $tries = array_unique([
            $slug,
            str_replace('_', '-', $slug),   // custom_html → custom-html
            str_replace('-', '_', $slug),   // custom-html → custom_html
        ]);

        foreach ($tries as $candidate) {
            $template = SectionTemplate::where('slug', $candidate)
                ->where('is_active', true)
                ->first();
            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Normalise content payload. For EditorJS-shaped wysiwyg content, merge
     * blocks by `id` (preserves user-edited blocks when the AI only changed
     * a couple of words) and auto-generate any missing block ids.
     *
     * Defence-in-depth: AI responses (and historical saves) sometimes deliver
     * `content.content` as a JSON-encoded STRING instead of a nested object.
     * If we let that through, Eloquent json_encodes the whole array on save,
     * producing a double-escaped payload whose literal `\n` / `\"` chars show
     * up as text on the frontend. We decode any string-shaped EditorJS
     * payload back to its native array before processing.
     */
    protected function normalizeContent(mixed $newContent, mixed $oldContent): mixed
    {
        $newContent = $this->coerceEditorJsShape($newContent);
        $oldContent = $this->coerceEditorJsShape($oldContent);

        if (! is_array($newContent)) {
            return $newContent;
        }

        // Auto-wrap: a wysiwyg section's content array must have a `content`
        // key holding the EditorJS object — that's the template field name.
        // If the AI returned the EditorJS payload FLAT at the root (i.e. has
        // `blocks` but no `content` key), wrap it so the wysiwyg field +
        // renderer can find it. Without this wrap the visual editor opens
        // empty and any subsequent autosave overwrites the real content.
        if (
            isset($newContent['blocks'])
            && is_array($newContent['blocks'])
            && ! isset($newContent['content'])
        ) {
            $newContent = ['content' => $newContent];
        }

        // The wysiwyg field is `content.content = { blocks: [...] }`
        if (isset($newContent['content']) && is_array($newContent['content'])
            && isset($newContent['content']['blocks'])) {
            $newContent['content'] = $this->mergeEditorJsBlocks(
                $newContent['content'],
                is_array($oldContent['content'] ?? null) ? $oldContent['content'] : []
            );
        }

        return $newContent;
    }

    /**
     * Recursively walk a content payload and decode any EditorJS substructure
     * that arrived as a JSON-encoded STRING. Touches only fields where this
     * actually means something:
     *
     *   - the root itself (if the whole content is a JSON string)
     *   - `content.content` (wysiwyg's inner EditorJS object)
     *   - `content.blocks`  (some templates put EditorJS at the root)
     *
     * String values inside HTML (e.g. data.html) are LEFT ALONE — they are
     * legitimately strings.
     */
    protected function coerceEditorJsShape(mixed $value): mixed
    {
        if (is_string($value)) {
            $trim = ltrim($value);
            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    $value = $decoded;
                }
            }
        }

        if (! is_array($value)) {
            return $value;
        }

        // Recursive case: only descend into the wysiwyg-relevant slots so we
        // don't accidentally decode user HTML that happens to start with `{`.
        if (isset($value['content']) && is_string($value['content'])) {
            $value['content'] = $this->coerceEditorJsShape($value['content']);
        }

        return $value;
    }

    /**
     * Merge EditorJS root by block id. Old blocks present in `old` but absent
     * from `new` are dropped — the AI's "new" represents intent. Missing block
     * ids in `new` get freshly generated 10-char ids so the editor renders.
     */
    protected function mergeEditorJsBlocks(array $new, array $old): array
    {
        $oldById = [];
        foreach (($old['blocks'] ?? []) as $b) {
            if (isset($b['id'])) {
                $oldById[$b['id']] = $b;
            }
        }

        $mergedBlocks = [];
        foreach ($new['blocks'] as $block) {
            if (! isset($block['id']) || $block['id'] === '') {
                $block['id'] = $this->generateBlockId();
            } elseif (isset($oldById[$block['id']])) {
                // Preserve any fields the AI didn't supply (e.g. tunes config
                // the user set in the visual editor)
                $block = array_replace_recursive($oldById[$block['id']], $block);
            }
            $mergedBlocks[] = $block;
        }

        return [
            'time' => $new['time'] ?? (int) (microtime(true) * 1000),
            'blocks' => $mergedBlocks,
            'version' => $new['version'] ?? '2.30.0',
        ];
    }

    /**
     * EditorJS-style 10-char alphanumeric block id.
     */
    protected function generateBlockId(): string
    {
        return Str::random(10);
    }

    /**
     * Pull the actual HTML / EditorJS payload out of whatever weird shape
     * the AI invented and return it wrapped for the wysiwyg field.
     *
     * Sources tried, in priority:
     *   1. spec.content already a sensible array → returned as-is
     *   2. spec.content as a string that LOOKS like HTML → use as html
     *   3. spec.html / spec.text / spec.body
     *   4. ad-hoc top-level string fields BUT ONLY when they look like HTML
     *
     * The "looks like HTML" check (`looksLikeHtml`) is critical — without
     * it we'd happily promote things like Tailwind class strings
     * (`"py-16 md:py-24 bg-slate-50 …"`) into content and render them
     * verbatim on the page.
     */
    protected function extractContentFromSloppyShape(array $spec): array
    {
        // ── 1. Top-priority: EditorJS `blocks` array at section root.
        //      Gemini routinely emits sections shaped like
        //      `{type:"section", content:{class:"…"}, blocks:[…]}`. Promote
        //      the blocks INTO the wysiwyg shape `{content:{blocks:…}}` so
        //      they render. Carry over any class/inner_class metadata.
        if (! empty($spec['blocks']) && is_array($spec['blocks'])) {
            $out = [
                'content' => [
                    'time' => (int) (microtime(true) * 1000),
                    'blocks' => $spec['blocks'],
                    'version' => $spec['version'] ?? '2.30.0',
                ],
            ];
            // Carry styling metadata from a sibling `content` object that
            // only had class fields (Gemini's pattern).
            if (is_array($spec['content'] ?? null)) {
                foreach (['class', 'classes', 'inner_class', 'wrapper', 'wrapper_class', 'container_class'] as $k) {
                    if (! empty($spec['content'][$k])) {
                        $out[$k] = $spec['content'][$k];
                    }
                }
            }

            return $out;
        }

        $c = $spec['content'] ?? null;
        if (is_array($c) && ! empty($c)) {
            return $c;
        }
        if (is_string($c) && $this->looksLikeHtml($c)) {
            return ['html' => $c];
        }

        foreach (['html', 'text', 'body'] as $key) {
            if (! empty($spec[$key]) && is_string($spec[$key]) && $this->looksLikeHtml($spec[$key])) {
                return ['html' => $spec[$key]];
            }
        }

        // Last resort: an ad-hoc field that ACTUALLY looks like HTML.
        $best = '';
        foreach ($spec as $k => $v) {
            if (in_array($k, ['id', 'order', 'name', 'section_type', 'section_template_id',
                'scope', 'edit_mode', 'is_active', 'is_visible', 'parent_section_id',
                'children', 'settings', 'css', 'class', 'classes',
                'wrapper', 'wrapper_class', 'container_class', 'inner_class',
                'type', 'layout'], true)) {
                continue;
            }
            if (is_string($v) && $this->looksLikeHtml($v) && mb_strlen($v) > mb_strlen($best)) {
                $best = $v;
            }
        }
        if ($best !== '') {
            return ['html' => $best];
        }

        return [];
    }

    /**
     * A cheap-but-effective sniff: real HTML content has angle brackets
     * around a tag name. CSS class strings, plain text, slugs, etc. don't.
     * Rejects strings that are clearly Tailwind/CSS classes (no tags, only
     * tokens with hyphens/colons/slashes separated by spaces).
     */
    protected function looksLikeHtml(string $s): bool
    {
        return (bool) preg_match('/<[a-z][a-z0-9]*\b[^>]*>/i', $s);
    }

    /**
     * Heuristic: does this section spec actually carry user-visible content?
     * Looks in EVERY plausible place (a sloppy AI puts the HTML at any
     * level): spec.content (string or array), spec.html, spec.text, spec.body,
     * EditorJS blocks, and even any non-empty scalar leaves.
     */
    protected function specHasContent(array $spec): bool
    {
        // ── 1. EditorJS blocks at section ROOT (Gemini frequently puts
        //      them here, as siblings of `content`) ─────────────────────
        if (! empty($spec['blocks']) && is_array($spec['blocks'])) {
            foreach ($spec['blocks'] as $b) {
                if (! empty($b['data'])) {
                    return true;
                }
            }
        }

        // ── 2. HTML at the section root (sloppy AI shapes) ──────────────
        foreach (['html', 'text', 'body'] as $key) {
            $v = $spec[$key] ?? null;
            if (is_string($v) && $this->looksLikeHtml($v)) {
                return true;
            }
        }

        $c = $spec['content'] ?? null;
        if (is_string($c) && $this->looksLikeHtml($c)) {
            return true;
        }

        // ── 3. Content under the standard `content` wrapper ─────────────
        if (is_array($c)) {
            foreach (['html', 'text', 'body'] as $key) {
                if (is_string($c[$key] ?? null) && $this->looksLikeHtml($c[$key])) {
                    return true;
                }
            }
            if (! empty($c['content']) && is_string($c['content']) && $this->looksLikeHtml($c['content'])) {
                return true;
            }
            if (! empty($c['blocks']) && is_array($c['blocks'])) {
                foreach ($c['blocks'] as $b) {
                    if (! empty($b['data'])) {
                        return true;
                    }
                }
            }
        }

        // ── 4. Ad-hoc keys with real HTML (not just class strings) ──────
        foreach ($spec as $k => $v) {
            if (in_array($k, ['id', 'order', 'name', 'section_type', 'section_template_id',
                'scope', 'edit_mode', 'is_active', 'is_visible', 'parent_section_id',
                'children', 'settings', 'css', 'content', 'class', 'classes',
                'wrapper', 'wrapper_class', 'container_class', 'inner_class',
                'type', 'layout', 'blocks'], true)) {
                continue;
            }
            if (is_string($v) && $this->looksLikeHtml($v)) {
                return true;
            }
        }

        return false;
    }
}
