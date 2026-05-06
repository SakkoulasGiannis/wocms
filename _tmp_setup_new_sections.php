<?php

use Modules\PageBuilder\Models\SectionTemplate;
use Modules\PageBuilder\Models\SectionTemplateField;

// 1) FAQ Accordion section template
$faq = SectionTemplate::firstOrCreate(
    ['slug' => 'faq-accordion'],
    [
        'name' => 'FAQ Accordion',
        'description' => 'Expandable Q&A list (homelengo-style accordion).',
        'category' => 'content',
        'html_template' => '',
        'blade_file' => 'components.sections.faq-accordion',
        'is_active' => true,
        'is_system' => false,
        'order' => 50,
    ]
);
echo "FAQ template id: {$faq->id}\n";

$ensureField = function ($templateId, $name, $attrs) {
    $existing = SectionTemplateField::where('section_template_id', $templateId)->where('name', $name)->first();
    if ($existing) {
        $existing->update($attrs);
        echo "  ~ updated field '{$name}'\n";
    } else {
        SectionTemplateField::create(array_merge(['section_template_id' => $templateId, 'name' => $name], $attrs));
        echo "  + added field '{$name}'\n";
    }
};
$ensureField($faq->id, 'subtitle', ['label' => 'Subtitle (optional)', 'type' => 'text', 'order' => 0]);
$ensureField($faq->id, 'title',    ['label' => 'Heading (optional)',  'type' => 'text', 'order' => 1, 'default_value' => 'Frequently Asked Questions']);
$ensureField($faq->id, 'description', ['label' => 'Description (optional)', 'type' => 'textarea', 'order' => 2]);
$ensureField($faq->id, 'faqs', [
    'label' => 'Q&A items',
    'type' => 'repeater',
    'order' => 3,
    'settings' => json_encode(['sub_fields' => [
        ['name' => 'question', 'label' => 'Question', 'type' => 'text'],
        ['name' => 'answer',   'label' => 'Answer',   'type' => 'textarea'],
    ]]),
]);

echo "\nDone. The FAQ Accordion section template is ready.\n";
