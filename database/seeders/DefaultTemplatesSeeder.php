<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;
use Illuminate\Database\Seeder;

class DefaultTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Home template is created by HomeTemplateSeeder, so we just need to get it
        $homeTemplate = Template::where('slug', 'home')->first();

        if (!$homeTemplate) {
            $this->command->error('Home template not found! Make sure HomeTemplateSeeder runs first.');
            return;
        }

        // Create default "Page" template
        $pageTemplate = Template::firstOrCreate(
            ['slug' => 'page'],
            [
                'name' => 'Page',
                'description' => 'Standard page template for general content',
                'is_active' => true,
                'is_public' => true,
                'show_in_menu' => true,
                'menu_label' => 'Pages',
                'menu_icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                'menu_order' => 10,
                'render_mode' => 'full_page_grapejs',
                'allow_children' => true,
                'allow_new_pages' => true,
                'has_physical_file' => true,
                'file_path' => 'frontend.page',
                'has_seo' => true, // Enable SEO fields
                'parent_id' => $homeTemplate->id,
            ]
        );

        // Add basic fields to Page template
        $fields = [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'description' => 'Page title',
                'is_required' => true,
                'adapts_to_render_mode' => false,
                'order' => 0,
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'description' => 'URL-friendly version of the title',
                'is_required' => true,
                'adapts_to_render_mode' => false,
                'order' => 1,
            ],
            [
                'name' => 'body',
                'label' => 'Body',
                'type' => 'grapejs',
                'description' => 'Main content - adapts based on render mode (GrapeJS/WYSIWYG/Sections)',
                'is_required' => false,
                'adapts_to_render_mode' => true,
                'order' => 2,
            ],
            [
                'name' => 'featured_image',
                'label' => 'Featured Image',
                'type' => 'image',
                'description' => 'Main page image',
                'is_required' => false,
                'adapts_to_render_mode' => false,
                'order' => 3,
            ],
        ];

        // Only create fields if they don't exist
        if ($pageTemplate->fields()->count() === 0) {
            foreach ($fields as $fieldData) {
                $fieldData['template_id'] = $pageTemplate->id;
                TemplateField::create($fieldData);
            }
        }

        // Update tree structure
        $pageTemplate->tree_level = $homeTemplate->tree_level + 1;
        $pageTemplate->tree_path = $homeTemplate->tree_path . '/' . $pageTemplate->id;
        $pageTemplate->save();

        // Generate table and model
        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->createTableAndModel($pageTemplate->fresh());

        $this->command->info('✓ Created default "Page" template with fields and database table');

        // Create a default sample page with content
        $samplePageCount = \DB::table('pages')->count();
        if ($samplePageCount === 0) {
            // Create the page content first
            $pageId = \DB::table('pages')->insertGetId([
                'title' => 'About Us',
                'slug' => 'about-us',
                'body' => '<h2>Welcome to our About page</h2><p>This is a sample page. You can edit or delete this page from the admin panel.</p><p>You can create more pages and organize them in a hierarchical structure.</p>',
                'body_css' => null,
                'featured_image' => null,
                'render_mode' => 'full_page_grapejs',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create ContentNode for the page
            \DB::table('content_tree')->insert([
                'template_id' => $pageTemplate->id,
                'parent_id' => null,
                'content_type' => 'App\\Models\\Page',
                'content_id' => $pageId,
                'title' => 'About Us',
                'slug' => 'about-us',
                'url_path' => '/about-us',
                'level' => 0,
                'tree_path' => '/' . $pageId,
                'is_published' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('✓ Created sample "About Us" page');
        }
    }
}
