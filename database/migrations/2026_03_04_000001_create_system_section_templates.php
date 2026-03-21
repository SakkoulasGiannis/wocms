<?php

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed all 15 hardcoded section types as system SectionTemplates.
     * Uses firstOrCreate by slug so it's idempotent.
     */
    public function up(): void
    {
        $templates = $this->getTemplateDefinitions();

        foreach ($templates as $definition) {
            $template = SectionTemplate::firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'category' => $definition['category'],
                    'description' => $definition['description'],
                    'html_template' => $definition['html_template'] ?? '',
                    'is_system' => true,
                    'is_active' => true,
                    'order' => $definition['order'] ?? 0,
                    'default_settings' => $definition['default_settings'] ?? [],
                ]
            );

            // Create fields if the template was just created (no fields yet)
            if ($template->wasRecentlyCreated && ! empty($definition['fields'])) {
                foreach ($definition['fields'] as $field) {
                    SectionTemplateField::create(array_merge(
                        ['section_template_id' => $template->id],
                        $field
                    ));
                }
            }
        }
    }

    public function down(): void
    {
        $slugs = collect($this->getTemplateDefinitions())->pluck('slug');

        SectionTemplate::whereIn('slug', $slugs)
            ->where('is_system', true)
            ->each(function ($template) {
                $template->fields()->delete();
                // Force delete since boot() prevents system template deletion
                $template->forceDelete();
            });
    }

    private function getTemplateDefinitions(): array
    {
        return [
            [
                'slug' => 'wysiwyg',
                'name' => 'WYSIWYG Editor',
                'category' => 'content',
                'description' => 'Rich text editor with formatting options',
                'order' => 1,
                'default_settings' => ['container' => true, 'padding' => 'medium'],
                'html_template' => '<section class="container mx-auto px-4 py-16"><div class="prose prose-lg max-w-none">{{content}}</div></section>',
                'fields' => [
                    ['name' => 'content', 'label' => 'Content', 'type' => 'wysiwyg', 'description' => 'Rich text content', 'is_required' => true, 'default_value' => '<p>Enter your content here...</p>', 'order' => 0],
                ],
            ],
            [
                'slug' => 'grapejs',
                'name' => 'GrapeJS Page Builder',
                'category' => 'custom',
                'description' => 'Full drag & drop page builder for this section',
                'order' => 2,
                'html_template' => '',
                'fields' => [
                    ['name' => 'html', 'label' => 'HTML', 'type' => 'wysiwyg', 'description' => 'GrapeJS HTML output', 'order' => 0],
                    ['name' => 'css', 'label' => 'CSS', 'type' => 'textarea', 'description' => 'GrapeJS CSS output', 'order' => 1],
                ],
            ],
            [
                'slug' => 'html',
                'name' => 'Raw HTML',
                'category' => 'custom',
                'description' => 'Custom HTML/Blade code',
                'order' => 3,
                'html_template' => '{{html}}',
                'fields' => [
                    ['name' => 'html', 'label' => 'HTML Code', 'type' => 'textarea', 'description' => 'Raw HTML content', 'is_required' => true, 'default_value' => '<div class="container mx-auto px-4 py-16"><h2>Custom Section</h2></div>', 'order' => 0],
                ],
            ],
            [
                'slug' => 'hero-slider',
                'name' => 'Hero Slider',
                'category' => 'hero',
                'description' => 'Carousel με slides (image, heading, text, button)',
                'order' => 4,
                'default_settings' => ['autoplay' => true, 'interval' => 5000, 'show_arrows' => true, 'show_dots' => true],
                'html_template' => '<section class="relative">{{#each slides}}<div class="slide"><img src="{{this.image}}" alt="{{this.heading}}"><h2>{{this.heading}}</h2><p>{{this.text}}</p><a href="{{this.button_url}}">{{this.button_text}}</a></div>{{/each}}</section>',
                'fields' => [
                    [
                        'name' => 'slides',
                        'label' => 'Slides',
                        'type' => 'repeater',
                        'description' => 'Carousel slides',
                        'order' => 0,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                                ['name' => 'heading', 'label' => 'Heading', 'type' => 'text'],
                                ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text'],
                                ['name' => 'text', 'label' => 'Text', 'type' => 'textarea'],
                                ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text'],
                                ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url'],
                            ],
                        ],
                        'default_value' => json_encode([
                            ['image' => '', 'heading' => 'Welcome', 'subheading' => 'Subtitle here', 'text' => 'Description text', 'button_text' => 'Learn More', 'button_url' => '#'],
                        ]),
                    ],
                ],
            ],
            [
                'slug' => 'hero-simple',
                'name' => 'Hero Simple',
                'category' => 'hero',
                'description' => 'Simple hero section with background, heading, text and CTA',
                'order' => 5,
                'default_settings' => ['height' => 'screen', 'overlay_opacity' => 0.5, 'text_alignment' => 'center'],
                'html_template' => '<section class="relative bg-cover bg-center h-screen" style="background-image: url(\'{{background_image}}\');"><div class="absolute inset-0 bg-black/50"></div><div class="relative container mx-auto px-4 h-full flex items-center justify-center"><div class="text-center max-w-3xl mx-auto text-white"><h1 class="text-5xl md:text-6xl font-bold mb-4">{{heading}}</h1><p class="text-xl md:text-2xl mb-8">{{subheading}}</p><p class="text-lg mb-8">{{text}}</p><a href="{{button_url}}" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">{{button_text}}</a></div></div></section>',
                'fields' => [
                    ['name' => 'background_image', 'label' => 'Background Image', 'type' => 'image', 'description' => 'Hero background image', 'order' => 0],
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'description' => 'Main heading', 'is_required' => true, 'default_value' => 'Welcome to Our Site', 'order' => 1],
                    ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'description' => 'Subheading text', 'default_value' => 'We create amazing things', 'order' => 2],
                    ['name' => 'text', 'label' => 'Description', 'type' => 'textarea', 'description' => 'Description text', 'default_value' => 'Discover what we can do for you', 'order' => 3],
                    ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text', 'description' => 'CTA button text', 'default_value' => 'Get Started', 'order' => 4],
                    ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url', 'description' => 'CTA button link', 'default_value' => '#contact', 'order' => 5],
                ],
            ],
            [
                'slug' => 'about-us',
                'name' => 'About Us',
                'category' => 'team',
                'description' => 'About section with image, text, features',
                'order' => 6,
                'default_settings' => ['layout' => 'image_left', 'show_features' => true],
                'html_template' => '<section class="container mx-auto px-4 py-16"><div class="grid md:grid-cols-2 gap-12 items-center"><div><img src="{{image}}" alt="{{heading}}" class="rounded-lg shadow-lg"></div><div><h2 class="text-4xl font-bold mb-4">{{heading}}</h2><p class="text-lg text-gray-600 mb-8">{{text}}</p>{{#each features}}<div class="flex items-start gap-4 mb-4"><span class="text-2xl">{{this.icon}}</span><div><h4 class="font-bold">{{this.title}}</h4><p class="text-gray-600">{{this.description}}</p></div></div>{{/each}}</div></div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'is_required' => true, 'default_value' => 'About Us', 'order' => 0],
                    ['name' => 'text', 'label' => 'Description', 'type' => 'textarea', 'default_value' => 'Company description here...', 'order' => 1],
                    ['name' => 'image', 'label' => 'Image', 'type' => 'image', 'order' => 2],
                    [
                        'name' => 'features',
                        'label' => 'Features',
                        'type' => 'repeater',
                        'order' => 3,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                            ],
                        ],
                        'default_value' => json_encode([
                            ['icon' => '', 'title' => 'Feature 1', 'description' => 'Description'],
                            ['icon' => '', 'title' => 'Feature 2', 'description' => 'Description'],
                        ]),
                    ],
                ],
            ],
            [
                'slug' => 'features-grid',
                'name' => 'Features Grid',
                'category' => 'features',
                'description' => 'Grid with features (icon, title, description)',
                'order' => 7,
                'default_settings' => ['columns' => 3, 'layout' => 'card'],
                'html_template' => '<section class="container mx-auto px-4 py-16"><div class="text-center mb-12"><h2 class="text-4xl font-bold mb-4">{{heading}}</h2><p class="text-xl text-gray-600">{{subheading}}</p></div><div class="grid grid-cols-1 md:grid-cols-3 gap-8">{{#each features}}<div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition"><div class="text-4xl mb-4">{{this.icon}}</div><h3 class="text-2xl font-bold mb-2">{{this.title}}</h3><p class="text-gray-600">{{this.description}}</p></div>{{/each}}</div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'is_required' => true, 'default_value' => 'Our Features', 'order' => 0],
                    ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'default_value' => 'What we offer', 'order' => 1],
                    [
                        'name' => 'features',
                        'label' => 'Features',
                        'type' => 'repeater',
                        'order' => 2,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'icon', 'label' => 'Icon', 'type' => 'text', 'placeholder' => 'Emoji or icon'],
                                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                            ],
                        ],
                        'default_value' => json_encode([
                            ['icon' => '🚀', 'title' => 'Fast', 'description' => 'Lightning fast performance'],
                            ['icon' => '🎨', 'title' => 'Beautiful', 'description' => 'Stunning design'],
                            ['icon' => '🔒', 'title' => 'Secure', 'description' => 'Bank-level security'],
                        ]),
                    ],
                ],
            ],
            [
                'slug' => 'blog-posts-list',
                'name' => 'Blog Posts List',
                'category' => 'blog',
                'description' => 'Dynamic blog posts listing',
                'order' => 8,
                'default_settings' => ['count' => 4, 'layout' => 'grid', 'columns' => 2, 'show_excerpt' => true, 'show_date' => true, 'show_author' => false],
                'html_template' => '<section class="container mx-auto px-4 py-16"><div class="text-center mb-12"><h2 class="text-4xl font-bold mb-4">{{heading}}</h2><p class="text-xl text-gray-600">{{subheading}}</p></div><div class="grid grid-cols-1 md:grid-cols-2 gap-8"><!-- Blog posts loaded dynamically --></div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'Latest Articles', 'order' => 0],
                    ['name' => 'subheading', 'label' => 'Subheading', 'type' => 'text', 'default_value' => 'Read our latest blog posts', 'order' => 1],
                ],
            ],
            [
                'slug' => 'testimonials',
                'name' => 'Testimonials',
                'category' => 'testimonials',
                'description' => 'Testimonials carousel/grid',
                'order' => 9,
                'default_settings' => ['layout' => 'carousel', 'show_rating' => true],
                'html_template' => '<section class="container mx-auto px-4 py-16"><h2 class="text-4xl font-bold text-center mb-12">{{heading}}</h2><div class="grid md:grid-cols-3 gap-8">{{#each testimonials}}<div class="bg-white rounded-lg shadow-md p-6"><p class="text-gray-600 mb-4">"{{this.text}}"</p><div class="flex items-center gap-3"><div><p class="font-bold">{{this.name}}</p><p class="text-sm text-gray-500">{{this.role}}</p></div></div></div>{{/each}}</div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'What Our Clients Say', 'order' => 0],
                    [
                        'name' => 'testimonials',
                        'label' => 'Testimonials',
                        'type' => 'repeater',
                        'order' => 1,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                                ['name' => 'role', 'label' => 'Role', 'type' => 'text'],
                                ['name' => 'avatar', 'label' => 'Avatar', 'type' => 'image'],
                                ['name' => 'text', 'label' => 'Testimonial', 'type' => 'textarea'],
                                ['name' => 'rating', 'label' => 'Rating', 'type' => 'number'],
                            ],
                        ],
                        'default_value' => json_encode([
                            ['name' => 'John Doe', 'role' => 'CEO', 'avatar' => '', 'text' => 'Great service!', 'rating' => 5],
                        ]),
                    ],
                ],
            ],
            [
                'slug' => 'call-to-action',
                'name' => 'Call to Action',
                'category' => 'cta',
                'description' => 'CTA banner with heading, text, button',
                'order' => 10,
                'default_settings' => ['style' => 'centered', 'overlay_opacity' => 0.7],
                'html_template' => '<section class="relative bg-cover bg-center py-20" style="background-image: url(\'{{background_image}}\');"><div class="absolute inset-0 bg-black/70"></div><div class="relative container mx-auto px-4 text-center text-white"><h2 class="text-4xl font-bold mb-4">{{heading}}</h2><p class="text-xl mb-8">{{text}}</p><a href="{{button_url}}" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">{{button_text}}</a></div></section>',
                'fields' => [
                    ['name' => 'background_image', 'label' => 'Background Image', 'type' => 'image', 'order' => 0],
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'Ready to Get Started?', 'order' => 1],
                    ['name' => 'text', 'label' => 'Text', 'type' => 'textarea', 'default_value' => 'Join us today and start your journey', 'order' => 2],
                    ['name' => 'button_text', 'label' => 'Button Text', 'type' => 'text', 'default_value' => 'Contact Us', 'order' => 3],
                    ['name' => 'button_url', 'label' => 'Button URL', 'type' => 'url', 'default_value' => '/contact', 'order' => 4],
                ],
            ],
            [
                'slug' => 'stats-counter',
                'name' => 'Stats Counter',
                'category' => 'content',
                'description' => 'Numbers/statistics showcase',
                'order' => 11,
                'default_settings' => ['columns' => 4, 'animated' => true],
                'html_template' => '<section class="container mx-auto px-4 py-16"><h2 class="text-4xl font-bold text-center mb-12">{{heading}}</h2><div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">{{#each stats}}<div><p class="text-4xl font-bold text-blue-600">{{this.number}}</p><p class="text-gray-600 mt-2">{{this.label}}</p></div>{{/each}}</div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'Our Achievements', 'order' => 0],
                    [
                        'name' => 'stats',
                        'label' => 'Stats',
                        'type' => 'repeater',
                        'order' => 1,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'number', 'label' => 'Number', 'type' => 'text'],
                                ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
                                ['name' => 'icon', 'label' => 'Icon', 'type' => 'text'],
                            ],
                        ],
                        'default_value' => json_encode([
                            ['number' => '500+', 'label' => 'Happy Clients', 'icon' => ''],
                            ['number' => '1000+', 'label' => 'Projects', 'icon' => ''],
                        ]),
                    ],
                ],
            ],
            [
                'slug' => 'gallery',
                'name' => 'Gallery',
                'category' => 'gallery',
                'description' => 'Image gallery with lightbox',
                'order' => 12,
                'default_settings' => ['columns' => 3, 'lightbox' => true],
                'html_template' => '<section class="container mx-auto px-4 py-16"><h2 class="text-4xl font-bold text-center mb-12">{{heading}}</h2><div class="grid grid-cols-2 md:grid-cols-3 gap-4">{{#each images}}<div class="overflow-hidden rounded-lg"><img src="{{this.url}}" alt="{{this.caption}}" class="w-full h-64 object-cover hover:scale-105 transition"></div>{{/each}}</div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'Gallery', 'order' => 0],
                    [
                        'name' => 'images',
                        'label' => 'Images',
                        'type' => 'repeater',
                        'order' => 1,
                        'settings' => [
                            'sub_fields' => [
                                ['name' => 'url', 'label' => 'Image', 'type' => 'image'],
                                ['name' => 'caption', 'label' => 'Caption', 'type' => 'text'],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'slug' => 'contact-form',
                'name' => 'Contact Form',
                'category' => 'forms',
                'description' => 'Contact form section',
                'order' => 13,
                'default_settings' => ['show_map' => false, 'show_info' => true],
                'html_template' => '<section class="container mx-auto px-4 py-16"><div class="max-w-2xl mx-auto text-center"><h2 class="text-4xl font-bold mb-4">{{heading}}</h2><p class="text-lg text-gray-600 mb-8">{{text}}</p></div></section>',
                'fields' => [
                    ['name' => 'heading', 'label' => 'Heading', 'type' => 'text', 'default_value' => 'Contact Us', 'order' => 0],
                    ['name' => 'text', 'label' => 'Description', 'type' => 'textarea', 'default_value' => 'Get in touch with us', 'order' => 1],
                    ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'order' => 2],
                    ['name' => 'phone', 'label' => 'Phone', 'type' => 'text', 'order' => 3],
                    ['name' => 'address', 'label' => 'Address', 'type' => 'textarea', 'order' => 4],
                ],
            ],
            [
                'slug' => 'structured-html',
                'name' => 'Structured HTML',
                'category' => 'custom',
                'description' => 'Structured JSON rendered dynamically to HTML with Tailwind CSS',
                'order' => 14,
                'default_settings' => ['container' => false, 'padding' => false],
                'html_template' => '',
                'fields' => [
                    ['name' => 'structure', 'label' => 'HTML Structure', 'type' => 'json', 'description' => 'JSON tree with type, classes, content, children', 'order' => 0],
                ],
            ],
            [
                'slug' => 'custom-html',
                'name' => 'Custom HTML (Legacy)',
                'category' => 'custom',
                'description' => 'Free HTML/Blade content with Tailwind CSS (deprecated - use structured-html)',
                'order' => 15,
                'default_settings' => ['container' => true, 'padding' => true],
                'html_template' => '{{html}}',
                'fields' => [
                    ['name' => 'html', 'label' => 'HTML', 'type' => 'textarea', 'description' => 'Raw HTML with Tailwind classes', 'default_value' => '<div class="container mx-auto px-4 py-16"><h2 class="text-3xl font-bold">Custom Section</h2></div>', 'order' => 0],
                ],
            ],
        ];
    }
};
