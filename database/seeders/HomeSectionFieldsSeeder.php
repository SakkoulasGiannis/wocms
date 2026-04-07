<?php

namespace Database\Seeders;

use App\Models\SectionTemplate;
use App\Models\SectionTemplateField;
use Illuminate\Database\Seeder;

class HomeSectionFieldsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedFields('benefits', [
            ['name' => 'image', 'label' => 'Left Image', 'type' => 'image', 'order' => 0],
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 1, 'default_value' => 'Our Benefit'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 2, 'default_value' => 'Why Choose Kreta Eiendom'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'order' => 3, 'default_value' => 'Our seasoned team excels in real estate with years of successful market navigation, offering informed decisions and optimal results.'],
            ['name' => 'items', 'label' => 'Benefit Items', 'type' => 'repeater', 'order' => 4, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'icon', 'label' => 'Icon Class', 'type' => 'text'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'link', 'label' => 'Link URL', 'type' => 'url'],
            ]])],
        ]);

        $this->seedFields('services-grid', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Our Services'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => 'Welcome to Kreta Eiendom'],
            ['name' => 'items', 'label' => 'Service Items', 'type' => 'repeater', 'order' => 2, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                ['name' => 'title', 'label' => 'Title', 'type' => 'text'],
                ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
                ['name' => 'link', 'label' => 'Link URL', 'type' => 'url'],
            ]])],
        ]);

        $this->seedFields('hero-slider-home5', [
            ['name' => 'slider_id', 'label' => 'Select Slider', 'type' => 'select', 'order' => 0, 'description' => 'Choose a slider from the Slider module'],
            ['name' => 'categories', 'label' => 'Category Links', 'type' => 'repeater', 'order' => 1, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'icon', 'label' => 'Icon Class', 'type' => 'text'],
                ['name' => 'label', 'label' => 'Label', 'type' => 'text'],
                ['name' => 'url', 'label' => 'URL', 'type' => 'url'],
            ]])],
        ]);

        $this->seedFields('testimonials-carousel', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Our Testimonials'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => "What's people say's"],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea', 'order' => 2],
            ['name' => 'testimonials', 'label' => 'Testimonials', 'type' => 'repeater', 'order' => 3, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'quote', 'label' => 'Quote', 'type' => 'textarea'],
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'role', 'label' => 'Role/Title', 'type' => 'text'],
                ['name' => 'avatar', 'label' => 'Avatar Image', 'type' => 'image'],
                ['name' => 'rating', 'label' => 'Stars (1-5)', 'type' => 'number'],
            ]])],
        ]);

        $this->seedFields('explore-cities', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Explore Cities'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => 'Our Location For You'],
            ['name' => 'cities', 'label' => 'Cities', 'type' => 'repeater', 'order' => 2, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'name', 'label' => 'City Name', 'type' => 'text'],
                ['name' => 'property_count', 'label' => 'Property Count Text', 'type' => 'text'],
                ['name' => 'image', 'label' => 'Image', 'type' => 'image'],
                ['name' => 'link', 'label' => 'Link URL', 'type' => 'url'],
            ]])],
        ]);

        $this->seedFields('agents-grid', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Our Teams'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => 'Meet Our Agents'],
            ['name' => 'agents', 'label' => 'Agents', 'type' => 'repeater', 'order' => 2, 'settings' => json_encode(['sub_fields' => [
                ['name' => 'name', 'label' => 'Name', 'type' => 'text'],
                ['name' => 'role', 'label' => 'Role', 'type' => 'text'],
                ['name' => 'image', 'label' => 'Photo', 'type' => 'image'],
                ['name' => 'link', 'label' => 'Profile URL', 'type' => 'url'],
            ]])],
        ]);

        $this->seedFields('featured-property', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Top Properties'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => 'Recommended For You'],
            ['name' => 'image', 'label' => 'Left Image', 'type' => 'image', 'order' => 2],
            ['name' => 'property_title', 'label' => 'Property Title', 'type' => 'text', 'order' => 3],
            ['name' => 'property_link', 'label' => 'Property Link', 'type' => 'url', 'order' => 4],
            ['name' => 'beds', 'label' => 'Bedrooms', 'type' => 'number', 'order' => 5, 'default_value' => '3'],
            ['name' => 'baths', 'label' => 'Bathrooms', 'type' => 'number', 'order' => 6, 'default_value' => '2'],
            ['name' => 'sqft', 'label' => 'Square Meters', 'type' => 'number', 'order' => 7, 'default_value' => '1150'],
            ['name' => 'address', 'label' => 'Address', 'type' => 'text', 'order' => 8],
            ['name' => 'price', 'label' => 'Price', 'type' => 'text', 'order' => 9, 'default_value' => '$250,00'],
            ['name' => 'price_suffix', 'label' => 'Price Suffix', 'type' => 'text', 'order' => 10, 'default_value' => '/month'],
        ]);

        $this->seedFields('latest-blog-posts', [
            ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'order' => 0, 'default_value' => 'Recent Articles'],
            ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'order' => 1, 'default_value' => 'Latest News'],
            ['name' => 'limit', 'label' => 'Number of Posts', 'type' => 'number', 'order' => 2, 'default_value' => '8'],
        ]);

        $this->command->info('Section template fields seeded successfully!');
    }

    protected function seedFields(string $slug, array $fields): void
    {
        $template = SectionTemplate::where('slug', $slug)->first();
        if (! $template) {
            $this->command->warn("Template '{$slug}' not found, skipping.");

            return;
        }

        // Remove existing fields to avoid duplicates
        SectionTemplateField::where('section_template_id', $template->id)->delete();

        foreach ($fields as $field) {
            SectionTemplateField::create(array_merge($field, [
                'section_template_id' => $template->id,
            ]));
        }

        $this->command->line('  Added '.count($fields)." fields to '{$slug}'");
    }
}
