<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

/**
 * Creates the 9 primitive section templates (idempotent).
 * Mirrors the pattern of 2026_03_04_000001_create_system_section_templates.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->definitions() as $def) {
            $fields = $def['fields'];
            unset($def['fields']);

            $template = SectionTemplate::firstOrCreate(
                ['slug' => $def['slug']],
                array_merge($def, ['is_system' => true, 'is_active' => true])
            );

            // Only insert fields if freshly created
            if ($template->wasRecentlyCreated) {
                foreach ($fields as $order => $field) {
                    SectionTemplateField::create(array_merge(
                        $field,
                        ['section_template_id' => $template->id, 'order' => $order + 1]
                    ));
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = collect($this->definitions())->pluck('slug');

        SectionTemplate::whereIn('slug', $slugs)
            ->where('is_system', true)
            ->each(function (SectionTemplate $t) {
                $t->fields()->delete();
                $t->delete();
            });
    }

    /** @return array<int, array<string, mixed>> */
    private function definitions(): array
    {
        return [
            [
                'name' => 'Heading',
                'slug' => 'primitive-heading',
                'category' => 'primitive',
                'description' => 'A heading element (h1–h6) with optional class and id.',
                'html_template' => '<{{tag}} id="{{id}}" class="my-3 {{class}}">{{text}}</{{tag}}>',
                'order' => 101,
                'fields' => [
                    ['name' => 'text',  'label' => 'Text',  'type' => 'text',   'is_required' => true,  'placeholder' => 'Enter heading text'],
                    ['name' => 'tag',   'label' => 'Tag',   'type' => 'select', 'is_required' => true,  'default_value' => 'h2', 'options' => json_encode(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])],
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text',   'is_required' => false, 'placeholder' => 'e.g. text-3xl font-bold'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text',   'is_required' => false],
                ],
            ],
            [
                'name' => 'Div',
                'slug' => 'primitive-div',
                'category' => 'primitive',
                'description' => 'A generic container div that can hold nested sections.',
                'html_template' => '<div id="{{id}}" class="p-2 {{class}}">{{children}}</div>',
                'order' => 102,
                'fields' => [
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text', 'is_required' => false, 'placeholder' => 'e.g. flex gap-4 items-center'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'is_required' => false],
                ],
            ],
            [
                'name' => 'Paragraph',
                'slug' => 'primitive-paragraph',
                'category' => 'primitive',
                'description' => 'A rich-text paragraph using the WYSIWYG editor.',
                'html_template' => '<div id="{{id}}" class="{{class}} prose">{{content}}</div>',
                'order' => 103,
                'fields' => [
                    ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'is_required' => false, 'placeholder' => 'Write your content...'],
                    ['name' => 'class',   'label' => 'Class',   'type' => 'text',    'is_required' => false],
                    ['name' => 'id',      'label' => 'ID',      'type' => 'text',    'is_required' => false],
                ],
            ],
            [
                'name' => 'Image',
                'slug' => 'primitive-image',
                'category' => 'primitive',
                'description' => 'A single image with alt text, class and id.',
                'html_template' => '<img src="{{src}}" alt="{{alt}}" class="{{class}}" id="{{id}}" width="{{width}}" height="{{height}}">',
                'order' => 104,
                'fields' => [
                    ['name' => 'src',    'label' => 'Image',    'type' => 'image', 'is_required' => true],
                    ['name' => 'alt',    'label' => 'Alt Text', 'type' => 'text',  'is_required' => false, 'placeholder' => 'Describe the image'],
                    ['name' => 'width',  'label' => 'Width',    'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. 800 or 100%'],
                    ['name' => 'height', 'label' => 'Height',   'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. 600 or auto'],
                    ['name' => 'class',  'label' => 'Class',    'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. rounded-xl w-full object-cover'],
                    ['name' => 'id',     'label' => 'ID',       'type' => 'text',  'is_required' => false],
                ],
            ],
            [
                'name' => 'Button',
                'slug' => 'primitive-button',
                'category' => 'primitive',
                'description' => 'A link button with text, URL, target, class and id.',
                'html_template' => '<a href="{{url}}" target="{{target}}" id="{{id}}" class="{{class}}">{{text}}</a>',
                'order' => 105,
                'fields' => [
                    ['name' => 'text',   'label' => 'Label',  'type' => 'text',   'is_required' => true,  'placeholder' => 'Click here'],
                    ['name' => 'url',    'label' => 'URL',    'type' => 'url',    'is_required' => false, 'placeholder' => 'https://...'],
                    ['name' => 'target', 'label' => 'Target', 'type' => 'select', 'is_required' => false, 'default_value' => '_self', 'options' => json_encode(['_self', '_blank'])],
                    ['name' => 'class',  'label' => 'Class',  'type' => 'text',   'is_required' => false, 'placeholder' => 'e.g. btn btn-primary'],
                    ['name' => 'id',     'label' => 'ID',     'type' => 'text',   'is_required' => false],
                ],
            ],
            [
                'name' => 'Grid',
                'slug' => 'primitive-grid',
                'category' => 'primitive',
                'description' => 'A CSS grid container for laying out columns.',
                'html_template' => '<div id="{{id}}" class="grid grid-cols-{{columns}} gap-{{gap}} {{class}}">{{children}}</div>',
                'order' => 106,
                'fields' => [
                    ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'is_required' => false, 'default_value' => '2', 'options' => json_encode(['1', '2', '3', '4', '5', '6'])],
                    ['name' => 'gap',     'label' => 'Gap',     'type' => 'select', 'is_required' => false, 'default_value' => '6',  'options' => json_encode(['0', '2', '4', '6', '8', '10', '12'])],
                    ['name' => 'class',   'label' => 'Class',   'type' => 'text',   'is_required' => false],
                    ['name' => 'id',      'label' => 'ID',      'type' => 'text',   'is_required' => false],
                ],
            ],
            [
                'name' => 'Section',
                'slug' => 'primitive-section',
                'category' => 'primitive',
                'description' => 'A semantic <section> wrapper with optional class and id.',
                'html_template' => '<section id="{{id}}" class="{{class}}">{{children}}</section>',
                'order' => 107,
                'fields' => [
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text', 'is_required' => false, 'placeholder' => 'e.g. py-16 bg-white'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'is_required' => false],
                ],
            ],
            [
                'name' => 'Spacer',
                'slug' => 'primitive-spacer',
                'category' => 'primitive',
                'description' => 'An empty block to add vertical spacing.',
                'html_template' => '<div class="{{class}}" style="height:{{height}}"></div>',
                'order' => 108,
                'fields' => [
                    ['name' => 'height', 'label' => 'Height', 'type' => 'text', 'is_required' => false, 'default_value' => '2rem', 'placeholder' => 'e.g. 2rem or 32px'],
                    ['name' => 'class',  'label' => 'Class',  'type' => 'text', 'is_required' => false],
                ],
            ],
            [
                'name' => 'Raw HTML',
                'slug' => 'primitive-raw-html',
                'category' => 'primitive',
                'description' => 'Arbitrary HTML markup — use with caution.',
                'html_template' => '{{html}}',
                'order' => 109,
                'fields' => [
                    ['name' => 'html', 'label' => 'HTML', 'type' => 'textarea', 'is_required' => false, 'placeholder' => '<div>...</div>'],
                ],
            ],
        ];
    }
};
