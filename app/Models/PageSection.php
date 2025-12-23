<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PageSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'section_template_id',
        'sectionable_type',
        'sectionable_id',
        'section_type',
        'edit_mode',
        'name',
        'order',
        'is_active',
        'content',
        'rendered_html',
        'css',
        'settings',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the parent sectionable model (Home, Page, etc).
     */
    public function sectionable()
    {
        return $this->morphTo();
    }

    /**
     * Get the section template for this section
     */
    public function sectionTemplate()
    {
        return $this->belongsTo(SectionTemplate::class);
    }

    /**
     * Get sections for a specific page
     */
    public static function getPageSections(string $pageType, bool $activeOnly = true): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::where('page_type', $pageType);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->orderBy('order')->get();
    }

    /**
     * Get available section types
     */
    public static function getSectionTypes(): array
    {
        return [
            // Flexible content types (Hybrid approach)
            'wysiwyg' => [
                'name' => 'WYSIWYG Editor',
                'description' => 'Rich text editor with formatting options',
                'schema' => [
                    'content' => 'html',
                ],
                'default_content' => '<p>Enter your content here...</p>',
                'default_settings' => [
                    'container' => true,
                    'padding' => 'medium',
                ]
            ],
            'grapejs' => [
                'name' => 'GrapeJS Page Builder',
                'description' => 'Full drag & drop page builder for this section',
                'schema' => [
                    'html' => 'html',
                    'css' => 'css',
                ],
                'default_content' => '',
                'default_settings' => []
            ],
            'html' => [
                'name' => 'Raw HTML',
                'description' => 'Custom HTML/Blade code',
                'schema' => [
                    'html' => 'html',
                ],
                'default_content' => '<div class="container mx-auto px-4 py-16"><h2>Custom Section</h2></div>',
                'default_settings' => []
            ],

            // Predefined Templates
            'hero_slider' => [
                'name' => 'Hero Slider',
                'description' => 'Carousel με slides (image, heading, text, button)',
                'icon' => '<svg>...</svg>',
                'schema' => [
                    'slides' => [
                        'type' => 'array',
                        'items' => [
                            'image' => 'string',
                            'heading' => 'string',
                            'subheading' => 'string',
                            'text' => 'string',
                            'button_text' => 'string',
                            'button_url' => 'string',
                        ]
                    ]
                ],
                'default_content' => [
                    'slides' => [
                        [
                            'image' => '',
                            'heading' => 'Welcome',
                            'subheading' => 'Subtitle here',
                            'text' => 'Description text',
                            'button_text' => 'Learn More',
                            'button_url' => '#',
                        ]
                    ]
                ],
                'default_settings' => [
                    'autoplay' => true,
                    'interval' => 5000,
                    'show_arrows' => true,
                    'show_dots' => true,
                ]
            ],
            'hero_simple' => [
                'name' => 'Simple Hero',
                'description' => 'Ένα hero section (background, heading, text, CTA)',
                'schema' => [
                    'background_image' => 'string',
                    'heading' => 'string',
                    'subheading' => 'string',
                    'text' => 'string',
                    'button_text' => 'string',
                    'button_url' => 'string',
                ],
                'default_content' => [
                    'background_image' => '',
                    'heading' => 'Welcome to Our Site',
                    'subheading' => 'We create amazing things',
                    'text' => 'Discover what we can do for you',
                    'button_text' => 'Get Started',
                    'button_url' => '#contact',
                ],
                'default_settings' => [
                    'height' => 'screen',
                    'overlay_opacity' => 0.5,
                    'text_alignment' => 'center',
                ]
            ],
            'about_us' => [
                'name' => 'About Us',
                'description' => 'About section με image, text, features',
                'schema' => [
                    'heading' => 'string',
                    'text' => 'string',
                    'image' => 'string',
                    'features' => [
                        'type' => 'array',
                        'items' => [
                            'icon' => 'string',
                            'title' => 'string',
                            'description' => 'string',
                        ]
                    ]
                ],
                'default_content' => [
                    'heading' => 'About Us',
                    'text' => 'Company description here...',
                    'image' => '',
                    'features' => [
                        ['icon' => '', 'title' => 'Feature 1', 'description' => 'Description'],
                        ['icon' => '', 'title' => 'Feature 2', 'description' => 'Description'],
                    ]
                ],
                'default_settings' => [
                    'layout' => 'image_left',
                    'show_features' => true,
                ]
            ],
            'features_grid' => [
                'name' => 'Features Grid',
                'description' => 'Grid με features (icon, title, description)',
                'schema' => [
                    'heading' => 'string',
                    'subheading' => 'string',
                    'features' => [
                        'type' => 'array',
                        'items' => [
                            'icon' => 'string',
                            'title' => 'string',
                            'description' => 'string',
                        ]
                    ]
                ],
                'default_content' => [
                    'heading' => 'Our Features',
                    'subheading' => 'What we offer',
                    'features' => [
                        ['icon' => '', 'title' => 'Feature 1', 'description' => 'Description'],
                        ['icon' => '', 'title' => 'Feature 2', 'description' => 'Description'],
                        ['icon' => '', 'title' => 'Feature 3', 'description' => 'Description'],
                    ]
                ],
                'default_settings' => [
                    'columns' => 3,
                    'layout' => 'card',
                ]
            ],
            'blog_posts_list' => [
                'name' => 'Blog Posts List',
                'description' => 'Dynamic λίστα με blog posts',
                'schema' => [
                    'heading' => 'string',
                    'subheading' => 'string',
                ],
                'default_content' => [
                    'heading' => 'Latest Articles',
                    'subheading' => 'Read our latest blog posts',
                ],
                'default_settings' => [
                    'count' => 4,
                    'layout' => 'grid',
                    'columns' => 2,
                    'show_excerpt' => true,
                    'show_date' => true,
                    'show_author' => false,
                ]
            ],
            'testimonials' => [
                'name' => 'Testimonials',
                'description' => 'Testimonials carousel/grid',
                'schema' => [
                    'heading' => 'string',
                    'testimonials' => [
                        'type' => 'array',
                        'items' => [
                            'name' => 'string',
                            'role' => 'string',
                            'avatar' => 'string',
                            'text' => 'string',
                            'rating' => 'number',
                        ]
                    ]
                ],
                'default_content' => [
                    'heading' => 'What Our Clients Say',
                    'testimonials' => [
                        ['name' => 'John Doe', 'role' => 'CEO', 'avatar' => '', 'text' => 'Great service!', 'rating' => 5],
                    ]
                ],
                'default_settings' => [
                    'layout' => 'carousel',
                    'show_rating' => true,
                ]
            ],
            'call_to_action' => [
                'name' => 'Call to Action',
                'description' => 'CTA banner με heading, text, button',
                'schema' => [
                    'background_image' => 'string',
                    'heading' => 'string',
                    'text' => 'string',
                    'button_text' => 'string',
                    'button_url' => 'string',
                ],
                'default_content' => [
                    'background_image' => '',
                    'heading' => 'Ready to Get Started?',
                    'text' => 'Join us today and start your journey',
                    'button_text' => 'Contact Us',
                    'button_url' => '/contact',
                ],
                'default_settings' => [
                    'style' => 'centered',
                    'overlay_opacity' => 0.7,
                ]
            ],
            'stats_counter' => [
                'name' => 'Stats Counter',
                'description' => 'Numbers/statistics showcase',
                'schema' => [
                    'heading' => 'string',
                    'stats' => [
                        'type' => 'array',
                        'items' => [
                            'number' => 'string',
                            'label' => 'string',
                            'icon' => 'string',
                        ]
                    ]
                ],
                'default_content' => [
                    'heading' => 'Our Achievements',
                    'stats' => [
                        ['number' => '500+', 'label' => 'Happy Clients', 'icon' => ''],
                        ['number' => '1000+', 'label' => 'Projects', 'icon' => ''],
                    ]
                ],
                'default_settings' => [
                    'columns' => 4,
                    'animated' => true,
                ]
            ],
            'gallery' => [
                'name' => 'Gallery',
                'description' => 'Image gallery με lightbox',
                'schema' => [
                    'heading' => 'string',
                    'images' => [
                        'type' => 'array',
                        'items' => [
                            'url' => 'string',
                            'caption' => 'string',
                        ]
                    ]
                ],
                'default_content' => [
                    'heading' => 'Gallery',
                    'images' => []
                ],
                'default_settings' => [
                    'columns' => 3,
                    'lightbox' => true,
                ]
            ],
            'contact_form' => [
                'name' => 'Contact Form',
                'description' => 'Contact form section',
                'schema' => [
                    'heading' => 'string',
                    'text' => 'string',
                    'email' => 'string',
                    'phone' => 'string',
                    'address' => 'string',
                ],
                'default_content' => [
                    'heading' => 'Contact Us',
                    'text' => 'Get in touch with us',
                    'email' => '',
                    'phone' => '',
                    'address' => '',
                ],
                'default_settings' => [
                    'show_map' => false,
                    'show_info' => true,
                ]
            ],
            'structured_html' => [
                'name' => 'Structured HTML',
                'description' => 'Structured JSON που αποδίδεται δυναμικά σε HTML με Tailwind CSS',
                'schema' => [
                    'structure' => 'object (JSON tree with type, classes, content, children)',
                ],
                'default_content' => [
                    'structure' => [
                        'type' => 'section',
                        'classes' => 'py-16 bg-gray-50',
                        'children' => [
                            [
                                'type' => 'div',
                                'classes' => 'container mx-auto px-4',
                                'children' => [
                                    [
                                        'type' => 'h2',
                                        'classes' => 'text-3xl font-bold text-center',
                                        'content' => 'Custom Section'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'default_settings' => [
                    'container' => false,
                    'padding' => false,
                ]
            ],
            'custom_html' => [
                'name' => 'Custom HTML (Legacy)',
                'description' => 'Ελεύθερο HTML/Blade content με Tailwind CSS (deprecated - use structured_html)',
                'schema' => [
                    'html' => 'string (HTML with Tailwind classes)',
                ],
                'default_content' => [
                    'html' => '<div class="container mx-auto px-4 py-16"><h2 class="text-3xl font-bold">Custom Section</h2></div>',
                ],
                'default_settings' => [
                    'container' => true,
                    'padding' => true,
                ]
            ],
        ];
    }

    /**
     * Validate content against section schema
     */
    public function validateContent(): bool
    {
        $types = static::getSectionTypes();

        if (!isset($types[$this->section_type])) {
            return false;
        }

        // Basic validation - can be enhanced with JSON schema validator
        $schema = $types[$this->section_type]['schema'];

        foreach ($schema as $key => $rules) {
            if (is_array($rules) && isset($rules['type']) && $rules['type'] === 'array') {
                if (isset($this->content[$key]) && !is_array($this->content[$key])) {
                    return false;
                }
            }
        }

        return true;
    }
}
