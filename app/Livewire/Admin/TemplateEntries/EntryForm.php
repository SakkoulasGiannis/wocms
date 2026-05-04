<?php

namespace App\Livewire\Admin\TemplateEntries;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\SectionTemplate;
use App\Models\Setting;
use App\Models\Template;
use App\Services\PageCssGenerator;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class EntryForm extends Component
{
    use WithFileUploads;

    public $templateSlug;

    public $template;

    public $entryId;

    public $entry;

    public $fieldValues = [];

    public $uploadedFiles = []; // For Livewire file uploads

    public $parentNodeId = null;

    public $availableParentNodes = [];

    public $createdAt = null; // Created date/time for the entry

    public $status = 'active'; // Entry status (active, draft, disabled)

    public $cacheEnabled = ''; // Cache override: '' = use template, '1' = force enable, '0' = force disable

    // Prevent re-render on file upload
    protected $listeners = ['fileUploaded' => 'handleFileUploaded'];

    // SEO Fields (for templates with has_seo = true)
    public $seoFields = [];

    // Page Sections (for render_mode = 'sections')
    public $sections = [];

    public $sectionTypes = [];

    public $availableSectionTemplates = [];

    public $editingSectionIndex = null;

    public $sectionForm = [];

    public $selectedTemplateId = null;

    public $showSectionForm = false;

    public $sectionImageUploads = [];

    /**
     * Auto-generate slug from the URL-identifier field as the user types.
     *
     * Behaviour:
     * - Only fires when creating a NEW entry (never when editing — would break URLs).
     * - Stops auto-syncing once the user has manually edited the slug for this session
     *   (detected via comparison with what we last auto-generated).
     */
    public bool $slugManuallyTouched = false;

    public string $autoSlugLastValue = '';

    public function updated($name, $value): void
    {
        if (! str_starts_with($name, 'fieldValues.')) {
            return;
        }
        $key = substr($name, strlen('fieldValues.'));

        // If the slug changed AND it's not the value we ourselves just generated,
        // mark it manually touched so we stop auto-syncing.
        if ($key === 'slug') {
            $current = (string) ($value ?? '');
            if ($current !== '' && $current !== $this->autoSlugLastValue) {
                $this->slugManuallyTouched = true;
            }

            return;
        }

        // Only auto-sync on CREATE.
        if ($this->entry && $this->entry->exists) {
            return;
        }

        if ($this->slugManuallyTouched) {
            return;
        }

        if (! $this->template) {
            return;
        }

        // Detect the slug-source field. Prefer the explicit is_url_identifier flag,
        // but if no field has it set, fall back to common naming conventions
        // (title / name / heading) — matches createContentNode's logic.
        $urlField = $this->template->fields->where('is_url_identifier', true)->first();
        if ($urlField) {
            if ($urlField->name !== $key) {
                return;
            }
        } elseif (! in_array($key, ['title', 'name', 'heading'], true)) {
            return;
        }

        $hasSlugField = $this->template->fields->where('name', 'slug')->isNotEmpty();
        if (! $hasSlugField) {
            return;
        }

        $newSlug = \Illuminate\Support\Str::slug((string) $value);
        $this->autoSlugLastValue = $newSlug;
        $this->fieldValues['slug'] = $newSlug;
    }

    /**
     * Handle section image upload for a specific field
     */
    public function updatedSectionImageUploads($value, $key): void
    {
        if (! $value) {
            return;
        }

        // Store the uploaded file to public storage
        $path = $value->store('sections', 'public');
        $url = asset('storage/'.$path);

        // Parse the key to determine if it's a regular field or a repeater sub-field
        // Key formats: "fieldName" or "fieldName.index.subFieldName"
        $parts = explode('.', $key);

        if (count($parts) === 1) {
            // Simple image field
            $this->sectionForm['field_data'][$parts[0]] = $url;
        } elseif (count($parts) === 3) {
            // Repeater sub-field: fieldName.index.subFieldName
            $this->sectionForm['field_data'][$parts[0]][$parts[1]][$parts[2]] = $url;
        }

        // Also update the sections array directly so syncSections() picks up the change
        if ($this->editingSectionIndex !== null && isset($this->sections[$this->editingSectionIndex])) {
            if (count($parts) === 1) {
                $this->sections[$this->editingSectionIndex]['field_data'][$parts[0]] = $url;
                $this->sections[$this->editingSectionIndex]['content'][$parts[0]] = $url;
            } elseif (count($parts) === 3) {
                $this->sections[$this->editingSectionIndex]['field_data'][$parts[0]][$parts[1]][$parts[2]] = $url;
                $this->sections[$this->editingSectionIndex]['content'][$parts[0]][$parts[1]][$parts[2]] = $url;
            }
        }

        // Clear the temp upload
        $this->sectionImageUploads[$key] = null;
    }

    protected function resolveModelClass(): string
    {
        $mc = $this->template->model_class;

        return str_contains($mc, '\\') ? $mc : "App\\Models\\{$mc}";
    }

    public function mount($templateSlug, $entryId = null)
    {
        $this->templateSlug = $templateSlug;
        // Cache template with fields for 1 hour
        $this->template = \Cache::remember("template.{$templateSlug}.with-fields", 3600, function () use ($templateSlug) {
            return Template::where('slug', $templateSlug)
                ->where('is_active', true)
                ->with(['fields' => function ($query) {
                    $query->orderBy('order');
                }])
                ->firstOrFail();
        });

        // Check if dynamic model exists
        if (! $this->template->model_class || ! class_exists($this->resolveModelClass())) {
            abort(500, 'Template model not found. Please save the template again to generate the model.');
        }

        $this->entryId = $entryId;

        // Load section templates if template or entry uses sections
        if ($this->template->render_mode === 'sections' || ($entryId && ($this->resolveModelClass()::find($entryId)?->render_mode ?? null) === 'sections')) {
            $this->sectionTypes = PageSection::getSectionTypes();
            $this->availableSectionTemplates = SectionTemplate::where('is_active', true)
                ->with('fields')
                ->orderBy('category')
                ->orderBy('order')
                ->get();
        }

        if ($entryId) {
            // Edit mode
            $modelClass = $this->resolveModelClass();
            $this->entry = $modelClass::findOrFail($entryId);

            // Load existing values
            foreach ($this->template->fields as $field) {
                $value = $this->entry->{$field->name};

                // Format dates for HTML input fields
                if ($field->type === 'date' && $value) {
                    $value = \Carbon\Carbon::parse($value)->format('Y-m-d');
                } elseif ($field->type === 'datetime' && $value) {
                    $value = \Carbon\Carbon::parse($value)->format('Y-m-d\TH:i');
                }

                $this->fieldValues[$field->name] = $value;

                // For grapejs fields, also load the CSS
                if ($field->type === 'grapejs') {
                    $cssFieldName = $field->name.'_css';
                    if (isset($this->entry->{$cssFieldName})) {
                        $this->fieldValues[$cssFieldName] = $this->entry->{$cssFieldName};
                    }
                }
            }

            // Load SEO fields if template has SEO enabled
            if ($this->template->has_seo) {
                $this->seoFields = [
                    'seo_title' => $this->entry->seo_title ?? '',
                    'seo_description' => $this->entry->seo_description ?? '',
                    'seo_keywords' => $this->entry->seo_keywords ?? '',
                    'seo_canonical_url' => $this->entry->seo_canonical_url ?? '',
                    'seo_focus_keyword' => $this->entry->seo_focus_keyword ?? '',
                    'seo_robots_index' => $this->entry->seo_robots_index ?? 'index',
                    'seo_robots_follow' => $this->entry->seo_robots_follow ?? 'follow',
                    'seo_og_title' => $this->entry->seo_og_title ?? '',
                    'seo_og_description' => $this->entry->seo_og_description ?? '',
                    'seo_og_image' => $this->entry->seo_og_image ?? '',
                    'seo_og_type' => $this->entry->seo_og_type ?? 'website',
                    'seo_og_url' => $this->entry->seo_og_url ?? '',
                    'seo_twitter_card' => $this->entry->seo_twitter_card ?? 'summary_large_image',
                    'seo_twitter_title' => $this->entry->seo_twitter_title ?? '',
                    'seo_twitter_description' => $this->entry->seo_twitter_description ?? '',
                    'seo_twitter_image' => $this->entry->seo_twitter_image ?? '',
                    'seo_twitter_site' => $this->entry->seo_twitter_site ?? '',
                    'seo_twitter_creator' => $this->entry->seo_twitter_creator ?? '',
                    'seo_schema_type' => $this->entry->seo_schema_type ?? '',
                    'seo_schema_custom' => $this->entry->seo_schema_custom ?? '',
                    'seo_redirect_url' => $this->entry->seo_redirect_url ?? '',
                    'seo_redirect_type' => $this->entry->seo_redirect_type ?? '301',
                    'seo_sitemap_include' => $this->entry->seo_sitemap_include ?? true,
                    'seo_sitemap_priority' => $this->entry->seo_sitemap_priority ?? '0.5',
                    'seo_sitemap_changefreq' => $this->entry->seo_sitemap_changefreq ?? 'weekly',
                ];
            }

            // Load sections if template or entry uses sections
            if (($this->template->render_mode === 'sections' || ($this->entry->render_mode ?? null) === 'sections') && method_exists($this->entry, 'sections')) {
                $this->sections = $this->entry->sections()->with('sectionTemplate')->orderBy('order')->get()->map(function ($section) {
                    return [
                        'id' => $section->id,
                        'section_template_id' => $section->section_template_id,
                        'section_type' => $section->section_type,
                        'edit_mode' => $section->edit_mode ?? 'simple',
                        'name' => $section->name,
                        'field_data' => $section->content ?? [],
                        'rendered_html' => $section->rendered_html ?? '',
                        'content' => $section->content ?? [],
                        'settings' => $section->settings ?? [],
                        'css' => $section->css ?? '',
                        'is_active' => $section->is_active,
                        'order' => $section->order,
                    ];
                })->toArray();
            }

            // Load parent node if exists
            $contentNode = ContentNode::where('content_type', get_class($this->entry))
                ->where('content_id', $this->entry->id)
                ->first();
            if ($contentNode) {
                $this->parentNodeId = $contentNode->parent_id;
                // Load cache setting
                $this->cacheEnabled = $contentNode->cache_enabled === null ? '' : (string) $contentNode->cache_enabled;
            }

            // Load created_at for editing
            if ($this->entry->created_at) {
                $this->createdAt = $this->entry->created_at->format('Y-m-d\TH:i');
            }

            // Load status for editing
            $this->status = $this->entry->status ?? 'active';
        } else {
            // Create mode - initialize with default values
            foreach ($this->template->fields as $field) {
                $this->fieldValues[$field->name] = $field->default_value ?? null;
            }

            // Initialize SEO fields with defaults for new entries
            if ($this->template->has_seo) {
                $this->seoFields = [
                    'seo_title' => '',
                    'seo_description' => '',
                    'seo_keywords' => '',
                    'seo_canonical_url' => '',
                    'seo_focus_keyword' => '',
                    'seo_robots_index' => 'index',
                    'seo_robots_follow' => 'follow',
                    'seo_og_title' => '',
                    'seo_og_description' => '',
                    'seo_og_image' => '',
                    'seo_og_type' => 'website',
                    'seo_og_url' => '',
                    'seo_twitter_card' => 'summary_large_image',
                    'seo_twitter_title' => '',
                    'seo_twitter_description' => '',
                    'seo_twitter_image' => '',
                    'seo_twitter_site' => '',
                    'seo_twitter_creator' => '',
                    'seo_schema_type' => '',
                    'seo_schema_custom' => '',
                    'seo_redirect_url' => '',
                    'seo_redirect_type' => '301',
                    'seo_sitemap_include' => true,
                    'seo_sitemap_priority' => '0.5',
                    'seo_sitemap_changefreq' => 'weekly',
                ];
            }
        }

        // Load available parent nodes
        $this->loadAvailableParentNodes();
    }

    protected function loadAvailableParentNodes()
    {
        // Load all nodes of the SAME template for parent selection (excluding self if editing)
        $query = ContentNode::where('template_id', $this->template->id)
            ->where('is_published', true);

        // If editing, exclude the current entry from parent options (can't be its own parent)
        if ($this->entryId) {
            $modelClass = $this->resolveModelClass();
            $query->where(function ($q) use ($modelClass) {
                $q->where('content_type', '!=', $modelClass)
                    ->orWhere('content_id', '!=', $this->entryId);
            });
        }

        // Order by tree path to show hierarchical structure
        $nodes = $query->orderBy('tree_path')->get();

        // Build hierarchical tree structure for display
        $this->availableParentNodes = $this->buildTreeStructure($nodes);
    }

    protected function buildTreeStructure($nodes)
    {
        $tree = [];
        $lookup = [];
        $childrenMap = [];

        // First pass: create lookup table
        foreach ($nodes as $node) {
            $lookup[$node->id] = $node;
            $childrenMap[$node->id] = [];
            $node->level = 0;
        }

        // Second pass: build tree
        foreach ($nodes as $node) {
            if ($node->parent_id && isset($lookup[$node->parent_id])) {
                $childrenMap[$node->parent_id][] = $node;
            } else {
                $tree[] = $node;
            }
        }

        // Assign children from map
        foreach ($nodes as $node) {
            $node->childNodes = $childrenMap[$node->id];
        }

        // Third pass: flatten with levels for display
        return $this->flattenTree($tree);
    }

    protected function flattenTree($nodes, $level = 0)
    {
        $result = [];
        foreach ($nodes as $node) {
            $node->level = $level;
            $result[] = $node;
            if (! empty($node->childNodes)) {
                $childResults = $this->flattenTree($node->childNodes, $level + 1);
                $result = array_merge($result, $childResults->all());
            }
        }

        return collect($result);
    }

    public function save()
    {
        $this->performSave();

        // CRITICAL: Skip render to prevent GrapeJS from being destroyed
        $this->skipRender();

        // Return result for JavaScript promise
        return [
            'success' => true,
            'message' => $this->template->name.' updated successfully!',
        ];
    }

    public function saveAndReturn()
    {
        $this->performSave();

        session()->flash('success', $this->template->name.' saved successfully!');

        // Return to the list page
        return redirect()->route('admin.template-entries.index', $this->templateSlug);
    }

    protected function performSave()
    {
        \Log::info('performSave() called', [
            'uploadedFiles' => array_keys($this->uploadedFiles),
            'entryId' => $this->entryId,
        ]);

        // Build validation rules
        $rules = [];
        foreach ($this->template->fields as $field) {
            // Skip image fields - they are validated separately
            if ($field->type === 'image') {
                $imageRules = ['nullable', 'image', 'max:10240']; // Max 10MB
                if ($field->is_required && ! $this->entryId) {
                    $imageRules[0] = 'required';
                }
                $rules["uploadedFiles.{$field->name}"] = implode('|', $imageRules);

                continue;
            }

            $fieldRules = [];

            if ($field->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            // Add type-specific validation
            switch ($field->type) {
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'number':
                case 'integer':
                    $fieldRules[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                case 'checkbox':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
            }

            $rules["fieldValues.{$field->name}"] = implode('|', $fieldRules);
        }

        $this->validate($rules);

        // Clean GrapeJS fields - remove <body> tags
        $this->cleanGrapeJsFields();

        // Normalize checkbox/boolean fields - convert null to false
        foreach ($this->template->fields as $field) {
            if (in_array($field->type, ['checkbox', 'boolean'])) {
                if (! isset($this->fieldValues[$field->name]) || $this->fieldValues[$field->name] === null) {
                    $this->fieldValues[$field->name] = false;
                }
            }
        }

        if ($this->entryId) {
            // Update
            $updateData = $this->fieldValues;

            // Add SEO fields if template has SEO
            if ($this->template->has_seo) {
                $updateData = array_merge($updateData, $this->seoFields);
            }

            // Add status to update data
            $updateData['status'] = $this->status;

            // Update main fields
            $this->entry->update($updateData);

            // Update created_at if provided
            if ($this->createdAt) {
                $this->entry->created_at = $this->createdAt;
                $this->entry->save();
            }

            // Handle file uploads for image fields
            $this->handleMediaUploads($this->entry);

            // Generate CSS file if there's a GrapeJS field with CSS
            $this->generateCssFiles($this->entry);

            // Generate Blade template file if there's a GrapeJS field with HTML
            $this->generateBladeTemplate($this->entry);

            // Sync sections if template uses sections
            // Reload entry to ensure trait methods are available
            $this->entry->refresh();
            $this->syncSections($this->entry);

            // Update ContentNode only if template is public
            if ($this->template->is_public) {
                $contentNode = ContentNode::where('content_type', get_class($this->entry))
                    ->where('content_id', $this->entry->id)
                    ->first();

                if ($contentNode) {
                    $this->updateContentNode($contentNode, $this->entry);
                }
            }
        } else {
            // Create new entry
            $modelClass = $this->resolveModelClass();
            $createData = $this->fieldValues;

            // Add SEO fields if template has SEO
            if ($this->template->has_seo) {
                $createData = array_merge($createData, $this->seoFields);
            }

            // Add status to create data
            $createData['status'] = $this->status;

            // Set render_mode from template if the model has this field
            if ($this->template->render_mode && \Schema::hasColumn((new $modelClass)->getTable(), 'render_mode')) {
                $createData['render_mode'] = $this->template->render_mode;
            }

            // Create entry
            $newEntry = $modelClass::create($createData);

            // Set created_at if provided
            if ($this->createdAt) {
                $newEntry->created_at = $this->createdAt;
                $newEntry->save();
            }

            // Handle file uploads for image fields
            $this->handleMediaUploads($newEntry);

            // Generate CSS file if there's a GrapeJS field with CSS
            $this->generateCssFiles($newEntry);

            // Generate Blade template file if there's a GrapeJS field with HTML
            $this->generateBladeTemplate($newEntry);

            // Sync sections if template uses sections
            $this->syncSections($newEntry);

            // Create ContentNode only if template is public
            if ($this->template->is_public) {
                $this->createContentNode($newEntry);
            }

            // Update component state to reflect the new entry
            $this->entryId = $newEntry->id;
            $this->entry = $newEntry;

            // Redirect to edit page of the newly created entry
            redirect()->route('admin.template-entries.edit', [
                'templateSlug' => $this->templateSlug,
                'entryId' => $newEntry->id,
            ]);
        }
    }

    /**
     * Create a ContentNode for the entry
     */
    protected function createContentNode($entry)
    {
        // Get title and slug from the entry
        $title = $entry->title ?? $entry->name ?? 'Untitled';
        $slug = $entry->slug ?? Str::slug($title);

        // Create the ContentNode
        $node = new ContentNode([
            'template_id' => $this->template->id,
            'content_type' => get_class($entry),
            'content_id' => $entry->id,
            'title' => $title,
            'slug' => $slug,
            'is_published' => true,
            'cache_enabled' => $this->cacheEnabled === '' ? null : (bool) $this->cacheEnabled,
        ]);

        // Use selected parent node or auto-detect
        if ($this->parentNodeId) {
            $node->parent_id = $this->parentNodeId;
        } elseif ($this->template->parent_id) {
            // Auto-detect parent node
            $parentTemplate = Template::find($this->template->parent_id);
            if ($parentTemplate) {
                $parentNode = ContentNode::where('template_id', $parentTemplate->id)
                    ->where('is_published', true)
                    ->first();

                if ($parentNode) {
                    $node->parent_id = $parentNode->id;
                }
            }
        }

        $node->save();

        return $node;
    }

    /**
     * Update a ContentNode
     */
    protected function updateContentNode($node, $entry)
    {
        $title = $entry->title ?? $entry->name ?? 'Untitled';
        $slug = $entry->slug ?? Str::slug($title);

        $node->update([
            'title' => $title,
            'slug' => $slug,
            'parent_id' => $this->parentNodeId,
            'cache_enabled' => $this->cacheEnabled === '' ? null : (bool) $this->cacheEnabled,
        ]);
    }

    /**
     * Generate CSS files for GrapeJS fields
     */
    protected function generateCssFiles($entry)
    {
        $cssGenerator = new PageCssGenerator;

        // Check if entry has a slug field
        $slug = $entry->slug ?? $entry->id;

        // Find all GrapeJS fields and generate CSS files for their _css counterparts
        foreach ($this->template->fields as $field) {
            if ($field->type === 'grapejs') {
                $cssFieldName = $field->name.'_css';

                // Check if the CSS field exists and has content
                if (isset($entry->$cssFieldName) && ! empty($entry->$cssFieldName)) {
                    // Generate CSS file
                    $cssGenerator->generateCssFile($slug, $entry->$cssFieldName);
                }
            }
        }
    }

    /**
     * Generate physical Blade template file for GrapeJS content
     */
    protected function generateBladeTemplate($entry)
    {
        // Don't generate blade templates for sections render mode
        if ($this->template->render_mode === 'sections' || ($this->entry?->render_mode ?? null) === 'sections') {
            return;
        }

        // Find the first GrapeJS field
        $grapeJsField = $this->template->fields->where('type', 'grapejs')->first();

        if (! $grapeJsField) {
            return;
        }

        $htmlFieldName = $grapeJsField->name;
        $cssFieldName = $htmlFieldName.'_css';
        $html = $entry->$htmlFieldName ?? '';
        $css = $entry->$cssFieldName ?? '';

        // Only generate if there's content
        if (empty($html)) {
            return;
        }

        // Decode HTML entities so Blade syntax works properly
        // GrapeJS encodes > to &gt;, < to &lt;, etc.
        $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Determine filename: template-slug-id.blade.php
        $slug = $entry->slug ?? $entry->id;
        $filename = "{$this->template->slug}-{$slug}.blade.php";
        $viewPath = "frontend.templates.{$this->template->slug}-{$slug}";
        $filePath = resource_path("views/frontend/templates/{$filename}");

        // Ensure directory exists
        $directory = dirname($filePath);
        if (! \File::exists($directory)) {
            \File::makeDirectory($directory, 0755, true);
        }

        // Create blade template content with dynamic theme layout
        $bladeContent = "@extends(app(\\App\\Services\\ThemeManager::class)->getLayout())\n\n";
        $bladeContent .= "@section('title', \$node->title ?? \$title ?? 'Page')\n\n";

        // Add CSS section if exists and setting is enabled
        $includeCss = Setting::get('grapejs_include_css_in_blade', true);
        if (! empty($css) && $includeCss) {
            $bladeContent .= "@push('styles')\n";
            $bladeContent .= "<style>\n{$css}\n</style>\n";
            $bladeContent .= "@endpush\n\n";
        }

        // Add content section with the GrapeJS HTML
        $bladeContent .= "@section('content')\n";
        $bladeContent .= "{$html}\n";
        $bladeContent .= "@endsection\n";

        // Write the file
        \File::put($filePath, $bladeContent);

        \Log::info("Generated Blade template: {$filePath} for entry {$entry->id}");
    }

    /**
     * Handle media uploads for image/gallery fields
     */
    protected function handleMediaUploads($entry)
    {
        \Log::info('handleMediaUploads() called', [
            'entryClass' => get_class($entry),
            'entryId' => $entry->id,
            'hasAddMedia' => method_exists($entry, 'addMedia'),
            'uploadedFiles' => array_keys($this->uploadedFiles),
        ]);

        // Check if model uses HasMedia trait
        if (! method_exists($entry, 'addMedia')) {
            \Log::warning('Entry does not have addMedia method');

            return;
        }

        foreach ($this->template->fields as $field) {
            \Log::info("Checking field: {$field->name}", [
                'type' => $field->type,
                'hasUpload' => isset($this->uploadedFiles[$field->name]),
            ]);

            if ($field->type === 'image' && isset($this->uploadedFiles[$field->name])) {
                $file = $this->uploadedFiles[$field->name];

                \Log::info("Processing image upload for field: {$field->name}", [
                    'file' => $file ? get_class($file) : 'null',
                    'fileName' => $file ? $file->getClientOriginalName() : 'null',
                ]);

                if ($file) {
                    try {
                        // Clear existing media for this field
                        $entry->clearMediaCollection($field->name);

                        // Add new media
                        $media = $entry->addMedia($file->getRealPath())
                            ->usingFileName($file->getClientOriginalName())
                            ->toMediaCollection($field->name);

                        \Log::info('Media uploaded successfully', [
                            'mediaId' => $media->id,
                            'collection' => $field->name,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error("Error uploading media for field {$field->name}", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Clean GrapeJS fields - remove <body> tags and unwanted wrapper elements
     */
    protected function cleanGrapeJsFields()
    {
        foreach ($this->template->fields as $field) {
            if ($field->type === 'grapejs' && isset($this->fieldValues[$field->name])) {
                $originalHtml = $this->fieldValues[$field->name];

                if (! empty($originalHtml)) {
                    $html = $originalHtml;

                    // Remove <body> tags and their attributes
                    $html = preg_replace('/<body[^>]*>/i', '', $html);
                    $html = preg_replace('/<\/body>/i', '', $html);

                    // Remove <html> tags if present
                    $html = preg_replace('/<html[^>]*>/i', '', $html);
                    $html = preg_replace('/<\/html>/i', '', $html);

                    // Remove <!DOCTYPE> if present
                    $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);

                    // Remove <head> section if present
                    $html = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $html);

                    // Trim whitespace
                    $html = trim($html);

                    $this->fieldValues[$field->name] = $html;

                    \Log::info("Cleaned GrapeJS field: {$field->name}", [
                        'original_length' => strlen($originalHtml),
                        'cleaned_length' => strlen($html),
                        'removed_body' => (strpos($originalHtml, '<body') !== false),
                    ]);
                }
            }
        }
    }

    public function addRepeaterItem($fieldName)
    {
        // Check if we're in section form context
        if (! empty($this->sectionForm['section_template_id'])) {
            $template = \App\Models\SectionTemplate::with('fields')->find($this->sectionForm['section_template_id']);
            $field = $template?->fields->where('name', $fieldName)->first();

            $settings = $field?->settings;
            if (is_string($settings)) {
                $settings = json_decode($settings, true);
            }
            $subFields = $settings['sub_fields'] ?? [];

            $newItem = [];
            foreach ($subFields as $sf) {
                $newItem[$sf['name']] = '';
            }

            $items = $this->sectionForm['field_data'][$fieldName] ?? [];
            if (! is_array($items)) {
                $items = [];
            }
            $items[] = $newItem;
            $this->sectionForm['field_data'][$fieldName] = $items;

            return;
        }

        // Fallback: fieldValues context
        if (! isset($this->fieldValues[$fieldName])) {
            $this->fieldValues[$fieldName] = [];
        }

        if (! is_array($this->fieldValues[$fieldName])) {
            $this->fieldValues[$fieldName] = [];
        }

        $this->fieldValues[$fieldName][] = '{}';
    }

    public function removeRepeaterItem($fieldName, $index)
    {
        // Check if we're in section form context
        if (! empty($this->sectionForm['field_data'][$fieldName])) {
            $items = $this->sectionForm['field_data'][$fieldName];
            unset($items[$index]);
            $this->sectionForm['field_data'][$fieldName] = array_values($items);

            return;
        }

        // Fallback: fieldValues context
        if (isset($this->fieldValues[$fieldName]) && is_array($this->fieldValues[$fieldName])) {
            array_splice($this->fieldValues[$fieldName], $index, 1);
            $this->fieldValues[$fieldName] = array_values($this->fieldValues[$fieldName]);
        }
    }

    // =============================================
    // Page Sections Management (Template-Based)
    // =============================================

    public function addSection()
    {
        \Log::info('🔧 AddSection called');

        // Reset state
        $this->editingSectionIndex = null;
        $this->selectedTemplateId = null;
        $this->sectionForm = [];
        $this->showSectionForm = true; // Show the form

        \Log::info('🔧 AddSection completed', [
            'showSectionForm' => $this->showSectionForm,
            'availableTemplatesCount' => $this->availableSectionTemplates->count(),
        ]);
    }

    public function selectTemplate($templateId)
    {
        $template = SectionTemplate::with('fields')->find($templateId);

        if (! $template) {
            return;
        }

        $this->selectedTemplateId = $templateId;

        // Initialize section form with template defaults
        $fieldData = [];
        foreach ($template->fields as $field) {
            $fieldData[$field->name] = $field->default_value ?? '';
        }

        $this->sectionForm = [
            'id' => null,
            'section_template_id' => $templateId,
            'section_type' => $template->slug,
            'edit_mode' => 'simple',
            'name' => '',
            'field_data' => $fieldData,
            'css' => '',
            'is_active' => true,
            'order' => count($this->sections),
        ];
    }

    public function editSection($index)
    {
        if (isset($this->sections[$index])) {
            $this->editingSectionIndex = $index;
            $section = $this->sections[$index];

            $this->selectedTemplateId = $section['section_template_id'] ?? null;
            $this->showSectionForm = true; // Show the form

            $this->sectionForm = [
                'id' => $section['id'],
                'section_template_id' => $section['section_template_id'] ?? null,
                'section_type' => $section['section_type'],
                'edit_mode' => $section['edit_mode'] ?? 'simple',
                'name' => $section['name'],
                'field_data' => $section['field_data'] ?? [],
                'css' => $section['css'] ?? '',
                'is_active' => $section['is_active'],
                'order' => $section['order'],
            ];
        }
    }

    public function saveSection()
    {
        \Log::info('🟢 saveSection() called', [
            'sectionForm' => $this->sectionForm,
            'editingSectionIndex' => $this->editingSectionIndex,
            'current_sections_count' => count($this->sections),
        ]);

        if (! isset($this->sectionForm['section_template_id'])) {
            session()->flash('section-error', 'Please select a template first.');

            return;
        }

        // Get the template
        $template = SectionTemplate::with('fields')->find($this->sectionForm['section_template_id']);

        if (! $template) {
            session()->flash('section-error', 'Template not found.');

            return;
        }

        // Render HTML from template
        $renderedHtml = $template->render($this->sectionForm['field_data'] ?? []);

        // Prepare section data
        $sectionData = [
            'id' => $this->sectionForm['id'] ?? null,
            'section_template_id' => $this->sectionForm['section_template_id'],
            'section_type' => $template->slug,
            'edit_mode' => $this->sectionForm['edit_mode'] ?? 'simple',
            'name' => $this->sectionForm['name'] ?: $template->name,
            'field_data' => $this->sectionForm['field_data'] ?? [],
            'rendered_html' => $renderedHtml,
            'content' => $this->sectionForm['field_data'] ?? [], // For backward compatibility
            'css' => $this->sectionForm['css'] ?? '',
            'is_active' => $this->sectionForm['is_active'] ?? true,
            'order' => $this->sectionForm['order'] ?? count($this->sections),
        ];

        if ($this->editingSectionIndex !== null) {
            // Update existing section
            $this->sections[$this->editingSectionIndex] = $sectionData;
            \Log::info('✏️ Updated existing section at index '.$this->editingSectionIndex);
        } else {
            // Add new section
            $this->sections[] = $sectionData;
            \Log::info('➕ Added new section', ['new_count' => count($this->sections)]);
        }

        \Log::info('📦 Sections array after save:', [
            'count' => count($this->sections),
            'sections' => $this->sections,
        ]);

        // Auto-save section to database immediately
        if ($this->entry && method_exists($this->entry, 'sections')) {
            $dbData = [
                'section_template_id' => $sectionData['section_template_id'] ?? null,
                'section_type' => $sectionData['section_type'],
                'name' => $sectionData['name'],
                'content' => $sectionData['field_data'] ?? $sectionData['content'] ?? [],
                'rendered_html' => $sectionData['rendered_html'] ?? '',
                'css' => $sectionData['css'] ?? '',
                'is_active' => $sectionData['is_active'] ?? true,
                'order' => $sectionData['order'] ?? 0,
            ];

            if (! empty($sectionData['id'])) {
                $this->entry->sections()->where('id', $sectionData['id'])->update($dbData);
            } else {
                $newSection = $this->entry->sections()->create($dbData);
                // Update the local array with the new ID
                $idx = $this->editingSectionIndex ?? (count($this->sections) - 1);
                if (isset($this->sections[$idx])) {
                    $this->sections[$idx]['id'] = $newSection->id;
                }
            }
        }

        // Reset form
        $this->editingSectionIndex = null;
        $this->selectedTemplateId = null;
        $this->sectionForm = [];
        $this->showSectionForm = false;

        session()->flash('section-success', 'Section saved!');
    }

    public function deleteSection($index)
    {
        if (isset($this->sections[$index])) {
            unset($this->sections[$index]);
            $this->sections = array_values($this->sections); // Re-index

            // Update order
            foreach ($this->sections as $i => $section) {
                $this->sections[$i]['order'] = $i;
            }
        }
    }

    public function moveSectionUp($index)
    {
        if ($index > 0 && isset($this->sections[$index])) {
            $temp = $this->sections[$index];
            $this->sections[$index] = $this->sections[$index - 1];
            $this->sections[$index - 1] = $temp;

            // Update order
            $this->sections[$index]['order'] = $index;
            $this->sections[$index - 1]['order'] = $index - 1;
        }
    }

    public function moveSectionDown($index)
    {
        if ($index < count($this->sections) - 1 && isset($this->sections[$index])) {
            $temp = $this->sections[$index];
            $this->sections[$index] = $this->sections[$index + 1];
            $this->sections[$index + 1] = $temp;

            // Update order
            $this->sections[$index]['order'] = $index;
            $this->sections[$index + 1]['order'] = $index + 1;
        }
    }

    public function toggleSection($index)
    {
        if (isset($this->sections[$index])) {
            $this->sections[$index]['is_active'] = ! $this->sections[$index]['is_active'];
        }
    }

    /**
     * Reorder sections using drag & drop
     */
    public function reorderSections($newOrder)
    {
        \Log::info('🔄 reorderSections() called', [
            'newOrder' => $newOrder,
            'current_sections_count' => count($this->sections),
        ]);

        // Create a new array with sections in the new order
        $reorderedSections = [];

        foreach ($newOrder as $newIndex => $oldIndex) {
            if (isset($this->sections[$oldIndex])) {
                $section = $this->sections[$oldIndex];
                $section['order'] = $newIndex;
                $reorderedSections[] = $section;
            }
        }

        // Update the sections array
        $this->sections = $reorderedSections;

        \Log::info('✅ Sections reordered successfully', [
            'new_sections_count' => count($this->sections),
        ]);
    }

    public function cancelSectionEdit()
    {
        $this->editingSectionIndex = null;
        $this->selectedTemplateId = null;
        $this->sectionForm = [];
        $this->showSectionForm = false; // Hide the form
    }

    /**
     * Sync sections to database
     */
    protected function syncSections($entry)
    {
        \Log::info('🔄 syncSections() called', [
            'entry_id' => $entry->id,
            'template_render_mode' => $this->template->render_mode,
            'has_sections_method' => method_exists($entry, 'sections'),
            'sections_count' => count($this->sections),
            'sections_data' => $this->sections,
        ]);

        if (($this->template->render_mode !== 'sections' && ($entry->render_mode ?? null) !== 'sections') || ! method_exists($entry, 'sections')) {
            \Log::warning('⚠️ syncSections() skipped - conditions not met', [
                'render_mode' => $this->template->render_mode,
                'has_sections_method' => method_exists($entry, 'sections'),
            ]);

            return;
        }

        // Delete sections not in the list
        $sectionIds = collect($this->sections)->pluck('id')->filter();
        $deletedCount = $entry->sections()->whereNotIn('id', $sectionIds)->delete();
        \Log::info('🗑️ Deleted old sections', ['count' => $deletedCount]);

        // Create/Update sections
        foreach ($this->sections as $order => $sectionData) {
            // Get template name if available
            $templateName = 'Section';
            if (isset($sectionData['section_template_id'])) {
                $template = SectionTemplate::find($sectionData['section_template_id']);
                if ($template) {
                    $templateName = $template->name;
                }
            }

            $data = [
                'section_template_id' => $sectionData['section_template_id'] ?? null,
                'section_type' => $sectionData['section_type'],
                'edit_mode' => $sectionData['edit_mode'] ?? 'simple',
                'name' => $sectionData['name'] ?: $templateName,
                'content' => is_array($sectionData['field_data'] ?? null) ? $sectionData['field_data'] : ($sectionData['content'] ?? []),
                'rendered_html' => $sectionData['rendered_html'] ?? '',
                'settings' => is_array($sectionData['settings'] ?? null) ? $sectionData['settings'] : [],
                'css' => $sectionData['css'] ?? '',
                'is_active' => $sectionData['is_active'] ?? true,
                'order' => $order,
            ];

            if (isset($sectionData['id']) && $sectionData['id']) {
                // Update existing
                $updated = $entry->sections()->where('id', $sectionData['id'])->update($data);
                \Log::info('✏️ Updated existing section', [
                    'section_id' => $sectionData['id'],
                    'affected_rows' => $updated,
                    'data' => $data,
                ]);
            } else {
                // Create new
                $newSection = $entry->sections()->create($data);
                \Log::info('➕ Created new section', [
                    'section_id' => $newSection->id,
                    'data' => $data,
                ]);
            }
        }

        \Log::info('✅ syncSections() completed', [
            'final_sections_count' => $entry->sections()->count(),
        ]);
    }

    /**
     * Get frontend URL for viewing the entry
     */
    public function getFrontendUrlProperty()
    {
        if (! $this->entryId || ! $this->entry) {
            return null;
        }

        $urlIdentifierField = $this->template->fields->where('is_url_identifier', true)->first();

        // Check if there's a URL identifier field
        if ($urlIdentifierField && isset($this->entry->{$urlIdentifierField->name})) {
            $urlValue = $this->entry->{$urlIdentifierField->name};

            // Special case for Home template
            if ($this->template->slug === 'home') {
                return '/';
            }

            // For templates with physical files, use the URL identifier field value
            if ($this->template->has_physical_file) {
                // Check if entry is linked to a ContentNode
                if (class_exists('App\Models\ContentNode')) {
                    $contentType = 'App\\Models\\'.Str::studly(Str::singular($this->template->slug));
                    $node = ContentNode::where('content_type', $contentType)
                        ->where('content_id', $this->entry->id)
                        ->where('is_published', true)
                        ->first();
                    if ($node) {
                        return $node->url_path;
                    }
                }
            }
            // For simple templates without ContentNode, construct URL from identifier
            elseif ($urlValue) {
                return $this->template->use_slug_prefix
                    ? '/'.$this->template->slug.'/'.$urlValue
                    : '/'.$urlValue;
            }
        }

        // Fallback: if the entry is linked to a ContentNode, use its url_path
        $node = ContentNode::where('content_type', get_class($this->entry))
            ->where('content_id', $this->entry->id)
            ->where('is_published', true)
            ->first();
        if ($node && $node->url_path) {
            return $node->url_path;
        }

        return null;
    }

    /**
     * Generate SEO metadata using AI based on entry content
     */
    public function generateSEOWithAI()
    {
        \Log::info('generateSEOWithAI called');

        if (! $this->template->has_seo) {
            session()->flash('error', 'This template does not have SEO enabled.');
            \Log::warning('Template does not have SEO enabled', ['template' => $this->template->slug]);

            return;
        }

        try {
            \Log::info('Collecting content data...', ['fieldValues' => array_keys($this->fieldValues)]);

            // Collect content data from fieldValues
            $contentData = array_merge(
                ['id' => $this->entryId ?? 'new'],
                $this->fieldValues
            );

            \Log::info('Calling AI to generate SEO...');

            // Use AI to generate SEO metadata
            $aiManager = new \App\Services\AI\AIManager;
            $result = $aiManager->getProvider()->generateSEO($contentData, "Template: {$this->template->name}");

            \Log::info('AI response received', ['success' => $result['success'] ?? false]);

            if ($result['success']) {
                $seoData = $result['data'];

                \Log::info('SEO data generated', ['data' => $seoData]);

                // Map AI response to SEO fields
                $this->seoFields['seo_title'] = $seoData['meta_title'] ?? '';
                $this->seoFields['seo_description'] = $seoData['meta_description'] ?? '';
                $this->seoFields['seo_keywords'] = $seoData['meta_keywords'] ?? '';
                $this->seoFields['seo_og_title'] = $seoData['og_title'] ?? $seoData['meta_title'] ?? '';
                $this->seoFields['seo_og_description'] = $seoData['og_description'] ?? $seoData['meta_description'] ?? '';

                // Also set Twitter fields to match OG
                $this->seoFields['seo_twitter_title'] = $this->seoFields['seo_og_title'];
                $this->seoFields['seo_twitter_description'] = $this->seoFields['seo_og_description'];

                \Log::info('SEO fields updated', ['seoFields' => array_keys($this->seoFields)]);

                session()->flash('message', '✅ SEO metadata generated successfully! Review and save when ready.');

                // Force component refresh
                $this->dispatch('seo-fields-updated');
            } else {
                $errorMsg = $result['message'] ?? 'Failed to generate SEO metadata';
                \Log::error('AI SEO generation failed', ['error' => $errorMsg]);
                session()->flash('error', '❌ '.$errorMsg);
            }
        } catch (\Exception $e) {
            \Log::error('SEO Generation Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', '❌ Error: '.$e->getMessage());
        }
    }

    public function improveContentWithAI(string $prompt)
    {
        \Log::info('improveContentWithAI called', ['prompt' => $prompt]);

        try {
            // Build field metadata with types
            $fieldsMetadata = [];
            foreach ($this->template->fields as $field) {
                $fieldsMetadata[$field->name] = [
                    'label' => $field->label,
                    'type' => $field->type,
                    'current_value' => $this->fieldValues[$field->name] ?? null,
                ];
            }

            // Prepare data for AI
            $contextData = [
                'template_name' => $this->template->name,
                'template_slug' => $this->template->slug,
                'entry_id' => $this->entryId ?? 'new',
                'fields_metadata' => $fieldsMetadata,
                'field_values' => $this->fieldValues,
            ];

            \Log::info('Calling AI to improve content...', [
                'fields_count' => count($fieldsMetadata),
                'prompt' => $prompt,
            ]);

            // Use AI to improve content
            $aiManager = new \App\Services\AI\AIManager;
            $result = $aiManager->getProvider()->improveContent($contextData, $prompt);

            \Log::info('AI response received', ['success' => $result['success'] ?? false]);

            if ($result['success']) {
                $improvedFields = $result['data'] ?? [];

                \Log::info('Content improvements received', [
                    'improved_fields' => array_keys($improvedFields),
                ]);

                // Update only the fields that were improved
                foreach ($improvedFields as $fieldName => $improvedValue) {
                    if (isset($this->fieldValues[$fieldName])) {
                        $this->fieldValues[$fieldName] = $improvedValue;
                        \Log::info("Updated field: {$fieldName}");
                    }
                }

                session()->flash('message', '✅ Content improved successfully! Review and save when ready.');
            } else {
                $errorMsg = $result['message'] ?? 'Failed to improve content';
                \Log::error('AI content improvement failed', ['error' => $errorMsg]);
                session()->flash('error', '❌ '.$errorMsg);
            }
        } catch (\Exception $e) {
            \Log::error('Content Improvement Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            session()->flash('error', '❌ Error: '.$e->getMessage());
        }
    }

    /**
     * Improve HTML code using AI
     * Used by the code editor AI assistant
     *
     * IMPORTANT: This method should NOT trigger a re-render
     * It only returns improved code without modifying component state
     */
    #[\Livewire\Attributes\Renderless]
    public function improveCode(string $currentCode, string $prompt): string
    {
        \Log::info('improveCode called', [
            'code_length' => strlen($currentCode),
            'prompt' => $prompt,
        ]);

        try {
            // Use AI to improve code
            $aiManager = new \App\Services\AI\AIManager;
            $result = $aiManager->getProvider()->improveCode($currentCode, $prompt);

            \Log::info('AI code improvement response', ['success' => $result['success'] ?? false]);

            if ($result['success']) {
                $improvedCode = $result['data'] ?? $currentCode;
                \Log::info('Code improved successfully', ['improved_code_length' => strlen($improvedCode)]);

                // Skip render to prevent GrapeJS reinitialization loops
                $this->skipRender();

                return $improvedCode;
            } else {
                $errorMsg = $result['message'] ?? 'Failed to improve code';
                \Log::error('AI code improvement failed', ['error' => $errorMsg]);
                throw new \Exception($errorMsg);
            }
        } catch (\Exception $e) {
            \Log::error('Code Improvement Error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw new \Exception('Failed to improve code: '.$e->getMessage());
        }
    }

    /**
     * Check if entry has a generated blade file
     */
    public function hasGeneratedBladeFile()
    {
        if (! $this->entry) {
            return false;
        }

        $slug = $this->entry->slug ?? $this->entry->id;
        $filename = "{$this->template->slug}-{$slug}.blade.php";
        $filePath = resource_path("views/frontend/templates/{$filename}");

        return file_exists($filePath);
    }

    /**
     * Get generated blade file path
     */
    public function getGeneratedBladeFilePath()
    {
        if (! $this->entry) {
            return null;
        }

        $slug = $this->entry->slug ?? $this->entry->id;

        return "{$this->template->slug}-{$slug}.blade.php";
    }

    /**
     * Delete generated blade file
     */
    public function deleteGeneratedBladeFile()
    {
        if (! $this->entry) {
            session()->flash('error', 'No entry found.');

            return;
        }

        $slug = $this->entry->slug ?? $this->entry->id;
        $filename = "{$this->template->slug}-{$slug}.blade.php";
        $filePath = resource_path("views/frontend/templates/{$filename}");

        if (file_exists($filePath)) {
            unlink($filePath);
            session()->flash('success', 'Generated blade file deleted successfully!');
            \Log::info('🗑️ Deleted generated blade file', ['file' => $filename]);
        } else {
            session()->flash('error', 'Generated blade file not found.');
        }
    }

    public function render()
    {
        // Reload template with fresh fields data
        $this->template->load(['fields' => function ($query) {
            $query->orderBy('order');
        }]);

        return view('livewire.admin.template-entries.entry-form')->layout('layouts.admin-clean');
    }
}
