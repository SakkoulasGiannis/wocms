<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateField;
use App\Services\TemplateTableGenerator;
use Illuminate\Database\Seeder;

class RenovationsTemplateSeeder extends Seeder
{
    public function __construct(protected TemplateTableGenerator $tableGenerator) {}

    public function run(): void
    {
        if (Template::where('slug', 'renovations')->exists()) {
            $this->command->warn('Renovations template already exists — skipping.');
            return;
        }

        $template = Template::create([
            'name' => 'Renovations',
            'slug' => 'renovations',
            'description' => 'Renovation projects — gallery, location and specifications',
            'render_mode' => 'simple_content',
            'is_system' => false,
            'requires_database' => true,
            'allow_children' => false,
            'is_active' => true,
            'is_public' => true,
            'show_in_menu' => true,
            'menu_label' => 'Renovations',
            'menu_icon' => 'paint-brush',
            'menu_order' => 27,
            'has_seo' => true,
            'has_physical_file' => true,
            'use_slug_prefix' => true,
            // Plural form: index uses templates.renovations, single strips trailing 's' → templates.renovation
            'file_path' => 'templates/renovations.blade.php',
        ]);

        $fields = [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'description' => 'Renovation project name', 'validation_rules' => ['required', 'string', 'max:255'], 'is_required' => true, 'is_searchable' => true, 'is_url_identifier' => true, 'show_in_table' => true, 'order' => 0],
            ['name' => 'slug', 'label' => 'Slug', 'type' => 'text', 'description' => 'URL-friendly identifier (auto-generated from Name)', 'validation_rules' => ['required', 'string', 'max:255'], 'is_required' => true, 'show_in_table' => true, 'order' => 1],
            ['name' => 'main_image', 'label' => 'Main Photo', 'type' => 'image', 'description' => 'Primary photo shown on listings and at the top of the detail page', 'validation_rules' => ['nullable', 'image'], 'is_required' => false, 'show_in_table' => true, 'order' => 2],
            ['name' => 'gallery', 'label' => 'Image Gallery', 'type' => 'gallery', 'description' => 'Additional photos of the project (before / after / detail shots)', 'validation_rules' => ['nullable', 'array'], 'is_required' => false, 'show_in_table' => false, 'order' => 3],
            ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'description' => 'Region / city / area', 'validation_rules' => ['nullable', 'string', 'max:255'], 'is_required' => false, 'is_searchable' => true, 'is_filterable' => true, 'show_in_table' => true, 'order' => 4],
            ['name' => 'year_built', 'label' => 'Year Built', 'type' => 'number', 'description' => 'Year the renovation was completed', 'validation_rules' => ['nullable', 'integer', 'min:1900', 'max:2100'], 'is_required' => false, 'is_filterable' => true, 'show_in_table' => true, 'order' => 5],
            ['name' => 'building_size', 'label' => 'Building Size (m²)', 'type' => 'number', 'description' => 'Total covered area in square meters', 'validation_rules' => ['nullable', 'numeric', 'min:0'], 'is_required' => false, 'is_filterable' => true, 'show_in_table' => true, 'order' => 6],
            ['name' => 'plot_size', 'label' => 'Plot Size (m²)', 'type' => 'number', 'description' => 'Total plot area in square meters', 'validation_rules' => ['nullable', 'numeric', 'min:0'], 'is_required' => false, 'is_filterable' => true, 'show_in_table' => true, 'order' => 7],
            ['name' => 'pool_size', 'label' => 'Pool Size (m²)', 'type' => 'number', 'description' => 'Pool area in square meters (optional)', 'validation_rules' => ['nullable', 'numeric', 'min:0'], 'is_required' => false, 'show_in_table' => false, 'order' => 8],
            ['name' => 'drawn_by', 'label' => 'Drawn By', 'type' => 'text', 'description' => 'Architect / designer credited with the drawings', 'validation_rules' => ['nullable', 'string', 'max:255'], 'is_required' => false, 'is_filterable' => true, 'show_in_table' => true, 'order' => 9],
        ];

        foreach ($fields as $fieldData) {
            TemplateField::create(array_merge(['template_id' => $template->id], $fieldData));
        }

        $template->refresh()->load('fields');

        if (! $this->tableGenerator->createTableAndModel($template)) {
            $this->command->error('Failed to create table and model for Renovations template');
            return;
        }

        $template->refresh();

        if ($template->has_physical_file) {
            $template->createPhysicalFile();
            $this->command->info('✓ Created physical blade files for Renovations');
        }

        $this->command->info("✓ Renovations template created — table: {$template->table_name}, model: {$template->model_class}");
    }
}
