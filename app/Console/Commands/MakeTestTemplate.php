<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;

class MakeTestTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:test-template {--with-entries : Create sample entries}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test template with all available field types for testing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Creating test template with all field types...');
        $this->newLine();

        // Check if template already exists
        $existing = Template::where('slug', 'test-template')->first();
        if ($existing) {
            if (!$this->confirm('Test template already exists. Delete and recreate?', true)) {
                $this->info('Operation cancelled.');
                return 0;
            }

            // Delete existing template and its table
            $tableGenerator = new TemplateTableGenerator();
            $tableGenerator->dropTableAndModel($existing);
            $existing->fields()->delete();
            $existing->delete();
            $this->comment('✓ Deleted existing test template');
        }

        // Create template
        $template = Template::create([
            'name' => 'Test Template',
            'slug' => 'test-template',
            'description' => 'Template with all field types for testing purposes',
            'model_class' => 'TestTemplate',
            'table_name' => 'test_templates',
            'is_active' => true,
            'is_public' => true,
            'has_seo' => true,
            'show_in_menu' => true,
            'menu_label' => 'Test Templates',
            'menu_icon' => 'beaker',
            'render_mode' => 'simple_content',
            'requires_database' => true,
        ]);

        $this->comment('✓ Created template: Test Template');

        // Define all field types with examples
        $fields = [
            // Basic text fields
            [
                'name' => 'title',
                'label' => 'Title',
                'type' => 'text',
                'is_required' => true,
                'is_url_identifier' => true,
                'order' => 1,
            ],
            [
                'name' => 'slug',
                'label' => 'Slug',
                'type' => 'text',
                'is_required' => true,
                'order' => 2,
            ],
            [
                'name' => 'subtitle',
                'label' => 'Subtitle',
                'type' => 'text',
                'is_required' => false,
                'order' => 3,
            ],

            // Email and URL
            [
                'name' => 'email',
                'label' => 'Contact Email',
                'type' => 'email',
                'is_required' => false,
                'order' => 4,
            ],
            [
                'name' => 'website',
                'label' => 'Website URL',
                'type' => 'url',
                'is_required' => false,
                'order' => 5,
            ],

            // Text areas and rich text
            [
                'name' => 'excerpt',
                'label' => 'Excerpt',
                'type' => 'textarea',
                'is_required' => false,
                'order' => 6,
            ],
            [
                'name' => 'description',
                'label' => 'Description (WYSIWYG)',
                'type' => 'wysiwyg',
                'is_required' => false,
                'order' => 7,
            ],
            [
                'name' => 'content',
                'label' => 'Main Content (GrapeJS)',
                'type' => 'grapejs',
                'is_required' => false,
                'order' => 8,
            ],

            // Image field
            [
                'name' => 'featured_image',
                'label' => 'Featured Image',
                'type' => 'image',
                'is_required' => false,
                'order' => 9,
            ],

            // Number fields
            [
                'name' => 'price',
                'label' => 'Price',
                'type' => 'number',
                'is_required' => false,
                'order' => 10,
            ],
            [
                'name' => 'rating',
                'label' => 'Rating (Decimal)',
                'type' => 'decimal',
                'is_required' => false,
                'order' => 11,
            ],

            // Boolean/Checkbox
            [
                'name' => 'is_featured',
                'label' => 'Featured',
                'type' => 'checkbox',
                'is_required' => false,
                'order' => 12,
            ],

            // Date fields
            [
                'name' => 'published_date',
                'label' => 'Published Date',
                'type' => 'date',
                'is_required' => false,
                'order' => 13,
            ],
            [
                'name' => 'event_datetime',
                'label' => 'Event Date & Time',
                'type' => 'datetime',
                'is_required' => false,
                'order' => 14,
            ],
        ];

        // Create fields
        foreach ($fields as $index => $fieldData) {
            $fieldData['template_id'] = $template->id;
            TemplateField::create($fieldData);
        }

        $this->comment('✓ Created ' . count($fields) . ' fields with all types');
        $this->newLine();
        $this->table(
            ['Field Name', 'Type', 'Required'],
            collect($fields)->map(fn($f) => [
                $f['name'],
                $f['type'],
                $f['is_required'] ? 'Yes' : 'No'
            ])
        );
        $this->newLine();

        // Generate table and model
        $tableGenerator = new TemplateTableGenerator();
        $tableGenerator->createTableAndModel($template->fresh());

        $this->comment('✓ Generated database table: test_templates');
        $this->comment('✓ Generated model class: App\Models\TestTemplate');

        // Create sample entries if requested
        if ($this->option('with-entries')) {
            $this->newLine();
            $this->info('📝 Creating sample entries...');

            $modelClass = "App\\Models\\{$template->model_class}";

            for ($i = 1; $i <= 3; $i++) {
                $modelClass::create([
                    'title' => "Test Entry {$i}",
                    'slug' => "test-entry-{$i}",
                    'subtitle' => "This is test entry number {$i}",
                    'email' => "test{$i}@example.com",
                    'website' => "https://example{$i}.com",
                    'excerpt' => "Short excerpt for test entry {$i}",
                    'description' => "<p>This is a <strong>rich text</strong> description for entry {$i}.</p>",
                    'content' => "<div class='p-4'><h2>Test Entry {$i}</h2><p>GrapeJS content here.</p></div>",
                    'price' => rand(10, 100),
                    'rating' => rand(1, 5) + (rand(0, 9) / 10),
                    'is_featured' => $i === 1,
                    'published_date' => now()->subDays(rand(1, 30)),
                    'event_datetime' => now()->addDays(rand(1, 60)),
                    'status' => 'active',
                ]);
            }

            $this->comment('✓ Created 3 sample entries');
        }

        $this->newLine();
        $this->info('✅ Test template created successfully!');
        $this->newLine();
        $this->info('📝 Summary:');
        $this->line('  • Template: Test Template');
        $this->line('  • Slug: test-template');
        $this->line('  • Fields: ' . count($fields) . ' (all types)');
        $this->line('  • Table: test_templates');
        $this->line('  • Model: App\Models\TestTemplate');
        if ($this->option('with-entries')) {
            $this->line('  • Sample Entries: 3');
        }
        $this->newLine();
        $this->info('🎯 Access in admin: /admin/test-template');

        return 0;
    }
}
