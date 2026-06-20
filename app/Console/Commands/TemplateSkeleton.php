<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\PageBuilder\Models\SectionTemplate;

/**
 * Emit an empty JSON skeleton for a SectionTemplate. The AI gets this
 * skeleton in its system message and fills the empty fields with content.
 *
 *   php artisan template:skeleton wysiwyg
 *   php artisan template:skeleton hero --pretty
 *   php artisan template:skeleton --list             # list all available templates
 *   php artisan template:skeleton --all              # emit one skeleton per active template
 */
class TemplateSkeleton extends Command
{
    protected $signature = 'template:skeleton {slug? : Template slug (omit with --list/--all)}
                                              {--list   : List all template slugs}
                                              {--all    : Emit one skeleton per active template}
                                              {--pretty : Pretty-print JSON}';

    protected $description = 'Emit an empty JSON skeleton for a SectionTemplate (or all templates)';

    public function handle(): int
    {
        if ($this->option('list')) {
            $this->listTemplates();

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $skeletons = SectionTemplate::where('is_active', true)
                ->with('fields')
                ->orderBy('category')
                ->orderBy('order')
                ->get()
                ->map(fn ($t) => $this->buildSkeleton($t))
                ->all();
            $this->emit($skeletons);

            return self::SUCCESS;
        }

        $slug = $this->argument('slug');
        if (! $slug) {
            $this->error('Provide a template slug, or use --list / --all.');

            return self::FAILURE;
        }

        /** @var SectionTemplate|null $tpl */
        $tpl = SectionTemplate::with('fields')->where('slug', $slug)->first();
        if (! $tpl) {
            $this->error("Template not found: {$slug}");

            return self::FAILURE;
        }

        $this->emit($this->buildSkeleton($tpl));

        return self::SUCCESS;
    }

    protected function listTemplates(): void
    {
        $rows = SectionTemplate::where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(fn ($t) => [
                $t->slug,
                $t->name,
                $t->category,
                $t->fields()->count().' fields',
                $t->is_system ? 'system' : 'custom',
            ])->all();

        $this->table(['slug', 'name', 'category', 'fields', 'kind'], $rows);
    }

    /**
     * Build an empty skeleton object for one template.
     */
    protected function buildSkeleton(SectionTemplate $tpl): array
    {
        $content = [];
        $settings = $tpl->default_settings ?? [];

        foreach ($tpl->fields as $f) {
            $content[$f->name] = $this->skeletonValueForType($f->type, $f->default_value, $f->options);
        }

        return [
            'section_type' => $tpl->slug,
            'section_template_id' => $tpl->id,
            'name' => $tpl->name,
            'order' => 0,
            'is_active' => true,
            'is_visible' => true,
            'content' => $content,
            'settings' => $settings,
            '_meta' => [
                'category' => $tpl->category,
                'description' => $tpl->description,
                'field_schema' => $tpl->fields->map(fn ($f) => [
                    'name' => $f->name,
                    'label' => $f->label,
                    'type' => $f->type,
                    'is_required' => (bool) $f->is_required,
                    'placeholder' => $f->placeholder,
                    'description' => $f->description,
                    'options' => $f->options,
                ])->values(),
            ],
        ];
    }

    /**
     * Return an empty/placeholder value matching a field's type. For wysiwyg
     * fields we emit a valid empty EditorJS structure so the AI can append
     * blocks directly.
     */
    protected function skeletonValueForType(string $type, mixed $default, ?array $options): mixed
    {
        if ($default !== null && $default !== '') {
            // Try to decode JSON defaults
            if (is_string($default) && (str_starts_with($default, '{') || str_starts_with($default, '['))) {
                $d = json_decode($default, true);
                if (is_array($d)) {
                    return $d;
                }
            }

            return $default;
        }

        return match ($type) {
            'wysiwyg', 'editorjs' => [
                'time' => 0,
                'blocks' => [],
                'version' => '2.30.0',
            ],
            'repeater' => [],
            'image' => '',
            'gallery' => [],
            'checkbox' => false,
            'number' => 0,
            'select' => $options[0]['value'] ?? '',
            'textarea',
            'text',
            'email',
            'url',
            'date' => '',
            default => '',
        };
    }

    protected function emit(mixed $data): void
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
               | ($this->option('pretty') ? JSON_PRETTY_PRINT : 0);
        $this->line(json_encode($data, $flags));
    }
}
