<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\TemplateField;
use Illuminate\Database\Seeder;

class ModuleTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'template' => [
                    'name' => 'Sliders',
                    'slug' => 'sliders',
                    'table_name' => 'sliders',
                    'model_class' => 'Modules\\Slider\\Models\\Slider',
                    'requires_database' => true,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'render_mode' => 'simple_content',
                    'icon' => 'photograph',
                    'menu_label' => 'Sliders',
                    'menu_order' => 10,
                ],
                'fields' => [
                    ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'is_active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            [
                'template' => [
                    'name' => 'Properties',
                    'slug' => 'properties',
                    'table_name' => 'properties',
                    'model_class' => 'Modules\\Properties\\Models\\Property',
                    'requires_database' => true,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'render_mode' => 'simple_content',
                    'icon' => 'office-building',
                    'menu_label' => 'Properties',
                    'menu_order' => 11,
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'property_type', 'label' => 'Type', 'type' => 'select'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select'],
                    ['name' => 'price', 'label' => 'Price', 'type' => 'number'],
                    ['name' => 'city', 'label' => 'City', 'type' => 'text'],
                    ['name' => 'bedrooms', 'label' => 'Bedrooms', 'type' => 'number'],
                    ['name' => 'bathrooms', 'label' => 'Bathrooms', 'type' => 'number'],
                    ['name' => 'area', 'label' => 'Area (m²)', 'type' => 'number'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                    ['name' => 'featured', 'label' => 'Featured', 'type' => 'checkbox'],
                ],
            ],
            [
                'template' => [
                    'name' => 'Rental Properties',
                    'slug' => 'rental-properties',
                    'table_name' => 'rental_properties',
                    'model_class' => 'Modules\\RentalProperties\\Models\\RentalProperty',
                    'requires_database' => true,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'render_mode' => 'simple_content',
                    'icon' => 'home',
                    'menu_label' => 'Rentals',
                    'menu_order' => 12,
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'property_type', 'label' => 'Type', 'type' => 'select'],
                    ['name' => 'status', 'label' => 'Status', 'type' => 'select'],
                    ['name' => 'price', 'label' => 'Monthly Rent', 'type' => 'number'],
                    ['name' => 'city', 'label' => 'City', 'type' => 'text'],
                    ['name' => 'bedrooms', 'label' => 'Bedrooms', 'type' => 'number'],
                    ['name' => 'area', 'label' => 'Area (m²)', 'type' => 'number'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            [
                'template' => [
                    'name' => 'Maps',
                    'slug' => 'maps',
                    'table_name' => 'maps',
                    'model_class' => 'Modules\\Maps\\Models\\Map',
                    'requires_database' => true,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'render_mode' => 'simple_content',
                    'icon' => 'map',
                    'menu_label' => 'Maps',
                    'menu_order' => 13,
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                    ['name' => 'default_lat', 'label' => 'Latitude', 'type' => 'number'],
                    ['name' => 'default_lng', 'label' => 'Longitude', 'type' => 'number'],
                    ['name' => 'default_zoom', 'label' => 'Zoom', 'type' => 'number'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
            [
                'template' => [
                    'name' => 'Image Maps',
                    'slug' => 'image-maps',
                    'table_name' => 'image_maps',
                    'model_class' => 'Modules\\ImageMaps\\Models\\ImageMap',
                    'requires_database' => true,
                    'is_active' => true,
                    'show_in_menu' => true,
                    'render_mode' => 'simple_content',
                    'icon' => 'photograph',
                    'menu_label' => 'Image Maps',
                    'menu_order' => 14,
                ],
                'fields' => [
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                    ['name' => 'slug', 'label' => 'Slug', 'type' => 'text'],
                    ['name' => 'active', 'label' => 'Active', 'type' => 'checkbox'],
                ],
            ],
        ];

        foreach ($modules as $module) {
            $template = Template::updateOrCreate(
                ['slug' => $module['template']['slug']],
                $module['template']
            );

            foreach ($module['fields'] as $order => $field) {
                TemplateField::firstOrCreate(
                    ['template_id' => $template->id, 'name' => $field['name']],
                    array_merge($field, ['order' => $order, 'template_id' => $template->id])
                );
            }

            $this->command->info("Registered template: {$template->name} with ".count($module['fields']).' fields');
        }
    }
}
