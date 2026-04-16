<?php

namespace Modules\PageBuilder\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PageBuilder\Models\SectionTemplate;
use Modules\PageBuilder\Models\SectionTemplateField;

class PrimitiveSectionTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $templateData) {
            $fields = $templateData['fields'];
            unset($templateData['fields']);

            $template = SectionTemplate::updateOrCreate(
                ['slug' => $templateData['slug']],
                array_merge($templateData, ['is_system' => true, 'is_active' => true])
            );

            // Remove old fields and re-create
            $template->fields()->delete();

            foreach ($fields as $order => $fieldData) {
                SectionTemplateField::create(array_merge(
                    $fieldData,
                    ['section_template_id' => $template->id, 'order' => $order + 1]
                ));
            }
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function templates(): array
    {
        return [
            // ─── HEADING ───────────────────────────────────────────────
            [
                'name' => 'Heading',
                'slug' => 'primitive-heading',
                'category' => 'primitive',
                'description' => 'A heading element (h1–h6) with optional class and id.',
                'html_template' => '<{{tag}} id="{{id}}" class="my-3 {{class}}">{{text}}</{{tag}}>',
                'order' => 1,
                'fields' => [
                    ['name' => 'text',  'label' => 'Text',  'type' => 'text',   'is_required' => true,  'placeholder' => 'Enter heading text'],
                    ['name' => 'tag',   'label' => 'Tag',   'type' => 'select', 'is_required' => true,  'default_value' => 'h2', 'options' => json_encode(['h1', 'h2', 'h3', 'h4', 'h5', 'h6'])],
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text',   'is_required' => false, 'placeholder' => 'e.g. text-3xl font-bold'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text',   'is_required' => false, 'placeholder' => 'e.g. main-title'],
                ],
            ],

            // ─── DIV (CONTAINER) ────────────────────────────────────────
            [
                'name' => 'Div',
                'slug' => 'primitive-div',
                'category' => 'primitive',
                'description' => 'A generic container div that can hold nested sections.',
                'html_template' => '<div id="{{id}}" class="p-2 {{class}}">{{children}}</div>',
                'order' => 2,
                'fields' => [
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text', 'is_required' => false, 'placeholder' => 'e.g. flex gap-4 items-center'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'is_required' => false, 'placeholder' => 'e.g. hero-container'],
                ],
            ],

            // ─── PARAGRAPH ──────────────────────────────────────────────
            [
                'name' => 'Paragraph',
                'slug' => 'primitive-paragraph',
                'category' => 'primitive',
                'description' => 'A rich-text paragraph using the WYSIWYG editor.',
                'html_template' => '<div id="{{id}}" class="{{class}} prose">{{content}}</div>',
                'order' => 3,
                'fields' => [
                    ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'is_required' => false, 'placeholder' => 'Write your content...'],
                    ['name' => 'class',   'label' => 'Class',   'type' => 'text',    'is_required' => false, 'placeholder' => 'e.g. text-lg text-gray-700'],
                    ['name' => 'id',      'label' => 'ID',      'type' => 'text',    'is_required' => false],
                ],
            ],

            // ─── IMAGE ──────────────────────────────────────────────────
            [
                'name' => 'Image',
                'slug' => 'primitive-image',
                'category' => 'primitive',
                'description' => 'A single image with alt text, optional width/height, class and id.',
                'html_template' => '<img src="{{src}}" alt="{{alt}}" class="{{class}}" id="{{id}}" width="{{width}}" height="{{height}}">',
                'order' => 4,
                'fields' => [
                    ['name' => 'src',    'label' => 'Image',   'type' => 'image', 'is_required' => true],
                    ['name' => 'alt',    'label' => 'Alt Text', 'type' => 'text',  'is_required' => false, 'placeholder' => 'Describe the image'],
                    ['name' => 'width',  'label' => 'Width',   'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. 800 or 100%'],
                    ['name' => 'height', 'label' => 'Height',  'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. 600 or auto'],
                    ['name' => 'class',  'label' => 'Class',   'type' => 'text',  'is_required' => false, 'placeholder' => 'e.g. rounded-xl w-full object-cover'],
                    ['name' => 'id',     'label' => 'ID',      'type' => 'text',  'is_required' => false],
                ],
            ],

            // ─── BUTTON ─────────────────────────────────────────────────
            [
                'name' => 'Button',
                'slug' => 'primitive-button',
                'category' => 'primitive',
                'description' => 'A link button with text, URL, target, class and id.',
                'html_template' => '<a href="{{url}}" target="{{target}}" id="{{id}}" class="{{class}}">{{text}}</a>',
                'order' => 5,
                'fields' => [
                    ['name' => 'text',   'label' => 'Label',  'type' => 'text',   'is_required' => true,  'placeholder' => 'Click here'],
                    ['name' => 'url',    'label' => 'URL',    'type' => 'url',    'is_required' => false, 'placeholder' => 'https://...'],
                    ['name' => 'target', 'label' => 'Target', 'type' => 'select', 'is_required' => false, 'default_value' => '_self', 'options' => json_encode(['_self', '_blank'])],
                    ['name' => 'class',  'label' => 'Class',  'type' => 'text',   'is_required' => false, 'placeholder' => 'e.g. cms_button btn btn-primary'],
                    ['name' => 'id',     'label' => 'ID',     'type' => 'text',   'is_required' => false],
                ],
            ],

            // ─── GRID / ROW ─────────────────────────────────────────────
            [
                'name' => 'Grid',
                'slug' => 'primitive-grid',
                'category' => 'primitive',
                'description' => 'A CSS grid container for laying out columns.',
                'html_template' => '<div id="{{id}}" class="grid grid-cols-{{columns}} gap-{{gap}} {{class}}">{{children}}</div>',
                'order' => 6,
                'fields' => [
                    ['name' => 'columns', 'label' => 'Columns', 'type' => 'select', 'is_required' => false, 'default_value' => '2', 'options' => json_encode(['1', '2', '3', '4', '5', '6'])],
                    ['name' => 'gap',     'label' => 'Gap',     'type' => 'select', 'is_required' => false, 'default_value' => '6', 'options' => json_encode(['0', '2', '4', '6', '8', '10', '12'])],
                    ['name' => 'class',   'label' => 'Class',   'type' => 'text',   'is_required' => false],
                    ['name' => 'id',      'label' => 'ID',      'type' => 'text',   'is_required' => false],
                ],
            ],

            // ─── SECTION WRAPPER ────────────────────────────────────────
            [
                'name' => 'Section',
                'slug' => 'primitive-section',
                'category' => 'primitive',
                'description' => 'A semantic <section> wrapper with optional class and id.',
                'html_template' => '<section id="{{id}}" class="{{class}}">{{children}}</section>',
                'order' => 7,
                'fields' => [
                    ['name' => 'class', 'label' => 'Class', 'type' => 'text', 'is_required' => false, 'placeholder' => 'e.g. py-16 bg-white'],
                    ['name' => 'id',    'label' => 'ID',    'type' => 'text', 'is_required' => false],
                ],
            ],

            // ─── SPACER ─────────────────────────────────────────────────
            [
                'name' => 'Spacer',
                'slug' => 'primitive-spacer',
                'category' => 'primitive',
                'description' => 'An empty block to add vertical spacing.',
                'html_template' => '<div class="{{class}}" style="height:{{height}}"></div>',
                'order' => 8,
                'fields' => [
                    ['name' => 'height', 'label' => 'Height', 'type' => 'text', 'is_required' => false, 'default_value' => '2rem', 'placeholder' => 'e.g. 2rem or 32px'],
                    ['name' => 'class',  'label' => 'Class',  'type' => 'text', 'is_required' => false],
                ],
            ],

            // ─── RAW HTML ───────────────────────────────────────────────
            [
                'name' => 'Raw HTML',
                'slug' => 'primitive-raw-html',
                'category' => 'primitive',
                'description' => 'Arbitrary HTML markup — use with caution.',
                'html_template' => '{{html}}',
                'order' => 9,
                'fields' => [
                    ['name' => 'html', 'label' => 'HTML', 'type' => 'textarea', 'is_required' => false, 'placeholder' => '<div>...</div>'],
                ],
            ],
        ];
    }
}
