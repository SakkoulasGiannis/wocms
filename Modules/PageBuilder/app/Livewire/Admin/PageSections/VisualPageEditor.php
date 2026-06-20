<?php

namespace Modules\PageBuilder\Livewire\Admin\PageSections;

use App\Models\ContentNode;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\PageBuilder\Models\PageSection;
use Modules\PageBuilder\Models\SectionTemplate;
use Modules\PageBuilder\Services\PageImporter;
use Modules\PageBuilder\Services\PageSerializer;

class VisualPageEditor extends Component
{
    use WithFileUploads;

    public string $sectionableType = '';

    public int $sectionableId = 0;

    /**
     * 'listing' = designing the INDEX page (/completed-villas)
     * 'entry'   = designing the SINGLE-ENTRY page (/completed-villas/{slug})
     * null      = legacy per-entity mode (Home, Page, etc.) — no scope filter
     */
    public ?string $scope = null;

    public string $pageTitle = '';

    public string $previewUrl = '';

    /**
     * The in-memory DRAFT representation of the page's sections. Every mutating
     * action (add / edit / move / reorder / delete / duplicate / toggle) mutates
     * THIS array only — nothing is persisted until saveDraft() reconciles it to
     * the database in a single transaction.
     *
     * Each entry is a flat array shape:
     * id, parent_section_id, section_template_id, section_type, scope, name,
     * order, is_active, is_visible, content (array), settings (array).
     *
     * Brand-new sections that have no DB row yet carry a NEGATIVE temp id so
     * children/order survive (in data-id, selectSection(int), drag payloads…)
     * until saveDraft() assigns the real auto-increment id.
     *
     * @var array<int, array<string, mixed>>
     */
    public array $sections = [];

    /**
     * True whenever the draft has unsaved mutations. Drives the "unsaved changes"
     * indicator and gates the Save / Discard buttons.
     */
    public bool $isDirty = false;

    /**
     * Next temp id to hand to a not-yet-saved section. Counts DOWN from -1 so
     * temp ids never collide with real (positive) auto-increment ids.
     */
    public int $nextTempId = -1;

    public ?int $selectedSectionId = null;

    public bool $showAddPanel = false;

    public ?int $addingChildOfSectionId = null;

    public ?int $editingSectionId = null;

    public ?int $selectedTemplateId = null;

    public string $sectionName = '';

    public array $sectionContent = [];

    public array $sectionSettings = [];

    public array $sectionImageUploads = [];

    public array $availableTemplates = [];

    public string $backUrl = '';

    // Undo / Redo
    public array $historyStack = [];

    public int $historyIndex = -1;

    public bool $veTailwindCdn = false;

    // Media library
    public bool $showMediaLibrary = false;

    public string $mediaTargetField = '';

    public function mount(string $sectionableType, int $sectionableId): void
    {
        $this->veTailwindCdn = (bool) Setting::get('ve_tailwind_cdn', false);
        $this->backUrl = url()->previous(route('admin.dashboard'));
        $this->sectionableType = str_replace('-', '\\', $sectionableType);
        $this->sectionableId = $sectionableId;

        // Capture ?scope=listing|entry. Only applies when designing a Template
        // (other models retain per-entity behavior — scope stays null).
        $scopeParam = request()->query('scope');
        if ($this->sectionableType === \App\Models\Template::class && in_array($scopeParam, ['listing', 'entry'], true)) {
            $this->scope = $scopeParam;
        }

        $this->pageTitle = $this->resolveTitle();
        $this->previewUrl = $this->resolvePreviewUrl().'?ve=1';
        $this->loadSections();
        $this->availableTemplates = SectionTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->toArray();

        // Capture initial state for undo
        $this->pushHistory();
    }

    protected function resolveTitle(): string
    {
        if (class_exists($this->sectionableType)) {
            $model = $this->sectionableType::find($this->sectionableId);
            if ($model) {
                $name = $model->title ?? $model->name ?? '#'.$this->sectionableId;
                // Suffix the scope so the page heading is unambiguous
                if ($this->scope === 'listing') {
                    return $name.' — Listing page design';
                }
                if ($this->scope === 'entry') {
                    return $name.' — Entry page design';
                }

                return $name;
            }
        }

        return '#'.$this->sectionableId;
    }

    protected function resolvePreviewUrl(): string
    {
        // Template-design mode → preview must match the SCOPE being designed.
        // Listing → /{template_slug}, Entry → first entry of that template.
        if ($this->sectionableType === \App\Models\Template::class) {
            $tpl = \App\Models\Template::find($this->sectionableId);
            if (! $tpl) {
                return '/';
            }

            if ($this->scope === 'entry') {
                // Try to find the first entry of this template
                $modelClass = $tpl->model_class;
                if ($modelClass && ! str_contains($modelClass, '\\')) {
                    $modelClass = 'App\\Models\\'.$modelClass;
                }
                if ($modelClass && class_exists($modelClass)) {
                    try {
                        $entry = $modelClass::query()->latest()->first();
                        if ($entry && isset($entry->slug) && $tpl->slug) {
                            return '/'.$tpl->slug.'/'.$entry->slug;
                        }
                    } catch (\Throwable $e) {
                    }
                }
                // Fall back to ContentNode lookup for entries of this template
                $node = ContentNode::where('template_id', $tpl->id)
                    ->whereNotNull('content_id')
                    ->latest()
                    ->first();
                if ($node) {
                    return $node->url_path;
                }
            }

            // Listing scope (or fallback) → the template's slug root
            return '/'.ltrim($tpl->slug, '/');
        }

        // Legacy: per-entity mode — use ContentNode url_path if available
        $node = ContentNode::where('content_type', $this->sectionableType)
            ->where('content_id', $this->sectionableId)
            ->first();

        if ($node) {
            return $node->url_path;
        }

        if (! class_exists($this->sectionableType)) {
            return '/';
        }

        $model = $this->sectionableType::find($this->sectionableId);

        if (! $model) {
            return '/';
        }

        return isset($model->slug) ? '/'.$model->slug : '/';
    }

    /**
     * (Re)build the in-memory draft from the database. This is also the
     * "Discard / Revert" path — it throws away any unsaved mutations and
     * resets the dirty flag + temp-id counter.
     */
    public function loadSections(): void
    {
        $query = PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId);

        // Filter by scope when designing a Template (listing/entry). Other modes
        // (Home, Page) leave $this->scope = null and see all their sections.
        if ($this->scope !== null) {
            $query->where('scope', $this->scope);
        }

        // Secondary sort by id so ties on `order` stay deterministic.
        $this->sections = $query->orderBy('order')->orderBy('id')->get()
            ->map(fn (PageSection $s) => $this->toDraftEntry($s))
            ->all();

        $this->isDirty = false;
        $this->nextTempId = -1;
    }

    /**
     * Normalize a PageSection row into the flat draft shape used by the UI.
     *
     * @return array<string, mixed>
     */
    protected function toDraftEntry(PageSection $section): array
    {
        return [
            'id' => $section->id,
            'parent_section_id' => $section->parent_section_id,
            'section_template_id' => $section->section_template_id,
            'section_type' => $section->section_type,
            'scope' => $section->scope,
            'name' => $section->name,
            'order' => (int) $section->order,
            'is_active' => (bool) $section->is_active,
            'is_visible' => $section->is_visible ?? true,
            'content' => $section->content ?? [],
            'settings' => $section->settings ?? [],
        ];
    }

    // ─── Draft helpers ───────────────────────────────────────────────────────

    /**
     * Locate the array index of a draft section by its (possibly temp) id.
     */
    protected function draftIndex(int $sectionId): ?int
    {
        foreach ($this->sections as $i => $s) {
            if ((int) $s['id'] === $sectionId) {
                return $i;
            }
        }

        return null;
    }

    /**
     * The next `order` value (1-based) for a given parent group in the draft.
     */
    protected function nextOrderForParent(?int $parentId): int
    {
        $max = 0;
        foreach ($this->sections as $s) {
            if (($s['parent_section_id'] ?? null) === $parentId && (int) $s['order'] > $max) {
                $max = (int) $s['order'];
            }
        }

        return $max + 1;
    }

    /**
     * Re-number sibling `order` values 1..N for a parent group in the draft,
     * keeping $movedId at slot $movedOrder (1-based). With $movedId = null the
     * existing order is simply re-densified.
     */
    protected function reflowDraftSiblings(?int $parentId, ?int $movedId, ?int $movedOrder): void
    {
        $siblingIdx = [];
        foreach ($this->sections as $i => $s) {
            if (($s['parent_section_id'] ?? null) === $parentId) {
                $siblingIdx[] = $i;
            }
        }

        usort($siblingIdx, function ($a, $b) {
            $cmp = (int) $this->sections[$a]['order'] <=> (int) $this->sections[$b]['order'];

            return $cmp !== 0 ? $cmp : ((int) $this->sections[$a]['id'] <=> (int) $this->sections[$b]['id']);
        });

        if ($movedId !== null && $movedOrder !== null) {
            $movedPos = null;
            foreach ($siblingIdx as $k => $i) {
                if ((int) $this->sections[$i]['id'] === $movedId) {
                    $movedPos = $k;
                    break;
                }
            }

            if ($movedPos !== null) {
                $moved = array_splice($siblingIdx, $movedPos, 1)[0];
                $target = max(0, min(count($siblingIdx), $movedOrder - 1));
                array_splice($siblingIdx, $target, 0, [$moved]);
            }
        }

        $position = 1;
        foreach ($siblingIdx as $i) {
            $this->sections[$i]['order'] = $position++;
        }
    }

    /**
     * Collect a section id plus every descendant id from the draft (recursive).
     *
     * @return array<int, int>
     */
    protected function draftDescendantIds(int $sectionId): array
    {
        $ids = [$sectionId];
        foreach ($this->sections as $s) {
            if ((int) ($s['parent_section_id'] ?? 0) === $sectionId) {
                $ids = array_merge($ids, $this->draftDescendantIds((int) $s['id']));
            }
        }

        return $ids;
    }

    protected function markDirty(): void
    {
        $this->isDirty = true;
    }

    // ─── Whole-draft Save / Discard ──────────────────────────────────────────

    /**
     * Reconcile the in-memory draft to the database in a SINGLE transaction:
     *   1. Delete DB rows for this sectionable + scope that are no longer in the
     *      draft (a section that was removed).
     *   2. Insert rows for brand-new (negative temp id) sections, root-first, so
     *      a parent's real id exists before its children reference it. This
     *      builds a tempId → realId map.
     *   3. Update existing (positive id) rows in place.
     *   4. Persist order + parent_section_id for the entire tree from the draft.
     *
     * After saving, the draft is reloaded from the DB (so temp ids become real)
     * and the preview iframe is refreshed.
     */
    public function saveDraft(array $contentPatch = []): void
    {
        // Fold any live editor content for the section currently open into the
        // draft first, so a Save never drops in-flight wysiwyg edits.
        if (! empty($contentPatch) && $this->editingSectionId !== null) {
            foreach ($contentPatch as $fieldName => $json) {
                $this->sectionContent[$fieldName] = $json;
            }
            $this->syncOpenSectionIntoDraft();
        }

        DB::transaction(function () {
            $draftIds = collect($this->sections)
                ->pluck('id')
                ->filter(fn ($id) => (int) $id > 0)
                ->map(fn ($id) => (int) $id)
                ->all();

            // 1. Delete removed sections (scope-aware so the other scope survives).
            $orphanQuery = PageSection::where('sectionable_type', $this->sectionableType)
                ->where('sectionable_id', $this->sectionableId)
                ->whereNotIn('id', $draftIds);
            if ($this->scope !== null) {
                $orphanQuery->where('scope', $this->scope);
            }
            $orphanQuery->delete();

            // 2. + 3. Insert new sections root-first / update existing ones.
            $idMap = [];
            $this->persistDraftTree(null, null, $idMap);
        });

        // Reload so temp ids become real and ordering is canonical.
        $this->loadSections();

        // Keep the edit panel pointing at the same section if it still exists.
        if ($this->editingSectionId !== null && $this->editingSectionId < 0) {
            // The open section was brand-new; its temp id is gone after save.
            $this->selectedSectionId = null;
            $this->editingSectionId = null;
            $this->resetForm();
        }

        $this->pushHistory();
        $this->dispatch('preview-reload');
        $this->dispatch('notify', type: 'success', message: 'Page saved.');
    }

    /**
     * Recursively persist the draft tree for one parent group. $realParentId is
     * the already-resolved DB id of the parent (null at the root). $idMap carries
     * tempId → realId so descendants can resolve their parent reference.
     *
     * @param  array<int, int>  $idMap
     */
    protected function persistDraftTree(?int $draftParentId, ?int $realParentId, array &$idMap): void
    {
        $children = collect($this->sections)
            ->filter(fn ($s) => ($s['parent_section_id'] ?? null) === $draftParentId)
            ->sortBy('order')
            ->values();

        $order = 1;
        foreach ($children as $s) {
            $draftId = (int) $s['id'];

            $attributes = [
                'name' => $s['name'],
                'content' => $s['content'] ?? [],
                'settings' => $s['settings'] ?? [],
                'order' => $order,
                'parent_section_id' => $realParentId,
                'is_active' => (bool) ($s['is_active'] ?? true),
                'is_visible' => $s['is_visible'] ?? true,
            ];

            if ($draftId < 0) {
                $section = PageSection::create(array_merge($attributes, [
                    'sectionable_type' => $this->sectionableType,
                    'sectionable_id' => $this->sectionableId,
                    'scope' => $this->scope,
                    'section_template_id' => $s['section_template_id'] ?? null,
                    'section_type' => $s['section_type'] ?? null,
                ]));
                $idMap[$draftId] = $section->id;
                $realId = $section->id;
            } else {
                $section = PageSection::find($draftId);
                if ($section) {
                    $section->update($attributes);
                }
                $realId = $draftId;
            }

            $order++;

            // Recurse into this section's children with the resolved real id.
            $this->persistDraftTree($draftId, $realId, $idMap);
        }
    }

    /**
     * Discard every unsaved mutation and reload the draft from the database.
     */
    public function discardDraft(): void
    {
        $this->loadSections();
        $this->selectedSectionId = null;
        $this->showAddPanel = false;
        $this->resetForm();
        $this->pushHistory();
        $this->dispatch('preview-reload');
        $this->dispatch('notify', type: 'success', message: 'Unsaved changes discarded.');
    }

    /**
     * Whether a starter preset is available for the current template + scope.
     * Drives the empty-state CTA shown in the editor sidebar.
     */
    public function getHasStarterPresetProperty(): bool
    {
        if ($this->sectionableType !== \App\Models\Template::class || ! $this->scope) {
            return false;
        }
        $tpl = \App\Models\Template::find($this->sectionableId);
        if (! $tpl) {
            return false;
        }

        return ! empty(app(\App\Services\StarterSectionPresets::class)->presetsFor($tpl, $this->scope));
    }

    /**
     * One-click "Use default design" — loads the starter preset's sections into
     * the DRAFT (not the DB). Only available when the current scope is empty.
     */
    public function applyStarterPreset(): void
    {
        if ($this->sectionableType !== \App\Models\Template::class || ! $this->scope) {
            return;
        }
        if (! empty($this->sections)) {
            session()->flash('error', 'Sections already exist — clear them first if you want to start from defaults.');

            return;
        }

        $tpl = \App\Models\Template::find($this->sectionableId);
        if (! $tpl) {
            return;
        }

        $preset = app(\App\Services\StarterSectionPresets::class)->presetsFor($tpl, $this->scope);
        if (empty($preset)) {
            return;
        }

        $this->pushHistory();
        $this->addPresetToDraft($preset, null);
        $this->markDirty();
        $this->pushHistory();
        session()->flash('success', 'Starter sections loaded — edit / reorder / delete, then Save.');
    }

    /**
     * Recursively append a tree of starter preset configs to the draft, using
     * temp ids so the structure survives until the page is saved.
     *
     * @param  array<int, array<string, mixed>>  $configs
     */
    protected function addPresetToDraft(array $configs, ?int $parentId): void
    {
        foreach ($configs as $cfg) {
            $children = $cfg['children'] ?? [];
            unset($cfg['children']);

            $tempId = $this->nextTempId--;
            $this->sections[] = [
                'id' => $tempId,
                'parent_section_id' => $parentId,
                'section_template_id' => $cfg['section_template_id'] ?? null,
                'section_type' => $cfg['section_type'] ?? null,
                'scope' => $this->scope,
                'name' => $cfg['name'] ?? 'Section',
                'order' => $this->nextOrderForParent($parentId),
                'is_active' => true,
                'is_visible' => true,
                'content' => $cfg['content'] ?? [],
                'settings' => $cfg['settings'] ?? [],
            ];

            if (! empty($children)) {
                $this->addPresetToDraft($children, $tempId);
            }
        }
    }

    // ─── History / Undo / Redo ───────────────────────────────────────────────

    protected function pushHistory(): void
    {
        $snapshot = json_encode($this->sections);

        // Don't push duplicate consecutive snapshots
        if (! empty($this->historyStack) && $this->historyIndex >= 0
            && $this->historyStack[$this->historyIndex] === $snapshot) {
            return;
        }

        // Trim future branch
        $this->historyStack = array_slice($this->historyStack, 0, $this->historyIndex + 1);
        $this->historyStack[] = $snapshot;

        if (count($this->historyStack) > 25) {
            array_shift($this->historyStack);
        }

        $this->historyIndex = count($this->historyStack) - 1;
    }

    public function getCanUndoProperty(): bool
    {
        return $this->historyIndex > 0;
    }

    public function getCanRedoProperty(): bool
    {
        return $this->historyIndex < count($this->historyStack) - 1;
    }

    public function undo(): void
    {
        if ($this->historyIndex <= 0) {
            return;
        }

        $this->historyIndex--;
        $this->restoreFromHistory($this->historyStack[$this->historyIndex]);
    }

    public function redo(): void
    {
        if ($this->historyIndex >= count($this->historyStack) - 1) {
            return;
        }

        $this->historyIndex++;
        $this->restoreFromHistory($this->historyStack[$this->historyIndex]);
    }

    /**
     * Restore the draft from a history snapshot. Operates on the in-memory draft
     * only — undo/redo never touch the database (a subsequent Save persists it).
     */
    protected function restoreFromHistory(string $json): void
    {
        $snapshot = json_decode($json, true);
        $this->sections = is_array($snapshot) ? $snapshot : [];
        $this->markDirty();

        $snapshotIds = array_map(fn ($s) => (int) $s['id'], $this->sections);

        // Reset panel if the edited section was removed by undo
        if ($this->selectedSectionId && ! in_array($this->selectedSectionId, $snapshotIds, true)) {
            $this->selectedSectionId = null;
            $this->resetForm();
        }

        $this->dispatch('preview-reload');
    }

    /**
     * Remove redundant empty wrapper sections (primitive_div / primitive_section
     * with NO class and NO id) from the DRAFT, lifting their children into the
     * wrapper's slot. Repeats until the tree is stable so chains collapse fully.
     */
    public function cleanupEmptyWrappers(): void
    {
        $this->pushHistory();

        $removed = 0;
        $guard = 0;

        do {
            $changed = false;

            foreach ($this->sections as $wrapper) {
                if (! in_array($wrapper['section_type'] ?? null, ['primitive_div', 'primitive_section'], true)) {
                    continue;
                }

                $content = is_array($wrapper['content'] ?? null) ? $wrapper['content'] : [];
                $cls = trim((string) ($content['class'] ?? ''));
                $pid = trim((string) ($content['id'] ?? ''));

                if ($cls !== '' || $pid !== '') {
                    continue; // has styling / identity — keep it
                }

                $wrapperId = (int) $wrapper['id'];
                $parentId = $wrapper['parent_section_id'] ?? null;
                $wrapperOrder = (int) $wrapper['order'];

                // Reparent the wrapper's children to the wrapper's parent, taking
                // its slot. Shift later siblings down to make room.
                $childCount = 0;
                foreach ($this->sections as $i => $s) {
                    if ((int) ($s['parent_section_id'] ?? 0) === $wrapperId) {
                        $childCount++;
                    }
                }

                // Make room: bump siblings after the wrapper by (childCount - 1).
                if ($childCount !== 1) {
                    foreach ($this->sections as $i => $s) {
                        if (($s['parent_section_id'] ?? null) === $parentId
                            && (int) $s['id'] !== $wrapperId
                            && (int) $s['order'] > $wrapperOrder) {
                            $this->sections[$i]['order'] += ($childCount - 1);
                        }
                    }
                }

                // Splice children into the wrapper's slot, in their own order.
                $childIdx = [];
                foreach ($this->sections as $i => $s) {
                    if ((int) ($s['parent_section_id'] ?? 0) === $wrapperId) {
                        $childIdx[] = $i;
                    }
                }
                usort($childIdx, fn ($a, $b) => (int) $this->sections[$a]['order'] <=> (int) $this->sections[$b]['order']);

                $slot = $wrapperOrder;
                foreach ($childIdx as $i) {
                    $this->sections[$i]['parent_section_id'] = $parentId;
                    $this->sections[$i]['order'] = $slot++;
                }

                // Drop the wrapper itself.
                $wi = $this->draftIndex($wrapperId);
                if ($wi !== null) {
                    array_splice($this->sections, $wi, 1);
                }

                $removed++;
                $changed = true;
                break; // restart with fresh data
            }
        } while ($changed && ++$guard < 200);

        if ($removed > 0) {
            $this->markDirty();
        }

        $this->pushHistory();
        $this->dispatch('preview-reload');
        $this->dispatch('notify',
            message: $removed > 0 ? "Removed {$removed} empty wrapper section(s)" : 'No empty wrappers found',
            type: 'success'
        );
    }

    // ─── Section CRUD (draft only) ───────────────────────────────────────────

    public function moveSection(int $sectionId, ?int $parentSectionId, int $order): void
    {
        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        if ($parentSectionId !== null && $parentSectionId === $sectionId) {
            return;
        }

        // Guard against dropping a container into one of its own descendants.
        if ($parentSectionId !== null
            && in_array($parentSectionId, $this->draftDescendantIds($sectionId), true)) {
            return;
        }

        $this->pushHistory();

        $oldParentId = $this->sections[$idx]['parent_section_id'] ?? null;

        $this->sections[$idx]['parent_section_id'] = $parentSectionId;
        $this->sections[$idx]['order'] = $order;

        $this->reflowDraftSiblings($parentSectionId, $sectionId, $order);

        if ($oldParentId !== $parentSectionId) {
            $this->reflowDraftSiblings($oldParentId, null, null);
        }

        $this->markDirty();
    }

    public function selectSection(int $sectionId): void
    {
        if ($this->selectedSectionId === $sectionId) {
            $this->selectedSectionId = null;
            $this->resetForm();

            return;
        }

        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        // Capture state before editing so Ctrl+Z can revert changes
        $this->pushHistory();

        $section = $this->sections[$idx];
        $this->selectedSectionId = $sectionId;
        $this->editingSectionId = $sectionId;
        $this->selectedTemplateId = $section['section_template_id'] ?? null;
        $this->sectionName = $section['name'] ?? '';
        $this->sectionContent = $section['content'] ?? [];
        $this->sectionSettings = $section['settings'] ?? [];
        $this->showAddPanel = false;
    }

    public function openAddPanel(): void
    {
        $this->showAddPanel = true;
        $this->selectedSectionId = null;
        $this->resetForm();
    }

    public function openAddPanelForChild(int $sectionId): void
    {
        $this->addingChildOfSectionId = $sectionId;
        $this->showAddPanel = true;
        $this->selectedSectionId = null;
        $this->resetForm();
        // Restore after resetForm clears it
        $this->addingChildOfSectionId = $sectionId;
    }

    public function selectTemplate(int $templateId): void
    {
        $this->selectedTemplateId = $templateId;
        $template = SectionTemplate::with('fields')->find($templateId);

        if ($template) {
            $this->sectionName = $template->name;
            $defaultContent = [];

            foreach ($template->fields as $field) {
                $defaultContent[$field->name] = $field->default_value ?? '';
            }

            $this->sectionContent = $defaultContent;
        }
    }

    /**
     * Add a brand-new section to the DRAFT (no DB write). Children/order survive
     * via a negative temp id until saveDraft() assigns the real id.
     */
    public function saveSection(array $contentPatch = []): void
    {
        if (! $this->selectedTemplateId) {
            return;
        }

        // Merge the live editor content so the new section is created WITH what
        // the user typed, not the empty template default.
        foreach ($contentPatch as $fieldName => $json) {
            $this->sectionContent[$fieldName] = $json;
        }

        $template = SectionTemplate::find($this->selectedTemplateId);

        if (! $template) {
            return;
        }

        $this->pushHistory();

        $parentId = $this->addingChildOfSectionId;
        $tempId = $this->nextTempId--;

        $this->sections[] = [
            'id' => $tempId,
            'parent_section_id' => $parentId,
            'section_template_id' => $this->selectedTemplateId,
            'section_type' => str_replace('-', '_', $template->slug),
            'scope' => $this->scope,
            'name' => $this->sectionName ?: $template->name,
            'order' => $this->nextOrderForParent($parentId),
            'is_active' => true,
            'is_visible' => true,
            'content' => $this->sectionContent,
            'settings' => $this->sectionSettings,
        ];

        $this->markDirty();
        $this->showAddPanel = false;
        $this->pushHistory();

        // Open the edit panel for the just-added (draft) section.
        $this->selectedSectionId = $tempId;
        $this->editingSectionId = $tempId;
        $this->addingChildOfSectionId = null;

        $this->dispatch('preview-reload');
    }

    public function duplicateSection(int $sectionId): void
    {
        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        $this->pushHistory();

        $original = $this->sections[$idx];
        $parentId = $original['parent_section_id'] ?? null;
        $insertOrder = (int) $original['order'] + 1;

        // Shift subsequent siblings down to make room for the copy.
        foreach ($this->sections as $i => $s) {
            if (($s['parent_section_id'] ?? null) === $parentId && (int) $s['order'] >= $insertOrder) {
                $this->sections[$i]['order'] = (int) $s['order'] + 1;
            }
        }

        // Deep-copy the section + its whole subtree, assigning fresh temp ids.
        $this->duplicateDraftSubtree($sectionId, $parentId, $insertOrder, true);

        $this->markDirty();
        $this->pushHistory();
        $this->dispatch('preview-reload');
    }

    /**
     * Recursively clone a draft subtree under $newParentId, giving every node a
     * fresh temp id. The root copy gets " (copy)" appended to its name.
     */
    protected function duplicateDraftSubtree(int $sourceId, ?int $newParentId, int $order, bool $isRoot): void
    {
        $idx = $this->draftIndex($sourceId);
        if ($idx === null) {
            return;
        }

        $source = $this->sections[$idx];
        $tempId = $this->nextTempId--;

        $this->sections[] = [
            'id' => $tempId,
            'parent_section_id' => $newParentId,
            'section_template_id' => $source['section_template_id'] ?? null,
            'section_type' => $source['section_type'] ?? null,
            'scope' => $this->scope,
            'name' => $isRoot ? ($source['name'].' (copy)') : $source['name'],
            'order' => $order,
            'is_active' => (bool) ($source['is_active'] ?? true),
            'is_visible' => $source['is_visible'] ?? true,
            'content' => $source['content'] ?? [],
            'settings' => $source['settings'] ?? [],
        ];

        $children = collect($this->sections)
            ->filter(fn ($s) => (int) ($s['parent_section_id'] ?? 0) === $sourceId)
            ->sortBy('order')
            ->values();

        $childOrder = 1;
        foreach ($children as $child) {
            $this->duplicateDraftSubtree((int) $child['id'], $tempId, $childOrder++, false);
        }
    }

    /**
     * Fold the currently-open edit form ($sectionName / $sectionContent /
     * $sectionSettings) back into the draft entry for $editingSectionId.
     */
    protected function syncOpenSectionIntoDraft(): void
    {
        if ($this->editingSectionId === null) {
            return;
        }

        $idx = $this->draftIndex($this->editingSectionId);
        if ($idx === null) {
            return;
        }

        $this->sections[$idx]['name'] = $this->sectionName;
        $this->sections[$idx]['content'] = $this->sectionContent;
        $this->sections[$idx]['settings'] = $this->sectionSettings;
        $this->markDirty();
    }

    public function updatedSectionContent(): void
    {
        if ($this->editingSectionId) {
            $this->syncOpenSectionIntoDraft();
        }
    }

    public function updatedSectionName(): void
    {
        if ($this->editingSectionId) {
            $this->syncOpenSectionIntoDraft();
        }
    }

    /**
     * Persist the editor content for the section currently being edited INTO THE
     * DRAFT (no DB write). The JS save buttons collect every EditorJS field into
     * $contentPatch and call this so the typed content lands in the draft; the
     * page-level Save then persists everything.
     *
     * @param  array<string, string>  $contentPatch  field name => EditorJS JSON
     */
    public function saveContent(array $contentPatch = []): void
    {
        foreach ($contentPatch as $fieldName => $json) {
            $this->sectionContent[$fieldName] = $json;
        }

        // ADD MODE: no section in the draft yet → create it from the form.
        if (! $this->editingSectionId) {
            if ($this->selectedTemplateId) {
                $this->saveSection();
            }

            return;
        }

        $this->syncOpenSectionIntoDraft();
    }

    public function deleteSection(int $sectionId): void
    {
        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        $this->pushHistory();

        // Remove the section AND its whole subtree from the draft.
        $removeIds = $this->draftDescendantIds($sectionId);
        $this->sections = array_values(array_filter(
            $this->sections,
            fn ($s) => ! in_array((int) $s['id'], $removeIds, true)
        ));

        $this->markDirty();

        if (in_array($this->selectedSectionId, $removeIds, true)) {
            $this->selectedSectionId = null;
            $this->resetForm();
        }

        $this->dispatch('preview-reload');
    }

    public function toggleActive(int $sectionId): void
    {
        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        $this->pushHistory();
        $this->sections[$idx]['is_active'] = ! ($this->sections[$idx]['is_active'] ?? true);
        $this->markDirty();
        $this->dispatch('preview-reload');
    }

    public function toggleVisibility(int $sectionId): void
    {
        $idx = $this->draftIndex($sectionId);
        if ($idx === null) {
            return;
        }

        $this->pushHistory();
        $newVisibility = ! ($this->sections[$idx]['is_visible'] ?? true);
        $this->sections[$idx]['is_visible'] = $newVisibility;
        $this->markDirty();
        $this->dispatch('preview-visibility', sectionId: $sectionId, visible: $newVisibility);
    }

    public function reorderSections(array $order): void
    {
        $this->pushHistory();

        foreach ($order as $index => $sectionId) {
            $idx = $this->draftIndex((int) $sectionId);
            if ($idx !== null) {
                $this->sections[$idx]['order'] = $index + 1;
            }
        }

        $this->markDirty();
        $this->dispatch('preview-reload');
    }

    public function addRepeaterItem(string $fieldName): void
    {
        $template = SectionTemplate::with('fields')->find($this->selectedTemplateId);

        if (! $template) {
            return;
        }

        $field = $template->fields->where('name', $fieldName)->first();

        if (! $field) {
            return;
        }

        $subFields = json_decode($field->settings ?? '{}', true)['sub_fields'] ?? [];
        $newItem = [];

        foreach ($subFields as $sf) {
            $newItem[$sf['name']] = '';
        }

        // Repeater items may be stored as a JSON-encoded string in sectionContent
        // (legacy / fresh-section state). Decode before appending.
        $raw = $this->sectionContent[$fieldName] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }
        $raw[] = $newItem;
        $this->sectionContent[$fieldName] = array_values($raw);

        $this->syncOpenSectionIntoDraft();
    }

    public function removeRepeaterItem(string $fieldName, int $index): void
    {
        $raw = $this->sectionContent[$fieldName] ?? [];
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            $raw = [];
        }
        unset($raw[$index]);
        $this->sectionContent[$fieldName] = array_values($raw);

        $this->syncOpenSectionIntoDraft();
    }

    public function updatedSectionImageUploads($value, string $key): void
    {
        if (! $value) {
            return;
        }

        $path = $value->store('sections', 'public');
        $url = asset('storage/'.$path);
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            $this->sectionContent[$parts[0]] = $url;
        } elseif (count($parts) === 3) {
            $this->sectionContent[$parts[0]][$parts[1]][$parts[2]] = $url;
        }

        $this->sectionImageUploads[$key] = null;
        $this->syncOpenSectionIntoDraft();
    }

    // ─── Media Library ───────────────────────────────────────────────────────

    public function openMediaLibrary(string $fieldName): void
    {
        $this->mediaTargetField = $fieldName;
        $this->showMediaLibrary = true;
    }

    public function closeMediaLibrary(): void
    {
        $this->showMediaLibrary = false;
        $this->mediaTargetField = '';
    }

    public function selectMedia(string $url): void
    {
        if ($this->mediaTargetField) {
            $parts = explode('.', $this->mediaTargetField);

            if (count($parts) === 1) {
                $this->sectionContent[$parts[0]] = $url;
            } elseif (count($parts) === 3) {
                $this->sectionContent[$parts[0]][$parts[1]][$parts[2]] = $url;
            }
        }

        $this->showMediaLibrary = false;
        $this->mediaTargetField = '';

        if ($this->editingSectionId) {
            $this->syncOpenSectionIntoDraft();
        }
    }

    public function getMediaFiles(): array
    {
        $files = [];
        $disks = ['sections', 'uploads', ''];

        foreach ($disks as $dir) {
            try {
                $paths = Storage::disk('public')->files($dir);

                foreach ($paths as $path) {
                    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                    if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif'])) {
                        continue;
                    }

                    $files[] = [
                        'path' => $path,
                        'url' => asset('storage/'.$path),
                        'name' => basename($path),
                        'size' => Storage::disk('public')->size($path),
                        'modified' => Storage::disk('public')->lastModified($path),
                    ];
                }
            } catch (\Throwable) {
                // Directory may not exist — skip silently
            }
        }

        usort($files, fn ($a, $b) => $b['modified'] - $a['modified']);

        return array_slice($files, 0, 80);
    }

    // ─── JSON Import / Export ─────────────────────────────────────────────────

    public function getPageJson(): array
    {
        $node = ContentNode::where('content_type', $this->sectionableType)
            ->where('content_id', $this->sectionableId)
            ->first();

        if (! $node) {
            return [];
        }

        return app(PageSerializer::class)->serialize($node);
    }

    public function exportJson(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $json = $this->getPageJson();
        $filename = str($this->pageTitle)->slug()->append('.json')->toString();

        return response()->streamDownload(function () use ($json) {
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    /**
     * Bulk JSON import. This is an explicit, immediate bulk operation that writes
     * to the database via PageImporter (it replaces the whole layout) and then
     * reloads the draft from the freshly-imported rows.
     */
    public function importJson(string $json): void
    {
        $node = ContentNode::where('content_type', $this->sectionableType)
            ->where('content_id', $this->sectionableId)
            ->first();

        if (! $node) {
            return;
        }

        $layout = json_decode($json, true);

        if (! $layout || ! isset($layout['sections'])) {
            $this->dispatch('notify', type: 'error', message: 'Invalid JSON format.');

            return;
        }

        app(PageImporter::class)->import($node, $layout);
        $this->loadSections();
        $this->pushHistory();
        $this->dispatch('preview-reload');
        $this->dispatch('notify', type: 'success', message: 'Page imported successfully.');
    }

    // ─── Build ───────────────────────────────────────────────────────────────

    public function buildAssets(): void
    {
        // PHP-FPM runs with a minimal environment — its PATH usually lacks the
        // directory holding node/npm, so a bare `npm run build` fails with
        // "vite: not found" (vite's `#!/usr/bin/env node` shebang can't locate
        // node). Resolve an absolute npm binary and pass an explicit PATH (incl.
        // node_modules/.bin where the vite binary lives) so it works like an
        // interactive shell. escapeshellarg + explicit cwd avoids shell concat.
        $npm = collect(['/usr/local/bin/npm', '/usr/bin/npm', '/opt/homebrew/bin/npm'])
            ->first(fn ($p) => is_executable($p)) ?: 'npm';
        $nodeBinDir = $npm !== 'npm' ? dirname($npm) : '/usr/bin';

        $env = [
            'PATH' => $nodeBinDir.':'.base_path().'/node_modules/.bin:/usr/local/bin:/usr/bin:/bin',
            'HOME' => base_path(), // npm needs a writable HOME for its cache
            'NODE_ENV' => 'production',
        ];

        $process = proc_open(
            escapeshellarg($npm).' run build 2>&1',
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            base_path(),
            $env
        );

        if (! is_resource($process)) {
            $this->dispatch('notify', type: 'error', message: 'Could not start build process.');

            return;
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $code = proc_close($process);

        if ($code === 0) {
            $this->dispatch('notify', type: 'success', message: 'Assets built successfully.');
            $this->dispatch('preview-reload');
        } else {
            $this->dispatch('notify', type: 'error', message: 'Build failed: '.($error ?: $output));
        }
    }

    // ─── Visual Editor Settings ───────────────────────────────────────────────

    public function toggleTailwindCdn(): void
    {
        $this->veTailwindCdn = ! $this->veTailwindCdn;
        Setting::set('ve_tailwind_cdn', $this->veTailwindCdn, 'visual_editor');
        $this->dispatch('preview-reload');
    }

    // ─── Panel ───────────────────────────────────────────────────────────────

    public function closePanel(): void
    {
        $this->selectedSectionId = null;
        $this->showAddPanel = false;
        $this->resetForm();
    }

    /**
     * Fold the open editor's content into the draft + close the panel in a SINGLE
     * call (no DB write). The client passes the final editor JSON for each wysiwyg
     * field as `$contentPatch = ['fieldName' => '{"blocks":...}', ...]`.
     */
    public function saveAndClose(array $contentPatch = []): void
    {
        if ($this->editingSectionId && ! empty($contentPatch)) {
            foreach ($contentPatch as $fieldName => $json) {
                $this->sectionContent[$fieldName] = $json;
            }
            $this->syncOpenSectionIntoDraft();
        }

        $this->selectedSectionId = null;
        $this->showAddPanel = false;
        $this->resetForm();
        $this->dispatch('preview-reload');
    }

    protected function resetForm(): void
    {
        $this->editingSectionId = null;
        $this->selectedTemplateId = null;
        $this->sectionName = '';
        $this->sectionContent = [];
        $this->sectionSettings = [];
        $this->addingChildOfSectionId = null;
    }

    public function saveAsTemplate(string $newName): void
    {
        if (! $this->editingSectionId || ! $this->selectedTemplateId) {
            return;
        }

        $newName = trim($newName);

        if (! $newName) {
            return;
        }

        $originalTemplate = SectionTemplate::with('fields')->find($this->selectedTemplateId);

        if (! $originalTemplate) {
            return;
        }

        $slug = \Illuminate\Support\Str::slug($newName);
        $count = SectionTemplate::where('slug', 'LIKE', $slug.'%')->count();

        if ($count > 0) {
            $slug .= '-'.($count + 1);
        }

        $newTemplate = SectionTemplate::create([
            'name' => $newName,
            'slug' => $slug,
            'category' => 'custom',
            'description' => 'Based on '.$originalTemplate->name,
            'html_template' => $originalTemplate->html_template,
            'blade_file' => $originalTemplate->blade_file,
            'is_system' => false,
            'is_active' => true,
            'order' => 999,
            'default_settings' => $originalTemplate->default_settings,
        ]);

        foreach ($originalTemplate->fields as $field) {
            $newTemplate->fields()->create([
                'name' => $field->name,
                'label' => $field->label,
                'type' => $field->type,
                'description' => $field->description,
                'placeholder' => $field->placeholder,
                'default_value' => isset($this->sectionContent[$field->name]) && ! is_array($this->sectionContent[$field->name])
                    ? $this->sectionContent[$field->name]
                    : $field->default_value,
                'is_required' => $field->is_required,
                'order' => $field->order,
                'options' => $field->options,
                'validation_rules' => $field->validation_rules,
                'settings' => $field->settings,
            ]);
        }

        $this->availableTemplates = SectionTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('order')
            ->get()
            ->toArray();

        $this->dispatch('notify', type: 'success', message: 'Template "'.$newName.'" saved.');
    }

    public function render(): \Illuminate\View\View
    {
        $selectedTemplate = $this->selectedTemplateId
            ? SectionTemplate::with('fields')->find($this->selectedTemplateId)
            : null;

        return view('pagebuilder::livewire.admin.page-sections.visual-page-editor', [
            'selectedTemplate' => $selectedTemplate,
            'backUrl' => $this->backUrl,
        ])->layout('layouts.visual-editor');
    }
}
