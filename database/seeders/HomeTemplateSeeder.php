<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;
use App\Services\PageCssGenerator;
use Illuminate\Database\Seeder;

class HomeTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Home template (root of the tree, system template)
        $homeTemplate = Template::create([
            'name' => 'Home',
            'slug' => 'home',
            'description' => 'Homepage template - root of the site tree',
            'is_active' => true,
            'is_public' => true, // Publicly accessible
            'is_system' => true, // Cannot be deleted
            'show_in_menu' => true,
            'menu_label' => 'Home',
            'menu_icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
            'menu_order' => 0,
            'render_mode' => 'full_page_grapejs', // Default render mode (can be changed per entry)
            'allow_children' => true,
            'allow_new_pages' => false, // Only one home page allowed
            'has_physical_file' => false,
            'requires_database' => true, // Enable database table creation
            'has_seo' => true, // Enable SEO fields
            'parent_id' => null, // Root template
            'tree_level' => 0,
            'tree_path' => '/1',
        ]);

        // Add fields to Home template
        $fields = [
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'description' => 'Page title',
                'is_required' => true,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 0,
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'description' => 'URL-friendly slug',
                'is_required' => true,
                'show_in_table' => true,
                'adapts_to_render_mode' => false,
                'order' => 1,
            ],
            [
                'name' => 'body',
                'label' => 'Body',
                'type' => 'grapejs',
                'description' => 'Main content - adapts based on render mode (GrapeJS/WYSIWYG/Sections)',
                'is_required' => false,
                'show_in_table' => false,
                'adapts_to_render_mode' => true,
                'order' => 2,
            ],
        ];

        foreach ($fields as $fieldData) {
            $fieldData['template_id'] = $homeTemplate->id;
            TemplateField::create($fieldData);
        }

        // Refresh to load the newly created fields
        $homeTemplate->refresh();
        $homeTemplate->load('fields');

        // Generate table and model
        $tableGenerator = new TemplateTableGenerator();
        $result = $tableGenerator->createTableAndModel($homeTemplate);

        if (!$result) {
            $this->command->error('Failed to create table and model for Home template');
            return;
        }

        // Refresh again to get the updated model_class and table name
        $homeTemplate->refresh();

        $this->command->info("Table name: {$homeTemplate->table_name}");
        $this->command->info("Model class: {$homeTemplate->model_class}");

        // Verify table exists
        if (!\Schema::hasTable($homeTemplate->table_name)) {
            $this->command->error("Table {$homeTemplate->table_name} was not created!");
            return;
        }

        // Create the default home entry using DB::table (since model file was just created)
        $homeId = \DB::table($homeTemplate->table_name)->insertGetId([
            'title' => 'Welcome to Our CMS',
            'slug' => 'home',
            'body' => '<section class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-20 px-4">
                <div class="container mx-auto text-center">
                    <h1 class="text-5xl font-bold mb-4">Welcome to Our CMS</h1>
                    <p class="text-xl mb-8">A powerful, template-driven content management system built with Laravel</p>
                    <a href="#features" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">Get Started</a>
                </div>
            </section>
            <section id="features" class="container mx-auto px-4 py-16">
                <h2 class="text-4xl font-bold mb-8 text-center">Features</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition">
                        <h3 class="text-2xl font-bold mb-2">Dynamic Templates</h3>
                        <p class="text-gray-600 mb-4">Create custom templates with any field structure you need</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition">
                        <h3 class="text-2xl font-bold mb-2">Visual Editor</h3>
                        <p class="text-gray-600 mb-4">Build pages with GrapeJS drag & drop page builder</p>
                    </div>
                    <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition">
                        <h3 class="text-2xl font-bold mb-2">Easy Management</h3>
                        <p class="text-gray-600 mb-4">Intuitive admin interface for content management</p>
                    </div>
                </div>
            </section>',
            'body_css' => '.container { max-width: 1200px; }
.hover\\:shadow-xl:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create ContentNode for the home entry
        \DB::table('content_tree')->insert([
            'template_id' => $homeTemplate->id,
            'parent_id' => null,
            'content_type' => 'App\\Models\\' . $homeTemplate->model_class,
            'content_id' => $homeId,
            'title' => 'Welcome to Our CMS',
            'slug' => 'home',
            'url_path' => '/',
            'level' => 0,
            'tree_path' => '/' . $homeId,
            'is_published' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create default page sections for the home page
        $sectionableType = 'App\\Models\\' . $homeTemplate->model_class;

        // Hero Section
        \DB::table('page_sections')->insert([
            'sectionable_type' => $sectionableType,
            'sectionable_id' => $homeId,
            'section_type' => 'hero_simple',
            'name' => 'Hero Section',
            'order' => 0,
            'is_active' => true,
            'content' => json_encode([
                'background_image' => '',
                'heading' => 'Welcome to Our CMS',
                'subheading' => 'A powerful, template-driven content management system',
                'text' => 'Build amazing websites with our flexible CMS platform',
                'button_text' => 'Get Started',
                'button_url' => '#features',
            ]),
            'settings' => json_encode([
                'height' => 'screen',
                'overlay_opacity' => 0.5,
                'text_alignment' => 'center',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Features Grid Section
        \DB::table('page_sections')->insert([
            'sectionable_type' => $sectionableType,
            'sectionable_id' => $homeId,
            'section_type' => 'features_grid',
            'name' => 'Features',
            'order' => 1,
            'is_active' => true,
            'content' => json_encode([
                'heading' => 'Features',
                'subheading' => 'Everything you need to build great websites',
                'features' => [
                    [
                        'icon' => '',
                        'title' => 'Dynamic Templates',
                        'description' => 'Create custom templates with any field structure you need',
                    ],
                    [
                        'icon' => '',
                        'title' => 'Visual Editor',
                        'description' => 'Build pages with GrapeJS drag & drop page builder',
                    ],
                    [
                        'icon' => '',
                        'title' => 'Easy Management',
                        'description' => 'Intuitive admin interface for content management',
                    ],
                ],
            ]),
            'settings' => json_encode([
                'columns' => 3,
                'layout' => 'card',
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Generate CSS file for the home page
        $cssGenerator = new PageCssGenerator();
        $cssUrl = $cssGenerator->generateCssFile('home', '.container { max-width: 1200px; }
.hover\\:shadow-xl:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }');

        $this->command->info('✓ Created Home template (system, root of tree)');
        $this->command->info('✓ Created default homepage entry');
        if ($cssUrl) {
            $this->command->info('✓ Generated CSS file: ' . $cssUrl);
        }
    }
}
