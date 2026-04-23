<?php

namespace App\Services\AI\Tools;

use App\Models\Blog;
use App\Models\Home;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SectionTemplate;

class CreatePageSectionTool extends BaseTool
{
    /**
     * Allowed sectionable model short-names, mapped to FQCN.
     *
     * @var array<string, class-string>
     */
    protected const SECTIONABLE_MAP = [
        'Home' => Home::class,
        'Page' => Page::class,
        'Blog' => Blog::class,
    ];

    public function name(): string
    {
        return 'create_page_section';
    }

    public function label(): string
    {
        return 'Add Page Section';
    }

    public function description(): string
    {
        return 'Add a new PageSection (hero, service card, contact form, etc.) to a Home, Page, or Blog entry using a SectionTemplate. Use this when the user wants to add/insert a new block/section onto a specific page.';
    }

    public function schema(): array
    {
        return [
            '$schema' => 'https://json-schema.org/draft/2020-12/schema',
            'type' => 'object',
            'properties' => [
                'sectionable_type' => [
                    'type' => 'string',
                    'enum' => ['Home', 'Page', 'Blog'],
                    'description' => 'Short class name of the parent model. Internally prepended with App\\Models\\.',
                ],
                'sectionable_id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'ID of the parent Home/Page/Blog entry.',
                ],
                'section_template_slug' => [
                    'type' => 'string',
                    'description' => "SectionTemplate slug (e.g. 'hero-slider', 'service-card', 'contact-form').",
                ],
                'content' => [
                    'type' => 'object',
                    'description' => 'Key/value map of section content fields (e.g. {heading, description, image_url}).',
                    'additionalProperties' => true,
                ],
                'order' => [
                    'type' => 'integer',
                    'minimum' => 0,
                    'description' => 'Display order within its siblings. Defaults to max+1.',
                ],
                'parent_section_id' => [
                    'type' => 'integer',
                    'minimum' => 1,
                    'description' => 'If provided, nests this section under an existing container section.',
                ],
            ],
            'required' => ['sectionable_type', 'sectionable_id', 'section_template_slug'],
            'additionalProperties' => false,
        ];
    }

    protected function validationRules(): array
    {
        return [
            'sectionable_type' => 'required|string|in:Home,Page,Blog',
            'sectionable_id' => 'required|integer|min:1',
            'section_template_slug' => 'required|string',
            'content' => 'sometimes|array',
            'order' => 'sometimes|integer|min:0',
            'parent_section_id' => 'sometimes|integer|min:1',
        ];
    }

    public function previewMessage(array $args): string
    {
        $type = $args['sectionable_type'] ?? '?';
        $id = $args['sectionable_id'] ?? '?';
        $slug = $args['section_template_slug'] ?? '?';

        $templateName = $slug;
        $template = SectionTemplate::where('slug', $slug)->first();
        if ($template) {
            $templateName = $template->name;
        }

        return "Θα προσθέσω section '{$templateName}' στο {$type} #{$id}";
    }

    public function execute(array $args): array
    {
        $errors = $this->validate($args);
        if (! empty($errors)) {
            return $this->error('Validation failed: '.implode(', ', $errors));
        }

        $sectionableShort = $args['sectionable_type'];
        $sectionableId = (int) $args['sectionable_id'];
        $sectionTemplateSlug = $args['section_template_slug'];
        $content = $args['content'] ?? [];
        $order = $args['order'] ?? null;
        $parentSectionId = $args['parent_section_id'] ?? null;

        $sectionableType = self::SECTIONABLE_MAP[$sectionableShort] ?? null;
        if (! $sectionableType) {
            return $this->error("Μη έγκυρος τύπος '{$sectionableShort}'. Επιτρεπτά: Home, Page, Blog.");
        }

        // Validate sectionable model exists
        $parentModel = $sectionableType::find($sectionableId);
        if (! $parentModel) {
            return $this->error("Δεν βρέθηκε {$sectionableShort} με ID #{$sectionableId}.");
        }

        // Validate SectionTemplate exists
        $template = SectionTemplate::where('slug', $sectionTemplateSlug)->first();
        if (! $template) {
            return $this->error("Δεν βρέθηκε SectionTemplate με slug '{$sectionTemplateSlug}'.");
        }

        // Optional parent_section_id validation
        if ($parentSectionId !== null) {
            $parentSection = PageSection::find($parentSectionId);
            if (! $parentSection) {
                return $this->error("Δεν βρέθηκε parent section #{$parentSectionId}.");
            }
        }

        $sectionType = str_replace('-', '_', $template->slug);

        // Determine order
        if ($order === null) {
            $query = PageSection::where('sectionable_type', $sectionableType)
                ->where('sectionable_id', $sectionableId);

            if ($parentSectionId !== null) {
                $query->where('parent_section_id', $parentSectionId);
            } else {
                $query->whereNull('parent_section_id');
            }

            $order = ((int) $query->max('order')) + 1;
        }

        try {
            $section = PageSection::create([
                'sectionable_type' => $sectionableType,
                'sectionable_id' => $sectionableId,
                'section_template_id' => $template->id,
                'section_type' => $sectionType,
                'name' => $template->name,
                'content' => $content,
                'settings' => $template->default_settings ?? [],
                'order' => $order,
                'is_active' => true,
                'is_visible' => true,
                'parent_section_id' => $parentSectionId,
            ]);
        } catch (\Throwable $e) {
            return $this->error('❌ Σφάλμα κατά τη δημιουργία section: '.$e->getMessage());
        }

        $visualEditorUrl = $this->buildVisualEditorUrl($sectionableType, $sectionableId);

        return $this->success(
            "✅ Πρόσθεσα section '{$template->name}' στο {$sectionableShort} #{$sectionableId}",
            [
                'id' => $section->id,
                'section_type' => $sectionType,
                'visual_editor_url' => $visualEditorUrl,
            ],
            [
                'section_id' => $section->id,
            ]
        );
    }

    /**
     * Build the Visual Page Editor URL for a sectionable.
     */
    protected function buildVisualEditorUrl(string $sectionableType, int $sectionableId): string
    {
        try {
            return route('admin.page-sections.visual', [
                'sectionableType' => urlencode($sectionableType),
                'sectionableId' => $sectionableId,
            ]);
        } catch (\Throwable $e) {
            $encoded = urlencode($sectionableType);

            return url("/admin/page-sections/visual/{$encoded}/{$sectionableId}");
        }
    }
}
