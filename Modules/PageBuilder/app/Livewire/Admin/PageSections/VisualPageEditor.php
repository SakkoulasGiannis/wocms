<?php

namespace Modules\PageBuilder\Livewire\Admin\PageSections;

use App\Models\ContentNode;
use App\Models\Setting;
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

    public array $sections = [];

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
                if ($this->scope === 'listing') return $name.' — Listing page design';
                if ($this->scope === 'entry')   return $name.' — Entry page design';
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
            if (! $tpl) return '/';

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
                    } catch (\Throwable $e) {}
                }
                // Fall back to ContentNode lookup for entries of this template
                $node = ContentNode::where('template_id', $tpl->id)
                    ->whereNotNull('content_id')
                    ->latest()
                    ->first();
                if ($node) return $node->url_path;
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

    public function loadSections(): void
    {
        $query = PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId);

        // Filter by scope when designing a Template (listing/entry). Other modes
        // (Home, Page) leave $this->scope = null and see all their sections.
        if ($this->scope !== null) {
            $query->where('scope', $this->scope);
        }

        $this->sections = $query->orderBy('order')->get()->toArray();
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
        if (! $tpl) return false;
        return ! empty(app(\App\Services\StarterSectionPresets::class)->presetsFor($tpl, $this->scope));
    }

    /**
     * One-click "Use default design" — persists the starter preset's sections.
     * Only available when current scope is empty. Idempotent: refuses to run if
     * sections already exist (avoids accidental duplication).
     */
    public function applyStarterPreset(): void
    {
        if ($this->sectionableType !== \App\Models\Template::class || ! $this->scope) {
            return;
        }
        if (! empty($this->sections)) {
            // Don't overwrite existing work
            session()->flash('error', 'Sections already exist — clear them first if you want to start from defaults.');
            return;
        }

        $tpl = \App\Models\Template::find($this->sectionableId);
        if (! $tpl) return;

        $preset = app(\App\Services\StarterSectionPresets::class)->presetsFor($tpl, $this->scope);
        if (empty($preset)) return;

        \DB::transaction(function () use ($preset) {
            $this->createPresetTree($preset, null);
        });

        $this->loadSections();
        $this->pushHistory();
        session()->flash('success', 'Starter sections loaded — edit / reorder / delete as needed.');
    }

    /**
     * Recursively persist a tree of starter sections. Each $cfg may carry a
     * 'children' array of nested configs which will be created with their
     * parent_section_id set to the freshly inserted parent's id. Keeps the
     * visual editor's nested-children rendering happy.
     */
    protected function createPresetTree(array $configs, ?int $parentId): void
    {
        foreach ($configs as $cfg) {
            $children = $cfg['children'] ?? [];
            unset($cfg['children']);

            $section = PageSection::create(array_merge($cfg, [
                'sectionable_type'  => $this->sectionableType,
                'sectionable_id'    => $this->sectionableId,
                'scope'             => $this->scope,
                'parent_section_id' => $parentId,
                'is_active'         => true,
                'is_visible'        => true,
            ]));

            if (! empty($children)) {
                $this->createPresetTree($children, $section->id);
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

    protected function restoreFromHistory(string $json): void
    {
        $snapshot = json_decode($json, true);
        $snapshotIds = array_column($snapshot, 'id');

        // Remove sections not in the snapshot
        // Scope-aware orphan delete: only sections belonging to the SCOPE we're currently
        // editing should be removed. Without this, saving "listing" design would wipe
        // every "entry" section (and vice-versa).
        $orphanQuery = PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId)
            ->whereNotIn('id', $snapshotIds);
        if ($this->scope !== null) {
            $orphanQuery->where('scope', $this->scope);
        }
        $orphanQuery->delete();

        foreach ($snapshot as $s) {
            /** @var PageSection|null $section */
            $section = PageSection::withTrashed()->find($s['id']);

            if ($section) {
                if ($section->trashed()) {
                    $section->restore();
                }

                $section->update([
                    'name' => $s['name'],
                    'content' => $s['content'],
                    'settings' => $s['settings'],
                    'order' => $s['order'],
                    'parent_section_id' => $s['parent_section_id'],
                    'is_active' => $s['is_active'],
                    'is_visible' => $s['is_visible'] ?? true,
                ]);
            } else {
                PageSection::create([
                    'id' => $s['id'],
                    'sectionable_type' => $this->sectionableType,
                    'sectionable_id' => $this->sectionableId,
                    'scope' => $this->scope,
                    'section_template_id' => $s['section_template_id'],
                    'section_type' => $s['section_type'],
                    'name' => $s['name'],
                    'content' => $s['content'],
                    'settings' => $s['settings'],
                    'order' => $s['order'],
                    'parent_section_id' => $s['parent_section_id'],
                    'is_active' => $s['is_active'],
                    'is_visible' => $s['is_visible'] ?? true,
                ]);
            }
        }

        $this->loadSections();

        // Reset panel if the edited section was removed by undo
        if ($this->selectedSectionId && ! in_array($this->selectedSectionId, $snapshotIds)) {
            $this->selectedSectionId = null;
            $this->resetForm();
        }

        $this->dispatch('preview-reload');
    }

    // ─── Section CRUD ────────────────────────────────────────────────────────

    public function moveSection(int $sectionId, ?int $parentSectionId, int $order): void
    {
        $section = PageSection::find($sectionId);

        if (! $section) {
            return;
        }

        if ($parentSectionId && $parentSectionId === $sectionId) {
            return;
        }

        $this->pushHistory();

        $section->update([
            'parent_section_id' => $parentSectionId,
            'order' => $order,
        ]);

        $this->loadSections();
        $this->dispatch('preview-reload');
    }

    public function selectSection(int $sectionId): void
    {
        if ($this->selectedSectionId === $sectionId) {
            $this->selectedSectionId = null;
            $this->resetForm();

            return;
        }

        $section = PageSection::find($sectionId);

        if (! $section) {
            return;
        }

        // Capture state before editing so Ctrl+Z can revert changes
        $this->pushHistory();

        $this->selectedSectionId = $sectionId;
        $this->editingSectionId = $sectionId;
        $this->selectedTemplateId = $section->section_template_id;
        $this->sectionName = $section->name;
        $this->sectionContent = $section->content ?? [];
        $this->sectionSettings = $section->settings ?? [];
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

    public function saveSection(): void
    {
        if (! $this->selectedTemplateId) {
            return;
        }

        $template = SectionTemplate::find($this->selectedTemplateId);

        if (! $template) {
            return;
        }

        $this->pushHistory();

        $parentId = $this->addingChildOfSectionId;

        $maxOrderQ = PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId)
            ->where('parent_section_id', $parentId);
        if ($this->scope !== null) {
            $maxOrderQ->where('scope', $this->scope);
        }
        $maxOrder = $maxOrderQ->max('order') ?? 0;

        $section = PageSection::create([
            'sectionable_type' => $this->sectionableType,
            'sectionable_id' => $this->sectionableId,
            'scope' => $this->scope,
            'section_template_id' => $this->selectedTemplateId,
            'section_type' => str_replace('-', '_', $template->slug),
            'name' => $this->sectionName ?: $template->name,
            'order' => $maxOrder + 1,
            'parent_section_id' => $parentId,
            'is_active' => true,
            'is_visible' => true,
            'content' => $this->sectionContent,
            'settings' => $this->sectionSettings,
        ]);

        $this->showAddPanel = false;
        $this->resetForm();
        $this->loadSections();
        $this->pushHistory();
        $this->selectedSectionId = $section->id;
        $this->dispatch('preview-reload');
    }

    public function duplicateSection(int $sectionId): void
    {
        $section = PageSection::find($sectionId);

        if (! $section) {
            return;
        }

        $this->pushHistory();

        // Shift subsequent sibling orders up
        PageSection::where('sectionable_type', $this->sectionableType)
            ->where('sectionable_id', $this->sectionableId)
            ->where('parent_section_id', $section->parent_section_id)
            ->where('order', '>', $section->order)
            ->increment('order');

        $copy = $section->replicate();
        $copy->name = $section->name.' (copy)';
        $copy->order = $section->order + 1;
        $copy->save();

        $this->loadSections();
        $this->pushHistory();
        $this->dispatch('preview-reload');
    }

    public function updatedSectionContent(): void
    {
        if ($this->editingSectionId) {
            $this->updateSection();
        }
    }

    public function updatedSectionName(): void
    {
        if ($this->editingSectionId) {
            $this->updateSection();
        }
    }

    public function updateSection(): void
    {
        if (! $this->editingSectionId) {
            return;
        }

        $section = PageSection::find($this->editingSectionId);

        if (! $section) {
            return;
        }

        $section->update([
            'name' => $this->sectionName,
            'content' => $this->sectionContent,
            'settings' => $this->sectionSettings,
        ]);

        $this->loadSections();
        $this->dispatch('preview-reload');
    }

    protected function renderSectionHtml(int $sectionId): string
    {
        $section = PageSection::with([
            'sectionTemplate.fields',
            'childrenRecursive.sectionTemplate',
        ])->find($sectionId);

        if (! $section) {
            return '';
        }

        return view('partials.render-section', [
            'section' => $section,
            'forceVe' => true,
        ])->render();
    }

    public function deleteSection(int $sectionId): void
    {
        $this->pushHistory();

        PageSection::destroy($sectionId);

        if ($this->selectedSectionId === $sectionId) {
            $this->selectedSectionId = null;
            $this->resetForm();
        }

        $this->loadSections();
        $this->dispatch('preview-reload');
    }

    public function toggleActive(int $sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $section->update(['is_active' => ! $section->is_active]);
            $this->loadSections();
            $this->dispatch('preview-reload');
        }
    }

    public function toggleVisibility(int $sectionId): void
    {
        $section = PageSection::find($sectionId);

        if ($section) {
            $newVisibility = ! ($section->is_visible ?? true);
            $section->update(['is_visible' => $newVisibility]);
            $this->loadSections();
            $this->dispatch('preview-visibility', sectionId: $sectionId, visible: $newVisibility);
        }
    }

    public function reorderSections(array $order): void
    {
        $this->pushHistory();

        foreach ($order as $index => $sectionId) {
            PageSection::where('id', $sectionId)->update(['order' => $index + 1]);
        }

        $this->loadSections();
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
        // (legacy / fresh-section state). Decode before appending so `$items[] = …`
        // doesn't blow up with "[] operator not supported for strings".
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
            $this->updateSection();
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

        $this->pushHistory();

        app(PageImporter::class)->import($node, $layout);
        $this->loadSections();
        $this->pushHistory();
        $this->dispatch('preview-reload');
        $this->dispatch('notify', type: 'success', message: 'Page imported successfully.');
    }

    // ─── Build ───────────────────────────────────────────────────────────────

    public function buildAssets(): void
    {
        $process = proc_open(
            'cd '.base_path().' && npm run build 2>&1',
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
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
