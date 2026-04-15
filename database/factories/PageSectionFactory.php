<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PageSection>
 */
class PageSectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sectionable_type' => 'App\\Models\\Home',
            'sectionable_id' => 1,
            'section_template_id' => null,
            'section_type' => 'hero-simple',
            'name' => $this->faker->words(2, true),
            'order' => $this->faker->numberBetween(1, 10),
            'is_active' => true,
            'content' => [],
            'settings' => [],
        ];
    }
}
