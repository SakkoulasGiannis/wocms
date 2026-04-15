<?php

namespace App\Services;

use App\Models\ContentNode;
use App\Models\PageSection;
use App\Models\Template;
use Throwable;

class PageSerializer
{
    /**
     * Serialize a ContentNode (page) to a canonical JSON-ready array.
     */
    public function serialize(ContentNode $node): array
    {
        try {
            $content = $node->content_type ? $node->content : null;
        } catch (Throwable) {
            $content = null;
        }

        $sections = PageSection::where('sectionable_type', $node->content_type)
            ->where('sectionable_id', $node->content_id)
            ->with('sectionTemplate')
            ->orderBy('order')
            ->get();

        $template = Template::with('fields')->find($node->template_id);

        return [
            'version' => '1.0',
            'meta' => [
                'title' => $node->title,
                'slug' => $node->slug,
                'url_path' => $node->url_path,
                'is_published' => $node->is_published,
                'content_type' => $node->content_type,
                'template_id' => $node->template_id,
            ],
            'template' => $template ? $this->serializeTemplate($template) : null,
            'fields' => $content ? $this->serializeFields($content) : [],
            'sections' => $sections->map(fn (PageSection $s) => $this->serializeSection($s))->values()->all(),
        ];
    }

    /**
     * Serialize a single PageSection to array.
     */
    public function serializeSection(PageSection $section): array
    {
        return [
            'id' => $section->id,
            'template' => $section->sectionTemplate?->slug,
            'template_id' => $section->section_template_id,
            'name' => $section->name,
            'order' => $section->order,
            'active' => (bool) $section->is_active,
            'content' => $section->content ?? [],
            'settings' => $section->settings ?? [],
        ];
    }

    /**
     * Serialize a Template with its fields.
     *
     * @return array<string, mixed>
     */
    protected function serializeTemplate(Template $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'slug' => $template->slug,
            'render_mode' => $template->render_mode,
            'fields' => $template->fields->map(fn ($f) => [
                'name' => $f->name,
                'label' => $f->label,
                'type' => $f->type,
                'is_required' => (bool) $f->is_required,
                'default_value' => $f->default_value,
                'placeholder' => $f->placeholder,
                'settings' => $f->settings,
            ])->values()->all(),
        ];
    }

    /**
     * Extract serializable fields from a content model (Page, Home, Blog, etc).
     *
     * @return array<string, mixed>
     */
    protected function serializeFields(mixed $content): array
    {
        if (! is_object($content)) {
            return [];
        }

        $skip = ['id', 'created_at', 'updated_at', 'deleted_at', 'render_mode', 'slug', 'title'];

        return collect($content->getAttributes())
            ->except($skip)
            ->filter(fn ($v) => $v !== null)
            ->all();
    }
}
