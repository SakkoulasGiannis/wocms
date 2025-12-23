<?php

namespace Database\Seeders;

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Seeder;

class SectionTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================
        // 1. Hero Simple
        // ============================================
        $heroSimple = SectionTemplate::create([
            'name' => 'Hero Simple',
            'slug' => 'hero-simple',
            'category' => 'hero',
            'description' => 'Simple hero section with background, heading, text and call-to-action button',
            'html_template' => '<section class="relative bg-cover bg-center h-screen" style="background-image: url(\'{{background_image}}\');">
    <div class="absolute inset-0 bg-black bg-opacity-50"></div>
    <div class="relative container mx-auto px-4 h-full flex items-center justify-center">
        <div class="text-center max-w-3xl mx-auto text-white">
            <h1 class="text-5xl md:text-6xl font-bold mb-4">{{heading}}</h1>
            <p class="text-xl md:text-2xl mb-8">{{subheading}}</p>
            <p class="text-lg mb-8">{{text}}</p>
            <a href="{{button_url}}" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                {{button_text}}
            </a>
        </div>
    </div>
</section>',
            'is_system' => true,
            'is_active' => true,
            'order' => 1,
        ]);

        // Hero Simple Fields
        $heroFields = [
            ['name' => 'background_image', 'label' => 'Background Image', 'type' => 'image', 'description' => 'Hero background image', 'order' => 0],
            ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'description' => 'Main heading', 'is_required' => true, 'default_value' => 'Welcome to Our Site', 'order' => 1],
            ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'description' => 'Subheading text', 'default_value' => 'We create amazing things', 'order' => 2],
            ['name' => 'text', 'label' => 'Description', 'type' => 'textarea', 'description' => 'Description text', 'default_value' => 'Discover what we can do for you', 'order' => 3],
            ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text', 'description' => 'CTA button text', 'default_value' => 'Get Started', 'order' => 4],
            ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url', 'description' => 'CTA button link', 'default_value' => '#contact', 'order' => 5],
        ];

        foreach ($heroFields as $field) {
            SectionTemplateField::create(array_merge(['section_template_id' => $heroSimple->id], $field));
        }

        // ============================================
        // 2. Features Grid
        // ============================================
        $featuresGrid = SectionTemplate::create([
            'name' => 'Features Grid',
            'slug' => 'features-grid',
            'category' => 'features',
            'description' => '3-column grid showcasing features with icons, titles and descriptions',
            'html_template' => '<section class="container mx-auto px-4 py-16">
    <div class="text-center mb-12">
        <h2 class="text-4xl font-bold mb-4">{{heading}}</h2>
        <p class="text-xl text-gray-600">{{subheading}}</p>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        {{#each features}}
        <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition">
            <div class="text-4xl mb-4">{{this.icon}}</div>
            <h3 class="text-2xl font-bold mb-2">{{this.title}}</h3>
            <p class="text-gray-600">{{this.description}}</p>
        </div>
        {{/each}}
    </div>
</section>',
            'is_system' => true,
            'is_active' => true,
            'order' => 2,
        ]);

        // Features Grid Fields
        $featuresFields = [
            ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'description' => 'Main heading', 'is_required' => true, 'default_value' => 'Our Features', 'order' => 0],
            ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'description' => 'Subheading text', 'default_value' => 'Everything you need to succeed', 'order' => 1],
            [
                'name' => 'features',
                'label' => 'Features',
                'type' => 'repeater',
                'description' => 'Add features (icon, title, description)',
                'order' => 2,
                'settings' => [
                    'sub_fields' => [
                        ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'placeholder' => 'Emoji or icon'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                        ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ]
                ],
                'default_value' => json_encode([
                    ['icon' => '🚀', 'title' => 'Fast', 'description' => 'Lightning fast performance'],
                    ['icon' => '🎨', 'title' => 'Beautiful', 'description' => 'Stunning design'],
                    ['icon' => '🔒', 'title' => 'Secure', 'description' => 'Bank-level security'],
                ])
            ],
        ];

        foreach ($featuresFields as $field) {
            SectionTemplateField::create(array_merge(['section_template_id' => $featuresGrid->id], $field));
        }

        // ============================================
        // 3. Content WYSIWYG
        // ============================================
        $contentWysiwyg = SectionTemplate::create([
            'name' => 'Content Block',
            'slug' => 'content-wysiwyg',
            'category' => 'content',
            'description' => 'Simple content block with title and rich text editor',
            'html_template' => '<section class="container mx-auto px-4 py-16">
    <div class="max-w-4xl mx-auto">
        <h2 class="text-4xl font-bold mb-8 text-center">{{title}}</h2>
        <div class="prose prose-lg max-w-none">
            {{content}}
        </div>
    </div>
</section>',
            'is_system' => true,
            'is_active' => true,
            'order' => 3,
        ]);

        // Content WYSIWYG Fields
        $contentFields = [
            ['name' => 'title', 'label' => 'Section Title', 'type' => 'text', 'description' => 'Section heading', 'default_value' => 'About Us', 'order' => 0],
            ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'description' => 'Main content', 'is_required' => true, 'default_value' => '<p>Enter your content here...</p>', 'order' => 1],
        ];

        foreach ($contentFields as $field) {
            SectionTemplateField::create(array_merge(['section_template_id' => $contentWysiwyg->id], $field));
        }

        $this->command->info('✓ Created 3 section templates (Hero Simple, Features Grid, Content Block)');
    }
}
