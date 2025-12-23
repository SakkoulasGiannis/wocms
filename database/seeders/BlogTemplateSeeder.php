<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;

class BlogTemplateSeeder extends Seeder
{
    protected TemplateTableGenerator $tableGenerator;

    public function __construct(TemplateTableGenerator $tableGenerator)
    {
        $this->tableGenerator = $tableGenerator;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Blog template
        $blogTemplate = Template::create([
            'name' => 'Blog',
            'slug' => 'blog',
            'description' => 'Blog post template with featured image, excerpt, and content',
            'table_name' => 'blogs',
            'render_mode' => 'full_page_grapejs',
            'is_system' => false,
            'requires_database' => true,
            'allow_children' => false, // Blog posts don't need hierarchical structure
            'is_active' => true,
            'is_public' => true, // Allow public access to blog posts
            'show_in_menu' => true,
            'menu_label' => 'Blog',
            'menu_icon' => 'newspaper',
            'menu_order' => 30,
            'has_seo' => true, // Enable SEO fields for blog posts
            'has_physical_file' => true, // Use physical blade files
            'use_slug_prefix' => true, // Use /blog/{slug} URL structure
            'file_path' => 'templates/blog.blade.php', // Will create blogs.blade.php (plural) and blog.blade.php (singular)
        ]);

        // Define blog post fields
        $fields = [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'description' => 'Blog post title',
                'validation_rules' => ['required', 'string', 'max:255'],
                'is_required' => true,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 0,
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'description' => 'URL-friendly version of the title',
                'validation_rules' => ['required', 'string', 'max:255'],
                'is_required' => true,
                'show_in_table' => true,
                'is_url_identifier' => true, // This field is used in URLs
                'adapts_to_render_mode' => false,
                'order' => 1,
            ],
            [
                'name' => 'excerpt',
                'label' => 'Excerpt',
                'type' => 'textarea',
                'description' => 'Short summary of the blog post',
                'validation_rules' => ['nullable', 'string'],
                'is_required' => false,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 2,
            ],
            [
                'name' => 'featured_image',
                'label' => 'Featured Image',
                'type' => 'image',
                'description' => 'Main image for the blog post',
                'validation_rules' => ['nullable', 'image'],
                'is_required' => false,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 3,
            ],
            [
                'name' => 'body',
                'label' => 'Body',
                'type' => 'grapejs',
                'description' => 'Main content - adapts based on render mode',
                'validation_rules' => ['nullable'],
                'is_required' => false,
                'show_in_table' => false,
                'adapts_to_render_mode' => true,
                'order' => 4,
            ],
            [
                'name' => 'author',
                'label' => 'Author',
                'type' => 'text',
                'description' => 'Author name',
                'validation_rules' => ['nullable', 'string', 'max:255'],
                'is_required' => false,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 5,
            ],
            [
                'name' => 'tags',
                'label' => 'Tags',
                'type' => 'text',
                'description' => 'Comma-separated tags',
                'validation_rules' => ['nullable', 'string'],
                'is_required' => false,
                'show_in_table' => false,
                'adapts_to_render_mode' => false,
                'order' => 6,
            ],
            [
                'name' => 'published_at',
                'label' => 'Published At',
                'type' => 'text',
                'description' => 'Publication date and time',
                'validation_rules' => ['nullable', 'date'],
                'is_required' => false,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 7,
            ],
        ];

        // Create fields
        foreach ($fields as $fieldData) {
            TemplateField::create(array_merge(
                ['template_id' => $blogTemplate->id],
                $fieldData
            ));
        }

        // Refresh to load the newly created fields
        $blogTemplate->refresh();
        $blogTemplate->load('fields');

        // Generate table and model for the blog template
        $result = $this->tableGenerator->createTableAndModel($blogTemplate);

        if (!$result) {
            $this->command->error('Failed to create table and model for Blog template');
            return;
        }

        // Refresh again to get the updated model_class and table name
        $blogTemplate->refresh();

        // Create physical blade files (blogs.blade.php for index, blog.blade.php for single)
        if ($blogTemplate->has_physical_file) {
            $blogTemplate->createPhysicalFile();
            $this->command->info("✓ Created physical blade files: templates/blogs.blade.php and templates/blog.blade.php");
        }

        $this->command->info("✓ Blog template created with table: {$blogTemplate->table_name}");
    }
}
